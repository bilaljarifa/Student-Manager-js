<?php
require_once 'includes/portal.php';
requirePortalRole('student');

require_once 'config/Database.php';
require_once 'models/Student.php';
require_once 'models/AttendanceModel.php';

$db = (new Database())->connect();
$student = (new Student($db))->getStudentWithClass($_SESSION['user_id']);
$records = $student ? (new AttendanceModel($db))->getForStudent($student['id']) : [];
$statuses = AttendanceModel::STATUSES;
$counts = array_fill_keys(array_keys($statuses), 0);
foreach ($records as $record) {
    $counts[$record['status']]++;
}
$pageTitle = 'My attendance';
require 'includes/layout.php';
?>
<header class="page-header">
    <div>
        <h1>My attendance</h1>
        <p>Review the roll calls your teacher has recorded.</p>
    </div>
</header>

<?php if (!$student): ?>
    <p class="notice">Ask your teacher to link your school record to this account.</p>
<?php else: ?>
    <section class="stats" aria-label="Attendance totals">
        <?php foreach ($statuses as $value => $label): ?>
            <div class="stat">
                <span class="status-chip attendance-<?= $value ?>"><?= h($label) ?></span>
                <strong class="stat-number"><?= number_format($counts[$value]) ?></strong>
            </div>
        <?php endforeach; ?>
    </section>
    <section class="card" aria-labelledby="attendance-history">
        <div class="panel-heading"><h2 id="attendance-history">Attendance history</h2></div>
        <?php if (!$records): ?>
            <p class="empty">Your teacher hasn’t recorded any attendance yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table" aria-labelledby="attendance-history">
                    <thead><tr><th scope="col">Date</th><th scope="col">Class</th><th scope="col">Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td class="person-name nowrap"><time datetime="<?= h($record['attendance_date']) ?>"><?= h(date('M j, Y', strtotime($record['attendance_date']))) ?></time></td>
                                <td><?= h($record['class_name']) ?></td>
                                <td><span class="status-chip attendance-<?= h($record['status']) ?>"><?= h($statuses[$record['status']]) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
<?php require 'includes/layout_footer.php'; ?>
