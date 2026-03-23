<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/affiliates.php';

header('X-Robots-Tag: noindex, nofollow', true);
header('Referrer-Policy: strict-origin-when-cross-origin');

$code = $_GET['code'] ?? '';
$code = is_string($code) ? trim($code) : '';

if ($code === '' || !preg_match('~^[A-Za-z0-9_-]+$~', $code)) {
    header('Location: /affiliate-missing.php', true, 302);
    exit;
}

$url = aff_resolve($code);
if ($url !== null) {
    header('Location: ' . $url, true, 302);
    exit;
}

header('Location: /affiliate-missing.php?code=' . rawurlencode($code), true, 302);
exit;