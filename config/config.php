<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once dirname(__DIR__) . '/includes/security_utils.php';

date_default_timezone_set('Europe/Rome');

$appEnv = getenv('APP_ENV') ?: 'production';
$isDebugEnv = ($appEnv === 'local' || $appEnv === 'development');

error_reporting(E_ALL);
ini_set('display_errors', $isDebugEnv ? '1' : '0');
ini_set('log_errors', '1');

app_start_secure_session();

$isLocalHost = (($_SERVER['SERVER_NAME'] ?? '') === 'localhost');
if ($isLocalHost) {
    // CONFIG LOCALE
    $host = getenv('DB_HOST') ?: "localhost";
    $user = getenv('DB_USER') ?: "root";
    $password = getenv('DB_PASS') ?: "";
    $database = getenv('DB_NAME') ?: "moki";
} else {
    // CONFIG ONLINE (InfinityFree)
    $host = getenv('DB_HOST') ?: "sql306.infinityfree.com";
    $user = getenv('DB_USER') ?: "if0_41207556";
    $password = getenv('DB_PASS') ?: "Moki2026";
    $database = getenv('DB_NAME') ?: "if0_41207556_moki";
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    exit('Servizio temporaneamente non disponibile.');
}

$conn->set_charset('utf8mb4');

try {
    $conn->query("SET time_zone = '+00:00'");
} catch (Throwable $e) {
}
?>