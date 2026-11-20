<?php
require_once 'includes/portal.php';
requirePortalRole('teacher');
requirePostToken();

require_once 'config/Database.php';
require_once 'models/Student.php';

$id = positiveId(INPUT_POST, 'id');
$deleted = $id && (new Student((new Database())->connect()))->delete($id);
if ($deleted) {
    portalNotice('Student deleted.');
} else {
    portalNotice('Student could not be deleted. Check for recorded grades or attendance.', true);
}
header('Location: index.php', true, 303);
exit;
