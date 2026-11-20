<?php
require_once 'includes/portal.php';
requirePortalRole('teacher', 'admin', 'student');
require_once 'models/Student.php';

$db = portalDb();
$isStudent = $_SESSION['role'] === 'student';
$learner = $isStudent ? (new Student($db))->getStudentWithClass(currentUserId()) : null;
$studentId = $isStudent ? ((int) ($learner['id'] ?? 0) ?: null) : positiveId(INPUT_GET, 'student_id');
$classPredicate = $isStudent
    ? 'EXISTS (SELECT 1 FROM enrollments r WHERE r.class_id = c.id AND r.student_id = ' . (int) $studentId . ')'
    : classScope('c.id');
$classes = portalRows($db, 'SELECT c.id, c.name, c.term_id, t.name AS term_name FROM classes c
    JOIN terms t ON t.id = c.term_id WHERE ' . $classPredicate . ' ORDER BY t.starts_on DESC, c.name');
$terms = portalRows($db, 'SELECT DISTINCT t.id, t.starts_on, t.name, y.name AS year_name FROM terms t
    JOIN academic_years y ON t.year_id = y.id JOIN classes c ON c.term_id = t.id
    WHERE ' . $classPredicate . ' ORDER BY t.starts_on DESC, t.id DESC');

$classId = positiveId(INPUT_GET, 'class_id');
$termId = positiveId(INPUT_GET, 'term_id');
$classIds = array_map('intval', array_column($classes, 'id'));
if ($classId && !in_array($classId, $classIds, true)) {
    forbid();
}
$predicates = [$classPredicate];
$values = [];
if ($termId) {
    $predicates[] = 'c.term_id = ?';
    $values[] = $termId;
}
if ($classId) {
    $predicates[] = 'c.id = ?';
    $values[] = $classId;
}
$filter = implode(' AND ', $predicates);

$studentChoices = $isStudent ? [] : portalRows($db, 'SELECT DISTINCT s.id, s.name, s.last_name FROM students s
    JOIN enrollments e ON e.student_id = s.id JOIN classes c ON e.class_id = c.id
    WHERE ' . $filter . ' ORDER BY s.last_name, s.name', $values);
if ($isStudent || $studentId) {
    $filter .= ' AND s.id = ?';
    $values[] = $studentId ?? 0;
}

$attendance = portalRows($db, "SELECT s.id, s.name, s.last_name, c.name AS class_name, t.name AS term_name,
    COUNT(a.student_id) AS total, COALESCE(SUM(a.status = 'present'), 0) AS present,
    COALESCE(SUM(a.status = 'late'), 0) AS late, COALESCE(SUM(a.status = 'absent'), 0) AS absent
    FROM (SELECT DISTINCT student_id, class_id FROM enrollments) e
    JOIN students s ON e.student_id = s.id JOIN classes c ON e.class_id = c.id JOIN terms t ON t.id = c.term_id
    LEFT JOIN attendance a ON a.class_id = c.id AND a.student_id = s.id WHERE $filter
    GROUP BY s.id, s.name, s.last_name, c.id, c.name, t.id, t.name
    ORDER BY s.last_name, s.name, c.name, t.name", $values);

$grades = portalRows($db, "SELECT s.id, s.name, s.last_name, c.name AS class_name, t.name AS term_name, g.subject,
    COUNT(*) AS entries, AVG(g.grade) AS average
    FROM grades g JOIN students s ON g.student_id = s.id JOIN classes c ON g.class_id = c.id
    JOIN terms t ON t.id = c.term_id WHERE $filter
    GROUP BY s.id, s.name, s.last_name, c.id, c.name, t.id, t.name, g.subject
    ORDER BY s.last_name, s.name, c.name, t.name, g.subject", $values);
$enrollments = $studentId ? portalRows($db, "SELECT c.name AS class_name, t.name AS term_name, e.starts_on, e.ends_on FROM enrollments e
    JOIN students s ON e.student_id = s.id JOIN classes c ON e.class_id = c.id JOIN terms t ON t.id = c.term_id
    WHERE $filter ORDER BY e.starts_on DESC, e.id DESC", $values) : [];

$export = filter_input(INPUT_GET, 'export');
if (in_array($export, ['attendance', 'grades'], true)) {
    $rows = $export === 'attendance' ? $attendance : $grades;
    $heads = $export === 'attendance' ? ['Student', 'Class', 'Term', 'Recorded', 'Present', 'Late', 'Absent']
        : ['Student', 'Class', 'Term', 'Subject', 'Recorded grades', 'Average'];
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="school-portal-' . $export . '.csv"');
    $file = fopen('php://output', 'wb');
    fwrite($file, "\xEF\xBB\xBF");
    fputcsv($file, $heads, ',', '"', '');
    $csvCell = static fn ($cell) => preg_match('/^[\s]*[=+\-@]|^[\t\r\n]/u', (string) $cell) ? "'" . $cell : $cell;
    foreach ($rows as $row) {
        $cells = [trim($row['name'] . ' ' . $row['last_name']), $row['class_name'], $row['term_name']];
        if ($export === 'attendance') {
            array_push($cells, $row['total'], $row['present'], $row['late'], $row['absent']);
        } else {
            array_push($cells, $row['subject'], $row['entries'], number_format($row['average'], 2, '.', ''));
        }
        fputcsv($file, array_map($csvCell, $cells), ',', '"', '');
    }
    fclose($file);
    exit;
}

$params = ['term_id' => $termId, 'class_id' => $classId, 'student_id' => $isStudent ? null : $studentId];
$pageTitle = $isStudent ? 'My reports' : 'Reports';
require 'includes/layout.php';
?>
<header class="page-header">
    <div><h1><?= h($pageTitle) ?></h1><p><?= $learner ? h(trim($learner['name'] . ' ' . $learner['last_name'])) : 'Review grades and attendance by class and term.' ?></p></div>
    <div class="row-actions no-print">
        <a href="gradebook.php" class="btn btn-secondary"><?= $isStudent ? 'Published report cards' : 'Gradebook' ?></a>
        <button type="button" class="btn btn-secondary" onclick="window.print()">Print report</button>
    </div>
</header>
<form class="card form-card mb-4 no-print" method="get">
    <div class="fields">
        <label><span class="form-label">Term</span><select name="term_id" class="form-select">
            <option value="">All terms</option><?php foreach ($terms as $term): ?><option value="<?= (int) $term['id'] ?>"<?= (int) $term['id'] === $termId ? ' selected' : '' ?>><?= h($term['year_name'] . ' · ' . $term['name']) ?></option><?php endforeach; ?>
        </select></label>
        <label><span class="form-label">Class</span><select name="class_id" class="form-select">
            <option value="">All classes</option><?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>"<?= (int) $class['id'] === $classId ? ' selected' : '' ?>><?= h($class['name'] . ' · ' . $class['term_name']) ?></option><?php endforeach; ?>
        </select></label>
        <?php if (!$isStudent): ?><label><span class="form-label">Student</span><select name="student_id" class="form-select">
            <option value="">All students</option><?php foreach ($studentChoices as $student): ?><option value="<?= (int) $student['id'] ?>"<?= (int) $student['id'] === $studentId ? ' selected' : '' ?>><?= h(trim($student['name'] . ' ' . $student['last_name'])) ?></option><?php endforeach; ?>
        </select></label><?php endif; ?>
        <div><button class="btn btn-primary">Apply filters</button></div>
    </div>
</form>
<div class="stack">
    <?php if ($attendance): ?><section class="stats" aria-label="Attendance totals">
        <?php foreach (['total' => 'Recorded', 'present' => 'Present', 'late' => 'Late', 'absent' => 'Absent'] as $key => $label): ?>
            <div class="stat"><span><?= h($label) ?></span><strong class="stat-number"><?= number_format(array_sum(array_column($attendance, $key))) ?></strong></div>
        <?php endforeach; ?>
    </section><?php endif; ?>
    <section class="card">
        <div class="panel-heading"><h2>Attendance summary</h2><a class="no-print" href="reports.php?<?= h(http_build_query($params + ['export' => 'attendance'])) ?>">Download CSV</a></div>
        <?php if (!$attendance): ?><p class="empty">No attendance records match these filters.</p><?php else: ?>
        <div class="table-responsive"><table class="table">
            <thead><tr><th scope="col">Student</th><th scope="col">Class and term</th><th scope="col">Recorded</th><th scope="col">Present</th><th scope="col">Late</th><th scope="col">Absent</th></tr></thead><tbody>
            <?php foreach ($attendance as $row): ?><tr>
                <td class="person-name"><?= h(trim($row['name'] . ' ' . $row['last_name'])) ?></td><td><?= h($row['class_name']) ?><span class="cell-detail"><?= h($row['term_name']) ?></span></td>
                <td><?= (int) $row['total'] ?></td><td><?= (int) $row['present'] ?></td><td><?= (int) $row['late'] ?></td><td><?= (int) $row['absent'] ?></td>
            </tr><?php endforeach; ?>
            </tbody></table></div><?php endif; ?>
    </section>
    <section class="card">
        <div class="panel-heading"><h2>Quick grade summary</h2><a class="no-print" href="reports.php?<?= h(http_build_query($params + ['export' => 'grades'])) ?>">Download CSV</a></div>
        <?php if (!$grades): ?><p class="empty">No grades match these filters.</p><?php else: ?>
        <div class="table-responsive"><table class="table">
            <thead><tr><th scope="col">Student</th><th scope="col">Class and term</th><th scope="col">Subject</th><th scope="col">Recorded grades</th><th scope="col">Average</th></tr></thead><tbody>
            <?php foreach ($grades as $row): ?><tr>
                <td class="person-name"><?= h(trim($row['name'] . ' ' . $row['last_name'])) ?></td><td><?= h($row['class_name']) ?><span class="cell-detail"><?= h($row['term_name']) ?></span></td>
                <td><?= h($row['subject']) ?></td><td><?= (int) $row['entries'] ?></td><td><?= number_format($row['average'], 2) ?></td>
            </tr><?php endforeach; ?>
            </tbody></table></div><?php endif; ?>
    </section>
    <?php if ($enrollments): ?><section class="card"><div class="panel-heading"><h2>Enrollment history</h2></div>
        <ul class="school-list"><?php foreach ($enrollments as $row): ?><li><?= h($row['class_name'] . ' · ' . $row['term_name']) ?>
            <span class="cell-detail"><?= h(($row['starts_on'] === '1000-01-01' ? 'Imported' : $row['starts_on']) . ' · ' . ($row['ends_on'] ? 'Ended ' . $row['ends_on'] : 'Current')) ?></span></li><?php endforeach; ?></ul>
    </section><?php endif; ?>
</div>
<?php require 'includes/layout_footer.php'; ?>
