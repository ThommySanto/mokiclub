<?php
require_once dirname(__DIR__) . '/security_headers.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

function inviaEmail($tipo, $destinatario, $oggetto, $htmlBody, $allegato= null) {

    global $conn;

    $mail = new PHPMailer(true);
   

    try {
        $smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $smtpUser = getenv('SMTP_USER') ?: 'infomokiclubnumana@gmail.com';
        $smtpPass = getenv('SMTP_PASS') ?: 'fjyveudgqneeyeca';
        $smtpPort = (int) (getenv('SMTP_PORT') ?: 465);
        $fromName = getenv('SMTP_FROM_NAME') ?: 'MOKI CLUB NUMANA';

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
        $stmt = $conn->prepare("INSERT INTO email_log (tipo, destinatario, oggetto, stato, data_invio) VALUES (?, ?, ?, 'inviata', ?)");
        $stmt->bind_param("ssss", $tipo, $destinatario, $oggetto, $dataInvio);
        $stmt->execute();

        return true;

    } catch (Exception $e) {

        $errore = $mail->ErrorInfo;
        $dataInvio = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO email_log (tipo, destinatario, oggetto, stato, errore, data_invio) VALUES (?, ?, ?, 'errore', ?, ?)");
        $stmt->bind_param("sssss", $tipo, $destinatario, $oggetto, $errore, $dataInvio);
        $stmt->execute();

        return false;
    }
}