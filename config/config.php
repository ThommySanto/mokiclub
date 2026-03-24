<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once dirname(__DIR__) . '/includes/security_utils.php';

date_default_timezone_set('Europe/Rome');


// Caricamento segreti: prima getenv, poi config/secrets.php se esiste
$secrets = [];
if (file_exists(__DIR__ . '/secrets.php')) {
    $secrets = include __DIR__ . '/secrets.php';
    if (!is_array($secrets)) $secrets = [];
}

function app_secret($key, $default = null) {
    // getenv ha priorità, poi secrets.php, poi default
    $env = getenv($key);
    if ($env !== false && $env !== '') return $env;
    global $secrets;
    if (isset($secrets[$key]) && $secrets[$key] !== '') return $secrets[$key];
    return $default;
}

$appEnv = app_secret('APP_ENV', 'production');
$isDebugEnv = ($appEnv === 'local' || $appEnv === 'development');

error_reporting(E_ALL);
ini_set('display_errors', $isDebugEnv ? '1' : '0');
ini_set('log_errors', '1');

app_start_secure_session();

// DB config: in localhost possiamo usare default; online richiediamo secrets/env (niente credenziali hardcoded)
$isLocal = (($_SERVER['SERVER_NAME'] ?? '') === 'localhost');

$host = app_secret('DB_HOST', $isLocal ? 'localhost' : null);
$user = app_secret('DB_USER', $isLocal ? 'root' : null);
$password = app_secret('DB_PASS', $isLocal ? '' : null);
$database = app_secret('DB_NAME', $isLocal ? 'clubmoki' : null);

// Se online e manca config DB, fallisci in modo controllato
if (!$isLocal && (!$host || !$user || !$password || !$database)) {
    error_log('Database config missing: set DB_HOST/DB_USER/DB_PASS/DB_NAME via env or config/secrets.php');
    http_response_code(500);
    exit('Servizio temporaneamente non disponibile.');
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