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

if ($path === '') {
    require_once __DIR__ . '/index.php';
    exit;
}

$send404 = static function (): void {
    http_response_code(404);
    $file = __DIR__ . '/404.php';

    if (is_file($file)) {
        require_once $file;
    } else {
        include __DIR__ . '/inc/head.php';
        echo '<main id="obsah"><article class="container"><h1>Stranka sa nenasla (404)</h1></article></main>';
        include __DIR__ . '/inc/footer.php';
    }

    exit;
};

if (preg_match('~^/go/([A-Za-z0-9_-]+)$~', $path, $m)) {
    $_GET['code'] = $m[1];
    require_once __DIR__ . '/go.php';
    exit;
}

if ($path === '/search') {
    require_once __DIR__ . '/search.php';
    exit;
}

if ($path === '/clanky') {
    $file = __DIR__ . '/clanky/index.php';
    is_file($file) ? require_once $file : $send404();
    exit;
}

if (preg_match('~^/clanky/([a-z0-9-]+)$~', $path, $m)) {
    $slug = $m[1];
    $html = __DIR__ . "/content/articles/{$slug}.html";
    $php = __DIR__ . "/clanky/{$slug}.php";

    if (is_file($html)) {
        $meta = article_meta($slug);
        $page_title = $meta['title'] . ' | Interesa';
        $page_description = $meta['description'];

        include __DIR__ . '/inc/head.php';
        echo '<article class="container article-body">';
        readfile($html);
        echo '</article>';
        include __DIR__ . '/inc/footer.php';
        exit;
    }

    if (is_file($php)) {
        require_once $php;
        exit;
    }

    $send404();
}

if ($path === '/kategorie') {
    $file = __DIR__ . '/kategorie/index.php';
    is_file($file) ? require_once $file : $send404();
    exit;
}

if (preg_match('~^/kategorie/([a-z0-9-]+)$~', $path, $m)) {
    $slug = $m[1];
    $php = __DIR__ . "/kategorie/{$slug}.php";

    if (is_file($php)) {
        require_once $php;
        exit;
    }

    $send404();
}

$send404();
