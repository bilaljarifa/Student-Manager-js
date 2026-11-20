<?php
require_once 'includes/portal.php';
requirePortalRole('teacher');

require_once 'config/Database.php';
require_once 'models/ClassModel.php';
require_once 'models/CourseModel.php';

$db = (new Database())->connect();
$classModel = new ClassModel($db);
$courseModel = new CourseModel($db);
$classId = null;
$title = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostToken();
    if (isset($_POST['delete_id'])) {
        $id = positiveId(INPUT_POST, 'delete_id');
        if ($id) {
            $courseModel->delete($id);
            portalNotice('Course material removed.');
        }
        header('Location: courses.php', true, 303);
        exit;
    }

    $classId = positiveId(INPUT_POST, 'class_id');
    $title = postText('title');
    $pdf = $_FILES['pdf_file'] ?? null;
    if (!$classId || !$classModel->exists($classId) || $title === '' || mb_strlen($title) > 255) {
        $error = 'Select an existing class and enter a title of up to 255 characters.';
    } elseif (($pdf['error'] ?? null) !== UPLOAD_ERR_OK || (new finfo(FILEINFO_MIME_TYPE))->file($pdf['tmp_name']) !== 'application/pdf') {
        $error = 'Choose a valid PDF within the server upload limit.';
    } else {
        $path = 'uploads/' . bin2hex(random_bytes(16)) . '.pdf';
        if (!move_uploaded_file($pdf['tmp_name'], __DIR__ . '/' . $path)) {
            $error = 'The PDF could not be saved. Try uploading it again.';
        } else {
            try {
                $courseModel->create($classId, $title, $path);
            } catch (PDOException $exception) {
                unlink(__DIR__ . '/' . $path);
                throw $exception;
            }
            portalNotice('Course material uploaded.');
            header('Location: courses.php', true, 303);
            exit;
        }
    }
}

$classes = $classModel->getAll()->fetchAll(PDO::FETCH_ASSOC);
$courses = $courseModel->getAll()->fetchAll(PDO::FETCH_ASSOC);
require 'includes/layout.php';
?>
<header class="page-header"><h1>Manage Course Materials</h1></header>

<section class="card form-card mb-4" id="upload-course" aria-labelledby="upload-course-title">
    <h2 id="upload-course-title">Upload course material</h2>
    <?php if ($error): ?><p class="notice notice-error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <?php if (!$classes): ?>
        <p><a href="classes.php#add-class">Add a class</a> to upload course material.</p>
    <?php else: ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <div class="fields">
                <div>
                    <label class="form-label" for="course-class">Class</label>
                    <select class="form-select" name="class_id" id="course-class" required>
                        <option value="">Select a class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= (int) $class['id'] ?>"<?= $classId === (int) $class['id'] ? ' selected' : '' ?>><?= h($class['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="course-title">Title</label>
                    <input class="form-control" id="course-title" name="title" value="<?= h($title) ?>" maxlength="255" required>
                </div>
                <div>
                    <label class="form-label" for="course-file">PDF file</label>
                    <input type="file" class="form-control" id="course-file" name="pdf_file" accept=".pdf,application/pdf" required>
                </div>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Upload PDF</button></div>
        </form>
    <?php endif; ?>
</section>

<section class="card" aria-labelledby="course-list">
    <div class="panel-heading"><h2 id="course-list">Uploaded materials</h2></div>
    <?php if (!$courses): ?>
        <p class="empty">No course materials have been uploaded.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table" aria-labelledby="course-list">
                <thead><tr><th scope="col">Title</th><th scope="col">Class</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td class="person-name"><?= h($course['title']) ?></td>
                            <td><?= h($course['class_name'] ?? 'Class unavailable') ?></td>
                            <td>
                                <div class="row-actions">
                                    <a href="<?= h(courseFileUrl($course['file_path'])) ?>" class="btn btn-sm btn-secondary">View PDF</a>
                                    <form method="post" onsubmit="return confirm('Remove this course material?');">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                        <button class="btn btn-sm btn-danger" type="submit" name="delete_id" value="<?= (int) $course['id'] ?>">Remove</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require 'includes/layout_footer.php'; ?>
