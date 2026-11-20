<?php
require_once 'includes/portal.php';
requirePortalRole('admin');
requirePostToken();

require_once 'config/Database.php';
require_once 'models/Student.php';

$id = positiveId(INPUT_POST, 'id');
$deleted = $id && (new Student((new Database())->connect()))->delete($id);
if ($deleted) {
    portalNotice('Student deleted.');
} else {
    portalNotice('Student could not be deleted. Check for enrollment, grades, or attendance history.', true);
}
header('Location: index.php', true, 303);
exit;
