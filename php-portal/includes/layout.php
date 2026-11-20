<?php
require_once __DIR__ . '/portal.php';

$currentPage = basename($_SERVER['SCRIPT_NAME']);
$isTeacher = $_SESSION['role'] === 'teacher';
$home = $isTeacher ? 'teacher_dashboard.php' : 'student_dashboard.php';
$navigation = $isTeacher
    ? [
        'teacher_dashboard.php' => 'Overview',
        'index.php' => 'Students',
        'classes.php' => 'Classes',
        'attendance.php' => 'Attendance',
        'exams.php' => 'Exams',
        'courses.php' => 'Course materials',
        'grades.php' => 'Grades',
    ]
    : [
        'student_dashboard.php' => 'Overview',
        'student_attendance.php' => 'Attendance',
        'student_dashboard.php#exam-schedule' => 'Exams',
        'student_dashboard.php#grades' => 'Grades',
        'student_dashboard.php#materials' => 'Course materials',
    ];
$activePage = in_array($currentPage, ['create.php', 'edit.php'], true) ? 'index.php' : $currentPage;
$pageTitle = $pageTitle ?? $navigation[$activePage] ?? 'School Portal';
$notice = $_SESSION['portal_notice'] ?? null;
unset($_SESSION['portal_notice']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?> | School Portal</title>
    <link rel="stylesheet" href="assets/portal.css?v=2">
</head>
<body class="portal">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <aside class="sidebar">
        <a class="brand" href="<?= $home ?>"><span class="brand-mark" aria-hidden="true">S</span>School Portal</a>
        <nav class="navigation" aria-label="Main navigation">
            <?php foreach ($navigation as $url => $label): ?>
                <a class="nav-link" href="<?= $url ?>"<?= $activePage === $url ? ' aria-current="page"' : '' ?>>
                    <?= h($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <a href="logout.php" class="sign-out">Sign out <span aria-hidden="true">↗</span></a>
    </aside>
    <div class="workspace">
        <header class="topbar">
            <span class="workspace-label"><?= $isTeacher ? 'Teacher workspace' : 'Student workspace' ?></span>
            <time datetime="<?= date('Y-m-d') ?>"><?= date('D, M j, Y') ?></time>
        </header>
        <main id="main-content" class="content" tabindex="-1">
            <?php if ($notice): ?>
                <p class="notice <?= $notice['error'] ? 'notice-error' : 'notice-success' ?>"
                   role="<?= $notice['error'] ? 'alert' : 'status' ?>"><?= h($notice['message']) ?></p>
            <?php endif; ?>
