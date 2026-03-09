<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = rtrim($path, '/');

if ($path === '') {
    require_once __DIR__ . '/index.php';
    exit;
}

$send404 = function () {
    http_response_code(404);
    $f = __DIR__ . '/404.php';
    if (is_file($f)) { require_once $f; }
    else {
        include __DIR__ . '/inc/head.php';
        echo '<main id="obsah"><article class="container"><h1>Stránka sa nenašla (404)</h1></article></main>';
        include __DIR__ . '/inc/footer.php';
    }
    exit;
};

# /clanky alebo /clanky/<slug>
if ($path === '/clanky') {
    $file = __DIR__ . '/clanky/index.php';
    return is_file($file) ? require_once $file : $send404();
}
if (preg_match('~^/clanky/([a-z0-9-]+)$~', $path, $m)) {
    $slug = $m[1];
    $php  = __DIR__ . "/clanky/{$slug}.php";
    $html = __DIR__ . "/content/articles/{$slug}.html";
    if (is_file($php)) { require_once $php; exit; }
    if (is_file($html)) {
        $page_title = humanize_slug($slug) . ' – Interesa';
        $page_description = null;
        include __DIR__ . '/inc/head.php';
        echo '<main id="obsah"><article class="container article-body">';
        readfile($html);
        echo '</article></main>';
        include __DIR__ . '/inc/footer.php';
        exit;
    }
    $send404();
}

# /kategorie alebo /kategorie/<slug>
if ($path === '/kategorie') {
    $file = __DIR__ . '/kategorie/index.php';
    return is_file($file) ? require_once $file : $send404();
}
if (preg_match('~^/kategorie/([a-z0-9-]+)$~', $path, $m)) {
    $slug = $m[1];
    $php  = __DIR__ . "/kategorie/{$slug}.php";
    return is_file($php) ? require_once $php : $send404();
}

# Iné cesty -> 404
$send404();
