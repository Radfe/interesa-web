<?php
declare(strict_types=1);

require dirname(__DIR__) . '/public/inc/functions.php';
require dirname(__DIR__) . '/public/inc/products.php';
require dirname(__DIR__) . '/public/inc/article-commerce.php';

$articleSlugs = [
    'protein-na-chudnutie',
    'najlepsie-proteiny-2025',
    'kreatin-porovnanie',
    'horcik-ktory-je-najlepsi-a-preco',
    'probiotika-ako-vybrat',
    'pre-workout-ako-vybrat',
    'kolagen-recenzia',
    'veganske-proteiny-top-vyber-2025',
];

$rows = [];
foreach ($articleSlugs as $articleSlug) {
    $articleMeta = article_meta($articleSlug);
    $commerce = interessa_article_commerce($articleSlug);
    $products = is_array($commerce['products'] ?? null) ? $commerce['products'] : [];

    foreach ($products as $row) {
        $resolved = interessa_resolve_product_reference(is_array($row) ? $row : []);
        $imageMode = trim((string) ($resolved['image_mode'] ?? 'placeholder'));
        if ($imageMode === 'local' || $imageMode === 'remote') {
            continue;
        }

        $rows[] = [
            'article_slug' => $articleSlug,
            'article_title' => trim((string) ($articleMeta['title'] ?? humanize_slug($articleSlug))),
            'product_slug' => trim((string) ($resolved['slug'] ?? '')),
            'product_name' => trim((string) ($resolved['product_name'] ?? $resolved['name'] ?? '')),
            'merchant' => trim((string) ($resolved['merchant'] ?? '')),
            'affiliate_code' => trim((string) ($resolved['code'] ?? $resolved['affiliate_code'] ?? '')),
            'fallback_url' => trim((string) ($resolved['fallback_url'] ?? $resolved['url'] ?? '')),
            'image_mode' => $imageMode !== '' ? $imageMode : 'placeholder',
        ];
    }
}

$csvPath = dirname(__DIR__) . '/docs/money-page-image-gaps.csv';
$mdPath = dirname(__DIR__) . '/docs/money-page-image-gaps-sk.md';

$csv = fopen($csvPath, 'wb');
if ($csv === false) {
    throw new RuntimeException('Nepodarilo sa otvorit CSV report pre image gaps.');
}
fputcsv($csv, ['article_slug', 'article_title', 'product_slug', 'product_name', 'merchant', 'affiliate_code', 'fallback_url', 'image_mode']);
foreach ($rows as $row) {
    fputcsv($csv, $row);
}
fclose($csv);

$grouped = [];
foreach ($rows as $row) {
    $grouped[$row['article_slug']][] = $row;
}

$markdown = [];
$markdown[] = '# Money Page Image Gaps';
$markdown[] = '';
$markdown[] = 'Aktualizovane: ' . date('d.m.Y H:i');
$markdown[] = '';
$markdown[] = 'Tento report ukazuje produkty na hlavnych komercnych clankoch, ktore este nemaju realny lokalny ani merchant obrazok a stale padaju na editorialny vizual.';
$markdown[] = '';
$markdown[] = '## Suhrn';
$markdown[] = '';
$markdown[] = '- Pocet sledovanych money pages: ' . count($articleSlugs);
$markdown[] = '- Produkty bez realneho obrazku: ' . count($rows);
$markdown[] = '';

foreach ($articleSlugs as $articleSlug) {
    $articleRows = $grouped[$articleSlug] ?? [];
    if ($articleRows === []) {
        continue;
    }

    $articleTitle = $articleRows[0]['article_title'] ?? humanize_slug($articleSlug);
    $markdown[] = '## ' . $articleTitle;
    $markdown[] = '';
    $markdown[] = '- Clanok: `/clanky/' . $articleSlug . '`';
    $markdown[] = '- Chybajuce realne obrazky: ' . count($articleRows);
    $markdown[] = '';
    foreach ($articleRows as $row) {
        $line = '- ' . ($row['product_name'] !== '' ? $row['product_name'] : $row['product_slug']);
        if ($row['merchant'] !== '') {
            $line .= ' (' . $row['merchant'] . ')';
        }
        if ($row['affiliate_code'] !== '') {
            $line .= ' — affiliate code: `' . $row['affiliate_code'] . '`';
        }
        $markdown[] = $line;
    }
    $markdown[] = '';
}

file_put_contents($mdPath, implode(PHP_EOL, $markdown) . PHP_EOL);

echo 'CSV: ' . $csvPath . PHP_EOL;
echo 'MD: ' . $mdPath . PHP_EOL;
