<?php
require_once 'includes/portal.php';
requirePortalRole('admin');

$db = portalDb();
$sections = ['accounts' => 'Accounts and assignments', 'academics' => 'Years, terms and subjects', 'audit' => 'Audit history'];
$section = filter_input(INPUT_GET, 'section');
if (!is_string($section) || !isset($sections[$section])) {
    $section = 'accounts';
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostToken();
    $action = postText('action');
    try {
        if ($section === 'accounts' && in_array($action, ['create_account', 'reset_password'], true)) {
            $temporary = bin2hex(random_bytes(12));
            $hash = password_hash($temporary, PASSWORD_DEFAULT);
            $email = postText('email');
            $role = postText('role');
            $accountId = positiveId(INPUT_POST, 'account_id');
            $email = portalTransaction($db, function () use ($db, $action, $email, $role, $hash, $accountId) {
                if ($action === 'create_account') {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100 || !in_array($role, ['admin', 'teacher', 'student'], true)) {
                        throw new DomainException('Enter a valid email and choose an account role.');
                    }
                    $db->prepare('INSERT INTO users (email, password, role, must_change) VALUES (?, ?, ?, 1)')->execute([$email, $hash, $role]);
                    auditEvent($db, 'created', 'account', (int) $db->lastInsertId(), null, ['role' => $role]);
                    return $email;
                }
                $account = $accountId && $accountId !== currentUserId()
                    ? portalRow($db, 'SELECT email FROM users WHERE id = ? FOR UPDATE', [$accountId]) : null;
                if (!$account) {
                    throw new DomainException('Choose another account to reset. Use Change password for your own account.');
                }
                $db->prepare('UPDATE users SET password = ?, must_change = 1, auth_version = auth_version + 1 WHERE id = ?')
                    ->execute([$hash, $accountId]);
                auditEvent($db, 'password reset', 'account', $accountId, null);
                return $account['email'];
            });
            $_SESSION['issued_credential'] = ['email' => $email, 'password' => $temporary];
        } elseif ($section === 'accounts' && in_array($action, ['assign_teacher', 'unassign_teacher'], true)) {
            $classId = positiveId(INPUT_POST, 'class_id');
            $teacherId = positiveId(INPUT_POST, 'teacher_id');
            portalTransaction($db, function () use ($db, $action, $classId, $teacherId) {
                if (!$classId || !$teacherId
                    || !portalRow($db, 'SELECT id FROM classes WHERE id = ? FOR UPDATE', [$classId])
                    || !portalRow($db, "SELECT id FROM users WHERE id = ? AND role = 'teacher' AND active = 1", [$teacherId])) {
                    throw new DomainException('Select an existing class and an active teacher.');
                }
                $statement = $action === 'assign_teacher'
                    ? $db->prepare('INSERT IGNORE INTO teacher_classes (teacher_id, class_id) VALUES (?, ?)')
                    : $db->prepare('DELETE FROM teacher_classes WHERE teacher_id = ? AND class_id = ?');
                $statement->execute([$teacherId, $classId]);
                if ($statement->rowCount()) {
                    auditEvent($db, $action === 'assign_teacher' ? 'assigned' : 'unassigned', 'teacher', $teacherId, $classId);
                }
            });
            portalNotice($action === 'assign_teacher' ? 'Teacher assignment saved.' : 'Teacher assignment removed.');
        } elseif ($section === 'academics' && $action === 'assign_subject') {
            $classId = positiveId(INPUT_POST, 'class_id');
            $subjectId = positiveId(INPUT_POST, 'subject_id');
            if (!$classId || !$subjectId || !portalRow($db, 'SELECT id FROM classes WHERE id = ?', [$classId])
                || !portalRow($db, 'SELECT id FROM subjects WHERE id = ?', [$subjectId])) {
                throw new DomainException('Select an existing class and subject.');
            }
            $db->prepare('INSERT IGNORE INTO class_subjects (class_id, subject_id) VALUES (?, ?)')->execute([$classId, $subjectId]);
            portalNotice('Class subject saved.');
        } elseif ($section === 'academics' && in_array($action, ['year', 'term', 'subject'], true)) {
            $name = postText('name');
            if ($name === '' || mb_strlen($name) > ($action === 'subject' ? 100 : 80)) {
                throw new DomainException('Enter a valid name.');
            }
            if ($action === 'subject') {
                $db->prepare('INSERT INTO subjects (name) VALUES (?)')->execute([$name]);
            } else {
                $start = postText('starts_on');
                $end = postText('ends_on');
                if (!portalDate($start) || !portalDate($end) || $start > $end) {
                    throw new DomainException('Enter a valid start and end date.');
                }
                if ($action === 'year') {
                    $db->prepare('INSERT INTO academic_years (name, starts_on, ends_on) VALUES (?, ?, ?)')->execute([$name, $start, $end]);
                } else {
                    $year = portalRow($db, 'SELECT * FROM academic_years WHERE id = ?', [positiveId(INPUT_POST, 'year_id')]);
                    if (!$year || $start < $year['starts_on'] || $end > $year['ends_on']) {
                        throw new DomainException('Term dates must fall within the selected academic year.');
                    }
                    $db->prepare('INSERT INTO terms (name, starts_on, ends_on, year_id) VALUES (?, ?, ?, ?)')->execute([$name, $start, $end, $year['id']]);
                }
            }
            portalNotice(ucfirst($action) . ' added.');
        } else {
            throw new DomainException('Choose a valid action.');
        }
        redirectTo('admin.php?section=' . $section);
    } catch (DomainException $exception) {
        $error = $exception->getMessage();
    } catch (PDOException $exception) {
        if ($exception->getCode() !== '23000') {
            throw $exception;
        }
        $error = 'A record with those details already exists.';
    }
}

$credential = $_SESSION['issued_credential'] ?? null;
unset($_SESSION['issued_credential']);
$accountList = $section === 'accounts' ? portalRows($db, 'SELECT id, email, role, must_change FROM users ORDER BY role, email') : [];
$classes = $section !== 'audit' ? staffClasses($db) : [];
$assignments = $section === 'accounts' ? portalRows($db, 'SELECT tc.*, u.email, c.name FROM teacher_classes tc JOIN users u ON u.id = tc.teacher_id JOIN classes c ON c.id = tc.class_id ORDER BY c.name, u.email') : [];
$years = $section === 'academics' ? portalRows($db, 'SELECT * FROM academic_years ORDER BY starts_on DESC') : [];
$terms = $section === 'academics' ? portalRows($db, 'SELECT t.*, y.name AS year_name FROM terms t JOIN academic_years y ON y.id = t.year_id ORDER BY t.starts_on DESC') : [];
$subjects = $section === 'academics' ? portalRows($db, 'SELECT * FROM subjects ORDER BY name') : [];
$classSubjects = $section === 'academics' ? portalRows($db, 'SELECT c.name AS class_name, t.name AS term_name, s.name AS subject_name
    FROM class_subjects cs JOIN classes c ON c.id = cs.class_id JOIN subjects s ON s.id = cs.subject_id
    JOIN terms t ON t.id = c.term_id ORDER BY c.name, t.name, s.name') : [];
$entity = filter_input(INPUT_GET, 'entity') ?: '';
$audit = $section === 'audit' ? portalRows($db, 'SELECT a.*, u.email AS actor_email, c.name AS class_name FROM portal_audit a LEFT JOIN users u ON a.actor_id = u.id LEFT JOIN classes c ON c.id = a.class_id WHERE (? = "" OR a.entity = ?) ORDER BY a.id DESC LIMIT 100', [$entity, $entity]) : [];

$pageTitle = 'Administration';
require 'includes/layout.php';
?>
<header class="page-header"><div><h1>Administration</h1><p>Set up accounts, classes, and the academic calendar.</p></div>
    <a href="gradebook.php" class="btn btn-secondary">Review gradebooks</a>
</header>
<nav class="section-tabs" aria-label="Administration sections">
    <?php foreach ($sections as $key => $label): ?>
        <a href="admin.php?section=<?= h($key) ?>"<?= $section === $key ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
    <?php endforeach; ?>
</nav>
<?php if ($error): ?><p class="notice notice-error" role="alert"><?= h($error) ?></p><?php endif; ?>
<?php if ($credential): ?>
    <div class="notice" role="status"><strong>Temporary sign-in for <?= h($credential['email']) ?></strong><br>
        <code><?= h($credential['password']) ?></code><br>The account must set a new password at the next sign-in. Copy this temporary password now.
    </div>
<?php endif; ?>
<?php if ($section === 'accounts'): ?>
<div class="panels mb-4">
    <form class="card form-card stack" method="post">
        <h2>Create an account</h2>
        <input name="csrf_token" type="hidden" value="<?= h(csrfToken()) ?>"><input name="action" type="hidden" value="create_account">
        <label><span class="form-label">Email address</span><input name="email" class="form-control" type="email" maxlength="100" required></label>
        <label><span class="form-label">Role</span><select class="form-select" name="role" required><option value="student">Student</option><option value="teacher">Teacher</option><option value="admin">Administrator</option></select></label>
        <div><button class="btn btn-primary">Create account</button></div>
    </form>
    <form class="card form-card stack" method="post">
        <h2>Assign a teacher</h2>
        <input name="csrf_token" type="hidden" value="<?= h(csrfToken()) ?>"><input name="action" type="hidden" value="assign_teacher">
        <label><span class="form-label">Teacher</span><select class="form-select" name="teacher_id" required><option value="">Select teacher</option>
            <?php foreach ($accountList as $user): if ($user['role'] !== 'teacher') { continue; } ?><option value="<?= (int) $user['id'] ?>"><?= h($user['email']) ?></option><?php endforeach; ?>
        </select></label>
        <label><span class="form-label">Class</span><select class="form-select" name="class_id" required><option value="">Select class</option>
            <?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>"><?= h($class['name'] . ' · ' . $class['term_name']) ?></option><?php endforeach; ?>
        </select></label>
        <div><button class="btn btn-primary">Assign teacher</button></div>
    </form>
</div>
<div class="panels">
<section class="card">
    <div class="panel-heading"><h2>Accounts</h2><a href="index.php">Link a student account</a></div>
    <div class="table-responsive"><table class="table"><thead><tr><th scope="col">Email</th><th scope="col">Role</th><th scope="col">Password</th></tr></thead><tbody>
    <?php foreach ($accountList as $account): ?><tr>
        <td class="person-name"><?= h($account['email']) ?><?= $account['must_change'] ? '<span class="cell-detail">Password change due</span>' : '' ?></td>
        <td><?= h(ucfirst($account['role'])) ?></td><td>
        <?php if ((int) $account['id'] !== currentUserId()): ?>
            <form method="post" onsubmit="return confirm('Reset this account’s password and sign it out?');">
                <input name="csrf_token" type="hidden" value="<?= h(csrfToken()) ?>"><input name="action" type="hidden" value="reset_password">
                <button class="btn btn-secondary btn-sm" name="account_id" value="<?= (int) $account['id'] ?>">Reset</button>
            </form>
        <?php else: ?><a href="password.php">Change</a><?php endif; ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
<section class="card">
    <div class="panel-heading"><h2>Teacher assignments</h2></div>
    <?php if (!$assignments): ?><p class="empty">No teachers are assigned to a class.</p><?php else: ?>
    <div class="table-responsive"><table class="table"><thead><tr><th scope="col">Teacher and class</th><th scope="col">Action</th></tr></thead><tbody>
    <?php foreach ($assignments as $assignment): ?><tr><td><?= h($assignment['email']) ?><span class="cell-detail"><?= h($assignment['name']) ?></span></td><td>
        <form method="post"><input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="action" value="unassign_teacher"><input type="hidden" name="class_id" value="<?= (int) $assignment['class_id'] ?>">
            <button class="btn btn-danger btn-sm" name="teacher_id" value="<?= (int) $assignment['teacher_id'] ?>">Remove</button></form>
    </td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</section>
</div>
<?php elseif ($section === 'academics'): ?>
<div class="stack">
    <?php foreach (['year' => 'Academic year', 'term' => 'Term', 'subject' => 'Subject'] as $key => $label): ?>
    <form class="card form-card" method="post">
        <h2>Add <?= h(mb_strtolower($label)) ?></h2>
        <input name="csrf_token" type="hidden" value="<?= h(csrfToken()) ?>"><input name="action" type="hidden" value="<?= h($key) ?>">
        <div class="fields">
            <label><span class="form-label">Name</span><input class="form-control" name="name" required maxlength="<?= $key === 'subject' ? 100 : 80 ?>"></label>
            <?php if ($key === 'term'): ?><label><span class="form-label">Academic year</span><select class="form-select" name="year_id" required>
                <option value="">Select year</option><?php foreach ($years as $year): ?><option value="<?= (int) $year['id'] ?>"><?= h($year['name']) ?></option><?php endforeach; ?>
            </select></label><?php endif; ?>
            <?php if ($key !== 'subject'): ?>
                <label><span class="form-label">Starts on</span><input class="form-control" type="date" name="starts_on" required></label>
                <label><span class="form-label">Ends on</span><input class="form-control" type="date" name="ends_on" required></label>
            <?php endif; ?>
        </div>
        <div class="form-actions"><button class="btn btn-primary">Add <?= h(mb_strtolower($label)) ?></button></div>
    </form>
    <?php endforeach; ?>
    <?php if ($classes && $subjects): ?><form class="card form-card" method="post">
        <h2>Assign a subject to a class</h2>
        <input name="csrf_token" type="hidden" value="<?= h(csrfToken()) ?>"><input name="action" type="hidden" value="assign_subject">
        <div class="fields fields-two">
            <label><span class="form-label">Class and term</span><select class="form-select" name="class_id" required><option value="">Select class</option>
                <?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>"><?= h($class['name'] . ' · ' . $class['term_name']) ?></option><?php endforeach; ?>
            </select></label>
            <label><span class="form-label">Subject</span><select class="form-select" name="subject_id" required><option value="">Select subject</option>
                <?php foreach ($subjects as $subject): ?><option value="<?= (int) $subject['id'] ?>"><?= h($subject['name']) ?></option><?php endforeach; ?>
            </select></label>
        </div>
        <div class="form-actions"><button class="btn btn-primary">Assign subject</button></div>
    </form><?php endif; ?>
    <div class="panels">
        <section class="card">
            <div class="panel-heading"><h2>Academic calendar</h2><a href="classes.php">Manage classes</a></div>
            <div class="table-responsive"><table class="table"><thead><tr><th scope="col">Year / term</th><th scope="col">Dates</th></tr></thead><tbody>
            <?php foreach ($years as $year): ?><tr><td class="person-name"><?= h($year['name']) ?></td><td><?= h($year['starts_on'] . ' to ' . $year['ends_on']) ?></td></tr>
                <?php foreach ($terms as $term): if ((int) $term['year_id'] !== (int) $year['id']) { continue; } ?><tr><td>↳ <?= h($term['name']) ?></td><td><?= h($term['starts_on'] . ' to ' . $term['ends_on']) ?></td></tr><?php endforeach; ?>
            <?php endforeach; ?>
            </tbody></table></div>
        </section>
        <section class="card"><div class="panel-heading"><h2>Subjects</h2></div>
            <?php if (!$subjects): ?><p class="empty">No subjects have been added.</p><?php else: ?><ul class="school-list"><?php foreach ($subjects as $subject): ?><li><?= h($subject['name']) ?></li><?php endforeach; ?></ul><?php endif; ?>
            <?php if ($classSubjects): ?><div class="panel-heading"><h2>Class subjects</h2></div>
                <ul class="school-list"><?php foreach ($classSubjects as $assignment): ?><li><?= h($assignment['subject_name']) ?>
                    <span class="cell-detail"><?= h($assignment['class_name'] . ' · ' . $assignment['term_name']) ?></span></li><?php endforeach; ?></ul>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php else: ?>
<section class="card">
    <div class="panel-heading"><h2>Recent changes</h2></div>
    <form class="filter-bar" method="get">
        <input type="hidden" name="section" value="audit">
        <label><span class="form-label">Record type</span><select class="form-select" name="entity">
            <?php foreach (['' => 'All records', 'grade' => 'Quick grades', 'gradebook' => 'Gradebook reviews', 'assessment' => 'Assessments', 'assessment mark' => 'Assessment marks', 'attendance' => 'Attendance', 'account' => 'Accounts', 'teacher' => 'Teacher assignments', 'enrollment' => 'Enrollments'] as $key => $label): ?>
                <option value="<?= h($key) ?>"<?= $entity === $key ? ' selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
        </select></label><div><button class="btn btn-secondary">Filter</button></div>
    </form>
    <?php if (!$audit): ?><p class="empty">No changes match this filter.</p><?php else: ?>
    <div class="table-responsive"><table class="table"><thead><tr><th scope="col">When</th><th scope="col">Who</th><th scope="col">Change</th><th scope="col">Details</th></tr></thead><tbody>
    <?php foreach ($audit as $entry): ?>
        <tr><td class="nowrap"><?= h($entry['recorded_at']) ?></td><td><?= h($entry['actor_email'] ?? 'Deleted account') ?></td>
            <td><?= h(ucfirst($entry['action']) . ' ' . $entry['entity']) ?><span class="cell-detail"><?= h($entry['class_name'] ?? '') ?></span></td>
            <td><?php foreach (json_decode($entry['details'], true) ?: [] as $key => $value): ?><span class="cell-detail"><?= h(ucfirst(str_replace('_', ' ', $key))) ?> · <?= h($value) ?></span><?php endforeach; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</section>
<?php endif; ?>
<?php require 'includes/layout_footer.php'; ?>
