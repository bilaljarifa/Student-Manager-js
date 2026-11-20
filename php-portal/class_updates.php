<?php
require_once 'includes/portal.php';
requirePortalRole('teacher', 'admin', 'student');
require_once 'models/Student.php';

$db = portalDb();
$isStudent = $_SESSION['role'] === 'student';
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$classes = $isStudent ? [] : staffClasses($db);
$student = $isStudent ? (new Student($db))->getStudentWithClass(currentUserId()) : null;
$classId = $isStudent ? ((int) ($student['class_id'] ?? 0) ?: null)
    : (positiveId($isPost ? INPUT_POST : INPUT_GET, 'class_id') ?? (isset($classes[0]) ? (int) $classes[0]['id'] : null));
$class = $classId ? ($isStudent ? portalRow($db, 'SELECT * FROM classes WHERE id = ?', [$classId]) : requireClass($db, $classId)) : null;
$days = [1 => 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$tab = (filter_input(INPUT_GET, 'tab') === 'announcements') ? 'announcements' : 'timetable';
$error = '';

if ($isPost) {
    if ($isStudent || !$class) {
        forbid();
    }
    requirePostToken();
    try {
        $action = postText('action');
        if ($action === 'post') {
            $title = postText('title');
            $message = postText('message');
            if ($title === '' || $message === '' || mb_strlen($title) > 120 || mb_strlen($message) > 4000) {
                throw new DomainException('Enter a title and a message of up to 4,000 characters.');
            }
            portalTransaction($db, function () use ($db, $classId, $title, $message) {
                requireClass($db, $classId);
                $db->prepare('INSERT INTO announcements (class_id, author_id, title, message) VALUES (?, ?, ?, ?)')
                    ->execute([$classId, currentUserId(), $title, $message]);
            });
            portalNotice('Announcement posted.');
        } elseif ($action === 'save_lesson') {
            $day = positiveId(INPUT_POST, 'weekday');
            $subject = positiveId(INPUT_POST, 'subject_id');
            $lessonId = positiveId(INPUT_POST, 'lesson_id');
            $start = postText('starts_at');
            $end = postText('ends_at');
            $room = postText('room');
            $clock = static fn ($time) => preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $time);
            if (!isset($days[$day]) || !$subject || !$clock($start) || !$clock($end) || $start >= $end || !$room || mb_strlen($room) > 50) {
                throw new DomainException('Choose a subject and day, enter a room, and make the end time later than the start.');
            }
            portalTransaction($db, function () use ($db, $classId, $subject, $day, $start, $end, $room, $lessonId) {
                requireClass($db, $classId);
                if (!portalRow($db, 'SELECT subject_id FROM class_subjects WHERE class_id = ? AND subject_id = ?', [$classId, $subject])) {
                    throw new DomainException('The selected subject is not assigned to this class.');
                }
                if ($lessonId && !portalRow($db, 'SELECT id FROM lessons WHERE id = ? AND class_id = ?', [$lessonId, $classId])) {
                    throw new DomainException('This lesson does not belong to the selected class.');
                }
                if (portalRow($db, 'SELECT id FROM lessons WHERE class_id = ? AND weekday = ? AND starts_at < ? AND ends_at > ? AND id <> ?', [$classId, $day, $end, $start, $lessonId ?? 0])) {
                    throw new DomainException('This lesson overlaps another lesson for the class.');
                }
                if ($lessonId) {
                    $db->prepare('UPDATE lessons SET subject_id = ?, weekday = ?, starts_at = ?, ends_at = ?, room = ? WHERE id = ? AND class_id = ?')
                        ->execute([$subject, $day, $start, $end, $room, $lessonId, $classId]);
                } else {
                    $db->prepare('INSERT INTO lessons (class_id, subject_id, weekday, starts_at, ends_at, room) VALUES (?, ?, ?, ?, ?, ?)')
                        ->execute([$classId, $subject, $day, $start, $end, $room]);
                }
            });
            portalNotice('Lesson saved.');
        } elseif ($action === 'remove_lesson' || $action === 'remove_post') {
            $id = positiveId(INPUT_POST, 'record_id');
            $table = $action === 'remove_lesson' ? 'lessons' : 'announcements';
            portalTransaction($db, function () use ($db, $classId, $table, $id) {
                requireClass($db, $classId);
                $db->prepare("DELETE FROM $table WHERE class_id = ? AND id = ?")->execute([$classId, $id]);
            });
            portalNotice($action === 'remove_lesson' ? 'Lesson removed.' : 'Announcement removed.');
        } else {
            throw new DomainException('Choose a valid action.');
        }
        redirectTo('class_updates.php?' . http_build_query(['tab' => $tab, 'class_id' => $classId]));
    } catch (DomainException $exception) {
        $error = $exception->getMessage();
    }
}

$path = 'class_updates.php?' . http_build_query(['tab' => $tab, 'class_id' => $classId]);
$lessons = $class && $tab === 'timetable' ? portalRows($db, 'SELECT l.*, s.name AS subject FROM lessons l JOIN subjects s ON s.id = l.subject_id WHERE l.class_id = ? ORDER BY l.weekday, l.starts_at', [$classId]) : [];
$posts = $class && $tab === 'announcements' ? portalRows($db, 'SELECT * FROM announcements WHERE class_id = ? ORDER BY posted_at DESC, id DESC', [$classId]) : [];
$subjects = !$isStudent && $tab === 'timetable' && $classId ? classSubjects($db, $classId) : [];
$editId = positiveId(INPUT_GET, 'edit');
$lesson = !$isStudent && $editId ? (array_column($lessons, null, 'id')[$editId] ?? null) : null;
$byDay = [];
foreach ($lessons as $item) {
    $byDay[$item['weekday']][] = $item;
}
$pageTitle = 'Class updates';
require 'includes/layout.php';
?>
<header class="page-header"><div><h1>Class updates</h1><p><?= h($class['name'] ?? 'Weekly timetable and class announcements') ?></p></div></header>
<nav class="section-tabs" aria-label="Class update sections">
    <?php foreach (['timetable' => 'Weekly timetable', 'announcements' => 'Announcements'] as $key => $label): ?>
        <a href="class_updates.php?<?= h(http_build_query(['tab' => $key, 'class_id' => $classId])) ?>"<?= $tab === $key ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
    <?php endforeach; ?>
</nav>
<?php if ($error): ?><p class="notice notice-error" role="alert"><?= h($error) ?></p><?php endif; ?>
<?php if (!$isStudent && $classes): ?>
    <form method="get" class="card form-card mb-4">
        <input type="hidden" name="tab" value="<?= h($tab) ?>">
        <div class="fields fields-two"><label><span class="form-label">Class and term</span><select class="form-select" name="class_id" required>
            <?php foreach ($classes as $option): ?><option value="<?= (int) $option['id'] ?>"<?= $classId === (int) $option['id'] ? ' selected' : '' ?>><?= h($option['name'] . ' · ' . $option['term_name']) ?></option><?php endforeach; ?>
        </select></label><div><button class="btn btn-secondary">View class</button></div></div>
    </form>
<?php endif; ?>

<?php if (!$class): ?>
    <p class="notice">No class has been assigned to this account.</p>
<?php elseif ($tab === 'timetable'): ?>
    <div class="<?= $isStudent ? 'stack' : 'panels' ?>">
        <section class="card">
            <div class="panel-heading"><h2>Weekly timetable</h2></div>
            <?php if (!$byDay): ?><p class="empty">No lessons are scheduled.</p><?php else: ?><div class="table-responsive">
            <table class="table"><thead><tr><th scope="col">Day</th><th scope="col">Lesson</th><th scope="col">Time and room</th><?php if (!$isStudent): ?><th scope="col">Actions</th><?php endif; ?></tr></thead><tbody>
            <?php foreach ($byDay as $day => $items): foreach ($items as $item): ?><tr>
                <td><?= h($days[$day]) ?></td><td class="person-name"><?= h($item['subject']) ?></td>
                <td class="nowrap"><?= h(substr($item['starts_at'], 0, 5) . '–' . substr($item['ends_at'], 0, 5)) ?><span class="cell-detail"><?= h($item['room']) ?></span></td>
                <?php if (!$isStudent): ?><td><div class="row-actions">
                    <a class="btn btn-secondary btn-sm" href="<?= h($path . '&edit=' . $item['id'] . '#lesson-editor') ?>">Edit</a>
                    <form method="post" action="<?= h($path) ?>">
                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="class_id" value="<?= $classId ?>">
                        <input type="hidden" name="action" value="remove_lesson"><button class="btn btn-danger btn-sm" name="record_id" value="<?= (int) $item['id'] ?>">Remove</button>
                    </form></div></td><?php endif; ?>
            </tr><?php endforeach; endforeach; ?>
            </tbody></table></div><?php endif; ?>
        </section>
        <?php if (!$isStudent): ?><form method="post" class="card form-card stack" id="lesson-editor" action="<?= h($path . ($lesson ? '&edit=' . $lesson['id'] : '')) ?>">
            <h2><?= $lesson ? 'Edit lesson' : 'Add a lesson' ?></h2>
            <?php if (!$subjects): ?><p>An administrator needs to assign subjects to this class before lessons can be scheduled.</p><?php else: ?>
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="class_id" value="<?= $classId ?>">
            <input type="hidden" name="action" value="save_lesson"><input type="hidden" name="lesson_id" value="<?= (int) ($lesson['id'] ?? 0) ?>">
            <label><span class="form-label">Subject</span><select class="form-select" name="subject_id" required><option value="">Select subject</option>
                <?php foreach ($subjects as $subject): ?><option value="<?= (int) $subject['id'] ?>"<?= (int) ($lesson['subject_id'] ?? 0) === (int) $subject['id'] ? ' selected' : '' ?>><?= h($subject['name']) ?></option><?php endforeach; ?>
            </select></label>
            <label><span class="form-label">Day</span><select class="form-select" name="weekday">
                <?php foreach ($days as $day => $name): ?><option value="<?= $day ?>"<?= (int) ($lesson['weekday'] ?? 0) === $day ? ' selected' : '' ?>><?= h($name) ?></option><?php endforeach; ?>
            </select></label>
            <div class="fields fields-two">
                <label><span class="form-label">Start time</span><input type="time" name="starts_at" class="form-control" value="<?= h(substr($lesson['starts_at'] ?? '', 0, 5)) ?>" required></label>
                <label><span class="form-label">End time</span><input type="time" name="ends_at" class="form-control" value="<?= h(substr($lesson['ends_at'] ?? '', 0, 5)) ?>" required></label>
            </div>
            <label><span class="form-label">Room</span><input name="room" class="form-control" value="<?= h($lesson['room'] ?? $class['room']) ?>" maxlength="50" required></label>
            <div class="row-actions"><button class="btn btn-primary">Save lesson</button><?php if ($lesson): ?><a href="<?= h($path) ?>">Cancel</a><?php endif; ?></div>
            <?php endif; ?>
        </form><?php endif; ?>
    </div>
<?php else: ?>
    <div class="<?= $isStudent ? 'stack' : 'panels' ?>">
        <section class="card">
            <div class="panel-heading"><h2>Announcements</h2></div>
            <?php if (!$posts): ?><p class="empty">No announcements have been posted.</p><?php endif; ?>
            <?php foreach ($posts as $post): ?>
                <article class="announcement">
                    <h3><?= h($post['title']) ?></h3><time class="cell-detail"><?= h(date('M j, Y', strtotime($post['posted_at']))) ?></time>
                    <p><?= nl2br(h($post['message'])) ?></p>
                    <?php if (!$isStudent): ?><form method="post" action="<?= h($path) ?>" onsubmit="return confirm('Remove this announcement?');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="class_id" value="<?= $classId ?>">
                        <input type="hidden" name="action" value="remove_post"><button class="btn btn-danger btn-sm" name="record_id" value="<?= (int) $post['id'] ?>">Remove</button>
                    </form><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
        <?php if (!$isStudent): ?><form method="post" action="<?= h($path) ?>" class="card form-card stack">
            <h2>Post an announcement</h2>
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="class_id" value="<?= $classId ?>"><input type="hidden" name="action" value="post">
            <label><span class="form-label">Title</span><input name="title" class="form-control" maxlength="120" required></label>
            <label><span class="form-label">Message</span><textarea name="message" class="form-control" rows="5" maxlength="4000" required></textarea></label>
            <div><button class="btn btn-primary">Post to class</button></div>
        </form><?php endif; ?>
    </div>
<?php endif; ?>
<?php require 'includes/layout_footer.php'; ?>
