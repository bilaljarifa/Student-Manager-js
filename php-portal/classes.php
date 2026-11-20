<?php
require_once 'includes/portal.php';
requirePortalRole('teacher');

require_once 'config/Database.php';
require_once 'models/ClassModel.php';

$classModel = new ClassModel((new Database())->connect());
$name = '';
$room = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostToken();
    if (isset($_POST['delete_id'])) {
        $id = positiveId(INPUT_POST, 'delete_id');
        if ($id && $classModel->delete($id)) {
            portalNotice('Class deleted.');
        } else {
            portalNotice('Class could not be deleted. Check for linked students, exams, course materials, or attendance records.', true);
        }
        header('Location: classes.php', true, 303);
        exit;
    }

    $name = postText('name');
    $room = postText('room');
    if ($name === '' || $room === '' || mb_strlen($name) > 50 || mb_strlen($room) > 50) {
        $error = 'Enter a class name and room, each up to 50 characters.';
    } else {
        $classModel->create($name, $room);
        portalNotice('Class added.');
        header('Location: classes.php', true, 303);
        exit;
    }
}

$classes = $classModel->getAll()->fetchAll(PDO::FETCH_ASSOC);
require 'includes/layout.php';
?>
<header class="page-header"><h1>Manage Classes</h1></header>

<section class="card form-card mb-4" id="add-class" aria-labelledby="add-class-title">
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
        <div class="form-actions"><button class="btn btn-primary" type="submit">Add class</button></div>
    </form>
</section>

<section class="card" aria-labelledby="class-list">
    <div class="panel-heading"><h2 id="class-list">Class list</h2></div>
    <?php if (!$classes): ?>
        <p class="empty">No classes have been added yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table" aria-labelledby="class-list">
                <thead><tr><th scope="col">Name</th><th scope="col">Room</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($classes as $class): ?>
                        <tr>
                            <td class="person-name"><?= h($class['name']) ?></td>
                            <td><?= h($class['room'] ?? '—') ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Delete this class?');">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                    <button class="btn btn-sm btn-danger" type="submit" name="delete_id" value="<?= (int) $class['id'] ?>">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require 'includes/layout_footer.php'; ?>
