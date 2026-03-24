<?php

if (!function_exists('app_is_https')) {
    function app_is_https(): bool
    {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443') ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        );
    }
}

if (!function_exists('app_start_secure_session')) {
    function app_start_secure_session(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = app_is_https();
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}

if (!function_exists('app_csrf_token')) {
    function app_csrf_token(): string
    {
        app_start_secure_session();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('app_csrf_input')) {
    function app_csrf_input(string $name = 'csrf_token'): string
    {
        $token = app_csrf_token();
        return '<input type="hidden" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('app_validate_csrf')) {
    function app_validate_csrf(?string $token): bool
    {
        app_start_secure_session();
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('app_require_csrf')) {
    function app_require_csrf(string $field = 'csrf_token'): void
    {
        $token = $_POST[$field] ?? $_GET[$field] ?? null;
        if (!app_validate_csrf($token)) {
            http_response_code(403);
            exit('Richiesta non valida (CSRF).');
        }
    }
}

if (!function_exists('app_rate_limit_storage_dir')) {
    function app_rate_limit_storage_dir(): string
    {
        $dir = dirname(__DIR__) . '/tmp/rate_limit';
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        return $dir;
    }
}

if (!function_exists('app_rate_limit_file')) {
    function app_rate_limit_file(string $key): string
    {
        return app_rate_limit_storage_dir() . '/' . sha1($key) . '.json';
    }
}

if (!function_exists('app_rate_limit_get_state')) {
    function app_rate_limit_get_state(string $key, int $windowSeconds): array
    {
        $file = app_rate_limit_file($key);
        $now = time();

        if (!is_file($file)) {
            return ['count' => 0, 'start' => $now];
        }

        $raw = file_get_contents($file);
        $state = json_decode((string) $raw, true);

        if (!is_array($state) || !isset($state['count'], $state['start'])) {
            return ['count' => 0, 'start' => $now];
        }

        if (($now - (int) $state['start']) > $windowSeconds) {
            return ['count' => 0, 'start' => $now];
        }

        return ['count' => (int) $state['count'], 'start' => (int) $state['start']];
    }
}

if (!function_exists('app_rate_limit_is_blocked')) {
    function app_rate_limit_is_blocked(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $state = app_rate_limit_get_state($key, $windowSeconds);
        return $state['count'] >= $maxAttempts;
    }
}

if (!function_exists('app_rate_limit_increment')) {
    function app_rate_limit_increment(string $key, int $windowSeconds): void
    {
        $state = app_rate_limit_get_state($key, $windowSeconds);
        $state['count']++;
        file_put_contents(app_rate_limit_file($key), json_encode($state));
    }
}

if (!function_exists('app_rate_limit_reset')) {
    function app_rate_limit_reset(string $key): void
    {
        $file = app_rate_limit_file($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }
}

if (!function_exists('app_rate_limit_retry_after')) {
    function app_rate_limit_retry_after(string $key, int $windowSeconds): int
    {
        $state = app_rate_limit_get_state($key, $windowSeconds);
        $elapsed = time() - $state['start'];
        $remaining = $windowSeconds - $elapsed;
        return max(0, $remaining);
    }
}

if (!function_exists('app_request_ip')) {
    function app_request_ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

if (!function_exists('app_is_post')) {
    function app_is_post(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }
}
