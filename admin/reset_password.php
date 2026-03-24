<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . '/../config/config.php';

app_start_secure_session();

$token = trim((string)($_GET['token'] ?? ''));
if ($token === '' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = trim((string)($_POST['token'] ?? ''));
}

$errors = [];
$success = false;
$validToken = false;
$adminId = null;
$token_hash = null;

// Validazione token
if ($token !== '') {
    $token_hash = hash('sha256', $token);

    $stmt = $conn->prepare(
        "SELECT admin_id
         FROM admin_password_resets
         WHERE token_hash = ?
           AND used_at IS NULL
           AND expires_at > NOW()
         ORDER BY id DESC
         LIMIT 1"
    );

    if ($stmt) {
        $stmt->bind_param('s', $token_hash);
        $stmt->execute();
        $stmt->bind_result($adminIdDb);
        if ($stmt->fetch()) {
            $validToken = true;
            $adminId = (int)$adminIdDb;
        }
        $stmt->close();
    }
}

// CSRF dedicato per reset password (indipendente dal resto del sito)
if (!isset($_SESSION['pwreset_csrf']) || !is_string($_SESSION['pwreset_csrf']) || $_SESSION['pwreset_csrf'] === '') {
    $_SESSION['pwreset_csrf'] = bin2hex(random_bytes(32));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $postedCsrf = (string)($_POST['pwreset_csrf'] ?? '');
    if (!hash_equals((string)$_SESSION['pwreset_csrf'], $postedCsrf)) {
        $errors[] = 'Richiesta non valida (CSRF).';
    } elseif (!$validToken) {
        $errors[] = 'Token invalido o scaduto. Richiedi di nuovo il reset password.';
    } else {
        $password = (string)($_POST['password'] ?? '');
        $password2 = (string)($_POST['password2'] ?? '');

        if (strlen($password) < 8) {
            $errors[] = 'La password deve avere almeno 8 caratteri.';
        }
        if ($password !== $password2) {
            $errors[] = 'Le password non coincidono.';
        }

        if (!$errors) {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE utenti_admin SET password = ? WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('si', $hash, $adminId);
                $stmt->execute();
                $stmt->close();
            }

            $stmt = $conn->prepare("UPDATE admin_password_resets SET used_at = NOW() WHERE token_hash = ? AND used_at IS NULL");
            if ($stmt) {
                $stmt->bind_param('s', $token_hash);
                $stmt->execute();
                $stmt->close();
            }

            // Invalida CSRF dedicato dopo uso
            unset($_SESSION['pwreset_csrf']);

            header('Location: login.php?reset=1');
            exit();
        }
    }
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Reset password admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;max-width:720px;margin:40px auto;padding:0 16px;}
    .box{border:1px solid #ddd;border-radius:10px;padding:18px;}
    .err{background:#fff3f3;border:1px solid #ffcccc;padding:12px;border-radius:8px;margin:12px 0;}
    input{width:100%;padding:10px;margin:6px 0 12px;border:1px solid #ccc;border-radius:8px;}
    button{padding:10px 14px;border:0;border-radius:8px;background:#111;color:#fff;cursor:pointer;}
    a{color:#111;}
  </style>
</head>
<body>
  <div class="box">
    <h2>Reimposta password</h2>

    <?php if (!$validToken): ?>
      <div class="err">Token invalido o scaduto. Richiedi di nuovo il reset password.</div>
      <p><a href="forgot_password.php">Torna a “Password dimenticata”</a></p>
    <?php else: ?>

      <?php if ($errors): ?>
        <div class="err">
          <ul>
            <?php foreach ($errors as $e): ?>
              <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" action="reset_password.php">
        <input type="hidden" name="pwreset_csrf" value="<?php echo htmlspecialchars((string)$_SESSION['pwreset_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

        <label>Nuova password</label>
        <input type="password" name="password" required>

        <label>Ripeti password</label>
        <input type="password" name="password2" required>

        <button type="submit">Aggiorna password</button>
      </form>

    <?php endif; ?>
  </div>
</body>
</html>