<?php
require_once 'includes/portal.php';
requirePortalRole('teacher', 'admin');

require_once 'config/Database.php';
require_once 'models/AttendanceModel.php';

$db = portalDb();
$model = new AttendanceModel($db);
$classes = $model->getClasses();
$classesById = array_column($classes, null, 'id');
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($isPost) {
    requirePostToken();
}

$source = $isPost ? INPUT_POST : INPUT_GET;
$classId = positiveId($source, 'class_id');
if (!$isPost && !filter_has_var(INPUT_GET, 'class_id')) {
    $classId = isset($classes[0]) ? (int) $classes[0]['id'] : null;
}
$class = $classesById[$classId] ?? null;
if ($classId && !$class) { requireClass($db, $classId); }
$dateInput = filter_input($source, 'date');
$date = $dateInput === null && !$isPost ? date('Y-m-d') : (is_string($dateInput) ? $dateInput : '');
$parsedDate = preg_match('/^[1-9]\d{3}-\d{2}-\d{2}$/D', $date)
    ? DateTimeImmutable::createFromFormat('!Y-m-d', $date)
    : false;
$validDate = $parsedDate && $parsedDate->format('Y-m-d') === $date
    && (!$class || ($date >= $class['starts_on'] && $date <= $class['ends_on']));
$statuses = AttendanceModel::STATUSES;
$error = '';
$submitted = [];

if ($classes && (!$class || !$validDate)) {
    $error = 'Choose an existing class and a date within its term.';
}

if ($isPost && $class && $validDate) {
    $submitted = filter_input(INPUT_POST, 'statuses', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?: [];
    $version = postText('version');

    try {
        if ($model->save($classId, $date, $version, $submitted)) {
            portalNotice('Attendance saved.');
            header('Location: attendance.php?' . http_build_query(['class_id' => $classId, 'date' => $date]), true, 303);
            exit;
        }
        $submitted = [];
        $error = 'This roster or its attendance changed. Review the latest entries and save again.';
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    }
}

$roster = $class && $validDate ? $model->getRoster($classId, $date) : [];
$recorded = count(array_filter(array_column($roster, 'status')));
$pageTitle = 'Attendance';
require 'includes/layout.php';
?>
<header class="page-header">
    <div>
        <h1>Attendance</h1>
        <p>Take roll call or correct a saved entry.</p>
    </div>
</header>

<?php if ($error): ?><p class="notice notice-error" role="alert"><?= h($error) ?></p><?php endif; ?>

<?php if (!$classes): ?>
    <section class="card form-card"><p><a href="classes.php#add-class">Add a class</a> before taking attendance.</p></section>
<?php else: ?>
    <form method="get" class="card form-card mb-4">
        <div class="fields attendance-filters">
            <div>
                <label class="form-label" for="attendance-class">Class</label>
                <select class="form-select" name="class_id" id="attendance-class" required>
                    <option value="">Select a class</option>
                    <?php foreach ($classes as $option): ?>
                        <option value="<?= (int) $option['id'] ?>"<?= $classId === (int) $option['id'] ? ' selected' : '' ?>>
                            <?= h($option['name'] . ' · ' . $option['term_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label" for="attendance-date">Date</label>
                <input class="form-control" type="date" name="date" id="attendance-date" value="<?= h($date) ?>" required>
            </div>
            <div><button class="btn btn-secondary" type="submit">Load roster</button></div>
        </div>
    </form>
<?php endif; ?>

<?php if ($class && $validDate): ?>
    <section class="card" aria-labelledby="roster-title">
        <div class="panel-heading">
            <div>
                <h2 id="roster-title"><?= h($class['name']) ?></h2>
                <p><?= h($parsedDate->format('l, F j, Y')) ?><?= $class['room'] ? ' · ' . h($class['room']) : '' ?></p>
            </div>
            <?php if ($roster): ?>
                <div class="row-actions">
                    <span class="tag"><?= $recorded ?> of <?= count($roster) ?> recorded</span>
                    <button class="btn btn-secondary" type="button" id="mark-present" form="attendance-form" hidden>Mark all present</button>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!$roster): ?>
            <p class="empty">This class has no students. <a href="index.php">Open the student directory</a> to assign students.</p>
        <?php else: ?>
            <form method="post" id="attendance-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <input type="hidden" name="class_id" value="<?= $classId ?>">
                <input type="hidden" name="date" value="<?= h($date) ?>">
                <input type="hidden" name="version" value="<?= h(AttendanceModel::rosterVersion($roster)) ?>">
                <div class="attendance-roster">
                    <?php foreach ($roster as $student): ?>
                        <?php
                        $id = (int) $student['id'];
                        $status = $submitted[$id] ?? $student['status'];
                        $name = trim($student['name'] . ' ' . $student['last_name']);
                        ?>
                        <div class="attendance-row">
                            <div>
                                <span id="roll-name-<?= $id ?>" class="person-name"><?= h($name) ?></span>
                                <span class="cell-detail"><?= h($student['email_academic']) ?></span>
                            </div>
                            <div class="attendance-choices" role="group" aria-labelledby="roll-name-<?= $id ?>">
                                <?php foreach ($statuses as $value => $label): ?>
                                    <label>
                                        <input type="radio" name="statuses[<?= $id ?>]" value="<?= $value ?>"
                                               <?= $status === $value ? 'checked' : '' ?> <?= $value === 'present' ? 'required' : '' ?>>
                                        <span class="attendance-choice attendance-<?= $value ?>"><?= h($label) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="attendance-actions">
                    <button class="btn btn-primary" type="submit">Save attendance</button>
                </div>
            </form>
            <script src="assets/attendance.js" defer></script>
        <?php endif; ?>
    </section>
<?php endif; ?>
<?php require 'includes/layout_footer.php'; ?>
