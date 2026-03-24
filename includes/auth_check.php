<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/security_utils.php';

app_start_secure_session();

$sessionTimeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > $sessionTimeout) {
    $_SESSION = [];
    session_destroy();
    header("Location: login.php");
    exit();
}

$_SESSION['last_activity'] = time();

if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit();
}
?>