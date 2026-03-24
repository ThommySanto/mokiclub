<?php
// Esempio di file di segreti da NON committare (crea config/secrets.php e personalizza i valori)
// NOTA: questo file è committato, quindi NON inserire password reali qui dentro.
return [
    'DB_HOST' => 'sql306.infinityfree.com',
    'DB_USER' => 'if0_XXXXXXXX',
    'DB_PASS' => 'CHANGE_ME',
    'DB_NAME' => 'if0_XXXXXXXX_moki',

    'SMTP_HOST' => 'smtp.gmail.com',
    'SMTP_USER' => 'youraddress@gmail.com',
    'SMTP_PASS' => 'CHANGE_ME',
    'SMTP_PORT' => 465,
    'SMTP_FROM_NAME' => 'MOKI CLUB NUMANA',

    'APP_ENV' => 'production',
];