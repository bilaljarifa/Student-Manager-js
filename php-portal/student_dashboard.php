<?php
require_once 'includes/portal.php';
requirePortalRole('student');

require_once 'config/Database.php';
require_once 'models/Student.php';
require_once 'models/ExamModel.php';
require_once 'models/GradeModel.php';
require_once 'models/CourseModel.php';

$db = (new Database())->connect();
$student = (new Student($db))->getStudentWithClass($_SESSION['user_id']);
$hasClass = $student && $student['class_name'] !== null;
$exams = $hasClass ? (new ExamModel($db))->getByClass($student['class_id'])->fetchAll(PDO::FETCH_ASSOC) : [];
$upcoming = array_values(array_filter($exams, static fn ($exam) => (bool) $exam['is_upcoming']));
$past = array_reverse(array_filter($exams, static fn ($exam) => !$exam['is_upcoming']));
$courses = $hasClass ? (new CourseModel($db))->getByClass($student['class_id'])->fetchAll(PDO::FETCH_ASSOC) : [];
$gradeModel = new GradeModel($db);
$grades = $student ? $gradeModel->getByStudent($student['id'])->fetchAll(PDO::FETCH_ASSOC) : [];
$average = $grades ? $gradeModel->getAverage($student['id'])['average_grade'] : null;

$pageTitle = 'Student Dashboard';
require 'includes/layout.php';
?>
<header class="hero">
    <div class="hero-copy">
        <h1>Student Dashboard</h1>
        <p><?= $student ? 'Welcome, ' . h($student['name']) . '.' : 'Check your exams, course materials, and grades here.' ?></p>
        <div class="hero-actions">
            <a class="btn btn-hero" href="student_attendance.php">View attendance <span aria-hidden="true">↗</span></a>
        </div>
    </div>
    <div class="hero-image" aria-hidden="true"></div>
</header>

<?php if (!$student): ?>
    <p class="notice">Ask your teacher to link your school record to this account.</p>
<?php elseif (!$hasClass): ?>
    <p class="notice">Ask your teacher to assign you to a class so you can see exams and course materials.</p>
<?php endif; ?>

<section class="stats" aria-label="Your school summary">
    <div class="stat"><span>Class</span><strong><?= h($student['class_name'] ?? 'Unassigned') ?></strong></div>
    <div class="stat"><span>Room</span><strong><?= h(($student['room'] ?? '') ?: '—') ?></strong></div>
    <div class="stat"><span>Upcoming exams</span><strong class="stat-number"><?= count($upcoming) ?></strong></div>
    <div class="stat"><span>Grade average</span><strong class="stat-number"><?= $average === null ? '—' : number_format($average, 2) ?></strong></div>
</section>

<div class="panels">
    <div class="stack">
        <section class="card" aria-labelledby="exam-schedule">
            <div class="panel-heading"><h2 id="exam-schedule">Exam schedule</h2></div>
            <?php if (!$upcoming): ?>
                <p class="empty">No upcoming exams<?= $hasClass ? ' for your class' : '' ?>.</p>
            <?php endif; ?>
            <?php if ($exams): ?>
                <div class="table-responsive">
                    <table class="table" aria-labelledby="exam-schedule">
                        <thead><tr><th scope="col">Subject</th><th scope="col">Date</th><th scope="col">Time</th><th scope="col">Status</th></tr></thead>
                        <tbody>
                            <?php foreach (array_merge($upcoming, $past) as $exam): ?>
                                <tr>
                                    <td class="person-name"><?= h($exam['subject']) ?></td>
                                    <td class="nowrap"><?= h(date('M j, Y', strtotime($exam['exam_date']))) ?></td>
                                    <td><?= h(substr($exam['exam_time'], 0, 5)) ?></td>
                                    <td><?= $exam['is_upcoming'] ? '<span class="tag">Upcoming</span>' : 'Past' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
        <section class="card" aria-labelledby="grades">
            <div class="panel-heading"><h2 id="grades">Your grades</h2></div>
            <?php if ($grades): ?>
                <div class="table-responsive">
                    <table class="table" aria-labelledby="grades">
                        <thead><tr><th scope="col">Subject</th><th scope="col">Grade</th></tr></thead>
                        <tbody>
                            <?php foreach ($grades as $grade): ?>
                                <tr><td class="person-name"><?= h($grade['subject']) ?></td><td><?= h($grade['grade']) ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty">No grades have been recorded.</p>
            <?php endif; ?>
        </section>
    </div>

    <section class="card" aria-labelledby="materials">
        <div class="panel-heading"><h2 id="materials">Course materials</h2></div>
        <?php if ($courses): ?>
            <ul class="quick-links">
                <?php foreach ($courses as $course): ?>
                    <li><a href="<?= h(courseFileUrl($course['file_path'])) ?>"><?= h($course['title']) ?> <span>PDF</span></a></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="empty">No course materials are available.</p>
        <?php endif; ?>
    </section>
</div>
<?php require 'includes/layout_footer.php'; ?>
