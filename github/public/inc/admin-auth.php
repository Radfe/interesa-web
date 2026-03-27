<?php

declare(strict_types=1);

if (!function_exists('interessa_admin_is_https_request')) {
    function interessa_admin_is_https_request(): bool {
        $https = strtolower(trim((string) ($_SERVER['HTTPS'] ?? '')));
        if ($https !== '' && $https !== 'off' && $https !== '0') {
            return true;
        }

        $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        return $forwardedProto === 'https';
    }
}

if (!function_exists('interessa_admin_auth_config')) {
    function interessa_admin_auth_config(): array {
        static $config = null;
        if (is_array($config)) {
            return $config;
        }

        $config = [
            'password' => '',
            'password_hash' => '',
            'source' => 'none',
            'file' => '',
            'password_configured' => false,
            'legacy_plaintext' => false,
        ];

        $envHash = trim((string) getenv('INTERESA_ADMIN_PASSWORD_HASH'));
        $envPassword = trim((string) getenv('INTERESA_ADMIN_PASSWORD'));
        if ($envHash !== '' || $envPassword !== '') {
            $config['password_hash'] = $envHash;
            $config['password'] = $envPassword;
            $config['source'] = 'env';
            $config['password_configured'] = $envHash !== '' || $envPassword !== '';
            $config['legacy_plaintext'] = $envHash === '' && $envPassword !== '';
            return $config;
        }

        $root = dirname(__DIR__, 2);
        $candidates = [
            $root . '/config/admin-auth.php' => 'config',
            dirname(__DIR__) . '/storage/admin/auth.php' => 'legacy-public-storage',
        ];

        foreach ($candidates as $file => $source) {
            if (!is_file($file)) {
                continue;
            }

            $data = include $file;
            if (!is_array($data)) {
                continue;
            }

            $config = array_replace($config, $data);
            $config['source'] = $source;
            $config['file'] = $file;
            break;
        }

        $hash = trim((string) ($config['password_hash'] ?? ''));
        $password = trim((string) ($config['password'] ?? ''));
        $config['password_hash'] = $hash;
        $config['password'] = $password;
        $config['password_configured'] = $hash !== '' || $password !== '';
        $config['legacy_plaintext'] = $hash === '' && $password !== '';

        return $config;
    }
}

if (!function_exists('interessa_admin_session_boot')) {
    function interessa_admin_session_boot(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = interessa_admin_is_https_request();
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            session_set_cookie_params(0, '/; samesite=Lax', '', $secure, true);
        }

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        session_name('interessa_admin');
        session_start();
    }
}

if (!function_exists('interessa_admin_is_authenticated')) {
    function interessa_admin_is_authenticated(): bool {
        interessa_admin_session_boot();
        return !empty($_SESSION['interesa_admin_ok']);
    }
}

if (!function_exists('interessa_admin_client_ip')) {
    function interessa_admin_client_ip(): string {
        $forwarded = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($forwarded !== '') {
            $parts = array_values(array_filter(array_map('trim', explode(',', $forwarded))));
            if ($parts !== []) {
                return (string) $parts[0];
            }
        }

        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        return $ip !== '' ? $ip : 'unknown';
    }
}

if (!function_exists('interessa_admin_login_rate_limit_file')) {
    function interessa_admin_login_rate_limit_file(): string {
        return dirname(__DIR__) . '/storage/admin/login-rate-limit.json';
    }
}

if (!function_exists('interessa_admin_login_rate_limit_read')) {
    function interessa_admin_login_rate_limit_read(): array {
        $file = interessa_admin_login_rate_limit_file();
        if (!is_file($file)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('interessa_admin_login_rate_limit_write')) {
    function interessa_admin_login_rate_limit_write(array $state): void {
        $file = interessa_admin_login_rate_limit_file();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $payload = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_string($payload)) {
            return;
        }

        file_put_contents($file, $payload, LOCK_EX);
    }
}

if (!function_exists('interessa_admin_login_rate_limit_key')) {
    function interessa_admin_login_rate_limit_key(): string {
        return hash('sha256', interessa_admin_client_ip());
    }
}

if (!function_exists('interessa_admin_login_rate_limit_status')) {
    function interessa_admin_login_rate_limit_status(): array {
        $state = interessa_admin_login_rate_limit_read();
        $key = interessa_admin_login_rate_limit_key();
        $row = is_array($state[$key] ?? null) ? $state[$key] : [];
        $now = time();
        $blockedUntil = (int) ($row['blocked_until'] ?? 0);

        if ($blockedUntil > $now) {
            return [
                'blocked' => true,
                'retry_after' => max(1, $blockedUntil - $now),
            ];
        }

        return [
            'blocked' => false,
            'retry_after' => 0,
        ];
    }
}

if (!function_exists('interessa_admin_login_rate_limit_record_failure')) {
    function interessa_admin_login_rate_limit_record_failure(): void {
        $state = interessa_admin_login_rate_limit_read();
        $key = interessa_admin_login_rate_limit_key();
        $row = is_array($state[$key] ?? null) ? $state[$key] : [];
        $now = time();

        $windowStart = (int) ($row['window_started_at'] ?? 0);
        $attempts = (int) ($row['attempts'] ?? 0);
        if ($windowStart <= 0 || ($now - $windowStart) > 900) {
            $windowStart = $now;
            $attempts = 0;
        }

        $attempts++;
        $row['attempts'] = $attempts;
        $row['window_started_at'] = $windowStart;
        $row['last_failed_at'] = $now;
        if ($attempts >= 5) {
            $row['blocked_until'] = $now + 900;
            $row['attempts'] = 0;
            $row['window_started_at'] = $now;
        }

        $cutoff = $now - 86400;
        foreach ($state as $entryKey => $entry) {
            if (!is_array($entry)) {
                unset($state[$entryKey]);
                continue;
            }

            $entryBlockedUntil = (int) ($entry['blocked_until'] ?? 0);
            $entryLastFailed = (int) ($entry['last_failed_at'] ?? 0);
            if ($entryBlockedUntil < $cutoff && $entryLastFailed < $cutoff) {
                unset($state[$entryKey]);
            }
        }

        $state[$key] = $row;
        interessa_admin_login_rate_limit_write($state);
    }
}

if (!function_exists('interessa_admin_login_rate_limit_clear')) {
    function interessa_admin_login_rate_limit_clear(): void {
        $state = interessa_admin_login_rate_limit_read();
        $key = interessa_admin_login_rate_limit_key();
        if (array_key_exists($key, $state)) {
            unset($state[$key]);
            interessa_admin_login_rate_limit_write($state);
        }
    }
}

if (!function_exists('interessa_admin_csrf_token')) {
    function interessa_admin_csrf_token(string $scope = 'login'): string {
        interessa_admin_session_boot();
        $key = 'interessa_admin_csrf_' . preg_replace('~[^a-z0-9_-]+~i', '_', $scope);
        $token = trim((string) ($_SESSION[$key] ?? ''));
        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            $_SESSION[$key] = $token;
        }

        return $token;
    }
}

if (!function_exists('interessa_admin_validate_csrf_token')) {
    function interessa_admin_validate_csrf_token(string $token, string $scope = 'login'): bool {
        interessa_admin_session_boot();
        $key = 'interessa_admin_csrf_' . preg_replace('~[^a-z0-9_-]+~i', '_', $scope);
        $expected = trim((string) ($_SESSION[$key] ?? ''));
        if ($expected === '' || $token === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }
}

if (!function_exists('interessa_admin_rotate_csrf_token')) {
    function interessa_admin_rotate_csrf_token(string $scope = 'login'): void {
        interessa_admin_session_boot();
        $key = 'interessa_admin_csrf_' . preg_replace('~[^a-z0-9_-]+~i', '_', $scope);
        $_SESSION[$key] = bin2hex(random_bytes(32));
    }
}

if (!function_exists('interessa_admin_attempt_login')) {
    function interessa_admin_attempt_login(string $password): bool {
        interessa_admin_session_boot();
        $status = interessa_admin_login_rate_limit_status();
        if (!empty($status['blocked'])) {
            return false;
        }

        $config = interessa_admin_auth_config();
        $expected = (string) ($config['password'] ?? '');
        $hash = trim((string) ($config['password_hash'] ?? ''));

        $isValid = false;
        if ($hash !== '') {
            $isValid = password_verify($password, $hash);
        } elseif ($expected !== '') {
            $isValid = hash_equals($expected, $password);
        }

        if ($isValid) {
            session_regenerate_id(true);
            $_SESSION['interesa_admin_ok'] = true;
            $_SESSION['interessa_admin_login_at'] = time();
            interessa_admin_login_rate_limit_clear();
            interessa_admin_rotate_csrf_token('login');
            return true;
        }

        interessa_admin_login_rate_limit_record_failure();
        return false;
    }
}

if (!function_exists('interessa_admin_logout')) {
    function interessa_admin_logout(): void {
        interessa_admin_session_boot();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            if (PHP_VERSION_ID >= 70300) {
                setcookie(session_name(), '', [
                    'expires' => time() - 42000,
                    'path' => $params['path'] ?? '/',
                    'domain' => $params['domain'] ?? '',
                    'secure' => (bool) ($params['secure'] ?? false),
                    'httponly' => (bool) ($params['httponly'] ?? true),
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]);
            } else {
                setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', (bool) ($params['secure'] ?? false), (bool) ($params['httponly'] ?? true));
            }
        }

        session_destroy();
    }
}
// deploy trigger