<?php
require_once 'includes/portal.php';
requirePortalRole('teacher', 'admin', 'student');

$db = portalDb();
$id = positiveId(INPUT_GET, 'id');
$parameters = [$id];
if ($_SESSION['role'] === 'student') {
    $scope = 'EXISTS (SELECT 1 FROM students s JOIN enrollments e ON e.student_id = s.id
        WHERE s.user_id = ? AND e.class_id = c.class_id AND e.starts_on <= ? AND (e.ends_on IS NULL OR e.ends_on > ?))';
    $today = date('Y-m-d');
    array_push($parameters, currentUserId(), $today, $today);
} else {
    $scope = classScope('c.class_id');
}
$file = $id ? portalRow($db, 'SELECT f.file_name, f.contents FROM course_files f JOIN courses c ON c.id = f.course_id
    WHERE f.course_id = ? AND ' . $scope, $parameters) : false;
if (!$file) {
    http_response_code(404);
    exit('Course PDF not found.');
}
header('Content-Type: application/pdf');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . strlen($file['contents']));
header('Content-Disposition: attachment; filename="course.pdf"; filename*=UTF-8\'\'' . rawurlencode($file['file_name']));
echo $file['contents'];
