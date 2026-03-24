<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/mailer.php";

header('Content-Type: application/json; charset=UTF-8');

$action = $_GET['action'] ?? '';
$source = $_GET['source'] ?? ($_POST['source'] ?? 'iscritti');

if (!in_array($source, ['iscritti', 'rimessaggi'], true)) {
    $source = 'iscritti';
}

$csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
if (!app_validate_csrf($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF token non valido']);
    exit;
}

if ($action === 'get_users') {
    if ($source === 'rimessaggi') {
        $result = $conn->query("SELECT id, nome, cognome, email, 1 as interessato_offerte FROM rimessaggi WHERE email IS NOT NULL AND email <> '' ORDER BY cognome ASC");
    } else {
        // Recuperiamo tutti i soci con lo stato del consenso
        $result = $conn->query("SELECT id_modulo as id, nome, cognome, email, interessato_offerte FROM iscritti ORDER BY cognome ASC");
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode(['success' => true, 'users' => $users]);
    exit;
}

if ($action === 'send_email') {
    $id = $_POST['id'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $messageTemplate = $_POST['message'] ?? '';
    $attachmentPath = $_POST['attachment'] ?? null;

    if (empty($id) || empty($subject) || empty($messageTemplate)) {
        echo json_encode(['success' => false, 'error' => 'Dati mancanti']);
        exit;
    }

    if ($source === 'rimessaggi') {
        $stmt = $conn->prepare("SELECT nome, cognome, email, 1 as interessato_offerte FROM rimessaggi WHERE id = ?");
    } else {
        // Cerchiamo il socio
        $stmt = $conn->prepare("SELECT nome, cognome, email, interessato_offerte FROM iscritti WHERE id_modulo = ?");
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Socio non trovato']);
        exit;
    }

    if ($source !== 'rimessaggi' && $user['interessato_offerte'] == 0) {
        echo json_encode(['success' => false, 'error' => 'Nessun consenso marketing']);
        exit;
    }

    // Sostituiamo il segnaposto {NOME}
    $personalizedMessage = str_replace('{NOME}', $user['nome'], $messageTemplate);
    // Convertiamo i newline in <br> per l'HTML
    $htmlBody = nl2br(htmlspecialchars($personalizedMessage));

    // Invio email
    $mailType = $source === 'rimessaggi' ? 'bulk_email_rimessaggi' : 'bulk_email';
    $success = inviaEmail($mailType, $user['email'], $subject, $htmlBody, $attachmentPath);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Errore invio']);
    }
    exit;
}

if ($action === 'upload_attachment') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../tmp/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        $filename = time() . '_' . basename($_FILES['file']['name']);
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            echo json_encode(['success' => true, 'path' => $targetPath]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Caricamento fallito']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Nessun file o errore']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Azione non valida']);
