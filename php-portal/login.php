<?php
require_once 'includes/portal.php';
startPortalSession();
header('Cache-Control: no-store');

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostToken();
    $email = postText('email');
    $password = $_POST['password'] ?? null;
    $db = portalDb();
    $identity = hash('sha256', mb_strtolower($email) . '|' . ($_SERVER['REMOTE_ADDR'] ?? ''));
    $blocked = portalRow($db, 'SELECT 1 FROM login_attempts WHERE identity_key = ? AND failures >= 6 AND first_failure >= CURRENT_TIMESTAMP - INTERVAL 15 MINUTE', [$identity]);
    $error = 'Invalid email or password.';
    if ($blocked) {
        http_response_code(429);
        $error = 'Too many sign-in attempts. Try again in 15 minutes.';
    } else {
        $user = portalRow($db, 'SELECT id, password, role, active, must_change, auth_version FROM users WHERE email = ?', [$email]);
        $hash = $user['password'] ?? '$2y$10$8H8jp3drHTXvYfuhqPPnZ.tstc333HTbSDuI1AYEqDDsIdEX6i3Xa';
        $verified = matchesPortalPassword($password, $hash, (bool) ($user['must_change'] ?? false));
        if ($user && $user['active'] && $verified && in_array($user['role'], ['admin', 'teacher', 'student'], true)) {
            $db->prepare('DELETE FROM login_attempts WHERE identity_key = ?')->execute([$identity]);
            $mustChange = (bool) $user['must_change'] || !validPortalPassword($password);
            if ($mustChange && !$user['must_change']) {
                $db->prepare('UPDATE users SET must_change = 1 WHERE id = ? AND auth_version = ?')
                    ->execute([$user['id'], $user['auth_version']]);
            }
            if (!$mustChange && password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                $db->prepare('UPDATE users SET password = ? WHERE id = ? AND auth_version = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id'], $user['auth_version']]);
            }
            session_regenerate_id(true);
            $_SESSION = ['user_id' => (int) $user['id'], 'role' => $user['role'], 'auth_version' => (int) $user['auth_version']];
            redirectTo($mustChange ? 'password.php' : homePage($user['role']));
        }
        $db->prepare('INSERT INTO login_attempts (identity_key, failures) VALUES (?, 1) ON DUPLICATE KEY UPDATE
            failures = IF(first_failure < CURRENT_TIMESTAMP - INTERVAL 15 MINUTE, 1, failures + 1),
            first_failure = IF(first_failure < CURRENT_TIMESTAMP - INTERVAL 15 MINUTE, CURRENT_TIMESTAMP, first_failure)')
            ->execute([$identity]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | School Portal</title>
    <link rel="stylesheet" href="assets/portal.css?v=6">
</head>
<body class="auth-page">
    <main class="auth-layout">
        <section class="auth-content" aria-labelledby="sign-in-title">
            <div class="brand"><span class="brand-mark" aria-hidden="true">S</span>School Portal</div>
            <div class="auth-copy">
                <h1 id="sign-in-title">Sign in</h1>
                <p>Use your school account to access the portal.</p>
                <?php if ($error): ?><p class="notice notice-error" role="alert"><?= h($error) ?></p><?php endif; ?>
                <form class="auth-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                    <div>
                        <label class="form-label" for="email">Email address</label>
                        <input class="form-control" type="email" id="email" name="email" value="<?= h($email) ?>" autocomplete="username" required>
                    </div>
                    <div>
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Sign in <span aria-hidden="true">↗</span></button>
                </form>
            </div>
        </section>
        <div class="auth-image" aria-hidden="true"></div>
    </main>
</body>
</html>
