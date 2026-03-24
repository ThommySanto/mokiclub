<?php
require_once dirname(__DIR__) . '/security_headers.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

// Caricamento segreti SMTP: usa prima getenv, poi config/secrets.php se esiste
if (!function_exists('app_mail_secret')) {
    function app_mail_secret(string $key, $default = null) {
        $env = getenv($key);
        if ($env !== false && $env !== '') {
            return $env;
        }

        $secrets = [];
        $secretsPath = dirname(__DIR__) . '/config/secrets.php';
        if (is_file($secretsPath)) {
            $loaded = include $secretsPath;
            if (is_array($loaded)) {
                $secrets = $loaded;
            }
        }

        if (isset($secrets[$key]) && $secrets[$key] !== '') {
            return $secrets[$key];
        }

        return $default;
    }
}

function inviaEmail($tipo, $destinatario, $oggetto, $htmlBody, $allegato = null) {
    global $conn;

    $mail = new PHPMailer(true);

    try {
        $smtpHost = app_mail_secret('SMTP_HOST', 'smtp.gmail.com'); // ok fallback non-segreto
        $smtpUser = app_mail_secret('SMTP_USER', null);
        $smtpPass = app_mail_secret('SMTP_PASS', null);
        $smtpPort = (int) app_mail_secret('SMTP_PORT', 465);
        $fromName = app_mail_secret('SMTP_FROM_NAME', 'MOKI CLUB NUMANA');

        // Se manca config SMTP, logga e ritorna false (non fatal)
        if (!$smtpHost || !$smtpUser || !$smtpPass) {
            error_log('SMTP config missing: impossibile inviare email.');
            return false;
        }

        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $smtpPort;

        $mail->setFrom($smtpUser, $fromName);
        $mail->addAddress($destinatario);

        // copia sempre a te
        $mail->addCC($smtpUser);

        if ($allegato && file_exists($allegato)) {
            $mail->addAttachment($allegato);
        }

        $mail->isHTML(true);
        $mail->Subject = $oggetto;
        $mail->Body = $htmlBody;

        $mail->send();

        $dataInvio = date('Y-m-d H:i:s');

        // LOG SUCCESSO
        if ($conn) {
            $stmt = $conn->prepare("INSERT INTO email_log (tipo, destinatario, oggetto, stato, data_invio) VALUES (?, ?, ?, 'inviata', ?)");
            if ($stmt) {
                $stmt->bind_param("ssss", $tipo, $destinatario, $oggetto, $dataInvio);
                $stmt->execute();
            }
        }

        return true;

    } catch (Exception $e) {
        $errore = $mail->ErrorInfo ?: $e->getMessage();
        $dataInvio = date('Y-m-d H:i:s');

        if ($conn) {
            $stmt = $conn->prepare("INSERT INTO email_log (tipo, destinatario, oggetto, stato, errore, data_invio) VALUES (?, ?, ?, 'errore', ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssss", $tipo, $destinatario, $oggetto, $errore, $dataInvio);
                $stmt->execute();
            }
        }

        return false;
    }
}