<?php
require_once 'includes/portal.php';
requirePortalRole('teacher', 'admin');

require_once 'config/Database.php';
require_once 'models/ClassModel.php';

$db = portalDb();
$classModel = new ClassModel($db);
$terms = isAdmin() ? portalRows($db, 'SELECT t.*, y.name AS year_name FROM terms t JOIN academic_years y ON y.id = t.year_id ORDER BY t.starts_on DESC, t.id DESC') : [];
$termId = null;
$name = '';
$room = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostToken();
    if (!isAdmin()) { forbid(); }
    if (isset($_POST['delete_id'])) {
        $id = positiveId(INPUT_POST, 'delete_id');
        if ($id && $classModel->delete($id)) {
            portalNotice('Class deleted.');
        } else {
            portalNotice('Class could not be deleted. Check for linked students, exams, course materials, attendance, or gradebook records.', true);
        }
        header('Location: classes.php', true, 303);
        exit;
    }

    $name = postText('name');
    $room = postText('room');
    $termId = positiveId(INPUT_POST, 'term_id');
    if ($name === '' || $room === '' || mb_strlen($name) > 50 || mb_strlen($room) > 50 || !in_array($termId, array_map('intval', array_column($terms, 'id')), true)) {
        $error = 'Enter a class name and room of up to 50 characters, and select a term.';
    } else {
        $classModel->create($name, $room, $termId);
        portalNotice('Class added.');
        header('Location: classes.php', true, 303);
        exit;
    }
}

$classes = staffClasses($db);
require 'includes/layout.php';
?>
<header class="page-header"><h1>Manage Classes</h1></header>

<?php if (isAdmin()): ?><section class="card form-card mb-4" id="add-class" aria-labelledby="add-class-title">
    <h2 id="add-class-title">Add a class</h2>
    <?php if ($error): ?><p class="notice notice-error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <div class="fields fields-two">
            <div>
                <label class="form-label" for="class-name">Class name</label>
                <input class="form-control" type="text" id="class-name" name="name" value="<?= h($name) ?>" maxlength="50" required>
            </div>
            <div>
                <label class="form-label" for="room">Room</label>
                <input class="form-control" type="text" id="room" name="room" value="<?= h($room) ?>" maxlength="50" required>
            </div>
        </div>
        <div class="form-actions"><label><span class="form-label">Term</span><select name="term_id" class="form-select" required>
            <option value="">Select term</option>
            <?php foreach ($terms as $term): ?><option value="<?= (int) $term['id'] ?>"<?= $termId === (int) $term['id'] ? ' selected' : '' ?>><?= h($term['year_name'] . ' · ' . $term['name']) ?></option><?php endforeach; ?>
        </select></label></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Add class</button></div>
    </form>
</section><?php endif; ?>

<section class="card" aria-labelledby="class-list">
    <div class="panel-heading"><h2 id="class-list">Class list</h2></div>
    <?php if (!$classes): ?>
        <p class="empty">No classes have been added yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table" aria-labelledby="class-list">
                <thead><tr><th scope="col">Name</th><th scope="col">Room</th><?php if (isAdmin()): ?><th scope="col">Actions</th><?php endif; ?></tr></thead>
                <tbody>
                    <?php foreach ($classes as $class): ?>
                        <tr>
                            <td class="person-name"><?= h($class['name']) ?><span class="cell-detail"><?= h($class['year_name'] . ' · ' . $class['term_name']) ?></span></td>
                            <td><?= h($class['room'] ?? '—') ?></td>
                            <?php if (isAdmin()): ?><td>
                                <form method="post" onsubmit="return confirm('Delete this class?');">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                    <button class="btn btn-sm btn-danger" type="submit" name="delete_id" value="<?= (int) $class['id'] ?>">Delete</button>
                                </form>
                            </td><?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require 'includes/layout_footer.php'; ?>
