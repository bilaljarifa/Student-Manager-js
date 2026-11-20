<?php
// PHP development server entry point. Apache uses the equivalent rules in .htaccess.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
$pages = [
    'index', 'login', 'logout', 'password', 'teacher_dashboard', 'student_dashboard',
    'admin', 'classes', 'create', 'edit', 'delete', 'attendance', 'student_attendance',
    'exams', 'grades', 'gradebook', 'courses', 'download', 'reports', 'class_updates',
];
if ($path === '/' || (preg_match('~^/([a-z_]+)\.php$~D', $path, $match) && in_array($match[1], $pages, true))
    || preg_match('~^/assets/[a-zA-Z0-9_-]+\.(?:css|js|jpg|png)$~D', $path)) {
    return false;
}
http_response_code(404);
echo 'Page not found.';
