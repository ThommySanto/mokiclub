<?php
@ini_set('expose_php', '0');
header_remove('X-Powered-By');

// Deve essere incluso prima di qualsiasi output.
if (headers_sent()) {
	return;
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Header aggiuntivi utili nei pentest e generalmente sicuri per questo progetto.
header('X-Frame-Options: SAMEORIGIN');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header('Cross-Origin-Opener-Policy: same-origin');
header('Cross-Origin-Resource-Policy: same-origin');
header('X-XSS-Protection: 1; mode=block');

// HSTS va inviato solo su HTTPS, altrimenti il browser lo ignora.
$isHttps = (
	(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
	(isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443') ||
	(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
);
if ($isHttps) {
	header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// CSP calibrata sul sito corrente (CDN flatpickr + script inline presenti nei template).
$csp = [
	"default-src 'self'",
	"base-uri 'self'",
	"object-src 'none'",
	"frame-ancestors 'self'",
	"form-action 'self'",
	"script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://npmcdn.com",
	"style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
	"img-src 'self' data: https:",
	"font-src 'self' data:",
	"connect-src 'self'",
	"upgrade-insecure-requests",
];
header('Content-Security-Policy: ' . implode('; ', $csp));
