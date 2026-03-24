<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . '/../config/config.php';

app_start_secure_session();

if (isset($_SESSION["admin"])) {
    header("Location: dashboard.php");
    exit();
}

$sent = isset($_GET['sent']) && $_GET['sent'] === '1';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password dimenticata - Admin</title>
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
            <h2>Password dimenticata</h2>
            <p>Inserisci il tuo username <b>oppure</b> la tua email</p>
        </div>

        <?php if ($sent): ?>
            <div class="success-container">
                Se l'account esiste, riceverai un'email con le istruzioni per reimpostare la password.
            </div>
        <?php endif; ?>

        <form class="login-form" method="POST" action="request_password_reset.php">
            <?= app_csrf_input() ?>
            <div class="form-group">
                <div class="input-group neu-input">
                    <input type="text" id="usernameOrEmail" name="usernameOrEmail" required placeholder=" " autocomplete="username email">
                    <label for="usernameOrEmail">Username o Email</label>
                </div>
            </div>

            <button type="submit" class="neu-button login-btn" style="margin-top: 10px;">
                <span class="btn-text">Invia richiesta</span>
            </button>
        </form>

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