<?php
require_once 'includes/portal.php';
requirePortalRole('teacher');

require_once 'config/Database.php';
require_once 'models/Student.php';
require_once 'models/ClassModel.php';

$db = (new Database())->connect();
$searchInput = filter_input(INPUT_GET, 'q');
$search = is_string($searchInput) ? trim($searchInput) : '';
$classId = positiveId(INPUT_GET, 'class_id');
$classes = (new ClassModel($db))->getAll()->fetchAll(PDO::FETCH_ASSOC);
$students = (new Student($db))->getAll($search, $classId)->fetchAll(PDO::FETCH_ASSOC);
$filtering = $search !== '' || $classId !== null;
$pageTitle = 'Students';
require 'includes/layout.php';
?>
<header class="page-header">
    <div>
        <h1>Manage Students</h1>
        <p>Find student records and update class assignments.</p>
    </div>
    <a href="create.php" class="btn btn-primary">Add student</a>
</header>

<section class="card" aria-labelledby="student-directory">
    <div class="panel-heading">
        <h2 id="student-directory">Student directory</h2>
        <span class="text-muted"><?= number_format(count($students)) ?> <?= count($students) === 1 ? 'student' : 'students' ?></span>
    </div>
    <form method="get" class="filter-bar">
        <div>
            <label class="form-label" for="student-search">Name or email</label>
            <input type="search" class="form-control" id="student-search" name="q" value="<?= h($search) ?>" placeholder="Search students">
        </div>
        <div>
            <label class="form-label" for="class-filter">Class</label>
            <select class="form-select" name="class_id" id="class-filter">
                <option value="">All classes</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= (int) $class['id'] ?>"<?= $classId === (int) $class['id'] ? ' selected' : '' ?>>
                        <?= h($class['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row-actions">
            <button class="btn btn-primary" type="submit">Search</button>
            <?php if ($filtering): ?><a class="btn btn-secondary" href="index.php">Clear</a><?php endif; ?>
        </div>
    </form>
    <?php if (!$students): ?>
        <p class="empty"><?= $filtering ? 'No students match these filters.' : 'No students have been added yet.' ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table" aria-labelledby="student-directory">
                <thead><tr><th scope="col">Student</th><th scope="col">Class</th><th scope="col">Age</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td>
                                <span class="person-name"><?= h(trim($student['name'] . ' ' . $student['last_name'])) ?></span>
                                <span class="cell-detail"><?= h($student['email_academic']) ?></span>
                            </td>
                            <td><?= $student['class_name'] === null ? 'Unassigned' : '<span class="tag">' . h($student['class_name']) . '</span>' ?></td>
                            <td><?= h($student['age'] ?? '—') ?></td>
                            <td>
                                <div class="row-actions">
                                    <a href="edit.php?id=<?= (int) $student['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                                    <a href="grades.php?student_id=<?= (int) $student['id'] ?>" class="btn btn-sm btn-secondary">Grades</a>
                                    <form action="delete.php" method="post" onsubmit="return confirm('Delete this student record?');">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $student['id'] ?>">
                                        <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require 'includes/layout_footer.php'; ?>
