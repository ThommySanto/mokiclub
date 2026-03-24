<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';

if (!app_is_post()) {
    http_response_code(405);
    exit('Metodo non consentito');
}

app_require_csrf();

require_once __DIR__ . "/../config/config.php";

// =============================
// RECUPERO DATI POST
// =============================
$cognome = $_POST['cognome'] ?? null;
$nome = $_POST['nome'] ?? null;
$data_nascita = $_POST['data_nascita'] ?? null;
$luogo_nascita = $_POST['luogo_nascita'] ?? null;
$indirizzo = $_POST['indirizzo'] ?? null;
$citta = $_POST['citta'] ?? null;
$cap = $_POST['cap'] ?? null;
$telefono = $_POST['telefono'] ?? null;
$email = $_POST['email'] ?? null;

$tavola_marca = $_POST['tavola_marca'] ?? null;
$tavola_modello = $_POST['tavola_modello'] ?? null;
$tavola_anno = $_POST['tavola_anno'] ?? null;
$sacca = $_POST['sacca'] ?? null;
$pagaia = $_POST['pagaia'] ?? null;
$altro = $_POST['altro'] ?? null;

$adulto_kid = $_POST['adulto_kid'] ?? null;
$tipo_documento = $_POST['tipo_documento'] ?? null;
$numero_documento = $_POST['numero_documento'] ?? null;

$tipo_rimessaggio = $_POST['tipo_rimessaggio'] ?? null;
$acconto = isset($_POST['acconto']) ? floatval($_POST['acconto']) : 0;

// =============================
// CALCOLO PREZZO
// =============================
switch ($tipo_rimessaggio) {
    case "Settimanale":
        $saldo_totale = 100;
        break;
    case "Mensile":
        $saldo_totale = 190;
        break;
    case "Annuale":
        $saldo_totale = 450;
        break;
    default:
        die("Tipo rimessaggio non valido");
}

$rimanente = $saldo_totale - $acconto;

$data_versamento_acconto = !empty($_POST['data_versamento_acconto'])
    ? $_POST['data_versamento_acconto']
    : NULL;

// =============================
// SALVATAGGIO FIRMA
// =============================
$firma_path = NULL;

if (!empty($_POST['firma'])) {

    $firma_base64 = str_replace('data:image/png;base64,', '', $_POST['firma']);
    $firma_base64 = str_replace(' ', '+', $firma_base64);
    $firma_data = base64_decode($firma_base64);

    $anno = date("Y");
    $directory = __DIR__ . "/uploads/firme/" . $anno . "/";

    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }

    $nome_file = strtolower($nome . "_" . $cognome . "_" . time() . ".png");
    $percorso_completo = $directory . $nome_file;

    file_put_contents($percorso_completo, $firma_data);

    // Percorso relativo alla root per il DB
    $firma_path = "cliente/uploads/firme/" . $anno . "/" . $nome_file;
}

// =============================
// UPLOAD RICEVUTA
// =============================
$ricevuta_path = NULL;

if (!empty($_FILES["ricevuta_pagamento"]["name"])) {

    $anno = date("Y");
    $directory = __DIR__ . "/uploads/ricevute/" . $anno . "/";

    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }

    $file_name = strtolower($nome . "_" . $cognome . "_" . time() . "_" . basename($_FILES["ricevuta_pagamento"]["name"]));
    $target = $directory . $file_name;

    if (move_uploaded_file($_FILES["ricevuta_pagamento"]["tmp_name"], $target)) {
        // Percorso relativo alla root per il DB
        $ricevuta_path = "cliente/uploads/ricevute/" . $anno . "/" . $file_name;
    }
}

// =============================
// INSERT DATABASE
// =============================
$stmt = $conn->prepare("INSERT INTO rimessaggi (
cognome,
nome,
data_nascita,
luogo_nascita,
indirizzo,
citta,
cap,
telefono,
email,
tavola_marca,
tavola_modello,
tavola_anno,
sacca,
pagaia,
adulto_kid,
tipo_documento,
numero_documento,
altro,
tipo_rimessaggio,
acconto,
saldo_totale,
rimanente,
data_versamento_acconto,
ricevuta_pagamento,
firma
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

if (!$stmt) {
    error_log('Errore prepare submit_rimessaggio: ' . $conn->error);
    http_response_code(500);
    exit('Servizio temporaneamente non disponibile.');
}

$stmt->bind_param(
    "sssssssssssssssssssdddsss",
    $cognome,
    $nome,
    $data_nascita,
    $luogo_nascita,
    $indirizzo,
    $citta,
    $cap,
    $telefono,
    $email,
    $tavola_marca,
    $tavola_modello,
    $tavola_anno,
    $sacca,
    $pagaia,
    $adulto_kid,
    $tipo_documento,
    $numero_documento,
    $altro,
    $tipo_rimessaggio,
    $acconto,
    $saldo_totale,
    $rimanente,
    $data_versamento_acconto,
    $ricevuta_path,
    $firma_path
);

if ($stmt->execute()) {

    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        require_once __DIR__ . '/../includes/mailer.php';
        require_once __DIR__ . '/../includes/email_templates/rimessaggio_template.php';
        $html = templateRimessaggio($nome);
        inviaEmail(
            'rimessaggio',
            $email,
            'Rinnovo Rimessaggio 2026 - MOKI CLUB NUMANA',
            $html,
            __DIR__ . '/../assets/img/MCN_26_ok.pdf'
        );
    }

    // Pagina di Successo Decorata
    $pageTitle = "Rimessaggio Salvato";
    include __DIR__ . "/../includes/header.php";
    ?>
    <main class="admin-container" style="text-align: center; padding: 100px 20px;">
        <div class="success-card"
            style="background: white; padding: 50px; border-radius: 30px; box-shadow: var(--shadow-lg); max-width: 600px; margin: 0 auto; border: 1px solid var(--glass-border); backdrop-filter: blur(10px);">
            <div class="success-icon" style="font-size: 80px; margin-bottom: 20px; animation: bounce 2s infinite;">✅</div>
            <h1 style="color: var(--primary-color); margin-bottom: 15px;">Rimessaggio Confermato!</h1>
            <p style="font-size: 18px; color: #666; margin-bottom: 30px;">
                Grazie <strong><?php echo htmlspecialchars($nome); ?></strong>, il tuo contratto di rimessaggio è stato
                salvato correttamente.<br>
                Ti abbiamo inviato un'email con il riepilogo all'indirizzo <?php echo htmlspecialchars($email); ?>.
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
    $pageTitle = "Errore Rimessaggio";
    include __DIR__ . "/../includes/header.php";
    error_log('Errore submit_rimessaggio: ' . $stmt->error);
    echo "<div class='error-message' style='margin: 50px auto; max-width: 600px;'>Errore durante l'invio. Riprova più tardi.</div>";
    include __DIR__ . "/../includes/footer.php";
}

$stmt->close();
$conn->close();
?>