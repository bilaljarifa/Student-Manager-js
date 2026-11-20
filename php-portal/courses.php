<?php
require_once 'includes/portal.php';
requirePortalRole('teacher', 'admin');

require_once 'config/Database.php';
require_once 'models/ClassModel.php';
require_once 'models/CourseModel.php';

$db = portalDb();
$courseModel = new CourseModel($db);
$classId = null;
$title = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostToken();
    if (isset($_POST['delete_id'])) {
        $id = positiveId(INPUT_POST, 'delete_id');
        if ($id && ($existing = portalRow($db, 'SELECT class_id FROM courses WHERE id = ?', [$id]))) {
            portalTransaction($db, function () use ($db, $existing, $id, $courseModel) {
                requireClass($db, (int) $existing['class_id']);
                $courseModel->delete($id);
            });
            portalNotice('Course material removed.');
        }
        header('Location: courses.php', true, 303);
        exit;
    }

    $classId = positiveId(INPUT_POST, 'class_id');
    $title = postText('title');
    $pdf = $_FILES['pdf_file'] ?? null;
    requireClass($db, $classId);
    if ($title === '' || mb_strlen($title) > 255) {
        $error = 'Enter a title of up to 255 characters.';
    } elseif (($pdf['error'] ?? null) !== UPLOAD_ERR_OK || $pdf['size'] > 8 * 1024 * 1024 || !is_uploaded_file($pdf['tmp_name'])
        || (new finfo(FILEINFO_MIME_TYPE))->file($pdf['tmp_name']) !== 'application/pdf') {
        $error = 'Choose a valid PDF up to 8 MB.';
    } else {
        portalTransaction($db, function () use ($db, $courseModel, $classId, $title, $pdf) {
            requireClass($db, $classId);
            $courseModel->create($classId, $title, 'private');
            $id = (int) $db->lastInsertId();
            $name = mb_substr(basename(str_replace('\\', '/', $pdf['name'])), 0, 255);
            $db->prepare('INSERT INTO course_files (course_id, file_name, contents) VALUES (?, ?, ?)')
                ->execute([$id, $name ?: 'course.pdf', file_get_contents($pdf['tmp_name'])]);
        });
        portalNotice('Course material uploaded.');
        redirectTo('courses.php');
    }
}

$classes = staffClasses($db);
$courses = $courseModel->getAll(classScope('courses.class_id'))->fetchAll(PDO::FETCH_ASSOC);
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
                            <option value="<?= (int) $class['id'] ?>"<?= $classId === (int) $class['id'] ? ' selected' : '' ?>><?= h($class['name'] . ' · ' . $class['term_name']) ?></option>
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
                                    <a href="<?= h(courseFileUrl($course['id'])) ?>" class="btn btn-sm btn-secondary">Download PDF</a>
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
