<?php
if (!isset($editing)) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/portal.php';
requirePortalRole('admin');

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/ClassModel.php';

$db = portalDb();
$studentModel = new Student($db);
$classes = staffClasses($db);
$id = $editing ? positiveId(INPUT_GET, 'id') : null;
$student = $id ? $studentModel->getById($id) : false;
if ($editing && !$student) {
    portalNotice('Student record could not be found.', true);
    header('Location: index.php');
    exit;
}

$fields = ['name', 'last_name', 'email_academic', 'age', 'class_id'];
$values = array_replace(array_fill_keys($fields, ''), array_intersect_key($student ?: [], array_flip($fields)));
$accountId = (int) ($student['user_id'] ?? 0) ?: null;
$accounts = portalRows($db, "SELECT u.id, u.email FROM users u WHERE u.role = 'student' AND u.active = 1
    AND NOT EXISTS (SELECT 1 FROM students s WHERE s.user_id = u.id AND s.id <> ?) ORDER BY u.email", [$id ?? 0]);
$enrolledOn = date('Y-m-d');
$history = $student ? portalRows($db, 'SELECT e.*, c.name, t.name AS term_name FROM enrollments e
    JOIN classes c ON c.id = e.class_id LEFT JOIN terms t ON t.id = c.term_id WHERE e.student_id = ? ORDER BY e.starts_on DESC, e.id DESC', [$id]) : [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostToken();
    foreach ($fields as $field) {
        $values[$field] = postText($field);
    }
    $accountId = positiveId(INPUT_POST, 'account_id');
    $enrolledOn = postText('enrolled_on');

    $age = $values['age'] === '' ? null : filter_var($values['age'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 150]]);
    $classId = $values['class_id'] === '' ? null : filter_var($values['class_id'], FILTER_VALIDATE_INT);
    $classIds = array_map('intval', array_column($classes, 'id'));

    if ($values['name'] === '' || mb_strlen($values['name']) > 50 || mb_strlen($values['last_name']) > 50) {
        $error = 'Enter a first name and keep each name within 50 characters.';
    } elseif (!filter_var($values['email_academic'], FILTER_VALIDATE_EMAIL) || mb_strlen($values['email_academic']) > 100) {
        $error = 'Enter a valid academic email address.';
    } elseif ($age === false) {
        $error = 'Enter a whole-number age from 0 to 150 or leave it blank.';
    } elseif ($classId !== null && !in_array($classId, $classIds, true)) {
        $error = 'Select an existing class or leave the student unassigned.';
    } elseif (!portalDate($enrolledOn) || ($accountId && !in_array($accountId, array_map('intval', array_column($accounts, 'id')), true))) {
        $error = 'Choose a valid enrollment date and an available student account.';
    } else {
        $record = array_replace($values, ['age' => $age, 'class_id' => $classId]);
        try {
            portalTransaction($db, function () use ($db, $studentModel, $editing, $id, $record, $accountId, $classId, $enrolledOn) {
                if ($accountId && (!portalRow($db, "SELECT id FROM users WHERE id = ? AND role = 'student' AND active = 1 FOR UPDATE", [$accountId])
                    || portalRow($db, 'SELECT id FROM students WHERE user_id = ? AND id <> ?', [$accountId, $id ?? 0]))) {
                    throw new DomainException('That login is already linked to another student.');
                }
                $current = $editing ? portalRow($db, 'SELECT id, class_id FROM students WHERE id = ? FOR UPDATE', [$id]) : null;
                if ($editing && !$current) {
                    throw new DomainException('Student record could not be found.');
                }
                if ($editing) {
                    $studentModel->update($id, $record);
                } else {
                    $studentModel->create($record);
                    $id = (int) $db->lastInsertId();
                }
                $db->prepare('UPDATE students SET user_id = ? WHERE id = ?')->execute([$accountId, $id]);
                $previous = (int) ($current['class_id'] ?? 0) ?: null;
                if ($classId === $previous) {
                    return;
                }
                $open = portalRow($db, 'SELECT starts_on FROM enrollments WHERE student_id = ? AND ends_on IS NULL FOR UPDATE', [$id]);
                $last = portalRow($db, 'SELECT MAX(ends_on) AS ends_on FROM enrollments WHERE student_id = ?', [$id]);
                if (($open && $enrolledOn < $open['starts_on']) || (!empty($last['ends_on']) && $enrolledOn < $last['ends_on'])) {
                    throw new DomainException('The effective date cannot precede the student’s latest enrollment change.');
                }
                if ($classId) {
                    $term = portalRow($db, 'SELECT t.starts_on, t.ends_on FROM classes c JOIN terms t ON t.id = c.term_id WHERE c.id = ?', [$classId]);
                    if (!$term || $enrolledOn < $term['starts_on'] || $enrolledOn > $term['ends_on']) {
                        throw new DomainException('The enrollment date must be within the selected class term.');
                    }
                }
                $db->prepare('UPDATE enrollments SET ends_on = ? WHERE student_id = ? AND ends_on IS NULL')->execute([$enrolledOn, $id]);
                if ($classId) {
                    $db->prepare('INSERT INTO enrollments (student_id, class_id, starts_on) VALUES (?, ?, ?)')->execute([$id, $classId, $enrolledOn]);
                }
                auditEvent($db, $classId ? 'enrolled' : 'withdrew', 'enrollment', $id, $classId ?? $previous, ['effective_on' => $enrolledOn, 'previous_class_id' => $previous]);
            });
            portalNotice($editing ? 'Student details updated.' : 'Student added.');
            header('Location: index.php', true, 303);
            exit;
        } catch (DomainException $exception) {
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
            $error = 'This student record conflicts with an existing record. Check the email and class.';
        }
    }
}

$pageTitle = $editing ? 'Edit student' : 'Add student';
require __DIR__ . '/layout.php';
?>
<header class="page-header">
    <h1><?= h($pageTitle) ?></h1>
    <a class="btn btn-secondary" href="index.php">Back to students</a>
</header>

<form class="card form-card form-page" method="post">
    <?php if ($error): ?><p class="notice notice-error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
    <div class="fields fields-two">
        <div>
            <label class="form-label" for="first-name">First name</label>
            <input class="form-control" type="text" id="first-name" name="name" value="<?= h($values['name']) ?>" maxlength="50" required>
        </div>
        <div>
            <label class="form-label" for="last-name">Last name</label>
            <input class="form-control" type="text" id="last-name" name="last_name" value="<?= h($values['last_name']) ?>" maxlength="50">
        </div>
        <div class="field-wide">
            <label class="form-label" for="academic-email">Academic email</label>
            <input class="form-control" type="email" id="academic-email" name="email_academic" value="<?= h($values['email_academic']) ?>" maxlength="100" required>
        </div>
        <div>
            <label class="form-label" for="student-age">Age</label>
            <input class="form-control" type="number" id="student-age" name="age" value="<?= h($values['age']) ?>" min="0" max="150">
        </div>
        <div>
            <label class="form-label" for="student-class">Class</label>
            <select class="form-select" name="class_id" id="student-class">
                <option value="">Unassigned</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= (int) $class['id'] ?>"<?= (string) $values['class_id'] === (string) $class['id'] ? ' selected' : '' ?>>
                        <?= h($class['name'] . ' · ' . $class['term_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <label><span class="form-label">Enrollment change date</span><input class="form-control" type="date" name="enrolled_on" value="<?= h($enrolledOn) ?>" required></label>
        <label><span class="form-label">Linked student login</span><select class="form-select" name="account_id">
            <option value="">No linked login</option><?php foreach ($accounts as $account): ?><option value="<?= (int) $account['id'] ?>"<?= $accountId === (int) $account['id'] ? ' selected' : '' ?>><?= h($account['email']) ?></option><?php endforeach; ?>
        </select></label>
    </div>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= $editing ? 'Save changes' : 'Add student' ?></button>
        <a class="btn btn-secondary" href="index.php">Cancel</a>
    </div>
</form>
<?php if ($history): ?><section class="card form-card form-page section-spaced"><h2>Enrollment history</h2>
    <ul class="school-list"><?php foreach ($history as $enrollment): ?><li><?= h($enrollment['name'] . ' · ' . $enrollment['term_name']) ?>
        <span class="cell-detail"><?= h(($enrollment['starts_on'] === '1000-01-01' ? 'Imported' : $enrollment['starts_on']) . ' · ' . ($enrollment['ends_on'] ? 'Ended ' . $enrollment['ends_on'] : 'Current')) ?></span></li><?php endforeach; ?></ul>
</section><?php endif; ?>
<?php require __DIR__ . '/layout_footer.php'; ?>
