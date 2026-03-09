<?php
declare(strict_types=1);

/**
 * Security headers + CSP s nonce.
 * Použitie v šablónach (ideálne v inc/head.php pred výstupom):
 *   require_once __DIR__ . '/security.php';
 *   interessa_security_headers();
 * Pre inline <script> tagy potom použi: nonce="<?= htmlspecialchars(interessa_csp_nonce()) ?>"
 */

if (!function_exists('interessa_csp_nonce')) {
    function interessa_csp_nonce(): string {
        static $nonce = null;
        if ($nonce === null) {
            $nonce = base64_encode(random_bytes(18));
            $_SERVER['__CSP_NONCE'] = $nonce;
        }
        return $nonce;
    }
}

if (!function_exists('interessa_security_headers')) {
    function interessa_security_headers(): void {
        $nonce = interessa_csp_nonce();
        $csp = [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "style-src 'self' 'unsafe-inline'", // ponecháme inline CSS kvôli legacy
            "script-src 'self' 'nonce-{$nonce}'",
            "connect-src 'self'",
            "form-action 'self'",
            "upgrade-insecure-requests",
        ];
        header('Content-Security-Policy: ' . implode('; ', $csp));
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
    }
}
