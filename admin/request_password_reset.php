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

// Rate limit
$rateKey = 'admin_pwreset:' . $ip . ':' . strtolower($usernameOrEmail);
$maxAttempts = 5;
$windowSeconds = 900;
if (app_rate_limit_is_blocked($rateKey, $maxAttempts, $windowSeconds)) {
    header('Location: forgot_password.php?sent=1');
    exit();
}
app_rate_limit_increment($rateKey, $windowSeconds);

// Find admin (no get_result)
$isEmail = strpos($usernameOrEmail, '@') !== false;
if ($isEmail) {
    $stmt = $conn->prepare('SELECT id, username, email FROM utenti_admin WHERE email = ? LIMIT 1');
} else {
    $stmt = $conn->prepare('SELECT id, username, email FROM utenti_admin WHERE username = ? LIMIT 1');
}

$found = false;
$adminId = null;
$adminUsername = null;
$adminEmail = null;

if ($stmt) {
    $stmt->bind_param('s', $usernameOrEmail);
    $stmt->execute();
    $stmt->bind_result($adminId, $adminUsername, $adminEmail);
    $found = (bool)$stmt->fetch();
    $stmt->close();
}

if ($found && !empty($adminEmail)) {
    $adminId = (int)$adminId;
    $adminUsername = (string)$adminUsername;
    $adminEmail = (string)$adminEmail;

    // Invalidate previous tokens
    $stmt = $conn->prepare('UPDATE admin_password_resets SET used_at = NOW() WHERE admin_id = ? AND used_at IS NULL');
    if ($stmt) {
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $stmt->close();
    }

    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);

    // Insert token
    $stmt = $conn->prepare(
        'INSERT INTO admin_password_resets (admin_id, token_hash, created_at, expires_at, used_at, request_ip)
         VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE), NULL, ?)'
    );

    if ($stmt) {
        $stmt->bind_param('iss', $adminId, $token_hash, $ip);
        $stmt->execute();
        $stmt->close();

        $resetUrl = 'https://mokiclub.infinityfreeapp.com/admin/reset_password.php?token=' . urlencode($token);

        $subject = 'Reset password area admin Moki Club';
        $textBody =
            "Ciao " . $adminUsername . ",\n\n" .
            "Per reimpostare la password, apri questo link (valido 30 minuti):\n" .
            $resetUrl . "\n\n" .
            "Se non hai richiesto tu questa operazione, ignora questa email.\n";

        $htmlBody = nl2br(htmlspecialchars($textBody, ENT_QUOTES, 'UTF-8'));

        // This should also write into email_log now
        inviaEmail('admin_password_reset', $adminEmail, $subject, $htmlBody);
    }
}

header('Location: forgot_password.php?sent=1');
exit();