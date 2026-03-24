<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/mailer.php';

app_start_secure_session();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: forgot_password.php');
    exit();
}

app_require_csrf();

$usernameOrEmail = trim((string)($_POST['usernameOrEmail'] ?? ''));
if ($usernameOrEmail === '') {
    header('Location: forgot_password.php?sent=1');
    exit();
}

$ip = app_request_ip();
$rateKey = 'admin_pwreset:' . $ip . ':' . strtolower($usernameOrEmail);
$maxAttempts = 5;
$windowSeconds = 900; // 15 min

if (app_rate_limit_is_blocked($rateKey, $maxAttempts, $windowSeconds)) {
    header('Location: forgot_password.php?sent=1');
    exit();
}

// Consuma 1 tentativo subito (anti abuso anche se l'utente non esiste)
app_rate_limit_increment($rateKey, $windowSeconds);

$isEmail = strpos($usernameOrEmail, '@') !== false;

if ($isEmail) {
    $stmt = $conn->prepare('SELECT id, username, email FROM utenti_admin WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $usernameOrEmail);
} else {
    $stmt = $conn->prepare('SELECT id, username, email FROM utenti_admin WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $usernameOrEmail);
}

$stmt->execute();
$result = $stmt->get_result();
$admin = $result ? $result->fetch_assoc() : null;

if ($admin && !empty($admin['email'])) {
    // Invalida token precedenti non usati
    $stmt = $conn->prepare('UPDATE admin_password_resets SET used_at = NOW() WHERE admin_id = ? AND used_at IS NULL');
    $stmt->bind_param('i', $admin['id']);
    $stmt->execute();

    // Genera token e salva hash
    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);

    // Insert usando tempo DB (evita problemi timezone)
    $stmt = $conn->prepare(
        'INSERT INTO admin_password_resets (admin_id, token_hash, created_at, expires_at, used_at, request_ip)
         VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE), NULL, ?)'
    );
    $stmt->bind_param('iss', $admin['id'], $token_hash, $ip);
    $stmt->execute();

    $resetUrl = 'https://mokiclub.infinityfreeapp.com/admin/reset_password.php?token=' . urlencode($token);

    $subject = 'Reset password area admin Moki Club';
    $body =
        "Ciao " . $admin['username'] . ",\n\n" .
        "Abbiamo ricevuto una richiesta di reset password per il tuo account admin.\n" .
        "Se non hai richiesto tu questa operazione, puoi ignorare questa email.\n\n" .
        "Per reimpostare la password, apri questo link (valido 30 minuti):\n" .
        $resetUrl . "\n\n" .
        "Moki SUP Club\n";

    // Invia testo semplice (più compatibile)
    inviaEmail($admin['email'], $subject, $body);
}

// Risposta sempre generica
header('Location: forgot_password.php?sent=1');
exit();