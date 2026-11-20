<?php
require_once __DIR__ . '/../config/Database.php';

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function portalDb(): PDO
{
    static $db;
    return $db ??= (new Database())->connect();
}

function startPortalSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'cookie_secure' => $secure, 'use_strict_mode' => true]);
    }
}

function homePage(string $role): string
{
    return match ($role) {
        'admin' => 'admin.php',
        'teacher' => 'teacher_dashboard.php',
        default => 'student_dashboard.php',
    };
}

function requirePortalRole(string ...$roles): void
{
    startPortalSession();
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $account = $userId ? portalRow(portalDb(), 'SELECT role, active, must_change, auth_version FROM users WHERE id = ?', [$userId]) : false;
    if (!$account || !$account['active'] || (int) $account['auth_version'] !== ($_SESSION['auth_version'] ?? 0)) {
        $_SESSION = [];
        header('Location: login.php');
        exit;
    }
    $_SESSION['role'] = $account['role'];
    if ($account['must_change'] && basename($_SERVER['SCRIPT_NAME']) !== 'password.php') {
        header('Location: password.php');
        exit;
    }
    if (!in_array($account['role'], $roles, true)) {
        forbid('Your account does not have access to this page.');
    }
    header('Cache-Control: no-store');
}

function portalNotice(string $message, bool $error = false): void
{
    $_SESSION['portal_notice'] = ['message' => $message, 'error' => $error];
}

function csrfToken(): string
{
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}

function requirePostToken(): void
{
    $token = $_POST['csrf_token'] ?? null;
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($token) || !hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        exit('This form has expired. Go back, reload the page, and try again.');
    }
}

function positiveId(int $source, string $field): ?int
{
    return filter_input($source, $field, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
}

function postText(string $field): string
{
    $value = filter_input(INPUT_POST, $field);
    return is_string($value) ? trim($value) : '';
}

function portalRow(PDO $db, string $sql, array $values = []): array|false
{
    $stmt = $db->prepare($sql);
    $stmt->execute($values);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function portalRows(PDO $db, string $sql, array $values = []): array
{
    $stmt = $db->prepare($sql);
    $stmt->execute($values);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function forbid(string $message = 'Your account does not have access to this record.'): never
{
    http_response_code(403);
    exit($message);
}

function redirectTo(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function currentUserId(): int
{
    return (int) $_SESSION['user_id'];
}

function isAdmin(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

function classScope(string $column): string
{
    return isAdmin() ? '1 = 1' : "EXISTS (SELECT 1 FROM teacher_classes access_assignment WHERE access_assignment.class_id = $column AND access_assignment.teacher_id = " . currentUserId() . ')';
}

function staffClasses(PDO $db): array
{
    return portalRows($db, 'SELECT c.*, t.name AS term_name, y.name AS year_name FROM classes c LEFT JOIN terms t ON t.id = c.term_id
        LEFT JOIN academic_years y ON y.id = t.year_id WHERE ' . classScope('c.id') . ' ORDER BY t.starts_on DESC, c.name, c.id');
}

function classSubjects(PDO $db, int $classId): array
{
    return portalRows($db, 'SELECT s.id, s.name FROM subjects s JOIN class_subjects cs ON cs.subject_id = s.id WHERE cs.class_id = ? ORDER BY s.name', [$classId]);
}

function requireClass(PDO $db, ?int $classId): array
{
    $lock = $db->inTransaction() ? ' FOR UPDATE' : '';
    if (!$classId || !($class = portalRow($db, 'SELECT c.* FROM classes c WHERE c.id = ? AND ' . classScope('c.id') . $lock, [$classId]))) {
        forbid();
    }
    return $class;
}

function auditEvent(PDO $db, string $action, string $entity, ?int $id, ?int $classId, array $details = []): void
{
    $db->prepare('INSERT INTO portal_audit (actor_id, action, entity, entity_id, class_id, details) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([currentUserId(), $action, $entity, $id, $classId, json_encode($details, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)]);
}

function portalTransaction(PDO $db, callable $operation): mixed
{
    $db->beginTransaction();
    try {
        $result = $operation();
        $db->commit();
        return $result;
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $exception;
    }
}

function portalDate(string $value): ?DateTimeImmutable
{
    if (!preg_match('/^[1-9]\d{3}-\d{2}-\d{2}$/D', $value)) {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $date : null;
}

function validPortalPassword($value): bool
{
    return is_string($value) && strlen($value) >= 12 && strlen($value) <= 72 && !str_contains($value, "\0");
}

function matchesPortalPassword($value, string $hash, bool $temporary): bool
{
    if (!is_string($value) || strlen($value) > 1024 || str_contains($value, "\0")) {
        return false;
    }
    if (password_verify($value, $hash)) {
        return true;
    }
    $trimmed = trim($value);
    return $temporary && $trimmed !== $value && preg_match('/^[a-f0-9]{24}$/D', $trimmed)
        && password_verify($trimmed, $hash);
}

function courseFileUrl(int $id): string
{
    return 'download.php?id=' . $id;
}
