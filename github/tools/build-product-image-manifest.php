<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public/inc/functions.php';
require_once $root . '/public/inc/products.php';

$rows = [];
foreach (interessa_product_catalog() as $slug => $product) {
    $normalized = interessa_normalize_product($product);
    $mode = (string) ($normalized['image_mode'] ?? 'placeholder');
    $targetAsset = (string) ($normalized['image_target_asset'] ?? '');
    $targetPath = (string) ($normalized['image_target_path'] ?? '');
    $localExists = $targetPath !== '' && is_file($targetPath) ? 'yes' : 'no';

    $recommendedAction = match ($mode) {
        'local' => 'keep-local-asset',
        'remote' => 'mirror-merchant-packshot-to-local-webp',
        default => 'source-real-packshot-and-save-to-target-path',
    };

    $rows[] = [
        'slug' => (string) ($normalized['slug'] ?? $slug),
        'merchant' => (string) ($normalized['merchant'] ?? ''),
        'category' => (string) ($normalized['category'] ?? ''),
        'image_mode' => $mode,
        'image_source' => (string) ($normalized['image_source'] ?? ''),
        'feed_source' => (string) ($normalized['feed_source'] ?? ''),
        'local_target_asset' => $targetAsset,
        'local_target_exists' => $localExists,
        'remote_src' => (string) ($normalized['image_remote_src'] ?? ''),
        'recommended_action' => $recommendedAction,
    ];
}

$csv = fopen($root . '/docs/product-image-manifest.csv', 'wb');
fputcsv($csv, array_keys($rows[0] ?? [
    'slug' => '',
    'merchant' => '',
    'category' => '',
    'image_mode' => '',
    'image_source' => '',
    'feed_source' => '',
    'local_target_asset' => '',
    'local_target_exists' => '',
    'remote_src' => '',
    'recommended_action' => '',
]));
foreach ($rows as $row) {
    fputcsv($csv, $row);
}
fclose($csv);

echo "Product image manifest refreshed.\n";
