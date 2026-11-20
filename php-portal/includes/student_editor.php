<?php
if (!isset($editing)) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/portal.php';
requirePortalRole('teacher');

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/ClassModel.php';

$db = (new Database())->connect();
$studentModel = new Student($db);
$classes = (new ClassModel($db))->getAll()->fetchAll(PDO::FETCH_ASSOC);
$id = $editing ? positiveId(INPUT_GET, 'id') : null;
$student = $id ? $studentModel->getById($id) : false;
if ($editing && !$student) {
    portalNotice('Student record could not be found.', true);
    header('Location: index.php');
    exit;
}

$fields = ['name', 'last_name', 'email_academic', 'age', 'class_id'];
$values = array_replace(array_fill_keys($fields, ''), array_intersect_key($student ?: [], array_flip($fields)));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostToken();
    foreach ($fields as $field) {
        $values[$field] = postText($field);
    }

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
    } else {
        $record = array_replace($values, ['age' => $age, 'class_id' => $classId]);
        try {
            if ($editing) {
                $studentModel->update($id, $record);
            } else {
                $studentModel->create($record);
            }
            portalNotice($editing ? 'Student details updated.' : 'Student added.');
            header('Location: index.php', true, 303);
            exit;
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
                        <?= h($class['name']) ?><?= $class['room'] ? ' · ' . h($class['room']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= $editing ? 'Save changes' : 'Add student' ?></button>
        <a class="btn btn-secondary" href="index.php">Cancel</a>
    </div>
</form>
<?php require __DIR__ . '/layout_footer.php'; ?>
