<?php
session_start();
require_once 'config/Database.php';
require_once 'includes/portal.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = postText('email');
    $password = $_POST['password'] ?? null;

    $db = (new Database())->connect();
    $stmt = $db->prepare('SELECT id, password, role FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $password === $user['password']) {
        session_regenerate_id(true);
        $_SESSION = ['user_id' => $user['id'], 'role' => $user['role']];
        header('Location: ' . ($user['role'] === 'teacher' ? 'teacher_dashboard.php' : 'student_dashboard.php'));
        exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | School Portal</title>
    <link rel="stylesheet" href="assets/portal.css?v=2">
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
