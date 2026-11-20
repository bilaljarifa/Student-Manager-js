<?php

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function requirePortalRole(string $role): void
{
    session_start();
    if (($_SESSION['role'] ?? null) !== $role) {
        header('Location: login.php');
        exit;
    }
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

function courseFileUrl(string $path): string
{
    return 'uploads/' . rawurlencode(basename(str_replace('\\', '/', $path)));
}
