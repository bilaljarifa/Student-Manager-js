<?php
require_once 'includes/portal.php';
requirePortalRole('teacher', 'admin');

require_once 'models/GradeModel.php';
$db = portalDb();
$model = new GradeModel($db);
$classes = staffClasses($db);
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$source = $isPost ? INPUT_POST : INPUT_GET;
$classId = positiveId($source, 'class_id');
$studentId = positiveId($source, 'student_id');
$error = '';

if ($isPost) {
    requirePostToken();
}
if (!$classId && $studentId) {
    $student = portalRow($db, 'SELECT s.class_id FROM students s WHERE s.id = ? AND ' . classScope('s.class_id'), [$studentId]);
    $classId = !empty($student['class_id']) ? (int) $student['class_id'] : null;
}
if ($classId) {
    requireClass($db, $classId);
}
$subjects = $classId ? classSubjects($db, $classId) : [];

$students = $classId ? portalRows($db, 'SELECT DISTINCT s.id, s.name, s.last_name, s.email_academic FROM students s
    JOIN enrollments e ON s.id = e.student_id WHERE e.class_id = ? ORDER BY s.last_name, s.name', [$classId]) : [];
$selected = $studentId ? (array_column($students, null, 'id')[$studentId] ?? null) : null;
$recordPath = 'grades.php?' . http_build_query(['class_id' => $classId, 'student_id' => $studentId]);

if ($isPost) {
    if (!$classId || !$selected) {
        forbid();
    }
    $action = postText('action');
    $gradeId = positiveId(INPUT_POST, 'grade_id');
    $subjectId = positiveId(INPUT_POST, 'subject_id');
    $grade = filter_var(postText('grade'), FILTER_VALIDATE_FLOAT);
    if (!in_array($action, ['add', 'edit', 'delete'], true) || ($action !== 'add' && !$gradeId)) {
        $error = 'Choose a valid grade action.';
    } else {
        try {
            $model->save($classId, $studentId, $action === 'add' ? null : $gradeId, $subjectId, $grade === false ? null : $grade, $action === 'delete');
            portalNotice(match ($action) { 'edit' => 'Grade updated.', 'delete' => 'Grade removed.', default => 'Grade recorded.' });
            redirectTo($recordPath);
        } catch (DomainException $exception) {
            $error = $exception->getMessage();
        }
    }
}

$grades = $selected ? $model->getByStudent($studentId, $classId)->fetchAll(PDO::FETCH_ASSOC) : [];
$editingId = positiveId(INPUT_GET, 'edit');
$editing = $editingId ? (array_column($grades, null, 'id')[$editingId] ?? null) : null;
$average = $grades ? array_sum(array_column($grades, 'grade')) / count($grades) : null;
$pageTitle = 'Manage grades';
require 'includes/layout.php';
?>
<header class="page-header"><div><h1>Manage grades</h1><p>Record and review grades by class and term.</p></div></header>
<?php if ($error): ?><p class="notice notice-error" role="alert"><?= h($error) ?></p><?php endif; ?>
<section class="card form-card mb-4">
    <h2>Find a student</h2>
    <form method="get" class="fields">
        <label><span class="form-label">Class and term</span><select class="form-select" name="class_id" required>
            <option value="">Select a class</option>
            <?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>"<?= $classId === (int) $class['id'] ? ' selected' : '' ?>><?= h($class['name'] . ' · ' . $class['term_name']) ?></option><?php endforeach; ?>
        </select></label>
        <?php if ($classId): ?><label><span class="form-label">Student</span><select class="form-select" name="student_id">
            <option value="">Select a student</option>
            <?php foreach ($students as $student): ?><option value="<?= (int) $student['id'] ?>"<?= $studentId === (int) $student['id'] ? ' selected' : '' ?>><?= h(trim($student['name'] . ' ' . $student['last_name'])) ?> · <?= h($student['email_academic']) ?></option><?php endforeach; ?>
        </select></label><?php endif; ?>
        <div><button class="btn btn-primary"><?= $classId ? 'View grades' : 'Load class' ?></button></div>
    </form>
    <?php if ($classId && !$students): ?><p class="section-spaced">This class has no enrollment records.</p><?php endif; ?>
</section>

<?php if ($selected): ?>
<div class="panels">
    <section class="card">
        <div class="panel-heading"><h2><?= h(trim($selected['name'] . ' ' . $selected['last_name'])) ?>’s grades</h2>
            <?php if ($average !== null): ?><span class="tag">Average <?= number_format($average, 2) ?></span><?php endif; ?></div>
        <?php if (!$grades): ?><p class="empty">No grades have been recorded for this class.</p><?php else: ?>
        <div class="table-responsive"><table class="table"><thead><tr><th scope="col">Subject</th><th scope="col">Grade</th><th scope="col">Actions</th></tr></thead><tbody>
            <?php foreach ($grades as $row): ?><tr>
                <td class="person-name"><?= h($row['subject']) ?></td><td><?= h($row['grade']) ?></td>
                <td><div class="row-actions">
                    <a class="btn btn-secondary btn-sm" href="<?= h($recordPath . '&edit=' . $row['id'] . '#grade-editor') ?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Remove this grade?');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="class_id" value="<?= $classId ?>"><input type="hidden" name="student_id" value="<?= $studentId ?>">
                        <input type="hidden" name="action" value="delete"><button class="btn btn-danger btn-sm" name="grade_id" value="<?= (int) $row['id'] ?>">Delete</button>
                    </form></div></td>
            </tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </section>
    <section class="card form-card" id="grade-editor">
        <h2><?= $editing ? 'Edit grade' : 'Record a grade' ?></h2>
        <?php if (!$subjects): ?><p>An administrator needs to assign subjects to this class.</p><?php else: ?>
        <form method="post" class="stack">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="class_id" value="<?= $classId ?>"><input type="hidden" name="student_id" value="<?= $studentId ?>">
            <input type="hidden" name="action" value="<?= $editing ? 'edit' : 'add' ?>"><input type="hidden" name="grade_id" value="<?= (int) ($editing['id'] ?? 0) ?>">
            <label><span class="form-label">Subject</span><select class="form-select" name="subject_id" required><option value="">Select subject</option>
                <?php foreach ($subjects as $subject): ?><option value="<?= (int) $subject['id'] ?>"<?= (int) ($editing['subject_id'] ?? 0) === (int) $subject['id'] ? ' selected' : '' ?>><?= h($subject['name']) ?></option><?php endforeach; ?>
            </select></label>
            <label><span class="form-label">Grade</span><input class="form-control" type="number" name="grade" step="0.01" min="-999.99" max="999.99" value="<?= h($editing['grade'] ?? '') ?>" required></label>
            <div class="row-actions"><button class="btn btn-primary"><?= $editing ? 'Save changes' : 'Save grade' ?></button>
                <?php if ($editing): ?><a href="<?= h($recordPath) ?>">Cancel</a><?php endif; ?></div>
        </form><?php endif; ?>
    </section>
</div>
<?php elseif ($studentId): ?><p class="notice">Select a student enrolled in the class.</p><?php endif; ?>
<?php require 'includes/layout_footer.php'; ?>
