<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/affiliates.php';
require_once __DIR__ . '/inc/logging.php';

header('X-Robots-Tag: noindex, nofollow', true);
header('Referrer-Policy: strict-origin-when-cross-origin');

$code = $_GET['code'] ?? '';
$code = is_string($code) ? trim($code) : '';
$articleSlug = trim((string) ($_GET['article'] ?? ''));
$productSlug = trim((string) ($_GET['product'] ?? ''));
$merchantSlug = trim((string) ($_GET['merchant'] ?? ''));
$slot = (int) ($_GET['slot'] ?? 0);
$variant = trim((string) ($_GET['variant'] ?? ''));

if ($code === '' || !preg_match('~^[A-Za-z0-9_-]+$~', $code)) {
    header('Location: /affiliate-missing.php', true, 302);
    exit;
}

$referrer = trim((string) ($_SERVER['HTTP_REFERER'] ?? ''));
if ($articleSlug === '' && $referrer !== '') {
    $refPath = (string) parse_url($referrer, PHP_URL_PATH);
    if (preg_match('~^/clanky/([^/]+)$~', $refPath, $matches)) {
        $articleSlug = canonical_article_slug(rawurldecode((string) $matches[1]));
    }
}

$record = aff_record($code);
$url = aff_resolve($code);
if ($url !== null) {
    if ($productSlug === '' && is_array($record)) {
        $productSlug = trim((string) ($record['product_slug'] ?? ''));
    }
    if ($merchantSlug === '' && is_array($record)) {
        $merchantSlug = trim((string) ($record['merchant_slug'] ?? ''));
    }
    interessa_log('go', [
        'code' => $code,
        'article_slug' => $articleSlug,
        'product_slug' => $productSlug,
        'merchant_slug' => $merchantSlug,
        'slot' => $slot > 0 ? $slot : null,
        'variant' => $variant,
        'resolved_url' => $url,
        'referrer' => $referrer,
    ]);
    header('Location: ' . $url, true, 302);
    exit;
}

header('Location: /affiliate-missing.php?code=' . rawurlencode($code), true, 302);
exit;
