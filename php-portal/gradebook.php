<?php
require_once 'includes/portal.php';
requirePortalRole('teacher', 'admin', 'student');
require_once 'models/GradebookModel.php';

$db = portalDb();
$model = new GradebookModel($db);
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($_SESSION['role'] === 'student') {
    if ($isPost) { forbid(); }
    $published = [];
    foreach ($model->publishedForUser(currentUserId()) as $row) {
        $published[$row['class_id']][] = $row;
    }
    $pageTitle = 'Published report cards';
    require 'includes/layout.php';
    ?>
    <header class="page-header">
        <div><h1>Published report cards</h1><p>Review your approved grades by class and term.</p></div>
        <?php if ($published): ?><button type="button" class="btn btn-secondary no-print" onclick="window.print()">Print report cards</button><?php endif; ?>
    </header>
    <div class="stack">
        <?php if (!$published): ?><p class="notice">No gradebook report cards have been published for your account yet.</p><?php endif; ?>
        <?php foreach ($published as $resultRows): $report = $resultRows[0]; ?>
            <section class="card">
                <div class="panel-heading"><div><h2><?= h($report['class_name']) ?></h2>
                    <p><?= h($report['year_name'] . ' · ' . $report['term_name']) ?></p></div>
                    <span class="tag">Published <?= h(date('M j, Y', strtotime($report['published_at']))) ?></span>
                </div>
                <?php $showLearner = false; require 'includes/gradebook_results.php'; ?>
            </section>
        <?php endforeach; ?>
    </div>
    <?php
    require 'includes/layout_footer.php';
    return;
}

$classes = staffClasses($db);
$source = $isPost ? INPUT_POST : INPUT_GET;
$classId = positiveId($source, 'class_id') ?? (!$isPost && $classes ? (int) $classes[0]['id'] : null);
$class = $classId ? requireClass($db, $classId) : null;
$markPage = filter_input($source, 'mark_page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]) ?: 1;
$assessmentId = positiveId($source, 'assessment_id');
$editId = positiveId(INPUT_GET, 'edit');
$path = 'gradebook.php?' . http_build_query(['class_id' => $classId]);
$error = '';
$input = [];
$action = '';
$revision = null;

if ($isPost) {
    requirePostToken();
    if (!$class) { forbid(); }
    $revision = filter_input(INPUT_POST, 'revision', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    $action = postText('action');
    if (in_array($action, ['publish', 'return'], true) && !isAdmin()) { forbid(); }
    $input = [
        'assessment_id' => $assessmentId, 'subject_id' => positiveId(INPUT_POST, 'subject_id'), 'mark_page' => $markPage,
        'title' => postText('title'), 'kind' => postText('kind'), 'due_on' => postText('due_on'),
        'max_points' => postText('max_points'), 'weight' => postText('weight'), 'review_note' => postText('review_note'),
        'statuses' => filter_input(INPUT_POST, 'status', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?: [],
        'points' => filter_input(INPUT_POST, 'points', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?: [],
    ];
    try {
        if (!is_int($revision)) { throw new DomainException('Reload this gradebook and try again.'); }
        $model->change($classId, $revision, $action, $input);
        $notices = [
            'save_assessment' => 'Assessment saved.', 'delete_assessment' => 'Assessment removed.', 'save_marks' => 'Marks saved.',
            'submit' => 'Gradebook submitted for review.', 'publish' => 'Report cards published.', 'return' => 'Gradebook returned to draft.',
        ];
        portalNotice($notices[$action]);
        redirectTo($action === 'save_marks' ? $path . '&assessment_id=' . $assessmentId . '&mark_page=' . $markPage . '#mark-editor' : $path);
    } catch (DomainException $exception) {
        $error = $exception->getMessage();
    }
}

$book = $classId ? $model->book($classId) : null;
$subjects = $classId ? classSubjects($db, $classId) : [];
$assessments = $classId ? $model->assessments($classId) : [];
$lookup = array_column($assessments, null, 'id');
$selected = $assessmentId ? ($lookup[$assessmentId] ?? null) : null;
$editing = $editId ? ($lookup[$editId] ?? null) : null;
$isDraft = ($book['state'] ?? '') === 'draft';
$markSheet = $selected && $isDraft ? $model->markSheet($selected) : [];
$totalPages = max(1, (int) ceil(count($markSheet) / GradebookModel::PAGE_SIZE));
$markPage = min($markPage, $totalPages);
$markRows = array_slice($markSheet, ($markPage - 1) * GradebookModel::PAGE_SIZE, GradebookModel::PAGE_SIZE);
$keepInput = $error && $revision === (int) ($book['revision'] ?? -1);
if ($keepInput && $action === 'save_assessment') {
    $editing = array_merge($input, ['id' => $assessmentId]);
}
$weights = [];
foreach ($assessments as $assessment) {
    $id = $assessment['subject_id'];
    $weights[$id] = ($weights[$id] ?? 0) + (float) $assessment['weight'];
}
$states = ['draft' => 'Draft', 'in_review' => 'Awaiting review', 'published' => 'Published'];
$pending = isAdmin() ? portalRows($db, "SELECT c.id, c.name, t.name AS term_name FROM gradebooks b
    JOIN classes c ON c.id = b.class_id JOIN terms t ON c.term_id = t.id WHERE b.state = 'in_review' ORDER BY b.submitted_at, c.name") : [];
$pageTitle = 'Gradebook';
require 'includes/layout.php';
?>
<header class="page-header"><div><h1>Gradebook</h1><p>Set assessment weights, enter marks, and prepare report cards for review.</p></div></header>
<?php if ($error): ?><p class="notice notice-error" role="alert"><?= h($error) ?></p><?php endif; ?>

<?php if ($pending): ?>
    <section class="card form-card mb-4">
        <h2>Awaiting review</h2>
        <ul class="school-list"><?php foreach ($pending as $waiting): ?><li>
            <a href="gradebook.php?class_id=<?= (int) $waiting['id'] ?>"><?= h($waiting['name'] . ' · ' . $waiting['term_name']) ?></a>
        </li><?php endforeach; ?></ul>
    </section>
<?php endif; ?>
<?php if (!$class): ?>
    <p class="notice">No classes are available for this account.</p>
<?php else: ?>
<form method="get" class="card form-card mb-4">
    <div class="fields fields-two">
        <label><span class="form-label">Class and term</span><select class="form-select" name="class_id" required>
            <?php foreach ($classes as $option): ?><option value="<?= (int) $option['id'] ?>"<?= $classId === (int) $option['id'] ? ' selected' : '' ?>>
                <?= h($option['name'] . ' · ' . $option['term_name']) ?>
            </option><?php endforeach; ?>
        </select></label>
        <div><button class="btn btn-secondary">View gradebook</button></div>
    </div>
</form>

<?php if (!empty($book['review_note'])): ?><p class="notice"><strong>Review note</strong><br><?= nl2br(h($book['review_note'])) ?></p><?php endif; ?>
<div class="stack">
<section class="card">
    <div class="panel-heading"><div><h2><?= h($class['name']) ?></h2><p>Assessments</p></div>
        <span class="tag"><?= h($states[$book['state']]) ?></span></div>
    <?php if ($subjects): ?><div class="gradebook-weights">
        <?php foreach ($subjects as $subject): ?><span><?= h($subject['name']) ?> <strong><?= number_format($weights[$subject['id']] ?? 0, 2) ?>%</strong></span><?php endforeach; ?>
    </div><?php endif; ?>
    <?php if (!$assessments): ?><p class="empty">No assessments have been added.</p><?php else: ?>
    <div class="table-responsive"><table class="table">
        <thead><tr><th scope="col">Assessment</th><th scope="col">Due date</th><th scope="col">Max points</th><th scope="col">Weight</th><?php if ($isDraft): ?><th scope="col">Actions</th><?php endif; ?></tr></thead>
        <tbody><?php foreach ($assessments as $item): ?><tr>
            <td class="person-name"><?= h($item['title']) ?><span class="cell-detail"><?= h($item['subject'] . ' · ' . GradebookModel::KINDS[$item['kind']]) ?></span></td>
            <td class="nowrap"><?= h($item['due_on']) ?></td><td><?= h($item['max_points']) ?></td><td><?= h($item['weight']) ?>%</td>
            <?php if ($isDraft): ?><td><div class="row-actions">
                <a class="btn btn-secondary btn-sm" href="<?= h($path . '&assessment_id=' . $item['id'] . '#mark-editor') ?>">Enter marks</a>
                <a class="btn btn-secondary btn-sm" href="<?= h($path . '&edit=' . $item['id'] . '#assessment-editor') ?>">Edit</a>
                <form method="post" action="<?= h($path) ?>" onsubmit="return confirm('Delete this assessment and its draft marks?');">
                    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="class_id" value="<?= $classId ?>">
                    <input type="hidden" name="revision" value="<?= (int) $book['revision'] ?>"><input type="hidden" name="action" value="delete_assessment">
                    <button class="btn btn-danger btn-sm" name="assessment_id" value="<?= (int) $item['id'] ?>">Delete</button>
                </form>
            </div></td><?php endif; ?>
        </tr><?php endforeach; ?></tbody>
    </table></div><?php endif; ?>
</section>

<?php if ($isDraft && $selected): ?>
<section class="card" id="mark-editor">
    <div class="panel-heading"><div><h2>Marks for <?= h($selected['title']) ?></h2>
        <p>Missing counts as zero. Excused work is excluded from the final grade.</p></div></div>
    <?php if (!$markRows): ?><p class="empty">No students were enrolled on this assessment’s due date.</p><?php else: ?>
    <form method="post" action="<?= h($path . '&assessment_id=' . $assessmentId . '&mark_page=' . $markPage . '#mark-editor') ?>">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="class_id" value="<?= $classId ?>">
        <input type="hidden" name="revision" value="<?= (int) $book['revision'] ?>"><input type="hidden" name="action" value="save_marks">
        <input type="hidden" name="assessment_id" value="<?= (int) $selected['id'] ?>"><input type="hidden" name="mark_page" value="<?= $markPage ?>">
        <div class="table-responsive"><table class="table">
            <thead><tr><th scope="col">Student</th><th scope="col">Status</th><th scope="col">Points out of <?= h($selected['max_points']) ?></th></tr></thead>
            <tbody><?php foreach ($markRows as $student):
                $id = (int) $student['id'];
                $name = trim($student['name'] . ' ' . $student['last_name']);
                $status = $keepInput ? ($input['statuses'][$id] ?? $student['status']) : $student['status'];
                $points = $keepInput ? ($input['points'][$id] ?? $student['points']) : $student['points'];
            ?><tr>
                <td class="person-name"><?= h($name) ?></td>
                <td><select name="status[<?= $id ?>]" class="form-select" aria-label="<?= h('Status for ' . $name) ?>">
                    <?php foreach (GradebookModel::MARKS as $key => $label): ?><option value="<?= h($key) ?>"<?= $key === $status ? ' selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
                </select></td>
                <td><input type="number" class="form-control" name="points[<?= $id ?>]" aria-label="<?= h('Points for ' . $name) ?>"
                    min="0" max="<?= h($selected['max_points']) ?>" step="0.01" value="<?= h(is_scalar($points) ? $points : '') ?>"></td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
        <div class="attendance-actions"><button class="btn btn-primary">Save marks</button></div>
    </form>
    <?php if ($totalPages > 1): ?><nav class="gradebook-pager" aria-label="Mark sheet pages">
        <?php if ($markPage > 1): ?><a href="<?= h($path . '&assessment_id=' . $assessmentId . '&mark_page=' . ($markPage - 1) . '#mark-editor') ?>">Previous</a><?php endif; ?>
        <span>Page <?= $markPage ?> of <?= $totalPages ?></span>
        <?php if ($markPage < $totalPages): ?><a href="<?= h($path . '&assessment_id=' . $assessmentId . '&mark_page=' . ($markPage + 1) . '#mark-editor') ?>">Next</a><?php endif; ?>
    </nav><?php endif; ?>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($isDraft): ?>
<form class="card form-card" method="post" id="assessment-editor" action="<?= h($path . '#assessment-editor') ?>">
    <h2><?= !empty($editing['id']) ? 'Edit assessment' : 'Add an assessment' ?></h2>
    <?php if (!$subjects): ?><p>An administrator needs to assign subjects to this class first.</p><?php else: ?>
    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="class_id" value="<?= $classId ?>">
    <input type="hidden" name="revision" value="<?= (int) $book['revision'] ?>"><input type="hidden" name="action" value="save_assessment">
    <input type="hidden" name="assessment_id" value="<?= (int) ($editing['id'] ?? 0) ?>">
    <div class="fields">
        <label><span class="form-label">Title</span><input class="form-control" name="title" maxlength="120" value="<?= h($editing['title'] ?? '') ?>" required></label>
        <label><span class="form-label">Subject</span><select class="form-select" name="subject_id" required><option value="">Select subject</option>
            <?php foreach ($subjects as $subject): ?><option value="<?= (int) $subject['id'] ?>"<?= (int) ($editing['subject_id'] ?? 0) === (int) $subject['id'] ? ' selected' : '' ?>><?= h($subject['name']) ?></option><?php endforeach; ?>
        </select></label>
        <label><span class="form-label">Type</span><select class="form-select" name="kind">
            <?php foreach (GradebookModel::KINDS as $key => $label): ?><option value="<?= h($key) ?>"<?= ($editing['kind'] ?? '') === $key ? ' selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
        </select></label>
        <label><span class="form-label">Due date</span><input class="form-control" type="date" name="due_on" value="<?= h($editing['due_on'] ?? date('Y-m-d')) ?>" required></label>
        <label><span class="form-label">Maximum points</span><input class="form-control" type="number" min="0.01" max="999999.99" step="0.01" name="max_points" value="<?= h($editing['max_points'] ?? '') ?>" required></label>
        <label><span class="form-label">Weight (%)</span><input class="form-control" type="number" min="0.01" max="100" step="0.01" name="weight" value="<?= h($editing['weight'] ?? '') ?>" required></label>
    </div>
    <div class="form-actions"><button class="btn btn-primary">Save assessment</button><?php if ($editing): ?><a href="<?= h($path) ?>">Cancel</a><?php endif; ?></div>
    <?php endif; ?>
</form>
<?php if ($assessments): ?>
<form class="card form-card" method="post" action="<?= h($path) ?>">
    <h2>Submit for review</h2><p>Each subject’s weights must total 100%, and every eligible student needs a mark or status.</p>
    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="class_id" value="<?= $classId ?>">
    <input type="hidden" name="revision" value="<?= (int) $book['revision'] ?>">
    <div class="form-actions"><button class="btn btn-primary" name="action" value="submit">Submit gradebook</button></div>
</form>
<?php endif; ?>
<?php else: ?>
<section class="card">
    <div class="panel-heading"><h2><?= $book['state'] === 'published' ? 'Published results' : 'Results for review' ?></h2></div>
    <?php $resultRows = $model->results($classId); $showLearner = true; require 'includes/gradebook_results.php'; ?>
</section>
<?php if ($book['state'] === 'in_review' && isAdmin()): ?>
<form class="card form-card" method="post" action="<?= h($path) ?>">
    <h2>Review decision</h2>
    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="class_id" value="<?= $classId ?>">
    <input type="hidden" name="revision" value="<?= (int) $book['revision'] ?>">
    <label><span class="form-label">Note when returning to draft</span><textarea class="form-control" name="review_note" maxlength="500" rows="3"><?= h($input['review_note'] ?? '') ?></textarea></label>
    <div class="form-actions">
        <button class="btn btn-primary" name="action" value="publish" onclick="return confirm('Publish these report cards to students?');">Publish report cards</button>
        <button class="btn btn-secondary" name="action" value="return">Return to draft</button>
    </div>
</form>
<?php endif; ?>
<?php endif; ?>
</div>
<?php endif; ?>
<?php require 'includes/layout_footer.php'; ?>
