<?php
require_once dirname(dirname(__DIR__)) . '/security_headers.php';
function templateIscrizione($nome) {

return '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;background:#e6f7ff;font-family:Arial,sans-serif;">

<div style="max-width:600px;margin:30px auto;background:white;border-radius:10px;overflow:hidden;box-shadow:0 5px 20px rgba(0,0,0,0.1);">

<div style="background:#00bcd4;padding:20px;text-align:center;">
<img 
  src="https://mokiclub.infinityfreeapp.com/assets/img/moki.jpg" 
  style="width:150px;height:150px;border-radius:50%;object-fit:cover;">
</div>

<div style="padding:30px;text-align:center;">

<h2 style="color:#0077b6;">Ciao '.$nome.',</h2>

<p style="font-size:16px;color:#333;">
Benvenuto al <strong>MOKI CLUB NUMANA</strong>!<br><br>
La tua iscrizione è stata registrata correttamente.
</p>

<a href="https://whatsapp.com/channel/0029Vb6b08F8kyyPJnCDGG0M"
style="display:inline-block;margin-top:20px;padding:14px 25px;background:#25D366;color:white;text-decoration:none;border-radius:6px;font-weight:bold;">
Unisciti al Canale WhatsApp
</a>

<p style="margin-top:30px;color:#0077b6;font-weight:bold;">
#MOKICLUBNUMANA_11<br>
#solocosebelle
</p>

</div>
</div>

</body>
</html>
';
}