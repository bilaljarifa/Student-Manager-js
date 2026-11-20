<?php
require_once 'includes/portal.php';
requirePortalRole('teacher');

require_once 'config/Database.php';
require_once 'models/ExamModel.php';

$db = portalDb();
$now = date('Y-m-d H:i:s');
$totalsQuery = $db->prepare(
    "SELECT (SELECT COUNT(*) FROM students s WHERE " . classScope('s.class_id') . ") AS students,
            (SELECT COUNT(*) FROM classes c WHERE " . classScope('c.id') . ") AS classes,
            (SELECT COUNT(*) FROM exams e WHERE TIMESTAMP(exam_date, exam_time) >= :now AND " . classScope('e.class_id') . ") AS exams,
            (SELECT COUNT(*) FROM courses co WHERE " . classScope('co.class_id') . ") AS courses"
);
$totalsQuery->execute(['now' => $now]);
$totals = $totalsQuery->fetch(PDO::FETCH_ASSOC);
$exams = (new ExamModel($db))->getUpcoming($now, classScope('e.class_id'))->fetchAll(PDO::FETCH_ASSOC);
$stats = [
    ['Students', $totals['students'], 'index.php'],
    ['Classes', $totals['classes'], 'classes.php'],
    ['Upcoming exams', $totals['exams'], 'exams.php'],
    ['Course materials', $totals['courses'], 'courses.php'],
];

$pageTitle = 'Teacher Dashboard';
require 'includes/layout.php';
?>
<header class="hero">
    <div class="hero-copy">
        <h1>Teacher Dashboard</h1>
        <p>Take attendance and keep track of your classes and exams.</p>
        <div class="hero-actions">
            <a class="btn btn-hero" href="attendance.php">Take attendance <span aria-hidden="true">↗</span></a>
            <a class="btn btn-hero-outline" href="reports.php">View reports</a>
        </div>
    </div>
    <div class="hero-image" aria-hidden="true"></div>
</header>

<section class="stats" aria-label="School totals">
    <?php foreach ($stats as [$label, $count, $url]): ?>
        <a href="<?= $url ?>" class="stat">
            <span><?= h($label) ?></span>
            <strong class="stat-number"><?= number_format($count) ?></strong>
        </a>
    <?php endforeach; ?>
</section>

<div class="panels">
    <section class="card" aria-labelledby="upcoming">
        <div class="panel-heading">
            <h2 id="upcoming">Upcoming exams</h2>
            <a href="exams.php">View schedule</a>
        </div>
        <?php if ($exams): ?>
            <div class="table-responsive">
                <table class="table" aria-labelledby="upcoming">
                    <thead><tr><th scope="col">Subject</th><th scope="col">Class</th><th scope="col">Date and time</th></tr></thead>
                    <tbody>
                        <?php foreach ($exams as $exam): ?>
                            <tr>
                                <td class="person-name"><?= h($exam['subject']) ?></td>
                                <td><?= h($exam['class_name'] ?? 'Class unavailable') ?></td>
                                <td class="nowrap">
                                    <?= h(date('M j, Y', strtotime($exam['exam_date']))) ?>
                                    <span class="cell-detail"><?= h(substr($exam['exam_time'], 0, 5)) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty">No upcoming exams are scheduled.</p>
        <?php endif; ?>
    </section>

    <section class="card" aria-labelledby="quick-actions">
        <div class="panel-heading"><h2 id="quick-actions">Quick actions</h2></div>
        <ul class="quick-links">
            <li><a href="class_updates.php">Timetable and announcements <span aria-hidden="true">→</span></a></li>
            <li><a href="exams.php#add-exam">Schedule an exam <span aria-hidden="true">→</span></a></li>
            <li><a href="courses.php#upload-course">Upload course material <span aria-hidden="true">→</span></a></li>
            <li><a href="gradebook.php">Open gradebook <span aria-hidden="true">→</span></a></li>
        </ul>
    </section>
</div>
<?php require 'includes/layout_footer.php'; ?>
