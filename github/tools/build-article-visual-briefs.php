<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public/inc/functions.php';
require_once $root . '/public/inc/media.php';
require_once $root . '/public/inc/hero-prompts.php';

$slugs = [];
foreach (indexed_articles() as $item) {
    $slug = canonical_article_slug((string) ($item['slug'] ?? ''));
    if ($slug !== '') {
        $slugs[$slug] = true;
    }
}
ksort($slugs);

$rows = [];
foreach (array_keys($slugs) as $slug) {
    $meta = article_meta($slug);
    $promptMeta = interessa_hero_prompt_meta($slug);
    $hero = interessa_article_image_meta($slug, 'hero', true);
    $currentAsset = trim((string) ($hero['asset'] ?? ''));
    $currentStatus = 'fallback';
    if ($currentAsset !== '') {
        if (str_ends_with(strtolower($currentAsset), '.webp')) {
            $currentStatus = 'webp-ready';
        } elseif (str_ends_with(strtolower($currentAsset), '.svg')) {
            $currentStatus = 'svg-fallback';
        } else {
            $currentStatus = 'other-raster';
        }
    }

    $rows[] = [
        'slug' => $slug,
        'title' => (string) ($promptMeta['title'] ?? $meta['title'] ?? humanize_slug($slug)),
        'category' => (string) ($promptMeta['category'] ?? $meta['category'] ?? ''),
        'dimensions' => (string) ($promptMeta['dimensions'] ?? '1200x800'),
        'target_folder' => (string) ($promptMeta['target_folder'] ?? 'public/assets/img/articles/heroes/'),
        'target_filename' => (string) ($promptMeta['file_name'] ?? ($slug . '.webp')),
        'target_asset_path' => (string) ($promptMeta['asset_path'] ?? ('public/assets/img/articles/heroes/' . $slug . '.webp')),
        'current_asset' => $currentAsset,
        'current_status' => $currentStatus,
        'alt_text' => (string) ($promptMeta['alt_text'] ?? ''),
        'style_brief' => (string) ($promptMeta['style_brief'] ?? ''),
        'prompt' => (string) ($promptMeta['prompt'] ?? ''),
    ];
}

$csv = fopen($root . '/docs/article-visual-briefs.csv', 'wb');
fputcsv($csv, array_keys($rows[0] ?? [
    'slug' => '',
    'title' => '',
    'category' => '',
    'dimensions' => '',
    'target_folder' => '',
    'target_filename' => '',
    'target_asset_path' => '',
    'current_asset' => '',
    'current_status' => '',
    'alt_text' => '',
    'style_brief' => '',
    'prompt' => '',
]));
foreach ($rows as $row) {
    fputcsv($csv, $row);
}
fclose($csv);

echo "Article visual briefs refreshed.\n";
