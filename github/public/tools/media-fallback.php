<?php
declare(strict_types=1);

define('INTERESA_SKIP_HTML_HEADER', 1);

require_once __DIR__ . '/../inc/functions.php';

$type = (string) ($_GET['type'] ?? 'article');
$type = in_array($type, ['article', 'category'], true) ? $type : 'article';
$context = (string) ($_GET['context'] ?? 'card');
$context = in_array($context, ['card', 'hero'], true) ? $context : 'card';
$slug = preg_replace('~[^a-z0-9\-_]+~i', '', (string) ($_GET['slug'] ?? '')) ?? '';

if ($slug === '') {
    http_response_code(404);
    exit;
}

if ($type === 'category') {
    $meta = category_meta($slug);
    $title = $meta['title'] ?? humanize_slug($slug);
    $eyebrow = 'KATEGORIA';
    $svg = interesa_media_svg('category', $slug, $title, $eyebrow, $context);
} else {
    $meta = article_meta($slug);
    $title = $meta['title'] ?? humanize_slug($slug);
    $category = $meta['category'] !== '' ? (category_meta($meta['category'])['title'] ?? strtoupper($meta['category'])) : 'CLANOK';
    $eyebrow = function_exists('mb_strtoupper') ? mb_strtoupper($category, 'UTF-8') : strtoupper($category);
    $svg = interesa_media_svg('article', $slug, $title, $eyebrow, $context);
}

header('Content-Type: image/svg+xml; charset=UTF-8');
header('Cache-Control: public, max-age=86400');

echo $svg;
