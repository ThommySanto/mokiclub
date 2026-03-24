<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . '/../config/config.php';

app_start_secure_session();

$token = (string)($_GET['token'] ?? ($_POST['token'] ?? ''));
$errore = '';
$tokenValid = false;
$reset_id = null;
$admin_id = null;

if ($token === '' || strlen($token) < 64) {
    $errore = "Link non valido o scaduto.";
} else {
    $token_hash = hash('sha256', $token);

    $stmt = $conn->prepare(
        'SELECT r.id, r.admin_id, r.expires_at, r.used_at
         FROM admin_password_resets r
         WHERE r.token_hash = ?
         LIMIT 1'
    );
    $stmt->bind_param('s', $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;

    if (!$row) {
        $errore = "Link non valido o scaduto.";
    } elseif (!empty($row['used_at'])) {
        $errore = "Link non valido o scaduto.";
    } elseif (strtotime((string)$row['expires_at']) < time()) {
        $errore = "Link non valido o scaduto.";
    } else {
        $tokenValid = true;
        $reset_id = (int)$row['id'];
        $admin_id = (int)$row['admin_id'];
    }
}

if ($tokenValid && (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST')) {
    app_require_csrf();

    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');

    if (strlen($password) < 10) {
        $errore = 'La password deve essere di almeno 10 caratteri.';
        $tokenValid = false;
    } elseif ($password !== $confirm) {
        $errore = 'Le password non coincidono.';
        $tokenValid = false;
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare('UPDATE utenti_admin SET password = ? WHERE id = ?');
        $stmt->bind_param('si', $hashed, $admin_id);
        $stmt->execute();

        $stmt = $conn->prepare('UPDATE admin_password_resets SET used_at = NOW() WHERE id = ?');
        $stmt->bind_param('i', $reset_id);
        $stmt->execute();

        // Logout "forte" (come admin/logout.php)
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'] ?? '',
                (bool)$params['secure'],
                (bool)$params['httponly']
            );
        }
        session_destroy();

        header('Location: login.php?reset=1');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Admin</title>
    <link rel="stylesheet" href="/assets/css/neumorphism.css">
    <link rel="icon" type="image/jpeg" href="/assets/img/moki.jpg">
</head>
<body class="login-body">
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="neu-icon">
                <div class="icon-inner" style="width: 60px; height: 60px;">
                    <img src="/assets/img/moki.jpg" alt="Moki Logo">
                </div>
            </div>
            <h2>Reset Password</h2>
            <p>Imposta una nuova password per il tuo account admin</p>
        </div>

        <?php if ($errore): ?>
            <div class="error-container">
                <?= htmlspecialchars($errore, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!$errore): ?>
            <form class="login-form" method="POST">
                <?= app_csrf_input() ?>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group">
                    <div class="input-group neu-input">
                        <input type="password" id="password" name="password" required minlength="10" placeholder=" ">
                        <label for="password">Nuova password (min 10 caratteri)</label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group neu-input">
                        <input type="password" id="confirm" name="confirm" required minlength="10" placeholder=" ">
                        <label for="confirm">Conferma password</label>
                    </div>
                </div>

                <button type="submit" class="neu-button login-btn" style="margin-top: 10px;">
                    <span class="btn-text">Imposta password</span>
                </button>
            </form>
        <?php endif; ?>

        <div class="divider">
            <div class="divider-line"></div>
            <span>Moki SUP Club</span>
            <div class="divider-line"></div>
        </div>

        <div class="signup-link">
            <p><a href="login.php">Torna al login</a></p>
        </div>
    </div>
</div>
</body>
</html>