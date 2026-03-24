<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';

if (!app_is_post()) {
    http_response_code(405);
    exit('Metodo non consentito');
}

app_require_csrf();

require_once __DIR__ . "/../config/config.php";

$nome = $_POST['nome'];
$cognome = $_POST['cognome'];
$luogo_nascita = $_POST['luogo_nascita'];
$data_nascita = $_POST['data_nascita'];
$indirizzo = $_POST['indirizzo'];
$citta = $_POST['citta'];
$cap = $_POST['cap'];
$telefono = $_POST['telefono'];
$email = $_POST['email'];
$tipo_documento = $_POST['tipo_documento'];
$numero_documento = $_POST['numero_documento'];
$adulto_kid = $_POST['categoria'];

$interessato_offerte = isset($_POST['newsletter']) ? 1 : 0;
$dichiaro_nuoto = isset($_POST['sa_nuotare']) ? 1 : 0;
$consenso_privacy = isset($_POST['privacy']) ? 1 : 0;

$tipo_iscrizione = $_POST['tipo_iscrizione'];
$data_iscrizione = date("Y-m-d");

$anno = date("Y");
$target_dir = "uploads/firme/" . $anno . "/";

if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$target_file = NULL;

if (!empty($_POST['firma_base64'])) {
    $firma_base64 = str_replace('data:image/png;base64,', '', $_POST['firma_base64']);
    $firma_base64 = str_replace(' ', '+', $firma_base64);
    $firma_data = base64_decode($firma_base64);

    $nome_pulito = preg_replace('/[^a-zA-Z0-9]/', '_', $nome);
    $cognome_pulito = preg_replace('/[^a-zA-Z0-9]/', '_', $cognome);

    $nome_file = strtolower($nome_pulito . "_" . $cognome_pulito . "_" . date("Ymd_His") . ".png");

    // Salvataggio fisico in cliente/uploads/firme/2026/
    file_put_contents($target_dir . $nome_file, $firma_data);

    // Percorso da salvare nel DB (relativo alla root del sito)
    $target_file = "cliente/" . $target_dir . $nome_file;
}


$stmt = $conn->prepare("
INSERT INTO iscritti
(nome, cognome, luogo_nascita, data_nascita,
indirizzo_residenza, citta_residenza, cap,
telefono, email,
tipo_documento, numero_documento,
adulto_kid,
interessato_offerte, dichiaro_nuoto, consenso_privacy,
data_iscrizione, tipo_iscrizione,
firma)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
    "ssssssssssssiiisss",
    $nome,
    $cognome,
    $luogo_nascita,
    $data_nascita,
    $indirizzo,
    $citta,
    $cap,
    $telefono,
    $email,
    $tipo_documento,
    $numero_documento,
    $adulto_kid,
    $interessato_offerte,
    $dichiaro_nuoto,
    $consenso_privacy,
    $data_iscrizione,
    $tipo_iscrizione,
    $target_file
);
if ($stmt->execute()) {

    // Invia email solo se valida
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {

        require_once __DIR__ . '/../includes/mailer.php';
        require_once __DIR__ . '/../includes/email_templates/iscrizione_template.php';

        $html = templateIscrizione($nome);

        inviaEmail(
            'iscrizione',
            $email,
            'Benvenuto al MOKI CLUB NUMANA',
            $html
        );
    }

    // Pagina di Successo Decorata
    $pageTitle = "Iscrizione Completata";
    include __DIR__ . "/../includes/header.php";
    ?>
    <main class="admin-container" style="text-align: center; padding: 100px 20px;">
        <div class="success-card"
            style="background: white; padding: 50px; border-radius: 30px; box-shadow: var(--shadow-lg); max-width: 600px; margin: 0 auto; border: 1px solid var(--glass-border); backdrop-filter: blur(10px);">
            <div class="success-icon" style="font-size: 80px; margin-bottom: 20px; animation: bounce 2s infinite;">✅</div>
            <h1 style="color: var(--primary-color); margin-bottom: 15px;">Iscrizione Completata!</h1>
            <p style="font-size: 18px; color: #666; margin-bottom: 30px;">
                Grazie <strong><?php echo htmlspecialchars($nome); ?></strong>, la tua richiesta è stata inviata con
                successo.<br>
                Ti abbiamo inviato un'email di conferma all'indirizzo <?php echo htmlspecialchars($email); ?>.
            </p>
            <p style="font-size: 14px; color: #999;">Verrai reindirizzato alla home tra pochi secondi...</p>
            <div class="loading-bar"
                style="width: 100%; height: 4px; background: #eee; border-radius: 2px; margin-top: 20px; overflow: hidden;">
                <div class="loading-progress"
                    style="width: 0%; height: 100%; background: var(--secondary-color); animation: progress 5s linear forwards;">
                </div>
            </div>
            <a href="/index.php" class="btn-primary"
                style="display: inline-block; margin-top: 30px; text-decoration: none;">Torna subito alla Home</a>
        </div>
    </main>

    <style>
        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-20px);
            }

            60% {
                transform: translateY(-10px);
            }
        }

        @keyframes progress {
            from {
                width: 0%;
            }

            to {
                width: 100%;
            }
        }
    </style>

    <script>
        setTimeout(function () {
            window.location.href = '/index.php';
        }, 5000);
    </script>
    <?php
    include __DIR__ . "/../includes/footer.php";

} else {
    $pageTitle = "Errore Iscrizione";
    include __DIR__ . "/../includes/header.php";
    error_log('Errore submit_iscrizione: ' . $stmt->error);
    echo "<div class='error-message' style='margin: 50px auto; max-width: 600px;'>Errore durante l'invio. Riprova più tardi.</div>";
    include __DIR__ . "/../includes/footer.php";
}

$stmt->close();
$conn->close();
?>