<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

if (PHP_SAPI === 'cli-server') {
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $resolved = realpath(__DIR__ . $uriPath);
    $docroot = realpath(__DIR__) ?: __DIR__;

    if ($resolved !== false && str_starts_with($resolved, $docroot) && is_file($resolved)) {
        return false;
    }
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = rtrim($path, '/');

$send404 = static function (): void {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
};

if ($path === '') {
    require __DIR__ . '/index.php';
    exit;
}

if (preg_match('~^/go/([A-Za-z0-9_-]+)$~', $path, $m)) {
    $_GET['code'] = $m[1];
    require __DIR__ . '/go.php';
    exit;
}

if ($path === '/search') {
    require __DIR__ . '/search.php';
    exit;
}

if ($path === '/clanky') {
    require __DIR__ . '/clanky/index.php';
    exit;
}

if (preg_match('~^/clanky/([a-z0-9-]+)$~', $path, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/article.php';
    exit;
}

if ($path === '/kategorie') {
    require __DIR__ . '/kategorie/index.php';
    exit;
}

if (preg_match('~^/kategorie/([a-z0-9-]+)$~', $path, $m)) {
    $slug = $m[1];
    $file = __DIR__ . '/kategorie/' . $slug . '.php';

    if (is_file($file)) {
        require $file;
        exit;
    }

    $_GET['slug'] = $slug;
    require __DIR__ . '/category.php';
    exit;
}

$send404();