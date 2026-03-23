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
    $articleTitle = trim((string) ($articleMeta['title'] ?? humanize_slug($articleSlug)));
    $articleTitle = function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake($articleTitle) : $articleTitle;

    foreach ($products as $row) {
        $resolved = interessa_resolve_product_reference(is_array($row) ? $row : []);
        $imageMode = trim((string) ($resolved['image_mode'] ?? 'placeholder'));
        if ($imageMode === 'local' || $imageMode === 'remote') {
            continue;
        }

        $productName = trim((string) ($resolved['product_name'] ?? $resolved['name'] ?? ''));
        $productName = function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake($productName) : $productName;
        $merchant = trim((string) ($resolved['merchant'] ?? ''));
        $merchant = function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake($merchant) : $merchant;

        $reference = [
            'article_slug' => $articleSlug,
            'article_title' => $articleTitle,
            'product_slug' => trim((string) ($resolved['slug'] ?? '')),
            'product_name' => $productName,
            'merchant' => $merchant,
            'affiliate_code' => trim((string) ($resolved['code'] ?? $resolved['affiliate_code'] ?? '')),
            'fallback_url' => trim((string) ($resolved['fallback_url'] ?? $resolved['url'] ?? '')),
            'target_asset' => trim((string) ($resolved['image_target_asset'] ?? '')),
            'image_mode' => $imageMode !== '' ? $imageMode : 'placeholder',
        ];

        $product = interessa_product((string) ($resolved['slug'] ?? ''));
        $reference['brief'] = is_array($product)
            ? interessa_product_packshot_brief($product)
            : interessa_product_packshot_brief_from_reference($reference);

        $rows[] = $reference;
    }
}

$csvPath = dirname(__DIR__) . '/docs/money-page-image-gaps.csv';
$mdPath = dirname(__DIR__) . '/docs/money-page-image-gaps-sk.md';

$csv = fopen($csvPath, 'wb');
if ($csv === false) {
    throw new RuntimeException('Nepodarilo sa otvorit CSV report pre image gaps.');
}
fputcsv($csv, ['article_slug', 'article_title', 'product_slug', 'product_name', 'merchant', 'affiliate_code', 'fallback_url', 'target_asset', 'image_mode', 'brief_filename', 'brief_alt_text', 'brief_dimensions', 'brief_prompt']);
foreach ($rows as $row) {
    $brief = is_array($row['brief'] ?? null) ? $row['brief'] : [];
    fputcsv($csv, [
        $row['article_slug'] ?? '',
        $row['article_title'] ?? '',
        $row['product_slug'] ?? '',
        $row['product_name'] ?? '',
        $row['merchant'] ?? '',
        $row['affiliate_code'] ?? '',
        $row['fallback_url'] ?? '',
        $row['target_asset'] ?? '',
        $row['image_mode'] ?? '',
        $brief['file_name'] ?? '',
        $brief['alt_text'] ?? '',
        $brief['dimensions'] ?? '',
        $brief['prompt'] ?? '',
    ]);
}
fclose($csv);

$grouped = [];
foreach ($rows as $row) {
    $grouped[$row['article_slug']][] = $row;
}

$merchantGrouped = [];
foreach ($rows as $row) {
    $merchantSlug = interessa_guess_slug_from_text((string) ($row['merchant'] ?? ''));
    if ($merchantSlug === '') {
        $merchantSlug = 'nezaradene';
    }

    if (!isset($merchantGrouped[$merchantSlug])) {
        $merchantGrouped[$merchantSlug] = [
            'merchant' => (string) ($row['merchant'] ?? 'Nezaradene'),
            'count' => 0,
            'articles' => [],
            'rows' => [],
        ];
    }

    $merchantGrouped[$merchantSlug]['count']++;
    $merchantGrouped[$merchantSlug]['articles'][(string) ($row['article_slug'] ?? '')] = true;
    $merchantGrouped[$merchantSlug]['rows'][] = $row;
}

uasort($merchantGrouped, static function (array $left, array $right): int {
    $countSort = ((int) ($right['count'] ?? 0)) <=> ((int) ($left['count'] ?? 0));
    if ($countSort !== 0) {
        return $countSort;
    }

    return strcasecmp((string) ($left['merchant'] ?? ''), (string) ($right['merchant'] ?? ''));
});

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
$markdown[] = '## Podla merchanta';
$markdown[] = '';
foreach ($merchantGrouped as $merchantGroup) {
    $markdown[] = '- ' . ($merchantGroup['merchant'] ?? 'Nezaradene') . ': ' . (int) ($merchantGroup['count'] ?? 0) . ' produktov / ' . count((array) ($merchantGroup['articles'] ?? [])) . ' clankov';
}
$markdown[] = '';

foreach ($merchantGrouped as $merchantGroup) {
    $markdown[] = '## Merchant: ' . ($merchantGroup['merchant'] ?? 'Nezaradene');
    $markdown[] = '';
    foreach ((array) ($merchantGroup['rows'] ?? []) as $row) {
        $line = '- ' . ($row['product_name'] !== '' ? $row['product_name'] : $row['product_slug']);
        $line .= ' -> /clanky/' . ($row['article_slug'] ?? '');
        if (($row['target_asset'] ?? '') !== '') {
            $line .= ' - asset: `' . $row['target_asset'] . '`';
        }
        $markdown[] = $line;
    }
    $markdown[] = '';
}

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
            $line .= ' - affiliate code: `' . $row['affiliate_code'] . '`';
        }
        if (($row['target_asset'] ?? '') !== '') {
            $line .= ' - asset: `' . $row['target_asset'] . '`';
        }
        $markdown[] = $line;
    }
    $markdown[] = '';
}

file_put_contents($mdPath, implode(PHP_EOL, $markdown) . PHP_EOL);

echo 'CSV: ' . $csvPath . PHP_EOL;
echo 'MD: ' . $mdPath . PHP_EOL;
