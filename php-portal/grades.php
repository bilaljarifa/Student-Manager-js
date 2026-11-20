<?php
require_once 'includes/portal.php';
requirePortalRole('teacher');

require_once 'config/Database.php';
require_once 'models/Student.php';
require_once 'models/GradeModel.php';

$db = (new Database())->connect();
$studentModel = new Student($db);
$gradeModel = new GradeModel($db);
$selectedId = positiveId(INPUT_GET, 'student_id');
$subject = '';
$gradeValue = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostToken();
    $selectedId = positiveId(INPUT_POST, 'student_id');
    if (!$selectedId || !$studentModel->getById($selectedId)) {
        $error = 'Select an existing student.';
    } elseif (isset($_POST['delete_id'])) {
        $id = positiveId(INPUT_POST, 'delete_id');
        if ($id && $gradeModel->delete($id, $selectedId)) {
            portalNotice('Grade removed.');
        } else {
            portalNotice('That grade could not be found for this student.', true);
        }
        header('Location: grades.php?student_id=' . $selectedId, true, 303);
        exit;
    } else {
        $subject = postText('subject');
        $gradeValue = postText('grade');
        $grade = filter_var($gradeValue, FILTER_VALIDATE_FLOAT);
        if ($subject === '' || mb_strlen($subject) > 100 || $grade === false || abs($grade) > 999.99) {
            $error = 'Enter a subject of up to 100 characters and a valid numeric grade.';
        } else {
            $gradeModel->create($selectedId, $subject, $grade);
            portalNotice('Grade recorded.');
            header('Location: grades.php?student_id=' . $selectedId, true, 303);
            exit;
        }
    }
}

$students = $studentModel->getAll()->fetchAll(PDO::FETCH_ASSOC);
$selectedStudent = $selectedId ? $studentModel->getById($selectedId) : false;
$grades = $selectedStudent ? $gradeModel->getByStudent($selectedId)->fetchAll(PDO::FETCH_ASSOC) : [];
$average = $grades ? $gradeModel->getAverage($selectedId)['average_grade'] : null;

require 'includes/layout.php';
?>
<header class="page-header"><h1>Manage Grades</h1></header>
<?php if ($error): ?><p class="notice notice-error" role="alert"><?= h($error) ?></p><?php endif; ?>

<section class="card form-card mb-4" aria-labelledby="find-student">
    <h2 id="find-student">Find a student</h2>
    <?php if (!$students): ?>
        <p><a href="create.php">Add a student</a> to record grades.</p>
    <?php else: ?>
        <form method="get" class="fields fields-two">
            <div>
                <label class="form-label" for="grade-student">Student</label>
                <select class="form-select" name="student_id" id="grade-student" required>
                    <option value="">Select a student</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= (int) $student['id'] ?>"<?= $selectedId === (int) $student['id'] ? ' selected' : '' ?>>
                            <?= h(trim($student['name'] . ' ' . $student['last_name'])) ?> · <?= h($student['email_academic']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><button class="btn btn-primary" type="submit">View grades</button></div>
        </form>
    <?php endif; ?>
</section>

<?php if ($selectedStudent): ?>
    <div class="panels">
        <section class="card" aria-labelledby="grade-list">
            <div class="panel-heading">
                <h2 id="grade-list"><?= h(trim($selectedStudent['name'] . ' ' . $selectedStudent['last_name'])) ?>’s grades</h2>
                <?php if ($average !== null): ?><span class="tag">Average <?= number_format($average, 2) ?></span><?php endif; ?>
            </div>
            <?php if (!$grades): ?>
                <p class="empty">No grades have been recorded for this student.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table" aria-labelledby="grade-list">
                        <thead><tr><th scope="col">Subject</th><th scope="col">Grade</th><th scope="col">Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($grades as $grade): ?>
                                <tr>
                                    <td class="person-name"><?= h($grade['subject']) ?></td>
                                    <td><?= h($grade['grade']) ?></td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Delete this grade?');">
                                            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                            <input type="hidden" name="student_id" value="<?= $selectedId ?>">
                                            <button class="btn btn-sm btn-danger" type="submit" name="delete_id" value="<?= (int) $grade['id'] ?>">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
        <section class="card form-card" aria-labelledby="record-grade">
            <h2 id="record-grade">Record a grade</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <input type="hidden" name="student_id" value="<?= $selectedId ?>">
                <div class="fields fields-two">
                    <div>
                        <label class="form-label" for="grade-subject">Subject</label>
                        <input class="form-control" id="grade-subject" name="subject" value="<?= h($subject) ?>" maxlength="100" required>
                    </div>
                    <div>
                        <label class="form-label" for="grade-value">Grade</label>
                        <input type="number" class="form-control" id="grade-value" name="grade" value="<?= h($gradeValue) ?>" step="0.01" min="-999.99" max="999.99" required>
                    </div>
                </div>
                <div class="form-actions"><button class="btn btn-primary" type="submit">Save grade</button></div>
            </form>
        </section>
    </div>
<?php elseif ($selectedId && !$error): ?>
    <p class="notice notice-error" role="alert">The selected student could not be found.</p>
<?php endif; ?>
<?php require 'includes/layout_footer.php'; ?>
