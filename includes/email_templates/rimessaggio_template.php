<?php
require_once dirname(dirname(__DIR__)) . '/security_headers.php';
function templateRimessaggio($nome) {

return '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;background:#e6f7ff;font-family:Arial,sans-serif;">

<div style="max-width:600px;margin:30px auto;background:white;border-radius:10px;overflow:hidden;box-shadow:0 5px 20px rgba(0,0,0,0.1);">

<div style="background:#0096c7;padding:20px;text-align:center;">
<img 
  src="https://mokiclub.infinityfreeapp.com/assets/img/moki.jpg" 
  style="width:150px;height:150px;border-radius:50%;object-fit:cover;">
</div>

<div style="padding:30px;text-align:center;">

<h2 style="color:#0077b6;">Ciao '.$nome.',</h2>

<p style="font-size:16px;color:#333;">
Nel PDF troverai tutte le info e indicazioni per rinnovare la tua affiliazione al MCN e il rimessaggio 2026.<br><br>
Per qualsiasi info non esitare a contattarci!
</p>

<p style="font-size:18px;margin-top:20px;">
🌊🐙🤙🏽<br>
<strong>Welcome aboard again!</strong>
</p>

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