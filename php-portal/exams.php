<?php
require_once 'includes/portal.php';
requirePortalRole('teacher', 'admin');

require_once 'config/Database.php';
require_once 'models/ClassModel.php';
require_once 'models/ExamModel.php';

$db = portalDb();
$subjects = portalRows($db, 'SELECT DISTINCT s.id, s.name FROM subjects s JOIN class_subjects cs ON s.id = cs.subject_id WHERE ' . classScope('cs.class_id') . ' ORDER BY s.name');
$subjectClasses = [];
foreach (portalRows($db, 'SELECT cs.subject_id, cs.class_id FROM class_subjects cs WHERE ' . classScope('cs.class_id')) as $assignment) {
    $subjectClasses[$assignment['subject_id']][] = $assignment['class_id'];
}
$examModel = new ExamModel($db);
$classId = null;
$subjectId = null;
$date = '';
$time = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostToken();
    if (isset($_POST['delete_id'])) {
        $id = positiveId(INPUT_POST, 'delete_id');
        if ($id && ($existing = portalRow($db, 'SELECT class_id FROM exams WHERE id = ?', [$id]))) {
            requireClass($db, (int) $existing['class_id']);
            $examModel->delete($id);
            portalNotice('Exam removed.');
        }
        header('Location: exams.php', true, 303);
        exit;
    }

    $classId = positiveId(INPUT_POST, 'class_id');
    $class = requireClass($db, $classId);
    $subjectId = positiveId(INPUT_POST, 'subject_id');
    $subject = $subjectId ? portalRow($db, 'SELECT s.name FROM subjects s JOIN class_subjects cs ON s.id = cs.subject_id WHERE s.id = ? AND cs.class_id = ?', [$subjectId, $classId]) : null;
    $date = postText('exam_date');
    $time = postText('exam_time');
    $parsedDate = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)
        ? DateTimeImmutable::createFromFormat('!Y-m-d', $date)
        : false;
    $term = portalRow($db, 'SELECT starts_on, ends_on FROM terms WHERE id = ?', [$class['term_id']]);
    if (!$subject) {
        $error = 'Select a subject assigned to this class.';
    } elseif (!$parsedDate || $parsedDate->format('Y-m-d') !== $date || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $time)) {
        $error = 'Enter a valid exam date and time.';
    } elseif (!$term || $date < $term['starts_on'] || $date > $term['ends_on']) {
        $error = 'The exam date must be within the class term.';
    } else {
        $examModel->create($classId, $subject['name'], $date, $time, $subjectId);
        portalNotice('Exam scheduled.');
        header('Location: exams.php', true, 303);
        exit;
    }
}

$classes = staffClasses($db);
$exams = $examModel->getAll(classScope('e.class_id'))->fetchAll(PDO::FETCH_ASSOC);
require 'includes/layout.php';
?>
<header class="page-header"><h1>Manage Exams</h1></header>

<section class="card form-card mb-4" id="add-exam" aria-labelledby="add-exam-title">
    <h2 id="add-exam-title">Schedule an exam</h2>
    <?php if ($error): ?><p class="notice notice-error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <?php if (!$classes || !$subjects): ?>
        <p>An administrator must assign a subject to a class before an exam can be scheduled.</p>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <div class="fields">
                <div>
                    <label class="form-label" for="exam-class">Class</label>
                    <select class="form-select" name="class_id" id="exam-class" required>
                        <option value="">Select a class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= (int) $class['id'] ?>"<?= $classId === (int) $class['id'] ? ' selected' : '' ?>><?= h($class['name'] . ' · ' . $class['term_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="exam-subject">Subject</label>
                    <select class="form-select" id="exam-subject" name="subject_id" required><option value="">Select subject</option>
                        <?php foreach ($subjects as $option): ?><option value="<?= (int) $option['id'] ?>"
                            data-classes="<?= h(implode(',', $subjectClasses[$option['id']] ?? [])) ?>"<?= $subjectId === (int) $option['id'] ? ' selected' : '' ?>><?= h($option['name']) ?></option><?php endforeach; ?>
                    </select>
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
<script src="assets/exam-form.js" defer></script>
<?php require 'includes/layout_footer.php'; ?>
