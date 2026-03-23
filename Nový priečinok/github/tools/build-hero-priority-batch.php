<?php
declare(strict_types=1);

require __DIR__ . '/../public/inc/functions.php';
require __DIR__ . '/../public/inc/hero-prompts.php';

$priority = [
    'protein-na-chudnutie',
    'kreatin-porovnanie',
    'kolagen-recenzia',
    'horcik-ktory-je-najlepsi-a-preco',
    'imunita-prirodne-latky-ktore-funguju',
    'pre-workout-ako-vybrat',
    'probiotika-ako-vybrat',
    'veganske-proteiny-top-vyber-2025',
    'najlepsie-proteiny-2025',
    'najlepsi-protein-na-chudnutie-wpc-vs-wpi',
];

$items = [];
foreach ($priority as $slug) {
    $items[] = ['slug' => $slug] + interessa_hero_prompt_meta($slug);
}

$md = [];
$md[] = '# Hero Priority Batch';
$md[] = '';
$md[] = 'This file contains the first batch of 10 hero images to create.';
$md[] = 'Use these prompts for Canva or any image generator, export WebP, and save the file into `public/assets/img/articles/heroes/`.';
$md[] = '';
$md[] = 'Technical rules:';
$md[] = '- format: WebP';
$md[] = '- resolution: about 1200x800';
$md[] = '- target size: under 350 KB';
$md[] = '- no text inside the image';
$md[] = '- realistic modern health and fitness visual';
$md[] = '';
foreach ($items as $item) {
    $md[] = '## ' . $item['title'];
    $md[] = '- slug: `' . $item['slug'] . '`';
    $md[] = '- category: `' . $item['category'] . '`';
    $md[] = '- file: `' . $item['file_name'] . '`';
    $md[] = '- path: `' . $item['asset_path'] . '`';
    $md[] = '- alt text: `' . $item['alt_text'] . '`';
    $md[] = '- prompt: ' . $item['prompt'];
    $md[] = '';
}
file_put_contents(__DIR__ . '/../docs/hero-priority-batch-sk.md', implode(PHP_EOL, $md) . PHP_EOL);

$csv = fopen(__DIR__ . '/../docs/hero-priority-batch.csv', 'wb');
fputcsv($csv, ['slug', 'title', 'category', 'file_name', 'asset_path', 'alt_text', 'prompt', 'status']);
foreach ($items as $item) {
    fputcsv($csv, [
        $item['slug'],
        $item['title'],
        $item['category'],
        $item['file_name'],
        $item['asset_path'],
        $item['alt_text'],
        $item['prompt'],
        $item['status'],
    ]);
}
fclose($csv);

$backlog = [
    '# Image Backlog',
    '',
    'Every article already has its own SVG fallback in `public/assets/img/articles/heroes/`.',
    'The next step is to replace the first 10 commercially important articles with final WebP heroes.',
    '',
    '## Priority 1',
];
foreach ($priority as $slug) {
    $backlog[] = '- `' . $slug . '.webp`';
}
$backlog[] = '';
$backlog[] = '## Working files';
$backlog[] = '- `docs/hero-priority-batch-sk.md`';
$backlog[] = '- `docs/hero-priority-batch.csv`';
$backlog[] = '- `public/inc/hero-prompts.php`';
$backlog[] = '- `public/content/media/article-hero-prompts.php`';
$backlog[] = '- `tools/build-hero-priority-batch.php`';
$backlog[] = '';
$backlog[] = '## Workflow';
$backlog[] = '1. Open the priority batch.';
$backlog[] = '2. Generate or edit the WebP using the prompt.';
$backlog[] = '3. Save the file into `public/assets/img/articles/heroes/` using the exact slug name.';
$backlog[] = '4. Refresh the article or `hero-helper` and check that the SVG fallback is gone.';
file_put_contents(__DIR__ . '/../docs/image-backlog.md', implode(PHP_EOL, $backlog) . PHP_EOL);

echo "Hero priority batch refreshed.\n";
