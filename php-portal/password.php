<?php
require_once 'includes/portal.php';
requirePortalRole('admin', 'teacher', 'student');

$db = portalDb();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostToken();
    $current = $_POST['current_password'] ?? null;
    $password = $_POST['new_password'] ?? null;
    $account = portalRow($db, 'SELECT password, auth_version, must_change FROM users WHERE id = ?', [currentUserId()]);
    if (!matchesPortalPassword($current, $account['password'], (bool) $account['must_change'])) {
        $error = 'The current password is incorrect.';
    } elseif (!validPortalPassword($password) || $password !== ($_POST['confirmation'] ?? null) || password_verify($password, $account['password'])) {
        $error = 'Use a different password of at least 12 characters and enter it twice.';
    } else {
        $statement = $db->prepare('UPDATE users SET password = ?, must_change = 0, auth_version = auth_version + 1 WHERE id = ? AND auth_version = ?');
        $statement->execute([password_hash($password, PASSWORD_DEFAULT), currentUserId(), $account['auth_version']]);
        if ($statement->rowCount()) {
            session_regenerate_id(true);
            $_SESSION['auth_version']++;
            portalNotice('Password updated.');
            redirectTo(homePage($_SESSION['role']));
        }
        redirectTo('login.php');
    }
}
$pageTitle = 'Change password';
require 'includes/layout.php';
?>
<header class="page-header"><h1>Change password</h1></header>
<form class="card form-card form-page stack" method="post">
    <?php if ($error): ?><p class="notice notice-error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
    <label><span class="form-label">Current password</span><input type="password" name="current_password" class="form-control" autocomplete="current-password" required></label>
    <label><span class="form-label">New password</span><input type="password" name="new_password" class="form-control" autocomplete="new-password" minlength="12" maxlength="72" required></label>
    <label><span class="form-label">Confirm new password</span><input type="password" name="confirmation" class="form-control" autocomplete="new-password" minlength="12" maxlength="72" required></label>
    <div><button type="submit" class="btn btn-primary">Save password</button></div>
</form>
<?php require 'includes/layout_footer.php'; ?>
