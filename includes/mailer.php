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

    $dataInvio = date('Y-m-d H:i:s');

    try {
        $smtpHost = app_mail_secret('SMTP_HOST', 'smtp.gmail.com');
        $smtpUser = app_mail_secret('SMTP_USER', null);
        $smtpPass = app_mail_secret('SMTP_PASS', null);
        $smtpPort = (int) app_mail_secret('SMTP_PORT', 465);
        $fromName = app_mail_secret('SMTP_FROM_NAME', 'MOKI CLUB NUMANA');

        // (Opzionale) CC a te stesso: mettila 1/0 in secrets.php, default 0
        $smtpCcSelf = (string) app_mail_secret('SMTP_CC_SELF', '0');

        // Se manca config SMTP, logga e ritorna false (NON fatal) + salva su email_log
        if (!$smtpHost || !$smtpUser || !$smtpPass) {
            $msg = 'SMTP config missing: impossibile inviare email.';
            error_log($msg);

            if ($conn) {
                $stmt = $conn->prepare(
                    "INSERT INTO email_log (tipo, destinatario, oggetto, stato, errore, data_invio)
                     VALUES (?, ?, ?, 'errore', ?, ?)"
                );
                if ($stmt) {
                    $stmt->bind_param("sssss", $tipo, $destinatario, $oggetto, $msg, $dataInvio);
                    $stmt->execute();
                }
            }

            return false;
        }

        $mail->CharSet = 'UTF-8';

        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;

        // Default: SMTPS 465 (come avevi). Se vuoi usare 587 STARTTLS, cambiamo qui.
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $smtpPort;

        $mail->setFrom($smtpUser, $fromName);
        $mail->addAddress($destinatario);

        // CC opzionale (evita problemi di deliverability)
        if ($smtpCcSelf === '1') {
            $mail->addCC($smtpUser);
        }

        if ($allegato && file_exists($allegato)) {
            $mail->addAttachment($allegato);
        }

        $mail->isHTML(true);
        $mail->Subject = (string)$oggetto;
        $mail->Body = (string)$htmlBody;

        // Versione testo per client che non leggono HTML
        $mail->AltBody = trim(html_entity_decode(strip_tags(str_replace('<br>', "\n", (string)$htmlBody)), ENT_QUOTES, 'UTF-8'));

        $mail->send();

        // LOG SUCCESSO
        if ($conn) {
            $stmt = $conn->prepare(
                "INSERT INTO email_log (tipo, destinatario, oggetto, stato, data_invio)
                 VALUES (?, ?, ?, 'inviata', ?)"
            );
            if ($stmt) {
                $stmt->bind_param("ssss", $tipo, $destinatario, $oggetto, $dataInvio);
                $stmt->execute();
            }
        }

        return true;

    } catch (Exception $e) {
        $errore = $mail->ErrorInfo ?: $e->getMessage();

        if ($conn) {
            $stmt = $conn->prepare(
                "INSERT INTO email_log (tipo, destinatario, oggetto, stato, errore, data_invio)
                 VALUES (?, ?, ?, 'errore', ?, ?)"
            );
            if ($stmt) {
                $stmt->bind_param("sssss", $tipo, $destinatario, $oggetto, $errore, $dataInvio);
                $stmt->execute();
            }
        }

        error_log('MAIL ERROR: ' . $errore);
        return false;
    }
}