<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . "/../config/config.php";

app_start_secure_session();

/* =========================
   Se già loggato → dashboard
========================= */
if (isset($_SESSION["admin"])) {
    header("Location: dashboard.php");
    exit();
}

/* =========================
   CREA TABELLA SE NON ESISTE
========================= */
$create_table_sql = "CREATE TABLE IF NOT EXISTS utenti_admin (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$conn->query($create_table_sql);

/* =========================
    CREA ADMIN ROOT (ID = 0)
========================= */
$default_username = getenv('DEFAULT_ADMIN_USER') ?: "admin";
$default_password = getenv('DEFAULT_ADMIN_PASS') ?: null;

@$conn->query("SET SESSION sql_mode = CONCAT_WS(',', @@sql_mode, 'NO_AUTO_VALUE_ON_ZERO')");

$stmt = $conn->prepare("SELECT id FROM utenti_admin WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $default_username);
$stmt->execute();
$result = $stmt->get_result();
$adminUser = $result->fetch_assoc();

$stmt = $conn->prepare("SELECT id FROM utenti_admin WHERE id = 0 LIMIT 1");
$stmt->execute();
$rootResult = $stmt->get_result();
$rootUser = $rootResult->fetch_assoc();

if ($adminUser && (int) $adminUser['id'] !== 0 && !$rootUser) {
    $stmt = $conn->prepare("UPDATE utenti_admin SET id = 0 WHERE username = ?");
    $stmt->bind_param("s", $default_username);
    $stmt->execute();
}

$stmt = $conn->prepare("SELECT id, username FROM utenti_admin WHERE id = 0 LIMIT 1");
$stmt->execute();
$rootResult = $stmt->get_result();
$rootUser = $rootResult->fetch_assoc();

if (!$rootUser && !empty($default_password)) {
    $hashed_default_password = password_hash($default_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO utenti_admin (id, username, password) VALUES (0, ?, ?)");
    $stmt->bind_param("ss", $default_username, $hashed_default_password);
    $stmt->execute();
} elseif ($rootUser && $rootUser['username'] !== $default_username) {
    $stmt = $conn->prepare("UPDATE utenti_admin SET username = ? WHERE id = 0");
    $stmt->bind_param("s", $default_username);
    $stmt->execute();
}

$errore = "";
$successo = "";
$rateKeyBase = 'admin_login:' . app_request_ip();
$maxAttempts = 7;
$windowSeconds = 900;

/* =========================
   LOGIN / RESET
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    app_require_csrf();

    if (isset($_POST['login'])) {

        $username = trim($_POST["username"]);
        $password = $_POST["password"];
        $rateKey = $rateKeyBase . ':' . strtolower($username);

        if (app_rate_limit_is_blocked($rateKey, $maxAttempts, $windowSeconds)) {
            $retryIn = app_rate_limit_retry_after($rateKey, $windowSeconds);
            $errore = "Troppi tentativi falliti. Riprova tra " . $retryIn . " secondi.";
        } else {

            $stmt = $conn->prepare("SELECT username, password FROM utenti_admin WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user["password"])) {
                    app_rate_limit_reset($rateKey);
                    session_regenerate_id(true);
                    $_SESSION["admin"] = $user["username"];
                    $_SESSION['last_activity'] = time();
                    header("Location: dashboard.php");
                    exit();
                } else {
                    app_rate_limit_increment($rateKey, $windowSeconds);
                    $errore = "Credenziali non valide";
                }
            } else {
                app_rate_limit_increment($rateKey, $windowSeconds);
                $errore = "Credenziali non valide";
            }
        }
    } elseif (isset($_POST['reset'])) {

        $username = trim($_POST["username"]);

        if ($username === '' || $username === 'admin') {
            $errore = "Per sicurezza, il reset del super-admin non e' consentito da questa pagina.";
        } else {
            try {
                $new_password = substr(bin2hex(random_bytes(8)), 0, 12);
            } catch (Throwable $e) {
                $new_password = substr(hash('sha256', uniqid('', true)), 0, 12);
            }

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE utenti_admin SET password = ? WHERE username = ?");
            $stmt->bind_param("ss", $hashed_password, $username);

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $successo = "Password resettata. Nuova password temporanea: " . $new_password;
            } else {
                $errore = "Utente non trovato";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Moki SUP Club</title>
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
                <h2>Moki Club Numana</h2>
                <p>Accedi all'area riservata</p>
            </div>

            <?php if ($errore): ?>
                <div class="error-container">
                    <?= htmlspecialchars($errore) ?>
                </div>
            <?php endif; ?>

            <?php if ($successo): ?>
                <div class="success-container">
                    <?= htmlspecialchars($successo) ?>
                </div>
            <?php endif; ?>

            <form class="login-form" id="loginForm" method="POST">
                <?= app_csrf_input() ?>
                <div class="form-group">
                    <div class="input-group neu-input">
                        <input type="text" id="username" name="username" required autocomplete="username"
                            placeholder=" "
                            value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                        <label for="username">Username</label>
                        <div class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group neu-input password-group">
                        <input type="password" id="password" name="password" required autocomplete="current-password"
                            placeholder=" ">
                        <label for="password">Password</label>
                        <div class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0110 0v4" />
                            </svg>
                        </div>
                        <button type="button" class="password-toggle neu-toggle" id="passwordToggle"
                            aria-label="Nascondi/Mostra password">
                            <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" style="display:none;">
                                <path
                                    d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                <line x1="1" y1="1" x2="23" y2="23" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" name="login" class="neu-button login-btn">
                    <span class="btn-text">Accedi</span>
                </button>

                <button type="submit" name="reset" class="neu-button reset-btn"
                    style="box-shadow: 4px 4px 10px #bec3cf, -4px -4px 10px #ffffff; font-size: 14px; padding: 12px; margin-top: 10px;">
                    <span class="btn-text">Reset Password</span>
                </button>
            </form>

            <div class="divider">
                <div class="divider-line"></div>
                <span>Moki SUP Club</span>
                <div class="divider-line"></div>
            </div>

            <div class="signup-link">
                <p>Torna alla <a href="/">Home Page</a></p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('passwordToggle').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const eyeOpen = this.querySelector('.eye-open');
            const eyeClosed = this.querySelector('.eye-closed');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.style.display = 'none';
                eyeClosed.style.display = 'block';
                this.classList.add('show-password');
            } else {
                passwordInput.type = 'password';
                eyeOpen.style.display = 'block';
                eyeClosed.style.display = 'none';
                this.classList.remove('show-password');
            }
        });

        // Simple form loading state
        document.getElementById('loginForm').addEventListener('submit', function () {
            const loginBtn = this.querySelector('.login-btn');
            loginBtn.classList.add('loading');
            loginBtn.innerHTML = '<div class="neu-spinner"></div>';
        });
    </script>
</body>

</html>