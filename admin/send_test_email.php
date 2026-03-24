<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/mailer.php";
require_once __DIR__ . "/../includes/email_templates/iscrizione_template.php";
require_once __DIR__ . "/../includes/email_templates/rimessaggio_template.php";

$isPost = (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST');
if (!$isPost) {
    http_response_code(405);
    exit('Metodo non consentito');
}

app_require_csrf();

$destinatario = $_POST['destinatario'] ?? '';
$tipo = $_POST['tipo'] ?? '';

if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
    header("Location: email_log.php");
    exit;
}

if ($tipo === "iscrizione") {
    $html = templateIscrizione("TEST");
    $oggetto = "TEST Iscrizione - MOKI CLUB NUMANA";
}

elseif ($tipo === "rimessaggio") {
    $html = templateRimessaggio("TEST");
    $oggetto = "TEST Rimessaggio - MOKI CLUB NUMANA";
}

else {
    header("Location: email_log.php");
    exit;
}

$pdf_path = null;

if ($tipo === "rimessaggio") {
    $pdf_path = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/MCN_26_ok.pdf';
}

inviaEmail($tipo, $destinatario, $oggetto, $html, $pdf_path);

header("Location: email_log.php");
exit;