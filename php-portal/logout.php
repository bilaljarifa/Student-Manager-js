<?php
require_once 'includes/portal.php';
startPortalSession();
requirePostToken();
$_SESSION = [];
session_destroy();
redirectTo('login.php');
