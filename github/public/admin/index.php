<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/inc/functions.php';
require_once dirname(__DIR__) . '/inc/products.php';
require_once dirname(__DIR__) . '/inc/hero-prompts.php';
require_once dirname(__DIR__) . '/inc/admin-content.php';
require_once dirname(__DIR__) . '/inc/admin-auth.php';
require_once dirname(__DIR__) . '/inc/admin-feed-import.php';
require_once dirname(__DIR__) . '/inc/article-commerce.php';

function interessa_admin_selected_section(): string {
    $section = strtolower(trim((string) ($_GET['section'] ?? 'articles')));
    return in_array($section, ['articles', 'products', 'images', 'affiliates', 'tools', 'help'], true) ? $section : 'articles';
}

function interessa_admin_redirect(string $section, array $query = []): never {
    $query = array_filter($query, static fn(mixed $value): bool => (string) $value !== '');
    $query['section'] = $section;
    header('Location: /admin?' . http_build_query($query), true, 303);
    exit;
}

function interessa_admin_decode_json_textarea(string $value, string $label): array {
    $value = trim($value);
    if ($value === '') {
        return [];
    }

    $decoded = json_decode($value, true);
    if (!is_array($decoded)) {
        throw new RuntimeException($label . ' musi byt validne JSON pole.');
    }

    return $decoded;
}

function interessa_admin_collect_sections(): array {
    $headings = $_POST['section_heading'] ?? [];
    $bodies = $_POST['section_body'] ?? [];
    $sections = [];

    if (!is_array($headings) || !is_array($bodies)) {
        return $sections;
    }

    $count = max(count($headings), count($bodies));
    for ($i = 0; $i < $count; $i++) {
        $sections[] = [
            'heading' => trim((string) ($headings[$i] ?? '')),
            'body' => trim((string) ($bodies[$i] ?? '')),
        ];
    }

    return $sections;
}

function interessa_admin_article_starter(string $type, string $title, string $intro): array {
    $type = strtolower(trim($type));
    $title = trim($title);
    $intro = trim($intro);

    $base = [
        'title' => $title,
        'intro' => $intro,
        'meta_title' => $title,
        'meta_description' => $intro,
        'sections' => [],
        'comparison' => ['columns' => [], 'rows' => []],
        'recommended_products' => [],
    ];

    if ($type === 'comparison') {
        $base['sections'] = [
            ['heading' => 'Rychly vyber', 'body' => 'Kratky uvod do porovnania a pre koho je urcene.'],
            ['heading' => 'Ako vyberat', 'body' => 'Zhrnutie, na co sa pri porovnani zamerat.'],
        ];
        $base['comparison'] = [
            'title' => 'Rychle porovnanie',
            'intro' => 'Prehlad hlavnych rozdielov na jednom mieste.',
            'columns' => [
                ['key' => 'product', 'label' => 'Produkt', 'type' => 'product'],
                ['key' => 'best_for', 'label' => 'Najlepsie pre', 'type' => 'text'],
                ['key' => 'cta', 'label' => 'Odkaz', 'type' => 'cta'],
            ],
            'rows' => [],
        ];
        return $base;
    }

    if ($type === 'review') {
        $base['sections'] = [
            ['heading' => 'Pre koho je produkt', 'body' => 'Strucne zhrnutie, komu dava produkt zmysel.'],
            ['heading' => 'Co sa nam paci', 'body' => 'Silne stranky produktu a prakticke plusy.'],
            ['heading' => 'Na co si dat pozor', 'body' => 'Slabsie stranky, limity alebo kompromisy.'],
        ];
        return $base;
    }

    $base['sections'] = [
        ['heading' => 'Co sa v clanku dozvies', 'body' => 'Strucny uvod k teme a hlavny prinos clanku.'],
        ['heading' => 'Na co sa pri vybere zamerat', 'body' => 'Zhrnutie najdolezitejsich kriterii.'],
    ];
    return $base;
}

function interessa_admin_recommended_selection(): array {
    $manual = interessa_admin_lines_to_array((string) ($_POST['recommended_products'] ?? ''));
    $checked = interessa_admin_lines_to_array($_POST['recommended_product_checks'] ?? []);
    return array_values(array_unique(array_merge($manual, $checked)));
}

function interessa_admin_recommended_preview_rows(array $slugs): array {
    $rows = [];
    foreach (array_values(array_unique(array_filter(array_map('strval', $slugs)))) as $slug) {
        $product = interessa_product($slug);
        if (!is_array($product)) {
            continue;
        }
        $normalized = interessa_normalize_product($product);
        $target = interessa_affiliate_target($normalized);
        $rows[] = [
            'slug' => $slug,
            'name' => (string) ($normalized['name'] ?? $slug),
            'merchant' => (string) ($normalized['merchant'] ?? ''),
            'summary' => (string) ($normalized['summary'] ?? ''),
            'image' => $normalized['image'] ?? null,
            'href' => (string) ($target['href'] ?? ''),
            'label' => (string) ($target['label'] ?? ''),
        ];
    }
    return $rows;
}

function interessa_admin_missing_product_rows(array $slugs, string $defaultCategory = ''): array {
    $rows = [];
    foreach (array_values(array_unique(array_filter(array_map('strval', $slugs)))) as $slug) {
        if ($slug === '' || interessa_product($slug) !== null) {
            continue;
        }

        $rows[] = [
            'slug' => $slug,
            'name' => humanize_slug($slug),
            'category' => normalize_category_slug($defaultCategory),
        ];
    }
    return $rows;
}


function interessa_admin_recommended_diagnostics(array $slugs): array {
    $rows = [];
    $slugs = array_values(array_unique(array_filter(array_map('strval', $slugs))));
    $summary = [
        'total' => count($slugs),
        'catalog' => 0,
        'missing_catalog' => 0,
        'affiliate_ready' => 0,
        'packshot_ready' => 0,
        'money_ready' => 0,
        'card_ready' => 0,
    ];

    foreach ($slugs as $slug) {
        $product = interessa_product($slug);
        if (!is_array($product)) {
            $summary['missing_catalog']++;
            $rows[] = [
                'slug' => $slug,
                'name' => humanize_slug($slug),
                'merchant' => '',
                'exists' => false,
                'affiliate_code' => '',
                'issues' => ['Produkt nie je v reusable katalogu'],
                'checklist_ready_count' => 0,
                'checklist_total' => 6,
                'checklist_percent' => 0,
                'summary_ready' => false,
                'rating_ready' => false,
                'pros_ready' => false,
                'cons_ready' => false,
                'affiliate_ready' => false,
                'has_click_target' => false,
                'packshot_ready' => false,
                'money_ready' => false,
                'card_ready' => false,
                'image_mode' => 'missing',
                'image_target_asset' => '',
                'href' => '',
                'summary' => '',
                'rating' => '',
            ];
            continue;
        }

        $normalized = interessa_normalize_product($product);
        $target = interessa_affiliate_target($normalized);
        $affiliateCode = trim((string) ($normalized['affiliate_code'] ?? ''));
        $resolved = $affiliateCode !== '' ? aff_resolve($affiliateCode) : null;
        $affiliateReady = is_array($resolved) && trim((string) ($resolved['href'] ?? '')) !== '';
        $hasClickTarget = trim((string) ($target['href'] ?? '')) !== '';
        $imageMode = trim((string) ($normalized['image_mode'] ?? 'placeholder'));
        $packshotReady = $imageMode !== '' && $imageMode !== 'placeholder';
        $moneyReady = $affiliateReady && $packshotReady;
        $pros = array_values(array_filter(array_map('trim', (array) ($normalized['pros'] ?? []))));
        $cons = array_values(array_filter(array_map('trim', (array) ($normalized['cons'] ?? []))));
        $summaryReady = trim((string) ($normalized['summary'] ?? '')) !== '';
        $ratingReady = trim((string) ($normalized['rating'] ?? '')) !== '';
        $prosReady = $pros !== [];
        $consReady = $cons !== [];
        $cardReady = $summaryReady && $ratingReady && $prosReady && $consReady && $moneyReady;
        $issues = [];

        if (!$summaryReady) {
            $issues[] = 'Chyba kratky popis';
        }
        if (!$ratingReady) {
            $issues[] = 'Chyba rating';
        }
        if (!$prosReady) {
            $issues[] = 'Chybaju plusy';
        }
        if (!$consReady) {
            $issues[] = 'Chybaju minusy';
        }
        if (!$affiliateReady) {
            $issues[] = 'Affiliate nie je hotovy';
        }
        if (!$packshotReady) {
            $issues[] = 'Obrazok produktu nie je hotovy';
        }

        $summary['catalog']++;
        if ($affiliateReady) {
            $summary['affiliate_ready']++;
        }
        if ($packshotReady) {
            $summary['packshot_ready']++;
        }
        if ($moneyReady) {
            $summary['money_ready']++;
        }
        if ($cardReady) {
            $summary['card_ready']++;
        }

        $rows[] = [
            'slug' => $slug,
            'name' => (string) ($normalized['name'] ?? $slug),
            'merchant' => (string) ($normalized['merchant'] ?? ''),
            'exists' => true,
            'affiliate_code' => $affiliateCode,
            'issues' => $issues,
            'checklist_ready_count' => 6 - count($issues),
            'checklist_total' => 6,
            'checklist_percent' => (int) round(((6 - count($issues)) / 6) * 100),
            'summary_ready' => $summaryReady,
            'rating_ready' => $ratingReady,
            'pros_ready' => $prosReady,
            'cons_ready' => $consReady,
            'affiliate_ready' => $affiliateReady,
            'has_click_target' => $hasClickTarget,
            'packshot_ready' => $packshotReady,
            'money_ready' => $moneyReady,
            'card_ready' => $cardReady,
            'image_mode' => $imageMode,
            'image_target_asset' => (string) ($normalized['image_target_asset'] ?? ''),
            'href' => trim((string) ($target['href'] ?? '')),
            'summary' => (string) ($normalized['summary'] ?? ''),
            'rating' => trim((string) ($normalized['rating'] ?? '')),
        ];
    }

    $actionRows = array_values(array_filter($rows, static function (array $row): bool {
        return empty($row['card_ready']);
    }));

    usort($actionRows, static function (array $left, array $right): int {
        return (int) ($right['checklist_percent'] ?? 0) <=> (int) ($left['checklist_percent'] ?? 0);
    });

    return [
        'summary' => $summary,
        'rows' => $rows,
        'action_rows' => $actionRows,
    ];
}

function interessa_admin_collect_comparison_visual(): array {
    $keys = $_POST['comparison_column_key'] ?? [];
    $labels = $_POST['comparison_column_label'] ?? [];
    $types = $_POST['comparison_column_type'] ?? [];
    $columns = [];

    if (is_array($keys) && is_array($labels) && is_array($types)) {
        $count = max(count($keys), count($labels), count($types));
        for ($i = 0; $i < $count; $i++) {
            $key = interessa_admin_slugify($keys[$i] ?? '');
            $label = interessa_admin_normalize_text($labels[$i] ?? '');
            $type = strtolower(trim((string) ($types[$i] ?? 'text')));
            if ($key === '' || $label === '') {
                continue;
            }
            if (!in_array($type, ['text', 'product', 'cta'], true)) {
                $type = 'text';
            }
            $columns[] = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
            ];
        }
    }

    $rows = [];
    if ($columns !== []) {
        $rowCount = 0;
        foreach (range(0, count($columns) - 1) as $index) {
            $values = $_POST['comparison_cell_' . $index] ?? [];
            if (is_array($values)) {
                $rowCount = max($rowCount, count($values));
            }
        }

        for ($rowIndex = 0; $rowIndex < $rowCount; $rowIndex++) {
            $row = [];
            foreach ($columns as $columnIndex => $column) {
                $cellValues = $_POST['comparison_cell_' . $columnIndex] ?? [];
                $value = is_array($cellValues) ? trim((string) ($cellValues[$rowIndex] ?? '')) : '';
                if ($value !== '') {
                    $row[$column['key']] = $value;
                }
            }
            if ($row !== []) {
                $rows[] = $row;
            }
        }
    }

    return [
        'columns' => $columns,
        'rows' => $rows,
    ];
}

function interessa_admin_article_options(): array {
    $items = indexed_articles();
    foreach ($items as $slug => $item) {
        $title = trim((string) ($item['title'] ?? ''));
        $items[$slug]['title'] = interessa_admin_clean_label($title !== '' ? $title : (string) $slug);
    }

    uasort($items, static function (array $left, array $right): int {
        return strcasecmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
    });

    return $items;
}

function interessa_admin_article_default_products(string $slug, array $catalog): array {
    $slug = canonical_article_slug($slug);
    if ($slug === '') {
        return [];
    }

    $section = function_exists('interessa_article_commerce') ? interessa_article_commerce($slug) : null;
    if (!is_array($section) || !is_array($section['products'] ?? null)) {
        return [];
    }

    $codes = [];
    foreach ($section['products'] as $row) {
        if (!is_array($row)) {
            continue;
        }

        $code = trim((string) ($row['code'] ?? ''));
        if ($code !== '') {
            $codes[] = $code;
        }
    }

    if ($codes === []) {
        return [];
    }

    $slugs = [];
    foreach ($catalog as $productSlug => $product) {
        $normalized = interessa_normalize_product(is_array($product) ? $product : []);
        $affiliateCode = trim((string) ($normalized['affiliate_code'] ?? ''));
        if ($affiliateCode !== '' && in_array($affiliateCode, $codes, true)) {
            $slugs[] = (string) ($normalized['slug'] ?? $productSlug);
        }
    }

    return array_values(array_unique(array_filter(array_map('strval', $slugs))));
}

function interessa_admin_clean_label(string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (function_exists('interessa_fix_mojibake')) {
        $value = interessa_fix_mojibake($value);
    }

    return trim($value);
}

function interessa_admin_comparison_editor_state(array $comparison): array {
    $sourceColumns = is_array($comparison['columns'] ?? null) ? array_values($comparison['columns']) : [];
    $sourceRows = is_array($comparison['rows'] ?? null) ? array_values($comparison['rows']) : [];
    $columnCount = max(count($sourceColumns), 1);
    $rowCount = max(count($sourceRows), 1);
    $columns = [];

    foreach (range(0, $columnCount - 1) as $index) {
        $columns[$index] = [
            'key' => (string) ($sourceColumns[$index]['key'] ?? ''),
            'label' => (string) ($sourceColumns[$index]['label'] ?? ''),
            'type' => (string) ($sourceColumns[$index]['type'] ?? 'text'),
        ];
    }

    $rows = [];
    foreach (range(0, $rowCount - 1) as $rowIndex) {
        $rowCells = [];
        foreach ($columns as $column) {
            $key = (string) ($column['key'] ?? '');
            $rowCells[] = $key !== '' ? (string) ($sourceRows[$rowIndex][$key] ?? '') : '';
        }
        $rows[] = $rowCells;
    }

    return [
        'columns' => $columns,
        'rows' => $rows,
    ];
}

function interessa_admin_brief_rows(array $articleOptions): array {
    $rows = [];
    foreach ($articleOptions as $slug => $item) {
        $brief = interessa_hero_prompt_meta($slug);
        $rows[] = [
            'slug' => $slug,
            'title' => interessa_admin_clean_label((string) ($item['title'] ?? $brief['title'] ?? $slug)),
            'filename' => (string) ($brief['file_name'] ?? ''),
            'alt_text' => (string) ($brief['alt_text'] ?? ''),
            'dimensions' => (string) ($brief['dimensions'] ?? '1200x800'),
            'asset_path' => (string) ($brief['asset_path'] ?? ''),
            'prompt' => (string) ($brief['prompt'] ?? ''),
        ];
    }
    return $rows;
}

function interessa_admin_image_queue(array $articleOptions, string $filter = 'missing', int $limit = 12): array {
    $rows = [];
    foreach ($articleOptions as $slug => $item) {
        $meta = interessa_article_image_meta((string) $slug, 'hero', true);
        $src = (string) ($meta['src'] ?? '');
        $isFinalWebp = str_ends_with(strtolower($src), '.webp');
        $promptMeta = interessa_hero_prompt_meta((string) $slug);
        $rows[] = [
            'slug' => (string) $slug,
            'title' => interessa_admin_clean_label((string) ($item['title'] ?? $slug)),
            'asset_path' => (string) ($promptMeta['asset_path'] ?? ''),
            'file_name' => (string) ($promptMeta['file_name'] ?? ''),
            'alt_text' => (string) ($promptMeta['alt_text'] ?? ''),
            'dimensions' => (string) ($promptMeta['dimensions'] ?? '1200x800'),
            'prompt' => (string) ($promptMeta['prompt'] ?? ''),
            'has_final_webp' => $isFinalWebp,
            'source_type' => (string) ($meta['source_type'] ?? 'placeholder'),
            'article_url' => article_url((string) $slug),
        ];
    }

    if ($filter === 'missing') {
        $rows = array_values(array_filter($rows, static fn(array $row): bool => empty($row['has_final_webp'])));
    } elseif ($filter === 'ready') {
        $rows = array_values(array_filter($rows, static fn(array $row): bool => !empty($row['has_final_webp'])));
    }

    usort($rows, static function (array $left, array $right): int {
        $statusSort = ((int) $left['has_final_webp']) <=> ((int) $right['has_final_webp']);
        if ($statusSort !== 0) {
            return $statusSort;
        }

        return strcasecmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
    });

    return array_slice($rows, 0, $limit);
}

function interessa_admin_product_image_queue(array $catalog, string $filter = 'missing', int $limit = 12): array {
    $rows = [];
    foreach ($catalog as $slug => $product) {
        $normalized = interessa_normalize_product(is_array($product) ? $product : []);
        $mode = trim((string) ($normalized['image_mode'] ?? 'placeholder'));
        $targetAsset = trim((string) ($normalized['image_target_asset'] ?? ''));
        $localAsset = trim((string) ($normalized['image_local_asset'] ?? ''));
        $hasLocalPackshot = !empty($normalized['has_local_image']);
        $rows[] = [
            'slug' => (string) ($normalized['slug'] ?? $slug),
            'name' => interessa_admin_clean_label((string) ($normalized['name'] ?? $slug)),
            'merchant' => (string) ($normalized['merchant'] ?? ''),
            'affiliate_code' => (string) ($normalized['affiliate_code'] ?? ''),
            'image_mode' => $mode,
            'target_asset' => $hasLocalPackshot && $localAsset !== '' ? $localAsset : $targetAsset,
            'upload_target_asset' => $targetAsset,
            'needs_local_packshot' => !$hasLocalPackshot,
            'remote_src' => (string) ($normalized['image_remote_src'] ?? ''),
        ];
    }

    if ($filter === 'missing') {
        $rows = array_values(array_filter($rows, static fn(array $row): bool => !empty($row['needs_local_packshot'])));
    } elseif ($filter === 'ready') {
        $rows = array_values(array_filter($rows, static fn(array $row): bool => empty($row['needs_local_packshot'])));
    } elseif ($filter === 'remote') {
        $rows = array_values(array_filter($rows, static fn(array $row): bool => ($row['image_mode'] ?? '') === 'remote'));
    } elseif ($filter === 'placeholder') {
        $rows = array_values(array_filter($rows, static fn(array $row): bool => ($row['image_mode'] ?? '') === 'placeholder'));
    }

    usort($rows, static function (array $left, array $right): int {
        $statusSort = ((int) $right['needs_local_packshot']) <=> ((int) $left['needs_local_packshot']);
        if ($statusSort !== 0) {
            return $statusSort;
        }

        return strcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
    });

    return array_slice($rows, 0, $limit);
}

function interessa_admin_product_affiliate_queue(array $catalog, int $limit = 12): array {
    $rows = [];
    foreach ($catalog as $slug => $product) {
        $normalized = interessa_normalize_product(is_array($product) ? $product : []);
        $productSlug = (string) ($normalized['slug'] ?? $slug);
        $affiliateCode = trim((string) ($normalized['affiliate_code'] ?? ''));
        $record = $affiliateCode !== '' ? aff_record($affiliateCode) : null;
        $resolved = $affiliateCode !== '' ? aff_resolve($affiliateCode) : null;
        $status = '';

        if ($affiliateCode === '') {
            $status = 'chyba affiliate kod';
        } elseif (!is_array($record)) {
            $status = 'kod nie je v registry';
        } elseif ($resolved === null) {
            $status = 'link sa neda vyriesit';
        }

        if ($status === '') {
            continue;
        }

        $rows[] = [
            'slug' => $productSlug,
            'name' => interessa_admin_clean_label((string) ($normalized['name'] ?? $productSlug)),
            'merchant' => (string) ($normalized['merchant'] ?? ''),
            'affiliate_code' => $affiliateCode,
            'status' => $status,
            'fallback_url' => (string) ($normalized['fallback_url'] ?? ''),
        ];
    }

    usort($rows, static function (array $left, array $right): int {
        return strcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
    });

    return array_slice($rows, 0, $limit);
}


function interessa_admin_product_quality_queue(array $catalog, int $limit = 12): array {
    $rows = [];
    foreach ($catalog as $slug => $product) {
        $normalized = interessa_normalize_product(is_array($product) ? $product : []);
        $productSlug = (string) ($normalized['slug'] ?? $slug);
        $pros = is_array($normalized['pros'] ?? null) ? array_values(array_filter(array_map('strval', $normalized['pros']))) : [];
        $cons = is_array($normalized['cons'] ?? null) ? array_values(array_filter(array_map('strval', $normalized['cons']))) : [];
        $affiliateCode = trim((string) ($normalized['affiliate_code'] ?? ''));
        $resolved = $affiliateCode !== '' ? aff_resolve($affiliateCode) : null;
        $affiliateReady = is_array($resolved) && trim((string) ($resolved['href'] ?? '')) !== '';
        $packshotReady = trim((string) ($normalized['image_mode'] ?? 'placeholder')) !== 'placeholder';

        $issues = [];
        if (trim((string) ($normalized['summary'] ?? '')) === '') {
            $issues[] = 'Chyba kratky popis';
        }
        if (trim((string) ($normalized['rating'] ?? '')) === '') {
            $issues[] = 'Chyba rating';
        }
        if ($pros === []) {
            $issues[] = 'Chybaju plusy';
        }
        if ($cons === []) {
            $issues[] = 'Chybaju minusy';
        }
        if (!$affiliateReady) {
            $issues[] = 'Affiliate nie je hotovy';
        }
        if (!$packshotReady) {
            $issues[] = 'Obrazok produktu nie je hotovy';
        }

        if ($issues === []) {
            continue;
        }

        $rows[] = [
            'slug' => $productSlug,
            'name' => interessa_admin_clean_label((string) ($normalized['name'] ?? $productSlug)),
            'merchant' => (string) ($normalized['merchant'] ?? ''),
            'issues' => $issues,
            'summary_ready' => trim((string) ($normalized['summary'] ?? '')) !== '',
            'rating_ready' => trim((string) ($normalized['rating'] ?? '')) !== '',
            'pros_ready' => $pros !== [],
            'cons_ready' => $cons !== [],
            'affiliate_ready' => $affiliateReady,
            'packshot_ready' => $packshotReady,
            'affiliate_code' => $affiliateCode,
        ];
    }

    usort($rows, static function (array $left, array $right): int {
        return count($right['issues'] ?? []) <=> count($left['issues'] ?? []);
    });

    return array_slice($rows, 0, max($limit, 1));
}
function interessa_admin_output_image_backlog_csv(array $articleOptions, array $catalog): never {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="interesa-image-backlog.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['type', 'slug', 'title', 'merchant', 'status', 'asset_path', 'code']);
    foreach (interessa_admin_image_queue($articleOptions, 'all', max(count($articleOptions), 1)) as $row) {
        fputcsv($out, [
            'hero',
            $row['slug'] ?? '',
            $row['title'] ?? '',
            '',
            !empty($row['has_final_webp']) ? 'ready' : 'missing',
            $row['asset_path'] ?? '',
            '',
        ]);
    }
    foreach (interessa_admin_product_image_queue($catalog, 'all', max(count($catalog), 1)) as $row) {
        fputcsv($out, [
            'product',
            $row['slug'] ?? '',
            $row['name'] ?? '',
            $row['merchant'] ?? '',
            !empty($row['needs_local_packshot']) ? 'missing-local-packshot' : 'ready',
            $row['target_asset'] ?? '',
            $row['affiliate_code'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

function interessa_admin_output_briefs_csv(array $rows): never {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="article-visual-briefs-admin.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['slug', 'title', 'filename', 'alt_text', 'dimensions', 'asset_path', 'prompt']);
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['slug'] ?? '',
            $row['title'] ?? '',
            $row['filename'] ?? '',
            $row['alt_text'] ?? '',
            $row['dimensions'] ?? '',
            $row['asset_path'] ?? '',
            $row['prompt'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}
$page_title = 'Admin | Interesa';
$page_description = 'Interny admin panel pre clanky, produkty, obrazky a affiliate odkazy.';
$page_canonical = '/admin';
$page_robots = 'noindex,nofollow';
$page_styles = [asset('css/admin.css')];
$page_scripts = [asset('js/admin.js')];

$section = interessa_admin_selected_section();
$flash = trim((string) ($_GET['saved'] ?? ''));
$error = '';

interessa_admin_session_boot();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'login') {
        $password = (string) ($_POST['password'] ?? '');
        if (interessa_admin_attempt_login($password)) {
            interessa_admin_redirect($section, ['saved' => 'login']);
        }
        $error = 'Nespravne heslo pre admin.';
    }

    if ($action === 'logout') {
        interessa_admin_logout();
        header('Location: /admin', true, 303);
        exit;
    }
}

$isAuthed = interessa_admin_is_authenticated();
$config = interessa_admin_auth_config();
$importSummary = '';
$articleOptions = interessa_admin_article_options();

if ($isAuthed) {
    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = trim((string) ($_POST['action'] ?? ''));

            if ($action === 'delete_article_override') {
                $slug = canonical_article_slug(trim((string) ($_POST['slug'] ?? '')));
                interessa_admin_delete_article_override($slug);
                interessa_admin_redirect('articles', ['slug' => $slug, 'saved' => 'article-reset']);
            }

            if ($action === 'create_article') {
                $slugInput = trim((string) ($_POST['new_article_slug'] ?? ''));
                $titleInput = trim((string) ($_POST['new_article_title'] ?? ''));
                $articleType = trim((string) ($_POST['new_article_type'] ?? 'guide'));
                $slug = interessa_admin_slugify($slugInput !== '' ? $slugInput : $titleInput);
                if ($slug === '') {
                    throw new RuntimeException('Vypln slug alebo titulok noveho clanku.');
                }

                $payload = interessa_admin_article_starter(
                    $articleType,
                    $titleInput,
                    (string) ($_POST['new_article_intro'] ?? '')
                );
                $payload['category'] = (string) ($_POST['new_article_category'] ?? '');

                interessa_admin_save_article_override($slug, $payload);
                interessa_admin_redirect('articles', ['slug' => $slug, 'saved' => 'article-created']);
            }

            if ($action === 'save_article') {
                $slug = canonical_article_slug(trim((string) ($_POST['slug'] ?? '')));
                $visualComparison = interessa_admin_collect_comparison_visual();
                $comparisonColumns = $visualComparison['columns'] !== []
                    ? $visualComparison['columns']
                    : interessa_admin_decode_json_textarea((string) ($_POST['comparison_columns_json'] ?? ''), 'Stlpce porovnania');
                $comparisonRows = $visualComparison['rows'] !== []
                    ? $visualComparison['rows']
                    : interessa_admin_decode_json_textarea((string) ($_POST['comparison_rows_json'] ?? ''), 'Riadky porovnania');
                $recommended = interessa_admin_recommended_selection();
                $payload = [
                    'title' => (string) ($_POST['title'] ?? ''),
                    'intro' => (string) ($_POST['intro'] ?? ''),
                    'meta_title' => (string) ($_POST['meta_title'] ?? ''),
                    'meta_description' => (string) ($_POST['meta_description'] ?? ''),
                    'category' => (string) ($_POST['category'] ?? ''),
                    'hero_asset' => (string) ($_POST['hero_asset'] ?? ''),
                    'sections' => interessa_admin_collect_sections(),
                    'comparison' => [
                        'title' => (string) ($_POST['comparison_title'] ?? ''),
                        'intro' => (string) ($_POST['comparison_intro'] ?? ''),
                        'columns' => $comparisonColumns,
                        'rows' => $comparisonRows,
                    ],
                    'recommended_products' => $recommended,
                ];

                if (!empty($_FILES['hero_image']['tmp_name'])) {
                    $payload['hero_asset'] = interessa_admin_store_uploaded_article_hero($slug, $_FILES['hero_image']);
                }

                interessa_admin_save_article_override($slug, $payload);
                interessa_admin_redirect('articles', ['slug' => $slug, 'saved' => 'article']);
            }

            if ($action === 'delete_product_override') {
                $slug = trim((string) ($_POST['product_slug'] ?? ''));
                interessa_admin_delete_product_record($slug);
                interessa_admin_redirect('products', ['product' => $slug, 'saved' => 'product-reset']);
            }

            if ($action === 'create_product') {
                $slugInput = trim((string) ($_POST['new_product_slug'] ?? ''));
                $nameInput = trim((string) ($_POST['new_product_name'] ?? ''));
                $merchantInput = trim((string) ($_POST['new_product_merchant'] ?? ''));
                $merchantSlugInput = trim((string) ($_POST['new_product_merchant_slug'] ?? ''));
                $returnSection = trim((string) ($_POST['return_section'] ?? ''));
                $returnSlug = canonical_article_slug(trim((string) ($_POST['return_slug'] ?? '')));
                $slug = interessa_admin_slugify($slugInput !== '' ? $slugInput : $nameInput);
                if ($slug === '') {
                    throw new RuntimeException('Vypln slug alebo nazov noveho produktu.');
                }

                interessa_admin_save_product_record($slug, [
                    'name' => $nameInput,
                    'brand' => (string) ($_POST['new_product_brand'] ?? ''),
                    'merchant' => $merchantInput,
                    'merchant_slug' => $merchantSlugInput !== '' ? $merchantSlugInput : interessa_admin_slugify($merchantInput),
                    'category' => (string) ($_POST['new_product_category'] ?? ''),
                    'affiliate_code' => (string) ($_POST['new_product_affiliate_code'] ?? ''),
                    'fallback_url' => (string) ($_POST['new_product_fallback_url'] ?? ''),
                    'summary' => (string) ($_POST['new_product_summary'] ?? ''),
                    'rating' => '0',
                    'pros' => '',
                    'cons' => '',
                    'image_remote_src' => (string) ($_POST['new_product_image_remote_src'] ?? ''),
                ]);
                if ($returnSection === 'articles' && $returnSlug !== '') {
                    interessa_admin_redirect('articles', ['slug' => $returnSlug, 'add_product' => $slug, 'saved' => 'product-created']);
                }
                interessa_admin_redirect('products', ['product' => $slug, 'saved' => 'product-created']);
            }

            if ($action === 'save_product') {
                $slug = trim((string) ($_POST['product_slug'] ?? ''));
                $merchantSlug = trim((string) ($_POST['merchant_slug'] ?? ''));
                $payload = [
                    'name' => (string) ($_POST['name'] ?? ''),
                    'brand' => (string) ($_POST['brand'] ?? ''),
                    'merchant' => (string) ($_POST['merchant'] ?? ''),
                    'merchant_slug' => $merchantSlug,
                    'category' => (string) ($_POST['category'] ?? ''),
                    'affiliate_code' => (string) ($_POST['affiliate_code'] ?? ''),
                    'fallback_url' => (string) ($_POST['fallback_url'] ?? ''),
                    'summary' => (string) ($_POST['summary'] ?? ''),
                    'rating' => (string) ($_POST['rating'] ?? ''),
                    'pros' => (string) ($_POST['pros'] ?? ''),
                    'cons' => (string) ($_POST['cons'] ?? ''),
                    'image_remote_src' => (string) ($_POST['image_remote_src'] ?? ''),
                ];

                if (!empty($_FILES['product_image']['tmp_name'])) {
                    $payload['image_asset'] = interessa_admin_store_uploaded_product_image($slug, $merchantSlug, $_FILES['product_image']);
                }

                interessa_admin_save_product_record($slug, $payload);
                $returnSection = trim((string) ($_POST['return_section'] ?? ''));
                $returnSlug = canonical_article_slug(trim((string) ($_POST['return_slug'] ?? '')));
                if ($returnSection === 'images' && $returnSlug !== '') {
                    interessa_admin_redirect('images', ['slug' => $returnSlug, 'saved' => 'product']);
                }
                if ($returnSection === 'articles' && $returnSlug !== '') {
                    interessa_admin_redirect('articles', ['slug' => $returnSlug, 'saved' => 'product']);
                }
                interessa_admin_redirect('products', ['product' => $slug, 'saved' => 'product']);
            }

            if ($action === 'delete_affiliate_override') {
                $code = trim((string) ($_POST['code'] ?? ''));
                interessa_admin_delete_affiliate_record($code);
                interessa_admin_redirect('affiliates', ['code' => $code, 'saved' => 'affiliate-reset']);
            }

            if ($action === 'create_affiliate') {
                $codeInput = trim((string) ($_POST['new_affiliate_code'] ?? ''));
                $productSlugInput = trim((string) ($_POST['new_affiliate_product_slug'] ?? ''));
                $merchantSlugInput = trim((string) ($_POST['new_affiliate_merchant_slug'] ?? ''));
                $code = interessa_admin_slugify($codeInput !== '' ? $codeInput : $productSlugInput);
                if ($code === '') {
                    throw new RuntimeException('Vypln kod alebo product slug noveho affiliate odkazu.');
                }

                interessa_admin_save_affiliate_record($code, [
                    'url' => '',
                    'merchant' => (string) ($_POST['new_affiliate_merchant'] ?? ''),
                    'merchant_slug' => $merchantSlugInput,
                    'product_slug' => $productSlugInput,
                    'link_type' => (string) ($_POST['new_affiliate_link_type'] ?? 'affiliate'),
                ]);
                $returnSection = trim((string) ($_POST['return_section'] ?? ''));
                $returnSlug = canonical_article_slug(trim((string) ($_POST['return_slug'] ?? '')));
                if ($returnSection === 'images' && $returnSlug !== '') {
                    interessa_admin_redirect('images', ['slug' => $returnSlug, 'saved' => 'affiliate-created']);
                }
                if ($returnSection === 'articles' && $returnSlug !== '') {
                    interessa_admin_redirect('articles', ['slug' => $returnSlug, 'saved' => 'affiliate-created']);
                }
                interessa_admin_redirect('affiliates', ['code' => $code, 'saved' => 'affiliate-created']);
            }

            if ($action === 'save_affiliate') {
                $code = trim((string) ($_POST['code'] ?? ''));
                interessa_admin_save_affiliate_record($code, [
                    'url' => (string) ($_POST['url'] ?? ''),
                    'merchant' => (string) ($_POST['merchant'] ?? ''),
                    'merchant_slug' => (string) ($_POST['merchant_slug'] ?? ''),
                    'product_slug' => (string) ($_POST['product_slug'] ?? ''),
                    'link_type' => (string) ($_POST['link_type'] ?? 'affiliate'),
                ]);
                $returnSection = trim((string) ($_POST['return_section'] ?? ''));
                $returnSlug = canonical_article_slug(trim((string) ($_POST['return_slug'] ?? '')));
                if ($returnSection === 'images' && $returnSlug !== '') {
                    interessa_admin_redirect('images', ['slug' => $returnSlug, 'saved' => 'affiliate']);
                }
                if ($returnSection === 'articles' && $returnSlug !== '') {
                    interessa_admin_redirect('articles', ['slug' => $returnSlug, 'saved' => 'affiliate']);
                }
                interessa_admin_redirect('affiliates', ['code' => $code, 'saved' => 'affiliate']);
            }

            if ($action === 'upload_hero_only') {
                $slug = canonical_article_slug(trim((string) ($_POST['slug'] ?? '')));
                $asset = interessa_admin_store_uploaded_article_hero($slug, $_FILES['hero_image']);
                $override = interessa_admin_article_override($slug);
                $override['hero_asset'] = $asset;
                interessa_admin_save_article_override($slug, $override);
                interessa_admin_redirect('images', ['slug' => $slug, 'saved' => 'hero']);
            }
            if ($action === 'upload_packshot_only') {
                $slug = trim((string) ($_POST['product_slug'] ?? ''));
                $returnSection = trim((string) ($_POST['return_section'] ?? 'products'));
                $returnArticleSlug = canonical_article_slug(trim((string) ($_POST['return_slug'] ?? '')));
                $product = interessa_product($slug);
                if (!is_array($product)) {
                    throw new RuntimeException('Vybrany produkt sa nenasiel v katalogu.');
                }

                $normalizedProduct = interessa_normalize_product($product);
                $merchantSlug = trim((string) ($normalizedProduct['merchant_slug'] ?? ''));
                $asset = interessa_admin_store_uploaded_product_image($slug, $merchantSlug, $_FILES['product_image']);
                $payload = array_replace($normalizedProduct, interessa_admin_product_record($slug) ?? []);
                $payload['image_asset'] = $asset;
                interessa_admin_save_product_record($slug, $payload);

                if ($returnSection === 'images' && $returnArticleSlug !== '') {
                    interessa_admin_redirect('images', ['slug' => $returnArticleSlug, 'saved' => 'packshot']);
                }

                interessa_admin_redirect('products', ['product' => $slug, 'saved' => 'packshot']);
            }

            if ($action === 'mirror_packshot_from_remote') {
                $slug = trim((string) ($_POST['product_slug'] ?? ''));
                $returnSection = trim((string) ($_POST['return_section'] ?? 'products'));
                $returnArticleSlug = canonical_article_slug(trim((string) ($_POST['return_slug'] ?? '')));
                $product = interessa_product($slug);
                if (!is_array($product)) {
                    throw new RuntimeException('Vybrany produkt sa nenasiel v katalogu.');
                }

                $normalizedProduct = interessa_normalize_product($product);
                $merchantSlug = trim((string) ($normalizedProduct['merchant_slug'] ?? ''));
                $remoteSrc = trim((string) ($normalizedProduct['image_remote_src'] ?? ''));
                if ($remoteSrc === '') {
                    throw new RuntimeException('Tento produkt nema dostupny remote obrazok.');
                }

                $asset = interessa_admin_mirror_remote_product_image($slug, $merchantSlug, $remoteSrc);
                $payload = array_replace($normalizedProduct, interessa_admin_product_record($slug) ?? []);
                $payload['image_asset'] = $asset;
                interessa_admin_save_product_record($slug, $payload);

                if ($returnSection === 'images' && $returnArticleSlug !== '') {
                    interessa_admin_redirect('images', ['slug' => $returnArticleSlug, 'saved' => 'packshot-mirrored']);
                }
                if ($returnSection === 'articles' && $returnArticleSlug !== '') {
                    interessa_admin_redirect('articles', ['slug' => $returnArticleSlug, 'saved' => 'packshot-mirrored']);
                }

                interessa_admin_redirect('products', ['product' => $slug, 'saved' => 'packshot-mirrored']);
            }


            if ($action === 'export_bundle') {
                header('Content-Type: application/json; charset=UTF-8');
                header('Content-Disposition: attachment; filename="interesa-admin-export.json"');
                echo json_encode(interessa_admin_export_bundle(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            if ($action === 'import_bundle') {
                if (empty($_FILES['bundle_file']['tmp_name']) || !is_uploaded_file($_FILES['bundle_file']['tmp_name'])) {
                    throw new RuntimeException('Vyber export subor pre import.');
                }
                $json = file_get_contents((string) $_FILES['bundle_file']['tmp_name']);
                $bundle = json_decode((string) $json, true);
                if (!is_array($bundle)) {
                    throw new RuntimeException('Importovany subor nie je validny JSON export.');
                }
                $result = interessa_admin_import_bundle($bundle);
                $importSummary = 'Importovane: ' . $result['articles'] . ' clankov, ' . $result['products'] . ' produktov, ' . $result['affiliate_links'] . ' affiliate odkazov.';
                $flash = 'bundle-import';
            }

            if ($action === 'affiliate_csv_import') {
                if (empty($_FILES['affiliate_csv_file']['tmp_name']) || !is_uploaded_file($_FILES['affiliate_csv_file']['tmp_name'])) {
                    throw new RuntimeException('Vyber affiliate CSV subor.');
                }
                $rows = interessa_admin_parse_affiliate_csv_file((string) $_FILES['affiliate_csv_file']['tmp_name']);
                $count = interessa_admin_import_affiliate_rows($rows);
                $importSummary = 'Affiliate CSV import: ' . $count . ' odkazov bolo ulozenych do admin affiliate vrstvy.';
                $flash = 'affiliate-import';
            }

            if ($action === 'feed_import') {
                if (empty($_FILES['feed_file']['tmp_name']) || !is_uploaded_file($_FILES['feed_file']['tmp_name'])) {
                    throw new RuntimeException('Vyber feed subor XML alebo CSV.');
                }
                $merchantSlug = interessa_admin_slugify((string) ($_POST['feed_merchant_slug'] ?? ''));
                if ($merchantSlug === '') {
                    throw new RuntimeException('Vypln merchant slug pre feed import.');
                }
                $limit = max(0, (int) ($_POST['feed_limit'] ?? 0));
                $rows = interessa_admin_parse_feed_file((string) $_FILES['feed_file']['tmp_name'], $merchantSlug, $limit);
                $imported = interessa_admin_import_feed_products($rows);
                $importSummary = 'Feed import: ' . count($imported) . ' produktov bolo ulozenych do admin produktov.';
                $flash = 'feed-import';
            }

            if ($action === 'export_image_backlog_csv') {
                interessa_admin_output_image_backlog_csv(interessa_admin_article_options(), interessa_product_catalog());
            }

            if ($action === 'export_briefs_csv') {
                interessa_admin_output_briefs_csv(interessa_admin_brief_rows(interessa_admin_article_options()));
            }
        }
    } catch (Throwable $e) {
        $error = trim($e->getMessage());
    }
}
$articleOptions = interessa_admin_article_options();
$selectedArticleSlug = canonical_article_slug(trim((string) ($_GET['slug'] ?? array_key_first($articleOptions) ?? '')));
$selectedArticleMeta = $selectedArticleSlug !== '' ? article_meta($selectedArticleSlug) : ['title' => '', 'description' => '', 'category' => ''];
$categoryOptions = category_registry();
$selectedArticleOverride = $selectedArticleSlug !== '' ? interessa_admin_article_content($selectedArticleSlug) : interessa_admin_normalize_article_override('', []);
$articlePrompt = $selectedArticleSlug !== '' ? interessa_hero_prompt_meta($selectedArticleSlug) : [];
$selectedArticleHero = $selectedArticleSlug !== '' ? interessa_article_image_meta($selectedArticleSlug, 'hero', true) : null;
$selectedArticleHeroSource = is_array($selectedArticleHero) ? (string) ($selectedArticleHero['source_type'] ?? 'placeholder') : 'missing';


$catalog = interessa_product_catalog();
$productSlugs = array_keys($catalog);
sort($productSlugs);
$selectedProductSlug = trim((string) ($_GET['product'] ?? ($productSlugs[0] ?? '')));
$selectedProduct = $selectedProductSlug !== '' ? interessa_product($selectedProductSlug) : null;
$selectedProduct = is_array($selectedProduct) ? interessa_normalize_product($selectedProduct) : null;
$selectedProductImage = is_array($selectedProduct) ? ($selectedProduct['image'] ?? null) : null;
$selectedProductImageSource = is_array($selectedProduct) ? (string) ($selectedProduct['image_mode'] ?? 'placeholder') : 'missing';
$selectedProductAffiliate = is_array($selectedProduct) && trim((string) ($selectedProduct['affiliate_code'] ?? '')) !== '' ? aff_record((string) $selectedProduct['affiliate_code']) : null;
$selectedProductAffiliateUrl = is_array($selectedProduct) && trim((string) ($selectedProduct['affiliate_code'] ?? '')) !== '' ? aff_resolve((string) $selectedProduct['affiliate_code']) : null;
$selectedProductAffiliateType = $selectedProductAffiliate !== null ? aff_link_type($selectedProductAffiliate) : '';
$selectedProductTarget = is_array($selectedProduct) ? interessa_affiliate_target($selectedProduct) : ['href' => '', 'label' => ''];
$selectedProductPros = is_array($selectedProduct) && is_array($selectedProduct['pros'] ?? null) ? array_values(array_filter(array_map('strval', $selectedProduct['pros']))) : [];
$selectedProductCons = is_array($selectedProduct) && is_array($selectedProduct['cons'] ?? null) ? array_values(array_filter(array_map('strval', $selectedProduct['cons']))) : [];
$selectedProductSummaryReady = is_array($selectedProduct) && trim((string) ($selectedProduct['summary'] ?? '')) !== '';
$selectedProductRatingReady = is_array($selectedProduct) && trim((string) ($selectedProduct['rating'] ?? '')) !== '';
$selectedProductAffiliateReady = is_array($selectedProductAffiliateUrl) && trim((string) ($selectedProductAffiliateUrl['href'] ?? '')) !== '';
$selectedProductPackshotReady = is_array($selectedProduct) && trim((string) ($selectedProduct['image_mode'] ?? 'placeholder')) !== 'placeholder';
$selectedProductChecklist = [
    'Popis' => $selectedProductSummaryReady,
    'Rating' => $selectedProductRatingReady,
    'Plusy' => $selectedProductPros !== [],
    'Minusy' => $selectedProductCons !== [],
    'Affiliate' => $selectedProductAffiliateReady,
    'Obrazok' => $selectedProductPackshotReady,
];
$selectedProductChecklistReadyCount = count(array_filter($selectedProductChecklist));
$selectedProductChecklistTotal = count($selectedProductChecklist);
$selectedProductChecklistPercent = $selectedProductChecklistTotal > 0 ? (int) round(($selectedProductChecklistReadyCount / $selectedProductChecklistTotal) * 100) : 0;



$affiliateRegistry = aff_registry();
$affiliateCodes = array_keys($affiliateRegistry);
sort($affiliateCodes);
$selectedAffiliateCode = trim((string) ($_GET['code'] ?? ($affiliateCodes[0] ?? '')));
$selectedAffiliate = $selectedAffiliateCode !== '' ? aff_record($selectedAffiliateCode) : null;
$prefillAffiliateCode = trim((string) ($_GET['prefill_code'] ?? ''));
$prefillAffiliateMerchant = trim((string) ($_GET['prefill_merchant'] ?? ''));
$prefillAffiliateMerchantSlug = trim((string) ($_GET['prefill_merchant_slug'] ?? ''));
$prefillAffiliateProductSlug = trim((string) ($_GET['prefill_product_slug'] ?? ''));
$returnSectionPrefill = trim((string) ($_GET['return_section'] ?? ''));
if (!in_array($returnSectionPrefill, ['articles', 'images'], true)) {
    $returnSectionPrefill = '';
}
$returnSlugPrefill = canonical_article_slug(trim((string) ($_GET['return_slug'] ?? '')));
$prefillNewProductBrand = trim((string) ($_GET['prefill_product_brand'] ?? ''));
$prefillNewProductMerchant = trim((string) ($_GET['prefill_product_merchant'] ?? ''));
$prefillNewProductMerchantSlug = trim((string) ($_GET['prefill_product_merchant_slug'] ?? ''));
$prefillNewProductCategory = trim((string) ($_GET['prefill_product_category'] ?? ''));
$prefillNewProductAffiliateCode = trim((string) ($_GET['prefill_product_affiliate_code'] ?? ''));
$prefillNewProductFallbackUrl = trim((string) ($_GET['prefill_product_fallback_url'] ?? ''));
$prefillNewProductImageRemoteSrc = trim((string) ($_GET['prefill_product_image_remote_src'] ?? ''));
$prefillNewProductSummary = trim((string) ($_GET['prefill_product_summary'] ?? ''));

$imageFilter = trim((string) ($_GET['image_filter'] ?? 'missing'));
if (!in_array($imageFilter, ['missing', 'all', 'ready'], true)) {
    $imageFilter = 'missing';
}
$productImageFilter = trim((string) ($_GET['product_image_filter'] ?? 'missing'));
if (!in_array($productImageFilter, ['missing', 'all', 'ready', 'remote', 'placeholder'], true)) {
    $productImageFilter = 'missing';
}

$allImageQueue = interessa_admin_image_queue($articleOptions, 'all', max(count($articleOptions), 1));
$allProductImageQueue = interessa_admin_product_image_queue($catalog, 'all', max(count($catalog), 1));
$imageQueue = interessa_admin_image_queue($articleOptions, $imageFilter, $imageFilter === 'all' ? max(count($articleOptions), 1) : 16);
$productImageQueue = interessa_admin_product_image_queue($catalog, $productImageFilter, $productImageFilter === 'all' ? max(count($catalog), 1) : 16);
$imageQueueCounts = [
    'all' => count($allImageQueue),
    'missing' => count(array_filter($allImageQueue, static fn(array $row): bool => empty($row['has_final_webp']))),
    'ready' => count(array_filter($allImageQueue, static fn(array $row): bool => !empty($row['has_final_webp']))),
];
$productImageQueueCounts = [
    'all' => count($allProductImageQueue),
    'missing' => count(array_filter($allProductImageQueue, static fn(array $row): bool => !empty($row['needs_local_packshot']))),
    'ready' => count(array_filter($allProductImageQueue, static fn(array $row): bool => empty($row['needs_local_packshot']))),
    'remote' => count(array_filter($allProductImageQueue, static fn(array $row): bool => ($row['image_mode'] ?? '') === 'remote')),
    'placeholder' => count(array_filter($allProductImageQueue, static fn(array $row): bool => ($row['image_mode'] ?? '') === 'placeholder')),
];
$helpPriorityHeroes = array_slice(array_values(array_filter($allImageQueue, static fn(array $row): bool => empty($row['has_final_webp']))), 0, 5);
$helpPriorityProductImages = array_slice(array_values(array_filter($allProductImageQueue, static fn(array $row): bool => !empty($row['needs_local_packshot']) && trim((string) ($row['remote_src'] ?? '')) !== '')), 0, 5);

$missingHeroSlugs = array_values(array_filter(array_map(static fn(array $row): string => (string) ($row['slug'] ?? ''), array_filter($allImageQueue, static fn(array $row): bool => empty($row['has_final_webp'])))));
$selectedHeroQueueIndex = array_search($selectedArticleSlug, $missingHeroSlugs, true);
$prevMissingHeroSlug = $selectedHeroQueueIndex !== false && $selectedHeroQueueIndex > 0 ? (string) $missingHeroSlugs[$selectedHeroQueueIndex - 1] : '';
$nextMissingHeroSlug = $selectedHeroQueueIndex !== false && $selectedHeroQueueIndex < count($missingHeroSlugs) - 1 ? (string) $missingHeroSlugs[$selectedHeroQueueIndex + 1] : '';
$selectedHeroQueuePosition = $selectedHeroQueueIndex !== false ? ($selectedHeroQueueIndex + 1) : 0;
$missingPackshotSlugs = array_values(array_filter(array_map(static fn(array $row): string => (string) ($row['slug'] ?? ''), array_filter($allProductImageQueue, static fn(array $row): bool => !empty($row['needs_local_packshot'])))));
$selectedPackshotQueueIndex = array_search($selectedProductSlug, $missingPackshotSlugs, true);
$prevMissingPackshotSlug = $selectedPackshotQueueIndex !== false && $selectedPackshotQueueIndex > 0 ? (string) $missingPackshotSlugs[$selectedPackshotQueueIndex - 1] : '';
$nextMissingPackshotSlug = $selectedPackshotQueueIndex !== false && $selectedPackshotQueueIndex < count($missingPackshotSlugs) - 1 ? (string) $missingPackshotSlugs[$selectedPackshotQueueIndex + 1] : '';
$selectedPackshotQueuePosition = $selectedPackshotQueueIndex !== false ? ($selectedPackshotQueueIndex + 1) : 0;

$productAffiliateQueueAll = interessa_admin_product_affiliate_queue($catalog, max(count($catalog), 1));
$productAffiliateQueue = array_slice($productAffiliateQueueAll, 0, 12);
$productAffiliateQueueCount = count($productAffiliateQueueAll);
$productQualityQueueAll = interessa_admin_product_quality_queue($catalog, max(count($catalog), 1));
$productQualityQueue = array_slice($productQualityQueueAll, 0, 12);
$productQualityQueueCount = count($productQualityQueueAll);

$dashboardStats = [
    'article_overrides' => count(interessa_admin_all_article_overrides()),
    'products' => count($catalog),
    'affiliate_codes' => count($affiliateCodes),
    'hero_ready' => $imageQueueCounts['ready'],
];


$sections = is_array($selectedArticleOverride['sections'] ?? null) ? $selectedArticleOverride['sections'] : [];
while (count($sections) < 5) {
    $sections[] = ['heading' => '', 'body' => ''];
}

$comparison = is_array($selectedArticleOverride['comparison'] ?? null) ? $selectedArticleOverride['comparison'] : ['columns' => [], 'rows' => []];
$comparisonEditor = interessa_admin_comparison_editor_state($comparison);
$articleEditorProductSlugs = is_array($selectedArticleOverride['recommended_products'] ?? null) ? array_values(array_unique(array_map('strval', $selectedArticleOverride['recommended_products']))) : [];
if ($articleEditorProductSlugs === []) {
    $articleEditorProductSlugs = interessa_admin_article_default_products($selectedArticleSlug, $catalog);
}
$articleEditorInjectedProduct = trim((string) ($_GET['add_product'] ?? ''));
if ($articleEditorInjectedProduct !== '' && interessa_product($articleEditorInjectedProduct) !== null && !in_array($articleEditorInjectedProduct, $articleEditorProductSlugs, true)) {
    $articleEditorProductSlugs[] = $articleEditorInjectedProduct;
}
$articleEditorInjectedProductName = $articleEditorInjectedProduct !== '' && interessa_product($articleEditorInjectedProduct) !== null
    ? (string) ((interessa_product($articleEditorInjectedProduct)['name'] ?? $articleEditorInjectedProduct))
    : '';

$recommendedProductsText = implode(PHP_EOL, $articleEditorProductSlugs);
$recommendedProductPreview = interessa_admin_recommended_preview_rows($articleEditorProductSlugs);

$missingRecommendedProducts = interessa_admin_missing_product_rows(
    $articleEditorProductSlugs,
    (string) ($selectedArticleOverride['category'] ?? $selectedArticleMeta['category'] ?? '')
);
$recommendedDiagnostics = interessa_admin_recommended_diagnostics($articleEditorProductSlugs);
$recommendedDiagnosticsSummary = is_array($recommendedDiagnostics['summary'] ?? null) ? $recommendedDiagnostics['summary'] : [];
$recommendedDiagnosticsRows = is_array($recommendedDiagnostics['rows'] ?? null) ? $recommendedDiagnostics['rows'] : [];
$recommendedActionRows = is_array($recommendedDiagnostics['action_rows'] ?? null) ? $recommendedDiagnostics['action_rows'] : [];
$recommendedCatalogCoverage = (int) ($recommendedDiagnosticsSummary['catalog'] ?? 0);
$recommendedTotalCount = (int) ($recommendedDiagnosticsSummary['total'] ?? 0);
$recommendedMissingCount = (int) ($recommendedDiagnosticsSummary['missing_catalog'] ?? 0);
$recommendedAffiliateReadyCount = (int) ($recommendedDiagnosticsSummary['affiliate_ready'] ?? 0);
$recommendedPackshotReadyCount = (int) ($recommendedDiagnosticsSummary['packshot_ready'] ?? 0);
$recommendedMoneyReadyCount = (int) ($recommendedDiagnosticsSummary['money_ready'] ?? 0);
$recommendedCardReadyCount = (int) ($recommendedDiagnosticsSummary['card_ready'] ?? 0);
$selectedArticlePackshotGaps = array_values(array_filter($recommendedDiagnosticsRows, static function (array $row): bool {
    return !empty($row['exists']) && empty($row['packshot_ready']);
}));
$selectedArticlePackshotGapCount = count($selectedArticlePackshotGaps);


require dirname(__DIR__) . '/inc/head.php';
?>
<section class="container admin-page">
  <?php if (!$isAuthed): ?>
    <div class="admin-login-wrap">
      <section class="admin-login-card">
        <p class="admin-kicker">Protected admin</p>
        <h1>Prihlasenie do adminu</h1>
        <p class="admin-meta">Tento panel je urceny na internu spravu clankov, produktov, obrazkov a affiliate odkazov.</p>
        <?php if ($importSummary !== ''): ?>
          <div class="admin-flash is-success"><?= esc($importSummary) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
          <div class="admin-flash is-error"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="post" class="admin-form admin-form-stack">
          <input type="hidden" name="action" value="login" />
          <label>
            <span>Heslo</span>
            <input type="password" name="password" autocomplete="current-password" required />
          </label>
          <button class="btn btn-cta" type="submit">Prihlasit sa</button>
        </form>
        <?php if (($config['source'] ?? 'default') === 'default'): ?>
          <p class="admin-note">Pouziva sa docasne predvolene heslo <strong><?= esc((string) ($config['label'] ?? 'interesa-admin')) ?></strong>. Pre lokalnu zmenu vytvor <code>public/storage/admin/auth.php</code> podla <code>auth.example.php</code>.</p>
        <?php endif; ?>
      </section>
    </div>
  <?php else: ?>
    <div class="admin-shell">
      <aside class="admin-sidebar">
        <div class="admin-sidebar-head">
          <div>
            <h1>Admin</h1>
            <p class="admin-meta">Lahky flat-file panel pre obsah a obrazky.</p>
          </div>
          <form method="post">
            <input type="hidden" name="action" value="logout" />
            <button class="btn btn-secondary btn-small" type="submit">Odhlasit</button>
          </form>
        </div>
        <nav class="admin-nav">
          <a class="<?= $section === 'articles' ? 'is-active' : '' ?>" href="/admin?section=articles&slug=<?= esc($selectedArticleSlug) ?>">Clanky</a>
          <a class="<?= $section === 'products' ? 'is-active' : '' ?>" href="/admin?section=products&product=<?= esc($selectedProductSlug) ?>">Produkty</a>
          <a class="<?= $section === 'images' ? 'is-active' : '' ?>" href="/admin?section=images&slug=<?= esc($selectedArticleSlug) ?>">Image briefy</a>
          <a class="<?= $section === 'affiliates' ? 'is-active' : '' ?>" href="/admin?section=affiliates&code=<?= esc($selectedAffiliateCode) ?>">Affiliate odkazy</a>
          <a class="<?= $section === 'tools' ? 'is-active' : '' ?>" href="/admin?section=tools">Import / export</a>
          <a class="<?= $section === 'help' ? 'is-active' : '' ?>" href="/admin?section=help">Pomoc / quickstart</a>
          <a href="/admin/ai-status">AI status</a>
        </nav>
        <div class="admin-note">
          Frontend ostava flat-file. Admin uklada len override data a obrazky.
        </div>
        <section class="admin-quickstart">
          <h2>Rychly start</h2>
          <?php if ($section === 'articles'): ?>
            <ol class="admin-quickstart-list">
              <li>Vyber clanok a uprav titulok, intro a sekcie.</li>
              <li>V casti odporucanych produktov pouzi reusable katalog a scaffold tlacidla.</li>
              <li>Uloz clanok a skontroluj live stranku.</li>
            </ol>
          <?php elseif ($section === 'images'): ?>
            <ol class="admin-quickstart-list">
              <li>Skopiruj prompt, filename a target path.</li>
              <li>Vytvor hero v Canve alebo AI nastroji a exportuj WebP.</li>
              <li>Nahraj hero alebo produktovy obrazok a vrat sa na clanok.</li>
            </ol>
          <?php elseif ($section === 'products'): ?>
            <ol class="admin-quickstart-list">
              <li>Dopln nazov, obchod, summary, rating, plusy a minusy.</li>
              <li>Skontroluj affiliate kod a produktovy obrazok.</li>
              <li>Pozri pouzitie produktu v clankoch a vrat sa spat do workflowu.</li>
            </ol>
          <?php elseif ($section === 'affiliates'): ?>
            <ol class="admin-quickstart-list">
              <li>Vyber alebo vytvor interny /go/ kod.</li>
              <li>Vloz finalnu cielovu URL a merchant data.</li>
              <li>Vrat sa na produkt alebo clanok a skontroluj CTA.</li>
            </ol>
          <?php elseif ($section === 'help'): ?>
            <ol class="admin-quickstart-list">
              <li>Ak ides upravit obsah, zacni v Clankoch.</li>
              <li>Ak ides riesit obrazky, otvor Image briefy.</li>
              <li>Ak ides riesit CTA a produkty, pouzi Produkty a Affiliate odkazy.</li>
            </ol>
          <?php else: ?>
            <ol class="admin-quickstart-list">
              <li>Articles: obsah, porovnania a odporucane produkty.</li>
              <li>Products: reusable produkty, obrazky produktov a affiliate diagnostika.</li>
              <li>Images: hero briefy, Canva workflow a upload obrazkov.</li>
            </ol>
          <?php endif; ?>
        </section>
      </aside>

      <div class="admin-main">
        <?php if ($flash !== ''): ?>
          <div class="admin-flash is-success">Ulozene: <?= esc($flash) ?></div>
        <?php endif; ?>
        <?php if ($importSummary !== ''): ?>
          <div class="admin-flash is-success"><?= esc($importSummary) ?></div>
        <?php endif; ?>
        <?php if ($section === 'articles' && $articleEditorInjectedProduct !== ''): ?>
          <div class="admin-flash is-success"><?= esc($articleEditorInjectedProductName !== '' ? $articleEditorInjectedProductName : $articleEditorInjectedProduct) ?> bol pridany do odporucanych produktov len v editore. Uloz clanok, aby zmena ostala natrvalo.</div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>

          <div class="admin-flash is-error"><?= esc($error) ?></div>
        <?php endif; ?>

        <section class="admin-dashboard-grid">
          <article class="admin-stat-card">
            <span class="admin-stat-label">Article overrides</span>
            <strong><?= esc((string) $dashboardStats['article_overrides']) ?></strong>
          </article>
          <article class="admin-stat-card">
            <span class="admin-stat-label">Produkty</span>
            <strong><?= esc((string) $dashboardStats['products']) ?></strong>
          </article>
          <article class="admin-stat-card">
            <span class="admin-stat-label">Affiliate kody</span>
            <strong><?= esc((string) $dashboardStats['affiliate_codes']) ?></strong>
          </article>
          <article class="admin-stat-card">
            <span class="admin-stat-label">Hero WebP hotovo</span>
            <strong><?= esc((string) $dashboardStats['hero_ready']) ?></strong>
          </article>
        </section>

        <?php if ($section === 'articles'): ?>
          <section class="admin-card">
            <div class="admin-card-head">
              <div>
                <p class="admin-kicker">Article management</p>
                <h2>Strukturovany obsah clanku</h2>
              </div>
              <div class="admin-inline-actions">
                <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>">Otvorit image workflow</a>
                <a class="btn btn-secondary btn-small" href="/hero-helper" target="_blank" rel="noopener">Hero helper</a>
                <a class="btn btn-secondary btn-small" href="<?= esc(article_url($selectedArticleSlug)) ?>" target="_blank" rel="noopener">Live clanok</a>
              </div>
              <form method="get" action="/admin" class="admin-inline-form">
                <input type="hidden" name="section" value="articles" />
                <select name="slug" onchange="this.form.submit()">
                  <?php foreach ($articleOptions as $slug => $item): ?>
                    <option value="<?= esc($slug) ?>" <?= $slug === $selectedArticleSlug ? 'selected' : '' ?>><?= esc($item['title']) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </div>

            <section class="admin-subsection">
              <div class="admin-subsection-head">
                <h3>Vytvorit novy clanok</h3>
              </div>
              <form method="post" class="admin-form admin-form-stack">
                <input type="hidden" name="action" value="create_article" />
                <div class="admin-grid three-up">
                  <label>
                    <span>Titulok</span>
                    <input type="text" name="new_article_title" placeholder="Napriklad Najlepsi omega-3 doplnok" />
                  </label>
                  <label>
                    <span>Slug</span>
                    <input type="text" name="new_article_slug" placeholder="najlepsi-omega-3-doplnok" />
                  </label>
                  <label>
                    <span>Kategoria</span>
                    <select name="new_article_category">
                      <option value="">Bez kategorie</option>
                      <?php foreach ($categoryOptions as $categorySlug => $categoryRow): ?>
                        <option value="<?= esc((string) $categorySlug) ?>"><?= esc((string) ($categoryRow['title'] ?? $categorySlug)) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                  <label>
                    <span>Typ clanku</span>
                    <select name="new_article_type">
                      <option value="guide">Guide</option>
                      <option value="comparison">Comparison</option>
                      <option value="review">Review</option>
                    </select>
                  </label>
                </div>
                <label>
                  <span>Startovacie intro</span>
                  <textarea name="new_article_intro" rows="2" placeholder="Kratky uvod, ktory sa zobrazi na clanku aj v zoznamoch."></textarea>
                </label>
                <div class="admin-actions">
                  <button class="btn btn-cta" type="submit">Vytvorit clanok</button>
                </div>
              </form>
            </section>

            <form method="post" enctype="multipart/form-data" class="admin-form admin-form-stack">
              <input type="hidden" name="action" value="save_article" />
              <input type="hidden" name="slug" value="<?= esc($selectedArticleSlug) ?>" />

              <div class="admin-grid three-up">
                <label>
                  <span>Titulok</span>
                  <input type="text" name="title" value="<?= esc((string) ($selectedArticleOverride['title'] ?: $selectedArticleMeta['title'])) ?>" />
                </label>
                <label>
                  <span>Kategoria</span>
                  <?php $selectedCategory = (string) ($selectedArticleOverride['category'] ?: $selectedArticleMeta['category']); ?>
                  <select name="category">
                    <option value="">Bez kategorie</option>
                    <?php foreach ($categoryOptions as $categorySlug => $categoryRow): ?>
                      <option value="<?= esc((string) $categorySlug) ?>" <?= $selectedCategory === (string) $categorySlug ? 'selected' : '' ?>><?= esc((string) ($categoryRow['title'] ?? $categorySlug)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
                <label>
                  <span>Hero asset</span>
                  <input type="text" name="hero_asset" value="<?= esc((string) ($selectedArticleOverride['hero_asset'] ?? '')) ?>" placeholder="img/articles/heroes/slug.webp" />
                </label>
              </div>

              <label>
                <span>Intro</span>
                <textarea name="intro" rows="3"><?= esc((string) ($selectedArticleOverride['intro'] ?: $selectedArticleMeta['description'])) ?></textarea>
              </label>

              <div class="admin-grid two-up">
                <label>
                  <span>Meta title</span>
                  <input type="text" name="meta_title" value="<?= esc((string) ($selectedArticleOverride['meta_title'] ?? '')) ?>" />
                </label>
                <label>
                  <span>Meta description</span>
                  <input type="text" name="meta_description" value="<?= esc((string) ($selectedArticleOverride['meta_description'] ?? '')) ?>" />
                </label>
              </div>

              <div class="admin-subsection">
                <div class="admin-subsection-head">
                  <h3>Sekcie clanku</h3>
                  <button class="btn btn-secondary btn-small" type="button" data-add-section>Pridej sekciu</button>
                </div>
                <div class="admin-sections" data-sections-root>
                  <?php foreach ($sections as $sectionRow): ?>
                    <div class="admin-section-row" data-section-row>
                      <input type="text" name="section_heading[]" value="<?= esc((string) ($sectionRow['heading'] ?? '')) ?>" placeholder="Nadpis sekcie" />
                      <textarea name="section_body[]" rows="4" placeholder="Obsah sekcie"><?= esc((string) ($sectionRow['body'] ?? '')) ?></textarea>
                    </div>
                  <?php endforeach; ?>
                </div>
                <template id="admin-section-template">
                  <div class="admin-section-row" data-section-row>
                    <input type="text" name="section_heading[]" placeholder="Nadpis sekcie" />
                    <textarea name="section_body[]" rows="4" placeholder="Obsah sekcie"></textarea>
                  </div>
                </template>
              </div>

              <div class="admin-subsection">
                <div class="admin-subsection-head">
                  <h3>Porovnanie</h3>
                  <div class="admin-inline-actions">
                    <button class="btn btn-secondary btn-small" type="button" data-add-column>Pridej stlpec</button>
                    <button class="btn btn-secondary btn-small" type="button" data-add-row>Pridej riadok</button>
                    <button class="btn btn-secondary btn-small" type="button" data-fill-columns>Priklad stlpcov</button>
                    <button class="btn btn-secondary btn-small" type="button" data-fill-rows>Priklad riadkov</button>
                    <button class="btn btn-secondary btn-small" type="button" data-apply-preset="top-picks">Preset top picks</button>
                    <button class="btn btn-secondary btn-small" type="button" data-apply-preset="catalog-picks">Preset katalog</button>
                    <button class="btn btn-secondary btn-small" type="button" data-apply-preset="duel">Preset duel</button>
                    <button class="btn btn-secondary btn-small" type="button" data-fill-from-products>Riadky z odporucanych produktov</button>
                    <button class="btn btn-secondary btn-small" type="button" data-fill-ready-products>Len money-page ready</button>
                    <button class="btn btn-secondary btn-small" type="button" data-fill-card-ready>Len karty ready</button>
                    <button class="btn btn-secondary btn-small" type="button" data-fill-money-scaffold>Money-page scaffold</button>
                    <button class="btn btn-secondary btn-small" type="button" data-sync-products-from-comparison>Porovnanie -> produkty</button>
                    <button class="btn btn-secondary btn-small" type="button" data-fill-ready-shortlist>Top 3 hotove vybery</button>
                  </div>
                </div>
                <div class="admin-grid two-up">
                  <label>
                    <span>Nadpis porovnania</span>
                    <input type="text" name="comparison_title" value="<?= esc((string) ($comparison['title'] ?? '')) ?>" />
                  </label>
                  <label>
                    <span>Intro porovnania</span>
                    <input type="text" name="comparison_intro" value="<?= esc((string) ($comparison['intro'] ?? '')) ?>" />
                  </label>
                </div>
                <div class="admin-subsection is-compact">
                  <h4>Vizualny editor porovnania</h4>
                  <div class="admin-comparison-editor">
                    <div class="admin-comparison-columns" data-comparison-columns>
                      <?php foreach ($comparisonEditor['columns'] as $index => $column): ?>
                        <div class="admin-comparison-column" data-comparison-column>
                          <button class="btn btn-secondary btn-small admin-comparison-remove" type="button" data-remove-column>Odstranit stlpec</button>
                          <input type="text" name="comparison_column_label[]" value="<?= esc((string) ($column['label'] ?? '')) ?>" placeholder="Label stlpca" />
                          <input type="text" name="comparison_column_key[]" value="<?= esc((string) ($column['key'] ?? '')) ?>" placeholder="key" />
                          <select name="comparison_column_type[]">
                            <?php $type = (string) ($column['type'] ?? 'text'); ?>
                            <option value="text" <?= $type === 'text' ? 'selected' : '' ?>>text</option>
                            <option value="product" <?= $type === 'product' ? 'selected' : '' ?>>product</option>
                            <option value="cta" <?= $type === 'cta' ? 'selected' : '' ?>>cta</option>
                          </select>
                        </div>
                      <?php endforeach; ?>
                    </div>
                    <div class="admin-comparison-rows" data-comparison-rows>
                      <?php foreach ($comparisonEditor['rows'] as $rowCells): ?>
                        <div class="admin-comparison-row-grid" data-comparison-row>
                          <?php foreach ($rowCells as $columnIndex => $value): ?>
                            <textarea name="comparison_cell_<?= (int) $columnIndex ?>[]" rows="2" placeholder="Hodnota bunky"><?= esc((string) $value) ?></textarea>
                          <?php endforeach; ?>
                          <button class="btn btn-secondary btn-small admin-comparison-remove admin-comparison-remove-row" type="button" data-remove-row>Odstranit riadok</button>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                  <details class="admin-advanced-json">
                    <summary>Advanced JSON fallback</summary>
                    <label>
                      <span>Stlpce porovnania (JSON)</span>
                      <textarea name="comparison_columns_json" rows="6" data-columns-json><?= esc(json_encode($comparison['columns'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></textarea>
                    </label>
                    <label>
                      <span>Riadky porovnania (JSON)</span>
                      <textarea name="comparison_rows_json" rows="8" data-rows-json><?= esc(json_encode($comparison['rows'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></textarea>
                    </label>
                  </details>
                </div>
              </div>

              <div class="admin-grid two-up">
                <label>
                  <span>Odporucane produkty (slug na riadok)</span>
                  <textarea name="recommended_products" rows="6"><?= esc($recommendedProductsText) ?></textarea>
                  <span>Vyber z existujucich produktov</span>
                  <div class="admin-inline-actions">
                    <button class="btn btn-secondary btn-small" type="button" data-select-card-ready-products>Oznacit karty ready</button>
                    <button class="btn btn-secondary btn-small" type="button" data-select-money-ready-products>Oznacit money-page ready</button>
                    <button class="btn btn-secondary btn-small" type="button" data-clear-product-selection>Vymazat vyber</button>
                  </div>
                  <div class="admin-check-grid">
                    <?php foreach ($catalog as $productSlug => $productRow): ?>
                      <?php $checked = in_array((string) $productSlug, is_array($selectedArticleOverride['recommended_products'] ?? null) ? $selectedArticleOverride['recommended_products'] : [], true); ?>
                      <?php $productNormalized = interessa_product((string) $productSlug) ?? interessa_normalize_product(is_array($productRow) ? $productRow : []); ?>
                      <?php $productTarget = interessa_affiliate_target($productNormalized); ?>
                      <?php $productAffiliateCode = trim((string) ($productNormalized['affiliate_code'] ?? '')); ?>
                      <?php $productImageMode = trim((string) ($productNormalized['image_mode'] ?? 'placeholder')); ?>
                      <?php $productPackshotReady = $productImageMode !== '' && $productImageMode !== 'placeholder'; ?>
                      <?php $productAffiliateReady = $productAffiliateCode !== '' && aff_resolve($productAffiliateCode) !== null; ?>
                      <div class="admin-check-card-wrap">
                        <label class="admin-check-card">
                          <input type="checkbox" name="recommended_product_checks[]" value="<?= esc((string) $productSlug) ?>" data-product-name="<?= esc((string) ($productNormalized['name'] ?? $productSlug)) ?>" data-product-bestfor="<?= esc((string) ($productNormalized['summary'] ?? '')) ?>" data-product-merchant="<?= esc((string) ($productNormalized['merchant'] ?? '')) ?>" data-product-rating="<?= esc((string) ($productNormalized['rating'] ?? '')) ?>" data-product-summary="<?= esc((string) ($productNormalized['summary'] ?? '')) ?>" data-product-affiliate-ready="<?= $productAffiliateReady ? 'true' : 'false' ?>" data-product-packshot-ready="<?= $productPackshotReady ? 'true' : 'false' ?>" data-product-summary-ready="<?= trim((string) ($productNormalized['summary'] ?? '')) !== '' ? 'true' : 'false' ?>" data-product-rating-ready="<?= trim((string) ($productNormalized['rating'] ?? '')) !== '' ? 'true' : 'false' ?>" data-product-pros-ready="<?= (is_array($productNormalized['pros'] ?? null) ? array_values(array_filter(array_map('strval', $productNormalized['pros']))) : []) !== [] ? 'true' : 'false' ?>" data-product-cons-ready="<?= (is_array($productNormalized['cons'] ?? null) ? array_values(array_filter(array_map('strval', $productNormalized['cons']))) : []) !== [] ? 'true' : 'false' ?>" data-product-card-ready="<?= ($productAffiliateReady && $productPackshotReady && trim((string) ($productNormalized['summary'] ?? '')) !== '' && trim((string) ($productNormalized['rating'] ?? '')) !== '' && (is_array($productNormalized['pros'] ?? null) ? array_values(array_filter(array_map('strval', $productNormalized['pros']))) : []) !== [] && (is_array($productNormalized['cons'] ?? null) ? array_values(array_filter(array_map('strval', $productNormalized['cons']))) : []) !== []) ? 'true' : 'false' ?>" <?= $checked ? 'checked' : '' ?> />
                          <span><strong><?= esc((string) ($productRow['name'] ?? $productSlug)) ?></strong><small><?= esc((string) $productSlug) ?></small></span>
                        </label>
                        <div class="admin-status-pills">
                          <span class="admin-status-pill<?= $productAffiliateReady ? ' is-good' : ' is-warning' ?>"><?= $productAffiliateReady ? 'Affiliate hotovy' : 'Affiliate chyba' ?></span>
                          <span class="admin-status-pill<?= $productPackshotReady ? ' is-good' : ' is-warning' ?>"><?= $productPackshotReady ? 'Obrazok pripraveny' : 'Obrazok chyba' ?></span>
                        </div>
                        <div class="admin-inline-actions admin-check-card__actions">
                          <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) $productSlug) ?>">Produkt</a>
                          <?php if ($productAffiliateCode !== ''): ?>
                            <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;code=<?= esc($productAffiliateCode) ?>">Affiliate</a>
                          <?php endif; ?>
                          <?php if (trim((string) ($productTarget['href'] ?? '')) !== ''): ?>
                            <a class="btn btn-secondary btn-small" href="<?= esc((string) ($productTarget['href'] ?? '')) ?>" target="_blank" rel="noopener">Ciel</a>
                          <?php endif; ?>
                          <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) $productSlug) ?>">Kopirovat slug</button>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </label>
                <label>
                  <span>Nahrat hero obrazok</span>
                  <input type="file" name="hero_image" accept="image/webp,image/png,image/jpeg" />
                  <small class="admin-note">Target hero asset: <code><?= esc((string) ($articlePrompt['asset_path'] ?? '')) ?></code></small>
                  <div class="admin-inline-actions">
                    <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>">Image workflow</a>
                    <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($articlePrompt['asset_path'] ?? '')) ?>">Kopirovat hero path</button>
                  </div>
                </label>
              </div>

              <section class="admin-subsection is-compact">
                <div class="admin-subsection-head">
                  <h3>Aktualny hero v editore</h3>
                </div>
                <div class="admin-mini-product-card admin-mini-hero-card">
                  <div class="admin-mini-product-card__media">
                    <?= interessa_render_image($selectedArticleHero, ['class' => 'admin-mini-product-card__image']) ?>
                  </div>
                  <div class="admin-mini-product-card__body">
                    <strong><?= esc((string) ($selectedArticleMeta['title'] ?? $selectedArticleSlug)) ?></strong>
                    <small>Zdroj: <?= esc($selectedArticleHeroSource) ?></small>
                    <small><code><?= esc((string) ($articlePrompt['asset_path'] ?? '')) ?></code></small>
                    <div class="admin-inline-actions admin-mini-product-card__actions">
                      <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>">Image workflow</a>
                      <a class="btn btn-secondary btn-small" href="/hero-helper" target="_blank" rel="noopener">Hero helper</a>
                    </div>
                  </div>
                </div>
              </section>

              <section class="admin-subsection is-compact">
                <div class="admin-subsection-head">
                  <div>
                    <h3>Workflow odporucanych produktov</h3>
                    <p class="admin-note">Money-page ready: <?= esc((string) $recommendedMoneyReadyCount) ?> / <?= esc((string) $recommendedTotalCount) ?> / reusable karta hotova: <?= esc((string) $recommendedCardReadyCount) ?> / <?= esc((string) $recommendedTotalCount) ?></p>
                  </div>
                </div>
                <div class="admin-status-grid">
                  <article class="admin-status-card">
                    <strong><?= esc((string) $recommendedCatalogCoverage) ?> / <?= esc((string) $recommendedTotalCount) ?></strong>
                    <span>V reusable katalogu</span>
                  </article>
                  <article class="admin-status-card">
                    <strong><?= esc((string) $recommendedAffiliateReadyCount) ?> / <?= esc((string) max($recommendedCatalogCoverage, 1)) ?></strong>
                    <span>Affiliate hotove</span>
                  </article>
                  <article class="admin-status-card">
                    <strong><?= esc((string) $recommendedPackshotReadyCount) ?> / <?= esc((string) max($recommendedCatalogCoverage, 1)) ?></strong>
                    <span>Obrazok pripraveny</span>
                  </article>
                  <article class="admin-status-card">
                    <strong><?= esc((string) $recommendedMissingCount) ?></strong>
                    <span>Mimo katalogu</span>
                  </article>
                </div>
                <?php if ($recommendedActionRows === []): ?>
                  <p class="admin-note">Vsetky odporucane produkty su pripravene pre komercny clanok.</p>
                <?php else: ?>
                  <div class="admin-queue-list">
                    <?php foreach ($recommendedActionRows as $actionRow): ?>
                      <article class="admin-queue-item<?= !empty($actionRow['money_ready']) ? ' is-done' : '' ?>">
                        <div>
                          <strong><?= esc((string) ($actionRow['name'] ?? '')) ?></strong>
                          <p><?= esc((string) ($actionRow['slug'] ?? '')) ?><?php if (trim((string) ($actionRow['merchant'] ?? '')) !== ''): ?> / <?= esc((string) ($actionRow['merchant'] ?? '')) ?><?php endif; ?></p>
                          <div class="admin-status-pills">
                            <span class="admin-status-pill<?= !empty($actionRow['exists']) ? ' is-good' : ' is-warning' ?>"><?= !empty($actionRow['exists']) ? 'V katalogu' : 'Mimo katalogu' ?></span>
                            <span class="admin-status-pill<?= !empty($actionRow['affiliate_ready']) ? ' is-good' : ' is-warning' ?>"><?= !empty($actionRow['affiliate_ready']) ? 'Affiliate hotovy' : 'Affiliate chyba' ?></span>
                            <span class="admin-status-pill<?= !empty($actionRow['packshot_ready']) ? ' is-good' : ' is-warning' ?>"><?= !empty($actionRow['packshot_ready']) ? 'Obrazok pripraveny' : 'Obrazok chyba' ?></span>
                          </div>
                            <small class="admin-note">Pripravenost: <?= esc((string) ($actionRow['checklist_percent'] ?? 0)) ?>% / chybaju <?= esc((string) count($actionRow['issues'] ?? [])) ?> oblasti</small>
                            <div class="admin-status-pills">
                              <span class="admin-status-pill<?= !empty($actionRow['summary_ready']) ? ' is-good' : ' is-warning' ?>">Popis</span>
                              <span class="admin-status-pill<?= !empty($actionRow['rating_ready']) ? ' is-good' : ' is-warning' ?>">Rating</span>
                              <span class="admin-status-pill<?= !empty($actionRow['pros_ready']) ? ' is-good' : ' is-warning' ?>">Plusy</span>
                              <span class="admin-status-pill<?= !empty($actionRow['cons_ready']) ? ' is-good' : ' is-warning' ?>">Minusy</span>
                              <span class="admin-status-pill<?= !empty($actionRow['card_ready']) ? ' is-good' : ' is-warning' ?>">Karta ready</span>
                            </div>
                          <?php if (trim((string) ($actionRow['summary'] ?? '')) !== ''): ?>
                            <small class="admin-note"><?= esc((string) ($actionRow['summary'] ?? '')) ?></small>
                          <?php endif; ?>
                        </div>
                        <div class="admin-queue-actions">
                          <?php if (!empty($actionRow['exists'])): ?>
                            <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($actionRow['slug'] ?? '')) ?>&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Produkt</a>
                            <?php if (trim((string) ($actionRow['affiliate_code'] ?? '')) !== ''): ?>
                              <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;code=<?= esc((string) ($actionRow['affiliate_code'] ?? '')) ?>&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Affiliate</a>
                            <?php else: ?>
                              <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;prefill_code=<?= esc((string) ($actionRow['slug'] ?? '')) ?>&amp;prefill_merchant=<?= esc((string) ($actionRow['merchant'] ?? '')) ?>&amp;prefill_merchant_slug=<?= esc(interessa_admin_slugify((string) ($actionRow['merchant'] ?? ''))) ?>&amp;prefill_product_slug=<?= esc((string) ($actionRow['slug'] ?? '')) ?>&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Vytvorit affiliate</a>
                            <?php endif; ?>
                            <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($actionRow['slug'] ?? '')) ?>&amp;product_image_filter=missing&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Obrazok produktu</a>
                            <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>">Image workflow</a>
                            <?php if (trim((string) ($actionRow['image_target_asset'] ?? '')) !== ''): ?>
                              <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($actionRow['image_target_asset'] ?? '')) ?>">Kopirovat path</button>
                            <?php endif; ?>
                          <?php else: ?>
                            <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;prefill_product_name=<?= esc((string) ($actionRow['name'] ?? '')) ?>&amp;prefill_product_slug=<?= esc((string) ($actionRow['slug'] ?? '')) ?>&amp;prefill_product_category=<?= esc((string) ($selectedArticleOverride['category'] ?? $selectedArticleMeta['category'] ?? '')) ?>&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Vytvorit produkt</a>
                            <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($actionRow['slug'] ?? '')) ?>">Kopirovat slug</button>
                          <?php endif; ?>
                        </div>
                      </article>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </section>

              <section class="admin-subsection is-compact">
                <div class="admin-subsection-head">
                  <h3>Preview odporucanych produktov</h3>
                </div>
                <?php if ($recommendedProductPreview === []): ?>
                  <p class="admin-note">Zatial tu nie je vybrany ziadny produkt na preview.</p>
                <?php else: ?>
                  <div class="admin-mini-product-grid">
                    <?php foreach ($recommendedProductPreview as $previewRow): ?>
                      <?php $previewStatus = $recommendedDiagnosticsBySlug[(string) ($previewRow['slug'] ?? '')] ?? []; ?>
                      <article class="admin-mini-product-card">
                        <div class="admin-mini-product-card__media">
                          <?= interessa_render_image($previewRow['image'] ?? null, ['class' => 'admin-mini-product-card__image']) ?>
                        </div>
                        <div class="admin-mini-product-card__body">
                          <strong><?= esc((string) ($previewRow['name'] ?? '')) ?></strong>
                          <small><?= esc((string) ($previewRow['slug'] ?? '')) ?><?php if (trim((string) ($previewRow['merchant'] ?? '')) !== ''): ?> / <?= esc((string) ($previewRow['merchant'] ?? '')) ?><?php endif; ?></small>
                          <div class="admin-status-pills">
                            <span class="admin-status-pill<?= !empty($previewStatus['affiliate_ready']) ? ' is-good' : ' is-warning' ?>"><?= !empty($previewStatus['affiliate_ready']) ? 'Affiliate hotovy' : 'Affiliate chyba' ?></span>
                            <span class="admin-status-pill<?= !empty($previewStatus['packshot_ready']) ? ' is-good' : ' is-warning' ?>"><?= !empty($previewStatus['packshot_ready']) ? 'Obrazok pripraveny' : 'Obrazok chyba' ?></span>
                          </div>
                          <small class="admin-note">Pripravenost: <?= esc((string) ($previewStatus['checklist_percent'] ?? 0)) ?>%</small>
                          <?php if (trim((string) ($previewRow['summary'] ?? '')) !== ''): ?>
                            <p><?= esc((string) ($previewRow['summary'] ?? '')) ?></p>
                          <?php endif; ?>
                          <?php if (trim((string) ($previewRow['href'] ?? '')) !== ''): ?>
                            <small><?= esc((string) ($previewRow['label'] ?? 'Do obchodu')) ?></small>
                          <?php endif; ?>
                        <div class="admin-inline-actions admin-mini-product-card__actions">
                          <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($previewRow['slug'] ?? '')) ?>&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Produkt</a>
                          <?php if (trim((string) ($previewRow['code'] ?? $previewRow['affiliate_code'] ?? '')) !== ''): ?>
                            <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;code=<?= esc((string) ($previewRow['code'] ?? $previewRow['affiliate_code'] ?? '')) ?>&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Affiliate</a>
                          <?php endif; ?>
                          <?php if (trim((string) ($previewRow['href'] ?? '')) !== ''): ?>
                            <a class="btn btn-secondary btn-small" href="<?= esc((string) ($previewRow['href'] ?? '')) ?>" target="_blank" rel="noopener">Ciel</a>
                          <?php endif; ?>
                          <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>">Image workflow</a>
                          <?php if (trim((string) ($previewRow['slug'] ?? '')) !== ''): ?>
                            <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($previewRow['slug'] ?? '')) ?>">Kopirovat slug</button>
                          <?php endif; ?>
                        </div>
                      </article>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </section>

              <section class="admin-subsection is-compact">
                <div class="admin-subsection-head">
                  <h3>Slugy mimo katalog reusable produktov</h3>
                </div>
                <?php if ($missingRecommendedProducts === []): ?>
                  <p class="admin-note">Vsetky odporucane produkty v tomto clanku uz existuju v katalogu.</p>
                <?php else: ?>
                  <div class="admin-queue-list">
                    <?php foreach ($missingRecommendedProducts as $missingRow): ?>
                      <article class="admin-queue-item">
                        <div>
                          <strong><?= esc((string) ($missingRow['name'] ?? '')) ?></strong>
                          <p><?= esc((string) ($missingRow['slug'] ?? '')) ?></p>
                          <?php if (trim((string) ($missingRow['category'] ?? '')) !== ''): ?>
                            <small class="admin-note">Kategoria: <?= esc((string) ($missingRow['category'] ?? '')) ?></small>
                          <?php endif; ?>
                        </div>
                        <div class="admin-queue-actions">
                          <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;prefill_product_name=<?= esc((string) ($missingRow['name'] ?? '')) ?>&amp;prefill_product_slug=<?= esc((string) ($missingRow['slug'] ?? '')) ?>&amp;prefill_product_category=<?= esc((string) ($missingRow['category'] ?? '')) ?>&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Vytvorit produkt</a>
                          <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($missingRow['slug'] ?? '')) ?>">Kopirovat slug</button>
                        </div>
                      </article>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </section>

              <div class="admin-actions">
                <button class="btn btn-cta" type="submit">Ulozit clanok</button>
                <a class="btn btn-secondary" href="<?= esc(article_url($selectedArticleSlug)) ?>" target="_blank" rel="noopener">Otvorit clanok</a>
                <button class="btn btn-secondary" type="submit" name="action" value="delete_article_override" onclick="return confirm('Naozaj resetovat admin override pre tento clanok?');">Reset override</button>
              </div>
            </form>
          </section>
        <?php endif; ?>

        <?php if ($section === 'products'): ?>
          <section class="admin-card">
            <div class="admin-card-head">
              <div>
                <p class="admin-kicker">Product management</p>
                <h2>Reusable produkty</h2>
              </div>
              <form method="get" action="/admin" class="admin-inline-form">
                <input type="hidden" name="section" value="products" />
                <select name="product" onchange="this.form.submit()">
                  <?php foreach ($productSlugs as $slug): ?>
                    <option value="<?= esc($slug) ?>" <?= $slug === $selectedProductSlug ? 'selected' : '' ?>><?= esc((string) ($catalog[$slug]['name'] ?? $slug)) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </div>

            <?php if ($returnSectionPrefill !== "" && $returnSlugPrefill !== ""): ?>
              <section class="admin-subsection is-compact">
                <p class="admin-note">Tento editor bol otvoreny z workflowu pre clanok <strong><?= esc($returnSlugPrefill) ?></strong>.</p>
                <div class="admin-inline-actions">
                  <a class="btn btn-secondary btn-small" href="/admin?section=<?= esc($returnSectionPrefill) ?>&amp;slug=<?= esc($returnSlugPrefill) ?>">Spat do workflowu clanku</a>
                </div>
              </section>
            <?php endif; ?>

            <section class="admin-subsection is-compact">
              <div class="admin-subsection-head">
                <h3>Rychlo vytvorit novy produkt</h3>
              </div>
              <form method="post" class="admin-form admin-form-stack">
                <input type="hidden" name="action" value="create_product" />
                <input type="hidden" name="return_section" value="<?= esc((string) ($_GET['return_section'] ?? '')) ?>" />
                <input type="hidden" name="return_slug" value="<?= esc((string) ($_GET['return_slug'] ?? '')) ?>" />
                <div class="admin-grid three-up">
                  <label><span>Nazov</span><input type="text" name="new_product_name" value="<?= esc($prefillNewProductName) ?>" placeholder="Napriklad GymBeam Magnesium Citrate" data-auto-slug-source="product" /></label>
                  <label><span>Slug</span><input type="text" name="new_product_slug" value="<?= esc($prefillNewProductSlug) ?>" placeholder="gymbeam-magnesium-citrate" data-auto-slug-target="product" /></label>
                  <label><span>Brand</span><input type="text" name="new_product_brand" value="<?= esc($prefillNewProductBrand) ?>" placeholder="GymBeam" /></label>
                </div>
                <div class="admin-grid three-up">
                  <label><span>Obchod</span><input type="text" name="new_product_merchant" value="<?= esc($prefillNewProductMerchant) ?>" placeholder="GymBeam" data-auto-slug-source="merchant" /></label>
                  <label><span>Merchant slug</span><input type="text" name="new_product_merchant_slug" value="<?= esc($prefillNewProductMerchantSlug) ?>" placeholder="gymbeam" data-auto-slug-target="merchant" /></label>
                  <label><span>Kategoria</span><input type="text" name="new_product_category" value="<?= esc($prefillNewProductCategory) ?>" placeholder="mineraly" /></label>
                </div>
                <div class="admin-grid two-up">
                  <label><span>Fallback URL</span><input type="url" name="new_product_fallback_url" value="<?= esc($prefillNewProductFallbackUrl) ?>" placeholder="https://merchant.example.com/produkt" /></label>
                  <label><span>Remote image URL</span><input type="url" name="new_product_image_remote_src" value="<?= esc($prefillNewProductImageRemoteSrc) ?>" placeholder="https://merchant.example.com/image.webp" /></label>
                </div>
                <label><span>Kratky popis</span><textarea name="new_product_summary" rows="3" placeholder="Strucne zhrnutie produktu pre karty a odporucania"><?= esc($prefillNewProductSummary) ?></textarea></label>
                <label><span>Affiliate code (volitelne)</span><input type="text" name="new_product_affiliate_code" value="<?= esc($prefillNewProductAffiliateCode) ?>" placeholder="horcik-ktory-je-najlepsi-a-preco-gymbeam" /></label>
                <small class="admin-note">Slug a merchant slug sa doplnia automaticky, ak polia nechate prazdne.</small>
                <div class="admin-actions">
                  <button class="btn btn-secondary" type="submit">Vytvorit produkt</button>
                </div>
              </form>
            </section>

            <section class="admin-subsection">
              <div class="admin-subsection-head">
                <div>
                  <h3>Queue chybajucich obrazkov produktov</h3>
                  <p class="admin-meta">Preferujeme lokalny WebP obrazok produktu v cielovej asset ceste. Remote obrazok je len prechodny fallback.</p>
                </div>
                <div class="admin-filter-pills">
                  <a class="admin-filter-pill<?= $productImageFilter === 'missing' ? ' is-active' : '' ?>" href="/admin?section=products&amp;product=<?= esc($selectedProductSlug) ?>&amp;product_image_filter=missing">Chyba lokalny (<?= esc((string) $productImageQueueCounts['missing']) ?>)</a>
                  <a class="admin-filter-pill<?= $productImageFilter === 'remote' ? ' is-active' : '' ?>" href="/admin?section=products&amp;product=<?= esc($selectedProductSlug) ?>&amp;product_image_filter=remote">Remote (<?= esc((string) $productImageQueueCounts['remote']) ?>)</a>
                  <a class="admin-filter-pill<?= $productImageFilter === 'placeholder' ? ' is-active' : '' ?>" href="/admin?section=products&amp;product=<?= esc($selectedProductSlug) ?>&amp;product_image_filter=placeholder">Placeholder (<?= esc((string) $productImageQueueCounts['placeholder']) ?>)</a>
                  <a class="admin-filter-pill<?= $productImageFilter === 'ready' ? ' is-active' : '' ?>" href="/admin?section=products&amp;product=<?= esc($selectedProductSlug) ?>&amp;product_image_filter=ready">Hotovo (<?= esc((string) $productImageQueueCounts['ready']) ?>)</a>
                  <a class="admin-filter-pill<?= $productImageFilter === 'all' ? ' is-active' : '' ?>" href="/admin?section=products&amp;product=<?= esc($selectedProductSlug) ?>&amp;product_image_filter=all">Vsetko (<?= esc((string) $productImageQueueCounts['all']) ?>)</a>
                </div>
              </div>
              <div class="admin-queue-list">
                <?php foreach ($productImageQueue as $queueRow): ?>
                  <article class="admin-queue-item<?= empty($queueRow['needs_local_packshot']) ? ' is-done' : '' ?>">
                    <div>
                      <strong><?= esc((string) $queueRow['name']) ?></strong>
                      <p><?= esc((string) $queueRow['slug']) ?><?php if ($queueRow['merchant'] !== ''): ?> / <?= esc((string) $queueRow['merchant']) ?><?php endif; ?></p>
                      <code><?= esc((string) $queueRow['target_asset']) ?></code>
                    </div>
                    <div class="admin-queue-actions">
                      <span class="admin-note"><?= esc((string) $queueRow['image_mode']) ?></span>
                      <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) $queueRow['slug']) ?>&amp;product_image_filter=<?= esc($productImageFilter) ?>">Otvorit v produkte</a>
                      <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($queueRow['target_asset'] ?? '')) ?>">Kopirovat asset</button>
                      <?php if (trim((string) ($queueRow['remote_src'] ?? '')) !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="<?= esc((string) $queueRow['remote_src']) ?>" target="_blank" rel="noopener">Remote preview</a>
                        <?php if (!empty($queueRow['needs_local_packshot'])): ?>
                          <form method="post" class="admin-inline-form">
                            <input type="hidden" name="action" value="mirror_packshot_from_remote" />
                            <input type="hidden" name="product_slug" value="<?= esc((string) ($queueRow['slug'] ?? '')) ?>" />
                            <button class="btn btn-secondary btn-small" type="submit">Zrkadlit remote</button>
                          </form>
                        <?php endif; ?>
                      <?php endif; ?>
                    </div>
                  </article>

                <?php endforeach; ?>
              </div>
            </section>
            <section class="admin-subsection is-compact">
              <div class="admin-subsection-head">
                <div>
                  <h3>Queue chybajucich affiliate napojeni</h3>
                  <p class="admin-meta">Produkty, ktore este nemaju kompletne affiliate napojenie alebo sa ich /go/ link nevie vyriesit.</p>
                </div>
                <span class="admin-note"><?= esc((string) $productAffiliateQueueCount) ?> zaznamov</span>
              </div>
              <?php if ($productAffiliateQueue === []): ?>
                <p class="admin-note">Vsetky produkty v katalogu maju pouzitelne affiliate napojenie.</p>
              <?php else: ?>
                <div class="admin-queue-list">
                  <?php foreach ($productAffiliateQueue as $queueRow): ?>
                    <article class="admin-queue-item">
                      <div>
                        <strong><?= esc((string) ($queueRow['name'] ?? '')) ?></strong>
                        <p><?= esc((string) ($queueRow['slug'] ?? '')) ?><?php if (trim((string) ($queueRow['merchant'] ?? '')) !== ''): ?> / <?= esc((string) ($queueRow['merchant'] ?? '')) ?><?php endif; ?></p>
                        <small class="admin-note"><?= esc((string) ($queueRow['status'] ?? '')) ?></small>
                        <?php if (trim((string) ($queueRow['affiliate_code'] ?? '')) !== ''): ?>
                          <code><?= esc((string) ($queueRow['affiliate_code'] ?? '')) ?></code>
                        <?php endif; ?>
                      </div>
                      <div class="admin-queue-actions">
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($queueRow['slug'] ?? '')) ?>&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Produkt</a>
                        <?php if (trim((string) ($queueRow['affiliate_code'] ?? '')) === ''): ?>
                          <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;prefill_code=<?= esc((string) ($queueRow['slug'] ?? '')) ?>&amp;prefill_merchant=<?= esc((string) ($queueRow['merchant'] ?? '')) ?>&amp;prefill_merchant_slug=<?= esc(interessa_admin_slugify((string) ($queueRow['merchant'] ?? ''))) ?>&amp;prefill_product_slug=<?= esc((string) ($queueRow['slug'] ?? '')) ?>">Vytvorit affiliate</a>
                        <?php endif; ?>
                        <?php if (trim((string) ($queueRow['affiliate_code'] ?? '')) !== ''): ?>
                          <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;code=<?= esc((string) ($queueRow['affiliate_code'] ?? '')) ?>">Affiliate</a>
                          <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($queueRow['affiliate_code'] ?? '')) ?>">Kopirovat kod</button>
                        <?php endif; ?>
                        <?php if (trim((string) ($queueRow['fallback_url'] ?? '')) !== ''): ?>
                          <a class="btn btn-secondary btn-small" href="<?= esc((string) ($queueRow['fallback_url'] ?? '')) ?>" target="_blank" rel="noopener">Fallback URL</a>
                        <?php endif; ?>
                      </div>
                    </article>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </section>

            <section class="admin-subsection is-compact">
              <div class="admin-subsection-head">
                <div>
                  <h3>Queue nedokoncenych produktov</h3>
                  <p class="admin-meta">Produkty, ktorym este chyba redakcna alebo obchodna kvalita reusable karty.</p>
                </div>
                <span class="admin-note"><?= esc((string) $productQualityQueueCount) ?> zaznamov</span>
              </div>
              <?php if ($productQualityQueue === []): ?>
                <p class="admin-note">Reusable produkty maju vyplneny popis, rating, plusy, minusy aj zakladny commerce stav.</p>
              <?php else: ?>
                <div class="admin-queue-list">
                  <?php foreach ($productQualityQueue as $queueRow): ?>
                    <article class="admin-queue-item">
                      <div>
                        <strong><?= esc((string) ($queueRow['name'] ?? '')) ?></strong>
                        <p><?= esc((string) ($queueRow['slug'] ?? '')) ?><?php if (trim((string) ($queueRow['merchant'] ?? '')) !== ''): ?> / <?= esc((string) ($queueRow['merchant'] ?? '')) ?><?php endif; ?></p>
                        <div class="admin-status-pills">
                          <span class="admin-status-pill<?= !empty($queueRow['summary_ready']) ? ' is-good' : ' is-warning' ?>">Popis</span>
                          <span class="admin-status-pill<?= !empty($queueRow['rating_ready']) ? ' is-good' : ' is-warning' ?>">Rating</span>
                          <span class="admin-status-pill<?= !empty($queueRow['pros_ready']) ? ' is-good' : ' is-warning' ?>">Plusy</span>
                          <span class="admin-status-pill<?= !empty($queueRow['cons_ready']) ? ' is-good' : ' is-warning' ?>">Minusy</span>
                          <span class="admin-status-pill<?= !empty($queueRow['affiliate_ready']) ? ' is-good' : ' is-warning' ?>">Affiliate</span>
                          <span class="admin-status-pill<?= !empty($queueRow['packshot_ready']) ? ' is-good' : ' is-warning' ?>">Obrazok</span>
                        </div>
                        <small class="admin-note"><?= esc(implode(', ', array_values(array_slice($queueRow['issues'] ?? [], 0, 3)))) ?></small>
                      </div>
                      <div class="admin-queue-actions">
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($queueRow['slug'] ?? '')) ?>&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Produkt</a>
                        <?php if (trim((string) ($queueRow['affiliate_code'] ?? '')) !== ''): ?>
                          <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;code=<?= esc((string) ($queueRow['affiliate_code'] ?? '')) ?>">Affiliate</a>
                        <?php else: ?>
                          <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;prefill_code=<?= esc((string) ($queueRow['slug'] ?? '')) ?>&amp;prefill_merchant=<?= esc((string) ($queueRow['merchant'] ?? '')) ?>&amp;prefill_merchant_slug=<?= esc(interessa_admin_slugify((string) ($queueRow['merchant'] ?? ''))) ?>&amp;prefill_product_slug=<?= esc((string) ($queueRow['slug'] ?? '')) ?>">Vytvorit affiliate</a>
                        <?php endif; ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($queueRow['slug'] ?? '')) ?>&amp;product_image_filter=missing&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Obrazok produktu</a>
                      </div>
                    </article>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </section>

            <section class="admin-subsection admin-asset-preview">
              <div class="admin-subsection-head">
                <h3>Aktualny obrazok produktu</h3>
              </div>
              <div class="admin-asset-preview__grid">
                <div class="admin-asset-preview__media">
                  <?= interessa_render_image($selectedProductImage, ['class' => 'admin-asset-preview__image']) ?>
                </div>
                <div class="admin-asset-preview__body">
                  <p><strong>Zdroj:</strong> <?= esc($selectedProductImageSource) ?></p>
                  <p><strong>Target asset:</strong> <code><?= esc((string) ($selectedProduct['image_target_asset'] ?? '')) ?></code></p>
                  <div class="admin-inline-actions">
                    <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($selectedProduct['image_target_asset'] ?? '')) ?>">Kopirovat target asset</button>
                    <?php if (trim((string) ($selectedProduct['image_remote_src'] ?? '')) !== ''): ?>
                      <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($selectedProduct['image_remote_src'] ?? '')) ?>">Kopirovat remote URL</button>
                    <?php endif; ?>
                  </div>
                  <?php if ($selectedPackshotQueuePosition > 0): ?>
                    <p class="admin-note">Backlog obrazkov: <?= esc((string) $selectedPackshotQueuePosition) ?> / <?= esc((string) count($missingPackshotSlugs)) ?></p>
                  <?php endif; ?>
                  <?php if ($prevMissingPackshotSlug !== '' || $nextMissingPackshotSlug !== ''): ?>
                    <div class="admin-inline-actions">
                      <?php if ($prevMissingPackshotSlug !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc($prevMissingPackshotSlug) ?>&amp;product_image_filter=missing">Predchadzajuci chyba</a>
                      <?php endif; ?>
                      <?php if ($nextMissingPackshotSlug !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc($nextMissingPackshotSlug) ?>&amp;product_image_filter=missing">Dalsi chyba</a>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                  <p><strong>Remote source:</strong> <?= esc((string) ($selectedProduct['image_remote_src'] ?? '')) ?></p>
                  <?php if (trim((string) ($selectedProduct['image_remote_src'] ?? '')) !== ''): ?>
                    <div class="admin-inline-actions">
                      <a class="btn btn-secondary btn-small" href="<?= esc((string) ($selectedProduct['image_remote_src'] ?? '')) ?>" target="_blank" rel="noopener">Remote preview</a>
                      <?php if (!$selectedProductPackshotReady): ?>
                        <form method="post" class="admin-inline-form">
                          <input type="hidden" name="action" value="mirror_packshot_from_remote" />
                          <input type="hidden" name="product_slug" value="<?= esc($selectedProductSlug) ?>" />
                          <input type="hidden" name="return_section" value="<?= esc($returnSectionPrefill !== '' ? $returnSectionPrefill : 'products') ?>" />
                          <input type="hidden" name="return_slug" value="<?= esc($returnSlugPrefill) ?>" />
                          <button class="btn btn-secondary btn-small" type="submit">Stiahnut remote obrazok</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                  <div class="admin-diagnostic-list">
                    <p><strong>Affiliate kod:</strong> <?= esc((string) ($selectedProduct['affiliate_code'] ?? '')) ?: 'chyba' ?></p>
                    <p><strong>Typ linku:</strong> <?= esc($selectedProductAffiliateType !== '' ? $selectedProductAffiliateType : 'neznamy') ?></p>
                    <p><strong>Registry source:</strong> <?= esc((string) ($selectedProductAffiliate['source'] ?? 'bez registry')) ?></p>
                    <p><strong>Cielova URL:</strong> <?= esc((string) ($selectedProductAffiliateUrl ?? ($selectedProduct['fallback_url'] ?? ''))) ?></p>
                  </div>
                    <?php if (trim((string) ($selectedProduct['affiliate_code'] ?? '')) !== ''): ?>
                      <div class="admin-inline-actions">
                        <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;code=<?= esc((string) ($selectedProduct['affiliate_code'] ?? '')) ?>">Otvorit affiliate</a>
                        <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($selectedProduct['affiliate_code'] ?? '')) ?>">Kopirovat affiliate kod</button>
                      </div>
                    <?php else: ?>
                      <div class="admin-inline-actions">
                        <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;prefill_code=<?= esc((string) ($selectedProduct['slug'] ?? '')) ?>&amp;prefill_merchant=<?= esc((string) ($selectedProduct['merchant'] ?? '')) ?>&amp;prefill_merchant_slug=<?= esc((string) ($selectedProduct['merchant_slug'] ?? '')) ?>&amp;prefill_product_slug=<?= esc((string) ($selectedProduct['slug'] ?? '')) ?>">Vytvorit affiliate</a>
                      </div>
                    <?php endif; ?>
                  <?php if (trim((string) ($selectedProductTarget['href'] ?? '')) !== ''): ?>
                    <div class="admin-actions">
                      <a class="btn btn-secondary btn-small" href="<?= esc((string) $selectedProductTarget['href']) ?>" target="_blank" rel="noopener">Otvorit aktualny ciel</a>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </section>

            <section class="admin-subsection is-compact">
              <div class="admin-subsection-head">
                <h3>Kde sa produkt pouziva</h3>
              </div>
              <?php if ($selectedProductUsage === []): ?>
                <p class="admin-note">Tento produkt zatial nie je naviazany na odporucane produkty ani commerce boxy.</p>
              <?php else: ?>
                <div class="admin-queue-list">
                  <?php foreach ($selectedProductUsage as $usageRow): ?>
                    <article class="admin-queue-item is-done">
                      <div>
                        <strong><?= esc((string) ($usageRow['title'] ?? '')) ?></strong>
                        <p><?= esc((string) ($usageRow['slug'] ?? '')) ?> / <?= esc((string) ($usageRow['source'] ?? '')) ?></p>
                      </div>
                      <div class="admin-queue-actions">
                        <a class="btn btn-secondary btn-small" href="/admin?section=articles&amp;slug=<?= esc((string) ($usageRow['slug'] ?? '')) ?>">Otvorit v admine</a>
                        <a class="btn btn-secondary btn-small" href="<?= esc((string) ($usageRow['url'] ?? '#')) ?>" target="_blank" rel="noopener">Clanok</a>
                      </div>
                    </article>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </section>

            <form method="post" enctype="multipart/form-data" class="admin-form admin-form-stack">
              <input type="hidden" name="action" value="save_product" />
              <?php if ($returnSectionPrefill !== ""): ?><input type="hidden" name="return_section" value="<?= esc($returnSectionPrefill) ?>" /><?php endif; ?>
              <?php if ($returnSlugPrefill !== ""): ?><input type="hidden" name="return_slug" value="<?= esc($returnSlugPrefill) ?>" /><?php endif; ?>
              <div class="admin-grid three-up">
                <label><span>Slug</span><input type="text" name="product_slug" value="<?= esc((string) ($selectedProduct['slug'] ?? $selectedProductSlug)) ?>" /></label>
                <label><span>Nazov</span><input type="text" name="name" value="<?= esc((string) ($selectedProduct['name'] ?? '')) ?>" /></label>
                <label><span>Brand</span><input type="text" name="brand" value="<?= esc((string) ($selectedProduct['brand'] ?? '')) ?>" /></label>
              </div>
              <div class="admin-grid three-up">
                <label><span>Obchod</span><input type="text" name="merchant" value="<?= esc((string) ($selectedProduct['merchant'] ?? '')) ?>" /></label>
                <label><span>Merchant slug</span><input type="text" name="merchant_slug" value="<?= esc((string) ($selectedProduct['merchant_slug'] ?? '')) ?>" /></label>
                <label><span>Kategoria</span><input type="text" name="category" value="<?= esc((string) ($selectedProduct['category'] ?? '')) ?>" /></label>
              </div>
              <div class="admin-grid three-up">
                <label><span>Affiliate code</span><input type="text" name="affiliate_code" value="<?= esc((string) (['affiliate_code'] ?? '')) ?>" /></label>
                <label><span>Fallback URL</span><input type="url" name="fallback_url" value="<?= esc((string) (['fallback_url'] ?? '')) ?>" /></label>
                <label><span>Rating</span><input type="number" min="0" max="5" step="0.1" name="rating" value="<?= esc((string) (['rating'] ?? '')) ?>" data-product-rating-input /></label>
              </div>
              <section class="admin-subsection is-compact">
                <div class="admin-subsection-head">
                  <h3>Rychle hodnotenie a checklist</h3>
                  <div class="admin-inline-actions">
                    <button class="btn btn-secondary btn-small" type="button" data-set-product-rating="4.1">4.1 Budget</button>
                    <button class="btn btn-secondary btn-small" type="button" data-set-product-rating="4.3">4.3 Solid</button>
                    <button class="btn btn-secondary btn-small" type="button" data-set-product-rating="4.5">4.5 Value</button>
                    <button class="btn btn-secondary btn-small" type="button" data-set-product-rating="4.7">4.7 Top pick</button>
                    <button class="btn btn-secondary btn-small" type="button" data-set-product-rating="4.9">4.9 Flagship</button>
                    <button class="btn btn-secondary btn-small" type="button" data-fill-product-rating-auto>Auto rating</button>
                  </div>
                </div>
                <div class="admin-product-readiness">
                  <div class="admin-product-readiness__summary">
                    <strong>Pripravenost produktu: <?= esc((string) $selectedProductChecklistPercent) ?>%</strong>
                    <small><?= esc((string) $selectedProductChecklistReadyCount) ?> / <?= esc((string) $selectedProductChecklistTotal) ?> zakladnych poloziek je hotovych</small>
                  </div>
                  <div class="admin-status-pills">
                    <?php foreach ($selectedProductChecklist as $checkLabel => $isReady): ?>
                      <span class="admin-status-pill<?= $isReady ? ' is-good' : ' is-warning' ?>"><?= esc((string) $checkLabel) ?></span>
                    <?php endforeach; ?>
                  </div>
                </div>
                <p class="admin-note">Rating preset je len rychly start. Pred publikaciou ho vzdy dolad podla realneho porovnania a kvality produktu.</p>
              </section>
              <section class="admin-subsection is-compact">
                <div class="admin-subsection-head">
                  <h3>Rychle scaffoldy produktu</h3>
                  <div class="admin-inline-actions">
                    <button class="btn btn-secondary btn-small" type="button" data-fill-product-empty>Iba doplnit prazdne</button>
                    <button class="btn btn-secondary btn-small" type="button" data-fill-product-summary>Starter summary</button>
                    <button class="btn btn-secondary btn-small" type="button" data-fill-product-pros>Starter plusy</button>
                    <button class="btn btn-secondary btn-small" type="button" data-fill-product-cons>Starter minusy</button>
                    <button class="btn btn-secondary btn-small" type="button" data-fill-product-all>Vyplnit vsetko</button>
                  </div>
                </div>
                <p class="admin-note">Vygeneruje zakladny draft z nazvu, brandu, obchodu a kategorie. Potom ho staci doladit redakcne.</p>
              </section>
              <label>
                <span>Kratky popis</span>
                <textarea name="summary" rows="3"><?= esc((string) ($selectedProduct['summary'] ?? '')) ?></textarea>
              </label>
              <div class="admin-grid two-up">
                <label>
                  <span>Plusy (riadok = 1 bod)</span>
                  <textarea name="pros" rows="6"><?= esc(implode(PHP_EOL, is_array($selectedProduct['pros'] ?? null) ? $selectedProduct['pros'] : [])) ?></textarea>
                </label>
                <label>
                  <span>Minusy (riadok = 1 bod)</span>
                  <textarea name="cons" rows="6"><?= esc(implode(PHP_EOL, is_array($selectedProduct['cons'] ?? null) ? $selectedProduct['cons'] : [])) ?></textarea>
                </label>
              </div>
              <div class="admin-grid two-up">
                <label>
                  <span>Remote image URL</span>
                  <input type="url" name="image_remote_src" value="<?= esc((string) ($selectedProduct['image_remote_src'] ?? '')) ?>" />
                </label>
                <label>
                  <span>Nahrat lokalny obrazok produktu</span>
                  <input type="file" name="product_image" accept="image/webp,image/png,image/jpeg" />
                  <small class="admin-note">Cielovy asset pre upload: <code><?= esc((string) ($selectedProduct['image_target_asset'] ?? '')) ?></code></small>
                </label>
              </div>
              <div class="admin-actions">
                <button class="btn btn-cta" type="submit">Ulozit produkt</button>
                <button class="btn btn-secondary" type="submit" name="action" value="delete_product_override" onclick="return confirm('Naozaj zmazat admin override produktu?');">Zmazat override produktu</button>
              </div>
            </form>
          </section>
        <?php endif; ?>

        <?php if ($section === 'images'): ?>
          <section class="admin-card">
            <div class="admin-card-head">
              <div>
                <p class="admin-kicker">Image brief generator</p>
                <h2>Canva / AI workflow</h2>
              </div>
              <form method="get" action="/admin" class="admin-inline-form">
                <input type="hidden" name="section" value="images" />
                <select name="slug" onchange="this.form.submit()">
                  <?php foreach ($articleOptions as $slug => $item): ?>
                    <option value="<?= esc($slug) ?>" <?= $slug === $selectedArticleSlug ? 'selected' : '' ?>><?= esc($item['title']) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </div>

            <section class="admin-subsection admin-asset-preview">
              <div class="admin-subsection-head">
                <h3>Aktualny hero asset</h3>
              </div>
              <div class="admin-asset-preview__grid">
                <div class="admin-asset-preview__media">
                  <?= interessa_render_image($selectedArticleHero, ['class' => 'admin-asset-preview__image']) ?>
                </div>
                <div class="admin-asset-preview__body">
                  <p><strong>Zdroj:</strong> <?= esc($selectedArticleHeroSource) ?></p>
                  <p><strong>Aktivny asset:</strong> <code><?= esc((string) ($selectedArticleHero['asset'] ?? '')) ?></code></p>
                  <p><strong>Target path:</strong> <code><?= esc((string) ($articlePrompt['asset_path'] ?? '')) ?></code></p>
                  <div class="admin-inline-actions">
                    <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($articlePrompt['asset_path'] ?? '')) ?>">Kopirovat target path</button>
                    <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($articlePrompt['file_name'] ?? '')) ?>">Kopirovat filename</button>
                  </div>
                  <?php if ($selectedHeroQueuePosition > 0): ?>
                    <p class="admin-note">Hero backlog: <?= esc((string) $selectedHeroQueuePosition) ?> / <?= esc((string) count($missingHeroSlugs)) ?></p>
                  <?php endif; ?>
                  <?php if ($prevMissingHeroSlug !== '' || $nextMissingHeroSlug !== ''): ?>
                    <div class="admin-inline-actions">
                      <?php if ($prevMissingHeroSlug !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($prevMissingHeroSlug) ?>&amp;image_filter=missing">Predchadzajuci chyba</a>
                      <?php endif; ?>
                      <?php if ($nextMissingHeroSlug !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($nextMissingHeroSlug) ?>&amp;image_filter=missing">Dalsi chyba</a>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </section>

            <?php if ($selectedArticlePackshotGaps !== []): ?>
              <section class="admin-subsection is-compact">
                <div class="admin-subsection-head">
                  <div>
                    <h3>Chybajuce obrazky produktov v tomto clanku</h3>
                    <p class="admin-meta">Odporucane produkty v tomto clanku, ktore este nemaju finalny obrazok produktu.</p>
                  </div>
                  <span class="admin-note"><?= esc((string) $selectedArticlePackshotGapCount) ?> zaznamov</span>
                </div>
                <div class="admin-queue-list">
                  <?php foreach ($selectedArticlePackshotGaps as $queueRow): ?>
                    <article class="admin-queue-item">
                      <div>
                        <strong><?= esc((string) ($queueRow['name'] ?? '')) ?></strong>
                        <p><?= esc((string) ($queueRow['slug'] ?? '')) ?><?php if (trim((string) ($queueRow['merchant'] ?? '')) !== ''): ?> / <?= esc((string) ($queueRow['merchant'] ?? '')) ?><?php endif; ?></p>
                        <?php if (trim((string) ($queueRow['image_target_asset'] ?? '')) !== ''): ?>
                          <code><?= esc((string) ($queueRow['image_target_asset'] ?? '')) ?></code>
                        <?php endif; ?>
                        <div class="admin-status-pills">
                          <span class="admin-status-pill<?= !empty($queueRow['affiliate_ready']) ? ' is-good' : ' is-warning' ?>"><?= !empty($queueRow['affiliate_ready']) ? 'Affiliate hotovy' : 'Affiliate chyba' ?></span>
                          <span class="admin-status-pill is-warning">Obrazok chyba</span>
                        </div>
                      </div>
                      <div class="admin-queue-actions">
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($queueRow['slug'] ?? '')) ?>&amp;product_image_filter=missing&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Obrazok produktu</a>
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($queueRow['slug'] ?? '')) ?>&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Produkt</a>
                        <?php if (trim((string) ($queueRow['image_target_asset'] ?? '')) !== ''): ?>
                          <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($queueRow['image_target_asset'] ?? '')) ?>">Kopirovat path</button>
                        <?php endif; ?>
                        <?php if (trim((string) ($queueRow['remote_src'] ?? '')) !== ''): ?>
                          <form method="post" class="admin-inline-form">
                            <input type="hidden" name="action" value="mirror_packshot_from_remote" />
                            <input type="hidden" name="product_slug" value="<?= esc((string) ($queueRow['slug'] ?? '')) ?>" />
                            <input type="hidden" name="return_section" value="images" />
                            <input type="hidden" name="return_slug" value="<?= esc($selectedArticleSlug) ?>" />
                            <button class="btn btn-secondary btn-small" type="submit">Zrkadlit remote</button>
                          </form>
                        <?php endif; ?>
                        <form method="post" enctype="multipart/form-data" class="admin-inline-upload">
                          <input type="hidden" name="action" value="upload_packshot_only" />
                          <input type="hidden" name="product_slug" value="<?= esc((string) ($queueRow['slug'] ?? '')) ?>" />
                          <input type="hidden" name="return_section" value="images" />
                          <input type="hidden" name="return_slug" value="<?= esc($selectedArticleSlug) ?>" />
                          <label class="admin-inline-upload__label">
                            <span>Obrazok</span>
                            <input type="file" name="product_image" accept="image/webp,image/png,image/jpeg" required />
                          </label>
                          <button class="btn btn-secondary btn-small" type="submit">Nahrat obrazok</button>
                        </form>
                      </div>
                    </article>
                  <?php endforeach; ?>
                </div>
              </section>
            <?php endif; ?>

            <?php if ($recommendedProductPreview !== []): ?>
              <section class="admin-subsection is-compact">
                <div class="admin-subsection-head">
                  <div>
                    <h3>Produkty v tomto clanku</h3>
                    <p class="admin-meta">Prehlad odporucanych produktov aj so stavom obrazka a affiliate priamo v image workflowe.</p>
                  </div>
                </div>
                <div class="admin-mini-product-grid">
                  <?php foreach ($recommendedProductPreview as $previewRow): ?>
                    <?php $previewStatus = $recommendedDiagnosticsBySlug[(string) ($previewRow['slug'] ?? '')] ?? []; ?>
                    <article class="admin-mini-product-card">
                      <div class="admin-mini-product-card__media">
                        <?= interessa_render_image($previewRow['image'] ?? null, ['class' => 'admin-mini-product-card__image']) ?>
                      </div>
                      <div class="admin-mini-product-card__body">
                        <strong><?= esc((string) ($previewRow['name'] ?? '')) ?></strong>
                        <small><?= esc((string) ($previewRow['slug'] ?? '')) ?><?php if (trim((string) ($previewRow['merchant'] ?? '')) !== ''): ?> / <?= esc((string) ($previewRow['merchant'] ?? '')) ?><?php endif; ?></small>
                        <div class="admin-status-pills">
                          <span class="admin-status-pill<?= !empty($previewStatus['affiliate_ready']) ? ' is-good' : ' is-warning' ?>"><?= !empty($previewStatus['affiliate_ready']) ? 'Affiliate hotovy' : 'Affiliate chyba' ?></span>
                          <span class="admin-status-pill<?= !empty($previewStatus['packshot_ready']) ? ' is-good' : ' is-warning' ?>"><?= !empty($previewStatus['packshot_ready']) ? 'Obrazok pripraveny' : 'Obrazok chyba' ?></span>
                        </div>
                        <?php if (trim((string) ($previewStatus['image_target_asset'] ?? '')) !== ''): ?>
                          <small><code><?= esc((string) ($previewStatus['image_target_asset'] ?? '')) ?></code></small>
                        <?php endif; ?>
                      </div>
                      <div class="admin-inline-actions admin-mini-product-card__actions">
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($previewRow['slug'] ?? '')) ?>&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Produkt</a>
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($previewRow['slug'] ?? '')) ?>&amp;product_image_filter=missing&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Obrazok produktu</a>
                        <?php if (trim((string) ($previewStatus['affiliate_code'] ?? '')) !== ''): ?>
                          <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;code=<?= esc((string) ($previewStatus['affiliate_code'] ?? '')) ?>&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Affiliate</a>
                        <?php else: ?>
                          <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;prefill_code=<?= esc((string) ($previewRow['slug'] ?? '')) ?>&amp;prefill_merchant=<?= esc((string) ($previewRow['merchant'] ?? '')) ?>&amp;prefill_merchant_slug=<?= esc(interessa_admin_slugify((string) ($previewRow['merchant'] ?? ''))) ?>&amp;prefill_product_slug=<?= esc((string) ($previewRow['slug'] ?? '')) ?>&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Vytvorit affiliate</a>
                        <?php endif; ?>
                        <?php if (trim((string) ($previewStatus['image_target_asset'] ?? '')) !== ''): ?>
                          <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($previewStatus['image_target_asset'] ?? '')) ?>">Kopirovat path</button>
                        <?php endif; ?>
                        <?php if (trim((string) ($previewStatus['image_remote_src'] ?? '')) !== '' && empty($previewStatus['packshot_ready'])): ?>
                          <form method="post" class="admin-inline-form">
                            <input type="hidden" name="action" value="mirror_packshot_from_remote" />
                            <input type="hidden" name="product_slug" value="<?= esc((string) ($previewRow['slug'] ?? '')) ?>" />
                            <input type="hidden" name="return_section" value="images" />
                            <input type="hidden" name="return_slug" value="<?= esc($selectedArticleSlug) ?>" />
                            <button class="btn btn-secondary btn-small" type="submit">Zrkadlit remote</button>
                          </form>
                        <?php endif; ?>
                      </div>
                        <?php if (empty($previewStatus['packshot_ready'])): ?>
                          <form method="post" enctype="multipart/form-data" class="admin-inline-upload">
                            <input type="hidden" name="action" value="upload_packshot_only" />
                            <input type="hidden" name="product_slug" value="<?= esc((string) ($previewRow['slug'] ?? '')) ?>" />
                            <input type="hidden" name="return_section" value="images" />
                            <input type="hidden" name="return_slug" value="<?= esc($selectedArticleSlug) ?>" />
                            <label class="admin-inline-upload__label">
                              <span>Obrazok</span>
                              <input type="file" name="product_image" accept="image/webp,image/png,image/jpeg" required />
                            </label>
                            <button class="btn btn-secondary btn-small" type="submit">Nahrat obrazok</button>
                          </form>
                        <?php endif; ?>
                    </article>
                  <?php endforeach; ?>
                </div>
              </section>
            <?php endif; ?>

            <div class="admin-brief-grid">
              <div class="admin-brief-card">
                <h3>Brief</h3>
                <p><strong>Prompt:</strong><br><?= esc((string) ($articlePrompt['prompt'] ?? '')) ?></p>
                <p><strong>Filename:</strong><br><?= esc((string) ($articlePrompt['file_name'] ?? '')) ?></p>
                <p><strong>Alt text:</strong><br><?= esc((string) ($articlePrompt['alt_text'] ?? '')) ?></p>
                <p><strong>Dimensions:</strong><br><?= esc((string) ($articlePrompt['dimensions'] ?? '1200x800')) ?></p>
                <p><strong>Target path:</strong><br><?= esc((string) ($articlePrompt['asset_path'] ?? '')) ?></p>
                <p><strong>Style brief:</strong><br><?= esc((string) ($articlePrompt['style_brief'] ?? '')) ?></p>
                <div class="admin-inline-actions">
                  <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($articlePrompt['prompt'] ?? '')) ?>">Kopirovat prompt</button>
                  <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($articlePrompt['file_name'] ?? '')) ?>">Kopirovat filename</button>
                  <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($articlePrompt['asset_path'] ?? '')) ?>">Kopirovat path</button>
                </div>
              </div>
              <div class="admin-brief-card">
                <h3>Workflow</h3>
                <ol class="admin-workflow-list">
                  <li>Skopiruj prompt do Canvy alebo AI generatora.</li>
                  <li>Exportuj WebP v rozmere 1200x800.</li>
                  <li>Dodrz naming podla odporucaneho filename.</li>
                  <li>Nahraj obrazok sem alebo do cielovej cesty v assets.</li>
                  <li>Clanok automaticky pouzije novy hero asset.</li>
                </ol>
                  <div class="admin-inline-actions">
                    <a class="btn btn-secondary btn-small" href="/hero-helper" target="_blank" rel="noopener">Otvorit hero helper</a>
                    <a class="btn btn-secondary btn-small" href="<?= esc(article_url($selectedArticleSlug)) ?>" target="_blank" rel="noopener">Otvorit clanok</a>
                  </div>
                <form method="post" enctype="multipart/form-data" class="admin-form">
                  <input type="hidden" name="action" value="upload_hero_only" />
                  <input type="hidden" name="slug" value="<?= esc($selectedArticleSlug) ?>" />
                  <label>
                    <span>Nahrat finalny hero obrazok</span>
                    <input type="file" name="hero_image" accept="image/webp,image/png,image/jpeg" required />
                    <small class="admin-note">Cielovy hero asset: <code><?= esc((string) ($articlePrompt['asset_path'] ?? '')) ?></code></small>
                  </label>
                  <button class="btn btn-cta" type="submit">Nahrat hero obrazok</button>
                </form>
              </div>
            </div>
          </section>

          <section class="admin-card">
            <div class="admin-card-head">
              <div>
                <p class="admin-kicker">Hero image backlog</p>
                <h2>Queue chybajucich hero obrazkov</h2>
              </div>
              <div class="admin-filter-pills">
                <a class="admin-filter-pill<?= $imageFilter === 'missing' ? ' is-active' : '' ?>" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>&amp;image_filter=missing">Chyba WebP (<?= esc((string) $imageQueueCounts['missing']) ?>)</a>
                <a class="admin-filter-pill<?= $imageFilter === 'ready' ? ' is-active' : '' ?>" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>&amp;image_filter=ready">Hotovo (<?= esc((string) $imageQueueCounts['ready']) ?>)</a>
                <a class="admin-filter-pill<?= $imageFilter === 'all' ? ' is-active' : '' ?>" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>&amp;image_filter=all">Vsetko (<?= esc((string) $imageQueueCounts['all']) ?>)</a>
              </div>
            </div>
            <div class="admin-queue-list">
              <?php foreach ($imageQueue as $queueRow): ?>
                <article class="admin-queue-item<?= !empty($queueRow['has_final_webp']) ? ' is-done' : '' ?>">
                  <div>
                    <strong><?= esc((string) $queueRow['title']) ?></strong>
                    <p><?= esc((string) $queueRow['slug']) ?> / <?= esc((string) ($queueRow['source_type'] ?? 'placeholder')) ?></p>
                    <code><?= esc((string) $queueRow['asset_path']) ?></code>
                    <small class="admin-note"><?= esc((string) ($queueRow['file_name'] ?? '')) ?> / <?= esc((string) ($queueRow['dimensions'] ?? '1200x800')) ?></small>
                    <?php if (trim((string) ($queueRow['alt_text'] ?? '')) !== ''): ?>
                      <small class="admin-note"><?= esc((string) ($queueRow['alt_text'] ?? '')) ?></small>
                    <?php endif; ?>
                  </div>
                  <div class="admin-queue-actions">
                    <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc((string) $queueRow['slug']) ?>&amp;image_filter=<?= esc($imageFilter) ?>">Otvorit</a>
                    <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($queueRow['prompt'] ?? '')) ?>">Kopirovat prompt</button>
                    <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($queueRow['asset_path'] ?? '')) ?>">Kopirovat path</button>
                    <a class="btn btn-secondary btn-small" href="<?= esc((string) ($queueRow['article_url'] ?? article_url((string) $queueRow['slug']))) ?>" target="_blank" rel="noopener">Clanok</a>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>

        <?php if ($section === 'affiliates'): ?>
          <section class="admin-card">
            <div class="admin-card-head">
              <div>
                <p class="admin-kicker">Affiliate management</p>
                <h2>Centralizovane /go/ odkazy</h2>
              </div>
              <form method="get" action="/admin" class="admin-inline-form">
                <input type="hidden" name="section" value="affiliates" />
                <select name="code" onchange="this.form.submit()">
                  <?php foreach ($affiliateCodes as $code): ?>
                    <option value="<?= esc($code) ?>" <?= $code === $selectedAffiliateCode ? 'selected' : '' ?>><?= esc($code) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </div>

            <?php if ($returnSectionPrefill !== "" && $returnSlugPrefill !== ""): ?>
              <section class="admin-subsection is-compact">
                <p class="admin-note">Tento editor bol otvoreny z workflowu pre clanok <strong><?= esc($returnSlugPrefill) ?></strong>.</p>
                <div class="admin-inline-actions">
                  <a class="btn btn-secondary btn-small" href="/admin?section=<?= esc($returnSectionPrefill) ?>&amp;slug=<?= esc($returnSlugPrefill) ?>">Spat do workflowu clanku</a>
                </div>
              </section>
            <?php endif; ?>

            <section class="admin-subsection is-compact">
              <div class="admin-subsection-head">
                <h3>Rychlo vytvorit novy affiliate kod</h3>
              </div>
              <form method="post" class="admin-form admin-form-stack">
                <input type="hidden" name="action" value="create_affiliate" />
                <?php if ($returnSectionPrefill !== ""): ?><input type="hidden" name="return_section" value="<?= esc($returnSectionPrefill) ?>" /><?php endif; ?>
                <?php if ($returnSlugPrefill !== ""): ?><input type="hidden" name="return_slug" value="<?= esc($returnSlugPrefill) ?>" /><?php endif; ?>
                <div class="admin-grid three-up">
                  <label><span>Kod</span><input type="text" name="new_affiliate_code" value="<?= esc($prefillAffiliateCode) ?>" placeholder="kolagen-recenzia-gymbeam" /></label>
                  <label><span>Merchant</span><input type="text" name="new_affiliate_merchant" value="<?= esc($prefillAffiliateMerchant) ?>" placeholder="GymBeam" /></label>
                  <label><span>Merchant slug</span><input type="text" name="new_affiliate_merchant_slug" value="<?= esc($prefillAffiliateMerchantSlug) ?>" placeholder="gymbeam" /></label>
                </div>
                <div class="admin-grid two-up">
                  <label><span>Product slug</span><input type="text" name="new_affiliate_product_slug" value="<?= esc($prefillAffiliateProductSlug) ?>" placeholder="gymbeam-collagen-complex" /></label>
                  <label><span>Typ linku</span>
                    <select name="new_affiliate_link_type">
                      <option value="affiliate">affiliate</option>
                      <option value="product">product</option>
                    </select>
                  </label>
                </div>
                <div class="admin-actions">
                  <button class="btn btn-secondary" type="submit">Vytvorit affiliate kod</button>
                </div>
              </form>
            </section>

            <form method="post" class="admin-form admin-form-stack">
              <input type="hidden" name="action" value="save_affiliate" />
              <?php if ($returnSectionPrefill !== ""): ?><input type="hidden" name="return_section" value="<?= esc($returnSectionPrefill) ?>" /><?php endif; ?>
              <?php if ($returnSlugPrefill !== ""): ?><input type="hidden" name="return_slug" value="<?= esc($returnSlugPrefill) ?>" /><?php endif; ?>
              <div class="admin-grid two-up">
                <label><span>Kod</span><input type="text" name="code" value="<?= esc((string) ($selectedAffiliate['code'] ?? $selectedAffiliateCode)) ?>" /></label>
                <label><span>Typ linku</span>
                  <select name="link_type">
                    <?php $linkType = (string) ($selectedAffiliate['link_type'] ?? 'affiliate'); ?>
                    <option value="affiliate" <?= $linkType === 'affiliate' ? 'selected' : '' ?>>affiliate</option>
                    <option value="product" <?= $linkType === 'product' ? 'selected' : '' ?>>product</option>
                  </select>
                </label>
              </div>
              <label><span>Cielova URL</span><input type="url" name="url" value="<?= esc((string) ($selectedAffiliate['url'] ?? '')) ?>" /></label>
              <div class="admin-grid three-up">
                <label><span>Obchod</span><input type="text" name="merchant" value="<?= esc((string) ($selectedAffiliate['merchant'] ?? '')) ?>" /></label>
                <label><span>Merchant slug</span><input type="text" name="merchant_slug" value="<?= esc((string) ($selectedAffiliate['merchant_slug'] ?? '')) ?>" /></label>
                <label><span>Product slug</span><input type="text" name="product_slug" value="<?= esc((string) ($selectedAffiliate['product_slug'] ?? '')) ?>" /></label>
              </div>
              <div class="admin-actions">
                <button class="btn btn-cta" type="submit">Ulozit affiliate odkaz</button>
                <?php if ($selectedAffiliateCode !== ''): ?>
                  <a class="btn btn-secondary" href="/go/<?= rawurlencode($selectedAffiliateCode) ?>" target="_blank" rel="noopener">Otvorit /go/ link</a>
                <?php endif; ?>
                <button class="btn btn-secondary" type="submit" name="action" value="delete_affiliate_override" onclick="return confirm('Naozaj zmazat admin override affiliate odkazu?');">Zmazat override odkazu</button>
              </div>
            </form>
          </section>
        <?php endif; ?>

        <?php if ($section === 'help'): ?>
          <section class="admin-card">
            <div class="admin-card-head">
              <div>
                <p class="admin-kicker">Pomoc pre kazdodennu pracu</p>
                <h2>Co kliknut a v akom poradi</h2>
              </div>
            </div>
            <section class="admin-subsection is-compact">
              <div class="admin-subsection-head">
                <div>
                  <h3>Co chces urobit prave teraz</h3>
                  <p class="admin-meta">Ak nechces studovat cely admin, zacni jednym z tychto 4 krokov.</p>
                </div>
              </div>
              <div class="admin-help-grid">
                <article class="admin-help-card">
                  <h3>Upravit clanok</h3>
                  <p class="admin-note">Nazov, intro, sekcie, SEO meta a odporucane produkty.</p>
                  <div class="admin-inline-actions">
                    <a class="btn btn-secondary btn-small" href="/admin?section=articles&amp;slug=<?= esc($selectedArticleSlug) ?>">Otvorit Clanky</a>
                    <a class="btn btn-secondary btn-small" href="<?= esc(article_url($selectedArticleSlug)) ?>" target="_blank" rel="noopener">Live clanok</a>
                  </div>
                </article>
                <article class="admin-help-card">
                  <h3>Doplnit hero obrazok</h3>
                  <p class="admin-note">Prompt, filename a upload finalneho WebP obrazku clanku.</p>
                  <div class="admin-inline-actions">
                    <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>">Otvorit Images</a>
                    <a class="btn btn-secondary btn-small" href="/hero-helper" target="_blank" rel="noopener">Hero helper</a>
                  </div>
                </article>
                <article class="admin-help-card">
                  <h3>Doplnit obrazok produktu</h3>
                  <p class="admin-note">Pouzi najprv Zrkadlit remote, az potom manualny upload.</p>
                  <div class="admin-inline-actions">
                    <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc($selectedProductSlug) ?>&amp;product_image_filter=missing">Otvorit Produkty</a>
                    <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>">Workflow clanku</a>
                  </div>
                </article>
                <article class="admin-help-card">
                  <h3>Doplnit Dognet link</h3>
                  <p class="admin-note">Finalny deeplink patri do centralnej affiliate sekcie.</p>
                  <div class="admin-inline-actions">
                    <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;code=<?= esc($selectedAffiliateCode) ?>">Affiliate odkazy</a>
                    <?php if ($selectedAffiliateCode !== ''): ?><a class="btn btn-secondary btn-small" href="/go/<?= rawurlencode($selectedAffiliateCode) ?>" target="_blank" rel="noopener">Otvorit /go/</a><?php endif; ?>
                  </div>
                </article>
              </div>
            </section>
            <div class="admin-help-grid">
              <article class="admin-help-card">
                <h3>1. Chcem upravit clanok</h3>
                <ol class="admin-quickstart-list">
                  <li>Otvor <a href="/admin?section=articles&slug=<?= esc($selectedArticleSlug) ?>">Clanky</a> a vyber konkretne URL.</li>
                  <li>Uprav titulok, intro, sekcie a SEO meta.</li>
                  <li>Ak je to money page, pouzi reusable produkty a tlacidlo <strong>Money-page scaffold</strong>.</li>
                  <li>Uloz clanok a otvor live stranku v novom tabu.</li>
                </ol>
              </article>

              <article class="admin-help-card">
                <h3>2. Chcem doplnit hero obrazok</h3>
                <ol class="admin-quickstart-list">
                  <li>Otvor <a href="/admin?section=images&slug=<?= esc($selectedArticleSlug) ?>">Image briefy</a>.</li>
                  <li>Skopiruj <strong>prompt</strong>, <strong>filename</strong> a <strong>target path</strong>.</li>
                  <li>V Canve alebo AI nastroji vytvor obrazok bez textu a exportuj WebP.</li>
                  <li>Nahraj hero cez admin a skontroluj live clanok.</li>
                </ol>
              </article>

              <article class="admin-help-card">
                <h3>3. Chcem doplnit obrazok produktu</h3>
                <ol class="admin-quickstart-list">
                  <li>Otvor <a href="/admin?section=products&product=<?= esc($selectedProductSlug) ?>">Produkty</a> alebo queue chybajucich obrazkov v image workflowe.</li>
                  <li>Ak uz produkt ma remote obrazok od merchanta, klikni <strong>Zrkadlit remote</strong>.</li>
                  <li>Ak remote obrazok nie je k dispozicii, skopiruj cielovu asset cestu a nahraj obrazok cez rychly upload.</li>
                  <li>Vrat sa na clanok a skontroluj, ci karta uz ukazuje finalny produktovy obrazok.</li>
                </ol>
              </article>

              <article class="admin-help-card">
                <h3>4. Chcem doplnit Dognet link</h3>
                <ol class="admin-quickstart-list">
                  <li>Otvor <a href="/admin?section=affiliates&code=<?= esc($selectedAffiliateCode) ?>">Affiliate odkazy</a>.</li>
                  <li>Vyber existujuci kod alebo vytvor novy interny <code>/go/</code> kod.</li>
                  <li>Vloz finalny Dognet deeplink a merchant data.</li>
                  <li>Skontroluj CTA na live stranke alebo otvor <code>/go/...</code> link.</li>
                </ol>
              </article>

              <article class="admin-help-card">
                <h3>5. Chcem rychlo vytvorit novu money page</h3>
                <ol class="admin-quickstart-list">
                  <li>V Clankoch vytvor novy clanok ako <strong>comparison</strong> alebo <strong>review</strong>.</li>
                  <li>Pridaj reusable produkty z katalogu.</li>
                  <li>Pouzi <strong>Money-page scaffold</strong> alebo <strong>Top 3 hotove vybery</strong>.</li>
                  <li>Dopln hero obrazok, obrazky produktov a affiliate linky iba tam, kde queue ukazuje medzery.</li>
                </ol>
              </article>

              <article class="admin-help-card">
                <h3>6. Odporucany bezny workflow</h3>
                <ol class="admin-quickstart-list">
                  <li>Najprv clanok a struktura.</li>
                  <li>Potom reusable produkty a porovnanie.</li>
                  <li>Potom hero obrazok a obrazky produktov.</li>
                  <li>Az nakoniec Dognet deeplinky a finalna kontrola live stranky.</li>
                </ol>
              </article>
            </div>

            <?php if ($helpPriorityHeroes !== [] || $helpPriorityProductImages !== []): ?>
              <section class="admin-subsection admin-help-checklist">
                <h3>Co urobit najblizsie</h3>
                <div class="admin-check-card-wrap">
                  <?php if ($helpPriorityHeroes !== []): ?>
                    <div class="admin-check-card">
                      <div>
                        <strong>Hero obrazky s najvyssou prioritou</strong>
                        <small>Zacni tymito clankami, aby sa najrychlejsie zlepsili hlavne vstupne stranky a money pages.</small>
                        <div class="admin-queue-list">
                          <?php foreach ($helpPriorityHeroes as $heroRow): ?>
                            <article class="admin-queue-item is-done">
                              <div>
                                <strong><?= esc((string) ($heroRow['title'] ?? '')) ?></strong>
                                <p><?= esc((string) ($heroRow['slug'] ?? '')) ?></p>
                                <?php if (trim((string) ($heroRow['asset_path'] ?? '')) !== ''): ?>
                                  <code><?= esc((string) ($heroRow['asset_path'] ?? '')) ?></code>
                                <?php endif; ?>
                              </div>
                              <div class="admin-queue-actions">
                                <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc((string) ($heroRow['slug'] ?? '')) ?>">Image brief</a>
                                <a class="btn btn-secondary btn-small" href="/hero-helper" target="_blank" rel="noopener">Hero helper</a>
                              </div>
                            </article>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>

                  <?php if ($helpPriorityProductImages !== []): ?>
                    <div class="admin-check-card">
                      <div>
                        <strong>Produktove obrazky, ktore vies doplnit hned</strong>
                        <small>Tieto produkty uz maju remote zdroj, takze ich vies zvycajne uzavriet jednym klikom cez <strong>Zrkadlit remote</strong>.</small>
                        <div class="admin-queue-list">
                          <?php foreach ($helpPriorityProductImages as $productRow): ?>
                            <article class="admin-queue-item is-done">
                              <div>
                                <strong><?= esc((string) ($productRow['name'] ?? '')) ?></strong>
                                <p><?= esc((string) ($productRow['slug'] ?? '')) ?><?php if (trim((string) ($productRow['merchant'] ?? '')) !== ''): ?> / <?= esc((string) ($productRow['merchant'] ?? '')) ?><?php endif; ?></p>
                                <?php if (trim((string) ($productRow['target_asset'] ?? '')) !== ''): ?>
                                  <code><?= esc((string) ($productRow['target_asset'] ?? '')) ?></code>
                                <?php endif; ?>
                              </div>
                              <div class="admin-queue-actions">
                                <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($productRow['slug'] ?? '')) ?>&amp;product_image_filter=missing">Produkt</a>
                              </div>
                            </article>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
              </section>
            <?php endif; ?>

            <section class="admin-subsection admin-help-checklist">
              <h3>Pred publikovanim skontroluj</h3>
              <div class="admin-check-card-wrap">
                <label class="admin-check-card">
                  <input type="checkbox" />
                  <div>
                    <strong>Titulok, intro a meta description su vyplnene</strong>
                    <small>Clanok by mal mat aj jasnu hlavnu temu a cisty slug.</small>
                  </div>
                </label>
                <label class="admin-check-card">
                  <input type="checkbox" />
                  <div>
                    <strong>Odporucane produkty maju affiliate a obrazok</strong>
                    <small>Ak nie, otvor queue nedokoncenych produktov alebo affiliate queue.</small>
                  </div>
                </label>
                <label class="admin-check-card">
                  <input type="checkbox" />
                  <div>
                    <strong>Hero obrazok je finalny WebP</strong>
                    <small>SVG fallback nechaj len docasne, nie ako finalny vystup.</small>
                  </div>
                </label>
                <label class="admin-check-card">
                  <input type="checkbox" />
                  <div>
                    <strong>Live clanok vyzera dobre na fronte</strong>
                    <small>Skontroluj CTA, porovnanie, related bloky a obrazky.</small>
                  </div>
                </label>
              </div>
            </section>
          </section>
        <?php endif; ?>

        <?php if ($section === 'tools'): ?>
          <section class="admin-card">
            <div class="admin-card-head">
              <div>
                <p class="admin-kicker">Import / export workflow</p>
                <h2>Admin bundle, feed import a batch briefy</h2>
              </div>
            </div>
            <div class="admin-tools-grid">
              <section class="admin-subsection">
                <h3>Export admin balika</h3>
                <p>Stiahne aktualne article, product a affiliate override data v jednom JSON subore.</p>
                <form method="post" class="admin-form">
                  <input type="hidden" name="action" value="export_bundle" />
                  <button class="btn btn-cta" type="submit">Exportovat JSON balik</button>
                </form>
              </section>

              <section class="admin-subsection">
                <h3>Export image backlog CSV</h3>
                <p>Stiahne zoznam chybajucich hero obrazkov a obrazkov produktov aj s cielovymi asset cestami.</p>
                <form method="post" class="admin-form">
                  <input type="hidden" name="action" value="export_image_backlog_csv" />
                  <button class="btn btn-secondary" type="submit">Exportovat image backlog</button>
                </form>
              </section>

              <section class="admin-subsection">
                <h3>Import admin balika</h3>
                <p>Nahraj skor exportovany JSON balik a admin ho sluci s aktualnymi override datami.</p>
                <form method="post" enctype="multipart/form-data" class="admin-form admin-form-stack">
                  <input type="hidden" name="action" value="import_bundle" />
                  <label>
                    <span>JSON balik</span>
                    <input type="file" name="bundle_file" accept="application/json,.json" required />
                  </label>
                  <button class="btn btn-cta" type="submit">Importovat balik</button>
                </form>
              </section>

              <section class="admin-subsection">
                <h3>Feed import produktov</h3>
                <p>Nahraj XML alebo CSV feed a admin z neho vytvori alebo aktualizuje produkty v admin override vrstve.</p>
                <form method="post" enctype="multipart/form-data" class="admin-form admin-form-stack">
                  <input type="hidden" name="action" value="feed_import" />
                  <div class="admin-grid two-up">
                    <label>
                      <span>Merchant slug</span>
                      <input type="text" name="feed_merchant_slug" value="gymbeam" required />
                    </label>
                    <label>
                      <span>Limit (0 = vsetko)</span>
                      <input type="number" name="feed_limit" min="0" step="1" value="25" />
                    </label>
                  </div>
                  <label>
                    <span>Feed subor</span>
                    <input type="file" name="feed_file" accept=".xml,.csv,.txt" required />
                  </label>
                  <button class="btn btn-cta" type="submit">Importovat produkty z feedu</button>
                </form>
              </section>

              <section class="admin-subsection">
                <h3>Affiliate CSV import</h3>
                <p>Nahraj CSV s code/url alebo deeplink_url a admin ho pripoji do centralnej affiliate override vrstvy.</p>
                <form method="post" enctype="multipart/form-data" class="admin-form admin-form-stack">
                  <input type="hidden" name="action" value="affiliate_csv_import" />
                  <label>
                    <span>Affiliate CSV</span>
                    <input type="file" name="affiliate_csv_file" accept=".csv,.txt" required />
                  </label>
                  <button class="btn btn-cta" type="submit">Importovat affiliate CSV</button>
                </form>
              </section>

              <section class="admin-subsection">
                <h3>Batch hero briefy</h3>
                <p>Vygeneruje CSV pre Canva / AI workflow napriec clankami.</p>
                <form method="post" class="admin-form">
                  <input type="hidden" name="action" value="export_briefs_csv" />
                  <button class="btn btn-cta" type="submit">Exportovat briefy do CSV</button>
                </form>
              </section>
            </div>

            <div class="admin-subsection">
              <h3>Preview briefov</h3>
              <div class="admin-brief-table-wrap">
                <table class="admin-brief-table">
                  <thead>
                    <tr>
                      <th>Slug</th>
                      <th>Filename</th>
                      <th>Dimensions</th>
                      <th>Alt text</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($briefRows as $row): ?>
                      <tr>
                        <td><?= esc((string) ($row['slug'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['filename'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['dimensions'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['alt_text'] ?? '')) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </section>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/inc/footer.php'; ?>




