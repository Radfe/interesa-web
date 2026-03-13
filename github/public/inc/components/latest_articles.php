<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/article-commerce.php';

$dir = dirname(__DIR__, 2) . '/content/articles';
$items = [];

if (is_dir($dir)) {
    foreach (glob($dir . '/*.html') ?: [] as $file) {
        $slug = basename($file, '.html');
        $canonicalSlug = canonical_article_slug($slug);
        $meta = article_meta($canonicalSlug);
        $title = trim((string) ($meta['title'] ?? ''));
        if ($title === '') {
            $title = humanize_slug($canonicalSlug);
        }

        $mtime = @filemtime($file) ?: time();
        $existing = $items[$canonicalSlug] ?? null;
        if (is_array($existing) && (int) ($existing['mtime'] ?? 0) >= $mtime) {
            continue;
        }

        $categorySlug = normalize_category_slug((string) ($meta['category'] ?? ''));
        $items[$canonicalSlug] = [
            'slug' => $canonicalSlug,
            'title' => function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake($title) : $title,
            'mtime' => $mtime,
            'category_meta' => $categorySlug !== '' ? category_meta($categorySlug) : null,
            'image' => interessa_article_image_meta($canonicalSlug, 'thumb', true),
        ];
    }
}

$items = array_values($items);
usort($items, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);
$items = array_slice($items, 0, 4);

echo '<article class="ad-card latest-articles">';
echo '<h3>' . esc(interessa_text('Najnovsie clanky')) . '</h3>';

if ($items === []) {
    echo '<p class="muted">' . esc(interessa_text('Zatial tu nie su ziadne clanky.')) . '</p>';
    echo '</article>';
    return;
}

echo '<ul class="latest-list">';
foreach ($items as $item) {
    $url = article_url((string) $item['slug']);
    $date = date('d.m.Y', (int) $item['mtime']);
    $categoryMeta = is_array($item['category_meta'] ?? null) ? $item['category_meta'] : null;
    $formatLabel = interessa_article_format_label((string) $item['slug'], (string) $item['title']);
    echo '<li class="latest-card">';
    echo '<a class="latest-card-thumb" href="' . esc($url) . '">';
    echo interessa_render_image((array) $item['image'], ['class' => 'latest-card-image', 'alt' => (string) $item['title']]);
    echo '</a>';
    echo '<div class="latest-card-body">';
    echo '<div class="latest-card-meta">';
    echo '<span class="article-card-chip is-format">' . esc($formatLabel) . '</span>';
    if ($categoryMeta !== null) {
        echo '<span class="article-card-chip">' . esc((string) ($categoryMeta['title'] ?? '')) . '</span>';
    }
    echo '<span class="date">' . esc($date) . '</span>';
    echo '</div>';
    echo '<a class="latest-card-title" href="' . esc($url) . '">' . esc((string) $item['title']) . '</a>';
    echo '</div>';
    echo '</li>';
}
echo '</ul>';
echo '</article>';
