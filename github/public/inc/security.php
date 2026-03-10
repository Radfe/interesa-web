<?php
declare(strict_types=1);

/**
 * Nastaví bezpečnostné HTTP hlavičky.
 */
function interessa_security_headers(): void {
  header('X-Frame-Options: SAMEORIGIN');         // Ochrana proti clickjackingu
  header('X-Content-Type-Options: nosniff');     // Zákaz mime type sniffingu
  header('X-XSS-Protection: 1; mode=block');     // Ochrana proti XSS
  header('Referrer-Policy: strict-origin-when-cross-origin');
  header('Permissions-Policy: interest-cohort=()'); // Zakázanie FLoC (tracking)
  header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload'); // Ak máš HTTPS
}
