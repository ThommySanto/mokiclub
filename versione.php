<?php
require_once __DIR__ . '/security_headers.php';
http_response_code(404);
exit('Not found');