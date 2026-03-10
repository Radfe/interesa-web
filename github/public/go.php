<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/affiliates.php';

header('X-Robots-Tag: noindex, nofollow', true);
header('Referrer-Policy: strict-origin-when-cross-origin');

$code = $_GET['code'] ?? '';
$code = is_string($code) ? trim($code) : '';

if ($code === '' || !preg_match('~^[A-Za-z0-9_-]+$~', $code)) {
    http_response_code(302);
    header('Location: /affiliate-missing.php');
    exit;
}

if ($url = aff_resolve($code)) {
    http_response_code(302);
    header('Location: ' . $url);
    exit;
}

http_response_code(302);
header('Location: /affiliate-missing.php?code=' . rawurlencode($code));
exit;
