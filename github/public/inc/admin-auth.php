<?php

declare(strict_types=1);

if (!function_exists('interessa_admin_auth_config')) {
    function interessa_admin_auth_config(): array {
        static $config = null;
        if (is_array($config)) {
            return $config;
        }

        $config = [
            'password' => 'interesa-admin',
            'label' => 'interesa-admin',
            'source' => 'default',
        ];

        $file = dirname(__DIR__) . '/storage/admin/auth.php';
        if (is_file($file)) {
            $data = include $file;
            if (is_array($data)) {
                $config = array_replace($config, $data);
                $config['source'] = 'file';
            }
        }

        return $config;
    }
}

if (!function_exists('interessa_admin_session_boot')) {
    function interessa_admin_session_boot(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name('interessa_admin');
        session_start();
    }
}

if (!function_exists('interessa_admin_is_authenticated')) {
    function interessa_admin_is_authenticated(): bool {
        interessa_admin_session_boot();
        return !empty($_SESSION['interessa_admin_ok']);
    }
}

if (!function_exists('interessa_admin_attempt_login')) {
    function interessa_admin_attempt_login(string $password): bool {
        interessa_admin_session_boot();
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
            $_SESSION['interessa_admin_ok'] = true;
            $_SESSION['interessa_admin_login_at'] = time();
            return true;
        }

        return false;
    }
}

if (!function_exists('interessa_admin_logout')) {
    function interessa_admin_logout(): void {
        interessa_admin_session_boot();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', (bool) ($params['secure'] ?? false), (bool) ($params['httponly'] ?? true));
        }
        session_destroy();
    }
}
