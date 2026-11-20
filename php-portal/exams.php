<?php
require_once 'includes/portal.php';
requirePortalRole('teacher');

require_once 'config/Database.php';
require_once 'models/ClassModel.php';
require_once 'models/ExamModel.php';

$db = (new Database())->connect();
$classModel = new ClassModel($db);
$examModel = new ExamModel($db);
$classId = null;
$subject = '';
$date = '';
$time = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostToken();
    if (isset($_POST['delete_id'])) {
        $id = positiveId(INPUT_POST, 'delete_id');
        if ($id) {
            $examModel->delete($id);
            portalNotice('Exam removed.');
        }
        header('Location: exams.php', true, 303);
        exit;
    }

    $classId = positiveId(INPUT_POST, 'class_id');
    $subject = postText('subject');
    $date = postText('exam_date');
    $time = postText('exam_time');
    $parsedDate = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)
        ? DateTimeImmutable::createFromFormat('!Y-m-d', $date)
        : false;
    if (!$classId || !$classModel->exists($classId) || $subject === '' || mb_strlen($subject) > 100) {
        $error = 'Select an existing class and enter a subject of up to 100 characters.';
    } elseif (!$parsedDate || $parsedDate->format('Y-m-d') !== $date || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $time)) {
        $error = 'Enter a valid exam date and time.';
    } else {
        $examModel->create($classId, $subject, $date, $time);
        portalNotice('Exam scheduled.');
        header('Location: exams.php', true, 303);
        exit;
    }
}

$classes = $classModel->getAll()->fetchAll(PDO::FETCH_ASSOC);
$exams = $examModel->getAll()->fetchAll(PDO::FETCH_ASSOC);
require 'includes/layout.php';
?>
<header class="page-header"><h1>Manage Exams</h1></header>

<section class="card form-card mb-4" id="add-exam" aria-labelledby="add-exam-title">
    <h2 id="add-exam-title">Schedule an exam</h2>
    <?php if ($error): ?><p class="notice notice-error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <?php if (!$classes): ?>
        <p><a href="classes.php#add-class">Add a class</a> to schedule an exam.</p>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <div class="fields">
                <div>
                    <label class="form-label" for="exam-class">Class</label>
                    <select class="form-select" name="class_id" id="exam-class" required>
                        <option value="">Select a class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= (int) $class['id'] ?>"<?= $classId === (int) $class['id'] ? ' selected' : '' ?>><?= h($class['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="exam-subject">Subject</label>
                    <input class="form-control" type="text" id="exam-subject" name="subject" value="<?= h($subject) ?>" maxlength="100" required>
                </div>
                <div>
                    <label class="form-label" for="exam-date">Date</label>
                    <input class="form-control" type="date" id="exam-date" name="exam_date" value="<?= h($date) ?>" required>
                </div>
                <div>
                    <label class="form-label" for="exam-time">Time</label>
                    <input class="form-control" type="time" id="exam-time" name="exam_time" value="<?= h($time) ?>" required>
                </div>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Schedule exam</button></div>
        </form>
    <?php endif; ?>
</section>

<section class="card" aria-labelledby="exam-list">
    <div class="panel-heading"><h2 id="exam-list">Exam schedule</h2></div>
    <?php if (!$exams): ?>
        <p class="empty">No exams have been scheduled.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table" aria-labelledby="exam-list">
                <thead><tr><th scope="col">Subject</th><th scope="col">Class</th><th scope="col">Date</th><th scope="col">Time</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($exams as $exam): ?>
                        <tr>
                            <td class="person-name"><?= h($exam['subject']) ?></td>
                            <td><?= h($exam['class_name'] ?? 'Class unavailable') ?></td>
                            <td class="nowrap"><?= h(date('M j, Y', strtotime($exam['exam_date']))) ?></td>
                            <td><?= h(substr($exam['exam_time'], 0, 5)) ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Delete this exam?');">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                    <button class="btn btn-sm btn-danger" type="submit" name="delete_id" value="<?= (int) $exam['id'] ?>">Delete</button>
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
