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

function interessa_admin_product_image_redirect_query(string $slug, string $saved, string $returnSection = '', string $returnSlug = ''): array {
    $query = [
        'product' => $slug,
        'saved' => $saved,
        'focus' => 'product_image',
    ];
    if ($returnSection !== '' && $returnSlug !== '') {
        $query['return_section'] = $returnSection;
        $query['return_slug'] = $returnSlug;
    }
    return $query;
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
                'image_local_asset' => '',
                'image_remote_src' => '',
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
        $packshotReady = !empty($normalized['has_local_image']);
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
            'image_local_asset' => (string) ($normalized['image_local_asset'] ?? ''),
            'image_remote_src' => (string) ($normalized['image_remote_src'] ?? ''),
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

function interessa_admin_product_image_brief(array $product): array {
    $brief = interessa_product_packshot_brief($product);
    $brief['name'] = interessa_admin_clean_label((string) ($brief['name'] ?? 'Produkt'));
    $brief['merchant'] = interessa_admin_clean_label((string) ($brief['merchant'] ?? ''));
    $brief['alt_text'] = interessa_admin_clean_label((string) ($brief['alt_text'] ?? ''));
    return $brief;
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
            'fallback_url' => (string) ($normalized['fallback_url'] ?? ''),
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

function interessa_admin_money_page_image_gap_report(string $merchantFilter = 'all'): array {
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

    $flatRows = [];

    foreach ($articleSlugs as $articleSlug) {
        $articleMeta = article_meta($articleSlug);
        $articleTitle = trim((string) ($articleMeta['title'] ?? humanize_slug($articleSlug)));
        if (function_exists('interessa_fix_mojibake')) {
            $articleTitle = interessa_fix_mojibake($articleTitle);
        }

        $commerce = interessa_article_commerce($articleSlug);
        $products = is_array($commerce['products'] ?? null) ? $commerce['products'] : [];
        foreach ($products as $productRow) {
            $resolved = interessa_resolve_product_reference(is_array($productRow) ? $productRow : []);
            $imageMode = trim((string) ($resolved['image_mode'] ?? 'placeholder'));
            if ($imageMode === 'local' || $imageMode === 'remote') {
                continue;
            }

            $productSlug = trim((string) ($resolved['slug'] ?? ''));
            $productName = trim((string) ($resolved['product_name'] ?? $resolved['name'] ?? $productSlug));
            $merchant = trim((string) ($resolved['merchant'] ?? ''));
            if (function_exists('interessa_fix_mojibake')) {
                $productName = interessa_fix_mojibake($productName);
                $merchant = interessa_fix_mojibake($merchant);
            }

            $product = $productSlug !== '' ? interessa_product($productSlug) : null;
            $briefSource = is_array($product) ? $product : [
                'slug' => $productSlug,
                'product_slug' => $productSlug,
                'name' => $productName,
                'product_name' => $productName,
                'merchant' => $merchant,
                'merchant_slug' => $merchant,
                'fallback_url' => trim((string) ($resolved['fallback_url'] ?? $resolved['url'] ?? '')),
                'target_asset' => trim((string) ($resolved['image_target_asset'] ?? '')),
            ];
            $brief = interessa_admin_product_image_brief($briefSource);
            $merchantSlug = interessa_admin_slugify($merchant);
            if ($merchantSlug === '') {
                $merchantSlug = 'nezaradene';
            }

            $flatRows[] = [
                'article_slug' => $articleSlug,
                'article_title' => $articleTitle,
                'product_slug' => $productSlug,
                'product_name' => $productName,
                'merchant' => $merchant,
                'merchant_slug' => $merchantSlug,
                'affiliate_code' => trim((string) ($resolved['code'] ?? $resolved['affiliate_code'] ?? '')),
                'target_asset' => trim((string) ($resolved['image_target_asset'] ?? '')),
                'fallback_url' => trim((string) ($resolved['fallback_url'] ?? $resolved['url'] ?? '')),
                'image_brief' => $brief,
                'article_url' => article_url($articleSlug),
                'workflow_url' => '/admin?section=images&slug=' . rawurlencode($articleSlug),
                'product_url' => $productSlug !== '' ? '/admin?section=products&product=' . rawurlencode($productSlug) . '&return_section=images&return_slug=' . rawurlencode($articleSlug) : '',
            ];
        }
    }

    $merchantGroups = [];
    foreach ($flatRows as $row) {
        $merchantSlug = (string) ($row['merchant_slug'] ?? 'nezaradene');
        $merchantName = trim((string) ($row['merchant'] ?? '')) !== '' ? (string) ($row['merchant'] ?? '') : 'Nezaradene';
        if (!isset($merchantGroups[$merchantSlug])) {
            $merchantGroups[$merchantSlug] = [
                'merchant_slug' => $merchantSlug,
                'merchant' => $merchantName,
                'count' => 0,
                'articles' => [],
                'rows' => [],
            ];
        }

        $merchantGroups[$merchantSlug]['count']++;
        $merchantGroups[$merchantSlug]['articles'][(string) ($row['article_slug'] ?? '')] = true;
        $merchantGroups[$merchantSlug]['rows'][] = $row;
    }

    uasort($merchantGroups, static function (array $left, array $right): int {
        $countSort = ((int) ($right['count'] ?? 0)) <=> ((int) ($left['count'] ?? 0));
        if ($countSort !== 0) {
            return $countSort;
        }

        return strcasecmp((string) ($left['merchant'] ?? ''), (string) ($right['merchant'] ?? ''));
    });

    $merchantGroups = array_values(array_map(static function (array $group): array {
        $group['article_count'] = count($group['articles'] ?? []);
        $group['sample_products'] = array_values(array_slice(array_map(static function (array $row): string {
            return (string) ($row['product_name'] ?? $row['product_slug'] ?? 'Produkt');
        }, (array) ($group['rows'] ?? [])), 0, 3));
        unset($group['articles']);
        return $group;
    }, $merchantGroups));

    $merchantFilter = interessa_admin_slugify($merchantFilter);
    if ($merchantFilter === '') {
        $merchantFilter = 'all';
    }

    $filteredRows = $flatRows;
    if ($merchantFilter !== 'all') {
        $filteredRows = array_values(array_filter($flatRows, static function (array $row) use ($merchantFilter): bool {
            return (string) ($row['merchant_slug'] ?? '') === $merchantFilter;
        }));
    }

    $groupMap = [];
    foreach ($filteredRows as $row) {
        $articleSlug = (string) ($row['article_slug'] ?? '');
        if ($articleSlug === '') {
            continue;
        }

        if (!isset($groupMap[$articleSlug])) {
            $groupMap[$articleSlug] = [
                'article_slug' => $articleSlug,
                'article_title' => (string) ($row['article_title'] ?? humanize_slug($articleSlug)),
                'count' => 0,
                'article_url' => (string) ($row['article_url'] ?? article_url($articleSlug)),
                'workflow_url' => '/admin?section=images&slug=' . rawurlencode($articleSlug),
                'rows' => [],
            ];
        }

        $groupMap[$articleSlug]['rows'][] = $row;
        $groupMap[$articleSlug]['count']++;
    }

    $groups = [];
    foreach ($articleSlugs as $articleSlug) {
        if (isset($groupMap[$articleSlug])) {
            $groups[] = $groupMap[$articleSlug];
        }
    }

    return [
        'tracked_pages' => count($articleSlugs),
        'filtered_pages' => count($groups),
        'missing_products_total' => count($flatRows),
        'missing_products' => count($filteredRows),
        'merchant_filter' => $merchantFilter,
        'merchant_groups' => $merchantGroups,
        'rows' => $filteredRows,
        'groups' => $groups,
    ];
}

function interessa_admin_money_page_gap_brief_pack(array $report): array {
    $merchantFilter = trim((string) ($report['merchant_filter'] ?? 'all'));
    if ($merchantFilter === '' || $merchantFilter === 'all') {
        return [
            'title' => 'Batch brief pack',
            'text' => '',
            'count' => 0,
        ];
    }

    $merchantName = $merchantFilter;
    foreach ((array) ($report['merchant_groups'] ?? []) as $merchantGroup) {
        if ((string) ($merchantGroup['merchant_slug'] ?? '') === $merchantFilter) {
            $merchantName = (string) ($merchantGroup['merchant'] ?? $merchantFilter);
            break;
        }
    }

    $lines = [];
    $lines[] = 'Merchant batch: ' . $merchantName;
    $lines[] = 'Pocet chybejucich obrazkov: ' . (string) ($report['missing_products'] ?? 0);
    $lines[] = '';

    foreach ((array) ($report['rows'] ?? []) as $row) {
        $brief = is_array($row['image_brief'] ?? null) ? $row['image_brief'] : [];
        $lines[] = 'Produkt: ' . (string) ($row['product_name'] ?? $row['product_slug'] ?? 'Produkt');
        $lines[] = 'Clanok: ' . (string) ($row['article_title'] ?? $row['article_slug'] ?? '');
        if (trim((string) ($brief['file_name'] ?? '')) !== '') {
            $lines[] = 'Filename: ' . (string) ($brief['file_name'] ?? '');
        }
        if (trim((string) ($brief['alt_text'] ?? '')) !== '') {
            $lines[] = 'Alt text: ' . (string) ($brief['alt_text'] ?? '');
        }
        if (trim((string) ($brief['dimensions'] ?? '')) !== '') {
            $lines[] = 'Dimensions: ' . (string) ($brief['dimensions'] ?? '');
        }
        if (trim((string) ($row['target_asset'] ?? '')) !== '') {
            $lines[] = 'Target path: ' . (string) ($row['target_asset'] ?? '');
        }
        if (trim((string) ($brief['reference_url'] ?? '')) !== '') {
            $lines[] = 'Referencia: ' . (string) ($brief['reference_url'] ?? '');
        }
        if (trim((string) ($brief['prompt'] ?? '')) !== '') {
            $lines[] = 'Prompt: ' . (string) ($brief['prompt'] ?? '');
        }
        $lines[] = '';
    }

    return [
        'title' => 'Batch brief pack pre ' . $merchantName,
        'text' => trim(implode(PHP_EOL, $lines)),
        'count' => count((array) ($report['rows'] ?? [])),
    ];
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
        $packshotReady = !empty($normalized['has_local_image']);

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

function interessa_admin_output_money_page_image_gap_csv(array $report): never {
    $merchantFilter = trim((string) ($report['merchant_filter'] ?? 'all'));
    $suffix = $merchantFilter !== '' && $merchantFilter !== 'all' ? '-' . $merchantFilter : '';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="money-page-image-gaps' . $suffix . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'article_slug',
        'article_title',
        'product_slug',
        'product_name',
        'merchant',
        'merchant_slug',
        'affiliate_code',
        'target_asset',
        'fallback_url',
        'brief_filename',
        'brief_alt_text',
        'brief_dimensions',
        'brief_prompt',
        'workflow_url',
        'product_url',
        'article_url',
    ]);

    foreach ((array) ($report['rows'] ?? []) as $row) {
        $brief = is_array($row['image_brief'] ?? null) ? $row['image_brief'] : [];
        fputcsv($out, [
            (string) ($row['article_slug'] ?? ''),
            (string) ($row['article_title'] ?? ''),
            (string) ($row['product_slug'] ?? ''),
            (string) ($row['product_name'] ?? ''),
            (string) ($row['merchant'] ?? ''),
            (string) ($row['merchant_slug'] ?? ''),
            (string) ($row['affiliate_code'] ?? ''),
            (string) ($row['target_asset'] ?? ''),
            (string) ($row['fallback_url'] ?? ''),
            (string) ($brief['file_name'] ?? ''),
            (string) ($brief['alt_text'] ?? ''),
            (string) ($brief['dimensions'] ?? ''),
            (string) ($brief['prompt'] ?? ''),
            (string) ($row['workflow_url'] ?? ''),
            (string) ($row['product_url'] ?? ''),
            (string) ($row['article_url'] ?? ''),
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
$adminScript = asset('js/admin.js');
$adminScript .= (str_contains($adminScript, '?') ? '&' : '?') . 'copyfix=20260314b';
$page_scripts = [$adminScript];

$section = interessa_admin_selected_section();
$flash = trim((string) ($_GET['saved'] ?? ''));
$focusProductSlug = trim((string) ($_GET['focus_product'] ?? ''));
$focusPanel = trim((string) ($_GET['focus'] ?? ''));
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
    $proxyAction = trim((string) ($_GET['action'] ?? ''));
    if ($proxyAction === 'product_remote_image_proxy') {
        try {
            $slug = trim((string) ($_GET['product'] ?? ''));
            $download = interessa_admin_prepare_remote_product_image_download($slug);
            $contentType = trim((string) ($download['content_type'] ?? 'application/octet-stream'));
            $fileName = trim((string) ($download['file_name'] ?? 'remote-packshot'));
            $safeFileName = preg_replace('~[^a-zA-Z0-9._-]+~', '-', $fileName) ?: 'remote-packshot';
            $body = (string) ($download['body'] ?? '');

            header('Content-Type: ' . $contentType);
            header('Content-Length: ' . strlen($body));
            header('Content-Disposition: inline; filename="' . $safeFileName . '"');
            echo $body;
            exit;
        } catch (Throwable $e) {
            http_response_code(422);
            header('Content-Type: text/plain; charset=UTF-8');
            echo trim($e->getMessage()) !== '' ? trim($e->getMessage()) : 'Remote packshot sa nepodarilo pripravit.';
            exit;
        }
    }

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
                $uploadedProductImage = !empty($_FILES['product_image']['tmp_name']);
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
                if ($returnSection === 'images' && $returnSlug !== '' && $uploadedProductImage) {
                    interessa_admin_redirect('products', interessa_admin_product_image_redirect_query($slug, 'packshot', 'images', $returnSlug));
                }
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
                    interessa_admin_redirect('products', interessa_admin_product_image_redirect_query($slug, 'packshot', 'images', $returnArticleSlug));
                }

                interessa_admin_redirect('products', interessa_admin_product_image_redirect_query($slug, 'packshot'));
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
                    interessa_admin_redirect('products', interessa_admin_product_image_redirect_query($slug, 'packshot-mirrored', 'images', $returnArticleSlug));
                }
                if ($returnSection === 'articles' && $returnArticleSlug !== '') {
                    interessa_admin_redirect('articles', ['slug' => $returnArticleSlug, 'saved' => 'packshot-mirrored']);
                }

                interessa_admin_redirect('products', interessa_admin_product_image_redirect_query($slug, 'packshot-mirrored'));
            }

            if ($action === 'enrich_product_from_source' || $action === 'autofill_product_from_source') {
                $slug = trim((string) ($_POST['product_slug'] ?? ''));
                $returnSection = trim((string) ($_POST['return_section'] ?? 'products'));
                $returnArticleSlug = canonical_article_slug(trim((string) ($_POST['return_slug'] ?? '')));

                $result = interessa_admin_enrich_product_record_from_source($slug);

                if ($action === 'autofill_product_from_source') {
                    $product = interessa_product($slug);
                    if (is_array($product)) {
                        $normalizedProduct = interessa_normalize_product($product);
                        $merchantSlug = trim((string) ($normalizedProduct['merchant_slug'] ?? ''));
                        $remoteSrc = trim((string) ($normalizedProduct['image_remote_src'] ?? ''));
                        $hasLocalImage = !empty($normalizedProduct['has_local_image']);
                        if (!$hasLocalImage && $remoteSrc !== '') {
                            $remoteExt = interessa_admin_detect_remote_image_extension($remoteSrc);
                            if ($remoteExt === 'webp') {
                                $asset = interessa_admin_mirror_remote_product_image($slug, $merchantSlug, $remoteSrc);
                                $payload = array_replace($normalizedProduct, interessa_admin_product_record($slug) ?? []);
                                $payload['image_asset'] = $asset;
                                interessa_admin_save_product_record($slug, $payload);
                            }
                        }
                    }
                }

                $savedKey = $action === 'autofill_product_from_source' ? 'product-autofill' : 'product-enriched';
                if ($action === 'autofill_product_from_source') {
                    $product = interessa_product($slug);
                    if (is_array($product)) {
                        $normalizedProduct = interessa_normalize_product($product);
                        if (empty($normalizedProduct['has_local_image']) && trim((string) ($normalizedProduct['image_remote_src'] ?? '')) !== '') {
                            $savedKey = interessa_admin_detect_remote_image_extension((string) $normalizedProduct['image_remote_src']) === 'webp'
                                ? 'product-autofill'
                                : 'product-remote-ready';
                        }
                    }
                }
                if ($returnSection === 'images' && $returnArticleSlug !== '') {
                    interessa_admin_redirect('products', interessa_admin_product_image_redirect_query($slug, $savedKey, 'images', $returnArticleSlug));
                }
                if ($returnSection === 'articles' && $returnArticleSlug !== '') {
                    interessa_admin_redirect('articles', ['slug' => $returnArticleSlug, 'saved' => $savedKey]);
                }

                interessa_admin_redirect('products', interessa_admin_product_image_redirect_query($slug, $savedKey));
            }

            if ($action === 'autofill_gap_products_by_filter') {
                $merchantFilter = trim((string) ($_POST['merchant_filter'] ?? 'all'));
                $report = interessa_admin_money_page_image_gap_report($merchantFilter);
                $result = interessa_admin_autofill_product_image_gaps((array) ($report['rows'] ?? []));
                $summaryBits = [
                    'spracovane ' . (int) ($result['processed'] ?? 0),
                    'obohatene ' . (int) ($result['enriched'] ?? 0),
                    'zrkadlene ' . (int) ($result['mirrored'] ?? 0),
                ];
                if ((int) ($result['failed'] ?? 0) > 0) {
                    $summaryBits[] = 'chyby ' . (int) ($result['failed'] ?? 0);
                }
                $importSummary = 'Autofill obrazkov: ' . implode(', ', $summaryBits) . '.';
                $flash = 'gap-autofill';
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

            if ($action === 'export_money_page_image_gap_csv') {
                $merchantFilter = trim((string) ($_POST['merchant_filter'] ?? 'all'));
                interessa_admin_output_money_page_image_gap_csv(interessa_admin_money_page_image_gap_report($merchantFilter));
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
$selectedProductLocalAsset = is_array($selectedProduct) ? trim((string) ($selectedProduct['image_local_asset'] ?? '')) : '';
$selectedProductLocalImageUrl = $selectedProductLocalAsset !== '' ? asset($selectedProductLocalAsset) : '';
$selectedProductImageBrief = is_array($selectedProduct) ? interessa_admin_product_image_brief($selectedProduct) : [];
$selectedProductAffiliate = is_array($selectedProduct) && trim((string) ($selectedProduct['affiliate_code'] ?? '')) !== '' ? aff_record((string) $selectedProduct['affiliate_code']) : null;
$selectedProductAffiliateUrl = is_array($selectedProduct) && trim((string) ($selectedProduct['affiliate_code'] ?? '')) !== '' ? aff_resolve((string) $selectedProduct['affiliate_code']) : null;
$selectedProductAffiliateType = $selectedProductAffiliate !== null ? aff_link_type($selectedProductAffiliate) : '';
$selectedProductTarget = is_array($selectedProduct) ? interessa_affiliate_target($selectedProduct) : ['href' => '', 'label' => ''];
$selectedProductPros = is_array($selectedProduct) && is_array($selectedProduct['pros'] ?? null) ? array_values(array_filter(array_map('strval', $selectedProduct['pros']))) : [];
$selectedProductCons = is_array($selectedProduct) && is_array($selectedProduct['cons'] ?? null) ? array_values(array_filter(array_map('strval', $selectedProduct['cons']))) : [];
$selectedProductSummaryReady = is_array($selectedProduct) && trim((string) ($selectedProduct['summary'] ?? '')) !== '';
$selectedProductRatingReady = is_array($selectedProduct) && trim((string) ($selectedProduct['rating'] ?? '')) !== '';
$selectedProductAffiliateReady = is_array($selectedProductAffiliateUrl) && trim((string) ($selectedProductAffiliateUrl['href'] ?? '')) !== '';
$selectedProductPackshotReady = is_array($selectedProduct) && !empty($selectedProduct['has_local_image']);
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
$selectedAffiliateProductSlug = is_array($selectedAffiliate) ? trim((string) ($selectedAffiliate['product_slug'] ?? '')) : '';
$selectedAffiliateProduct = $selectedAffiliateProductSlug !== '' ? interessa_product($selectedAffiliateProductSlug) : null;
$selectedAffiliateProduct = is_array($selectedAffiliateProduct) ? interessa_normalize_product($selectedAffiliateProduct) : null;
$selectedAffiliateResolvedTarget = $selectedAffiliateCode !== '' ? aff_resolve($selectedAffiliateCode) : null;
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
$moneyPageMerchantFilter = interessa_admin_slugify((string) ($_GET['merchant_filter'] ?? 'all'));
if ($moneyPageMerchantFilter === '') {
    $moneyPageMerchantFilter = 'all';
}
$moneyPageImageGapReport = interessa_admin_money_page_image_gap_report($moneyPageMerchantFilter);
$moneyPageGapBriefPack = interessa_admin_money_page_gap_brief_pack($moneyPageImageGapReport);
$briefRows = interessa_admin_brief_rows($articleOptions);
$flashMessages = [
    'login' => 'Prihlasenie prebehlo uspesne.',
    'article' => 'Clanok bol ulozeny.',
    'article-created' => 'Novy clanok bol vytvoreny.',
    'article-reset' => 'Override clanku bol resetovany.',
    'product' => 'Produkt bol ulozeny.',
    'product-created' => 'Novy produkt bol vytvoreny.',
    'product-reset' => 'Override produktu bol zmazany.',
    'affiliate' => 'Affiliate zaznam bol ulozeny.',
    'affiliate-created' => 'Novy affiliate zaznam bol vytvoreny.',
    'affiliate-reset' => 'Affiliate override bol zmazany.',
    'hero' => 'Hero obrazok bol nahraty.',
    'packshot' => 'Produktovy obrazok bol nahraty.',
    'packshot-mirrored' => 'Remote obrazok bol zrkadleny do lokalneho assetu.',
    'product-enriched' => 'Produkt bol doplneny z referencnej produktovej stranky.',
    'product-autofill' => 'Produkt bol automaticky doplneny a obrazok sa pokusil zrkadlit.',
    'product-remote-ready' => 'Produkt ma zisteny obrazok z e-shopu. Teraz klikni Ulozit obrazok produktu z e-shopu a vznikne lokalny WebP.',
];
$flashMessage = $importSummary !== '' ? $importSummary : ($flashMessages[$flash] ?? '');

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
$recommendedDiagnosticsBySlug = [];
foreach ($recommendedDiagnosticsRows as $diagnosticRow) {
    $diagnosticSlug = trim((string) ($diagnosticRow['slug'] ?? ''));
    if ($diagnosticSlug !== '') {
        $recommendedDiagnosticsBySlug[$diagnosticSlug] = $diagnosticRow;
    }
}
$recommendedActionRows = is_array($recommendedDiagnostics['action_rows'] ?? null) ? $recommendedDiagnostics['action_rows'] : [];
$recommendedCatalogCoverage = (int) ($recommendedDiagnosticsSummary['catalog'] ?? 0);
$recommendedTotalCount = (int) ($recommendedDiagnosticsSummary['total'] ?? 0);
$recommendedMissingCount = (int) ($recommendedDiagnosticsSummary['missing_catalog'] ?? 0);
$recommendedAffiliateReadyCount = (int) ($recommendedDiagnosticsSummary['affiliate_ready'] ?? 0);
$recommendedPackshotReadyCount = (int) ($recommendedDiagnosticsSummary['packshot_ready'] ?? 0);
$recommendedMoneyReadyCount = (int) ($recommendedDiagnosticsSummary['money_ready'] ?? 0);
$recommendedCardReadyCount = (int) ($recommendedDiagnosticsSummary['card_ready'] ?? 0);
$selectedArticlePackshotGaps = array_values(array_map(static function (array $row): array {
    $brief = [];
    $slug = trim((string) ($row['slug'] ?? ''));
    if (!empty($row['exists']) && $slug !== '') {
        $product = interessa_product($slug);
        if (is_array($product)) {
            $brief = interessa_admin_product_image_brief($product);
        }
    }
    $row['image_brief'] = $brief;
    return $row;
}, array_filter($recommendedDiagnosticsRows, static function (array $row): bool {
    return !empty($row['exists']) && empty($row['packshot_ready']);
})));
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
      <?php if ($flashMessage !== ''): ?>
        <div class="admin-flash is-success">
          <div><?= esc($flashMessage) ?></div>
          <?php if ($section === 'products' && in_array($flash, ['packshot', 'packshot-mirrored', 'product-autofill'], true)): ?>
            <div class="admin-inline-actions">
              <?php if ($selectedProductLocalImageUrl !== ''): ?>
                <a class="btn btn-secondary btn-small" href="<?= esc($selectedProductLocalImageUrl) ?>" target="_blank" rel="noopener">Otvorit ulozeny obrazok</a>
              <?php endif; ?>
              <?php if ($returnSectionPrefill === 'images' && $returnSlugPrefill !== ''): ?>
                <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($returnSlugPrefill) ?>&amp;focus_product=<?= esc($selectedProductSlug) ?>">Spat do workflowu clanku</a>
              <?php endif; ?>
              <?php if ($nextMissingPackshotSlug !== ''): ?>
                <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc($nextMissingPackshotSlug) ?>&amp;product_image_filter=missing">Dalsi chyba</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <?php if ($error !== ''): ?>
        <div class="admin-flash is-error"><?= esc($error) ?></div>
      <?php endif; ?>
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
          <a class="<?= $section === 'images' ? 'is-active' : '' ?>" href="/admin?section=images&slug=<?= esc($selectedArticleSlug) ?>">Obrazky</a>
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
              <li>Skopiruj text pre Canvu.</li>
              <li>Sprav obrazok v Canve a stiahni ho.</li>
              <li>Nahraj obrazok sem a skontroluj clanok na webe.</li>
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
              <li>Ak ides riesit obrazky, otvor Obrazky.</li>
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
                <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>">Otvorit obrazky</a>
                <a class="btn btn-secondary btn-small" href="/hero-helper" target="_blank" rel="noopener">Pomocnik pre obrazok</a>
                <a class="btn btn-secondary btn-small" href="<?= esc(article_url($selectedArticleSlug)) ?>" target="_blank" rel="noopener">Otvorit clanok na webe</a>
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
                  <span>Hlavny obrazok clanku</span>
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
                      <?php $productNormalized = interessa_normalize_product(interessa_product((string) $productSlug) ?? (is_array($productRow) ? $productRow : [])); ?>
                      <?php $productTarget = interessa_affiliate_target($productNormalized); ?>
                      <?php $productAffiliateCode = trim((string) ($productNormalized['affiliate_code'] ?? '')); ?>
                      <?php $productImageMode = trim((string) ($productNormalized['image_mode'] ?? 'placeholder')); ?>
                      <?php $productPackshotReady = !empty($productNormalized['has_local_image']); ?>
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
                  <small class="admin-note">Kam sa ulozi hlavny obrazok: <code><?= esc((string) ($articlePrompt['asset_path'] ?? '')) ?></code></small>
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
                      <a class="btn btn-secondary btn-small" href="/hero-helper" target="_blank" rel="noopener">Pomocnik pre obrazok</a>
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
                              <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($actionRow['image_target_asset'] ?? '')) ?>">Kopirovat cestu</button>
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
                <a class="btn btn-secondary" href="<?= esc(article_url($selectedArticleSlug)) ?>" target="_blank" rel="noopener">Otvorit clanok na webe</a>
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
                      <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) $queueRow['slug']) ?>&amp;product_image_filter=<?= esc($productImageFilter) ?>">Otvorit detail produktu</a>
                      <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($queueRow['target_asset'] ?? '')) ?>">Kopirovat asset</button>
                      <?php if (trim((string) ($queueRow['fallback_url'] ?? '')) !== ''): ?>
                        <form method="post" class="admin-inline-form">
                          <input type="hidden" name="action" value="autofill_product_from_source" />
                          <input type="hidden" name="product_slug" value="<?= esc((string) ($queueRow['slug'] ?? '')) ?>" />
                          <button class="btn btn-secondary btn-small" type="submit">Auto doplnit</button>
                        </form>
                      <?php endif; ?>
                      <?php if (trim((string) ($queueRow['remote_src'] ?? '')) !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="<?= esc((string) $queueRow['remote_src']) ?>" target="_blank" rel="noopener">Remote preview</a>
                        <?php if (!empty($queueRow['needs_local_packshot'])): ?>
                          <form method="post" class="admin-inline-form" data-remote-packshot-form="true">
                            <input type="hidden" name="action" value="mirror_packshot_from_remote" />
                            <input type="hidden" name="product_slug" value="<?= esc((string) ($queueRow['slug'] ?? '')) ?>" />
                            <button class="btn btn-secondary btn-small" type="submit">Ulozit obrazok produktu z e-shopu</button>
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
                    <?php $queueSlug = trim((string) ($queueRow['slug'] ?? '')); ?>
                    <article id="image-product-<?= esc($queueSlug) ?>" class="admin-queue-item<?= $focusProductSlug === $queueSlug ? ' is-focused' : '' ?>" data-focus-product="<?= $focusProductSlug === $queueSlug ? 'true' : 'false' ?>">
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

            <section id="product-image-preview" class="admin-subsection admin-asset-preview<?= $focusPanel === 'product_image' ? ' is-focused' : '' ?>">
              <div class="admin-subsection-head">
                <h3>Aktualny obrazok produktu</h3>
              </div>
              <div class="admin-asset-preview__grid">
                <div class="admin-asset-preview__media">
                  <?= interessa_render_image($selectedProductImage, ['class' => 'admin-asset-preview__image']) ?>
                </div>
                <div class="admin-asset-preview__body">
                  <p><strong>Zdroj:</strong> <?= esc($selectedProductImageSource) ?></p>
                  <p><strong>Ulozeny lokalny asset:</strong> <code><?= esc($selectedProductLocalAsset !== '' ? $selectedProductLocalAsset : 'zatial chyba') ?></code></p>
                  <p><strong>Cielovy asset:</strong> <code><?= esc((string) ($selectedProduct['image_target_asset'] ?? '')) ?></code></p>
                  <?php if ($selectedProductPackshotReady): ?>
                    <p class="admin-note">Toto je hotovy obrazok produktu, ktory sa zobrazi na webe.</p>
                  <?php elseif (trim((string) ($selectedProduct['image_remote_src'] ?? '')) !== ''): ?>
                    <p class="admin-note">Nasiel sa obrazok z e-shopu. Teraz klikni <strong>2. Ulozit obrazok produktu</strong>.</p>
                  <?php else: ?>
                    <p class="admin-note">Produkt este nema obrazok ulozeny u nas. Najprv klikni <strong>1. Najst obrazok z e-shopu</strong>.</p>
                  <?php endif; ?>
                  <div class="admin-inline-actions">
                    <?php if ($selectedProductLocalImageUrl !== ''): ?>
                      <a class="btn btn-secondary btn-small" href="<?= esc($selectedProductLocalImageUrl) ?>" target="_blank" rel="noopener">Otvorit ulozeny obrazok</a>
                    <?php endif; ?>
                  </div>
                  <?php if ($selectedPackshotQueuePosition > 0): ?>
                    <p class="admin-note">Kolko produktov este caka: <?= esc((string) $selectedPackshotQueuePosition) ?> / <?= esc((string) count($missingPackshotSlugs)) ?></p>
                  <?php endif; ?>
                  <?php if ($prevMissingPackshotSlug !== '' || $nextMissingPackshotSlug !== ''): ?>
                    <div class="admin-inline-actions">
                      <?php if ($prevMissingPackshotSlug !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc($prevMissingPackshotSlug) ?>&amp;product_image_filter=missing">Predchadzajuci produkt bez obrazka</a>
                      <?php endif; ?>
                      <?php if ($nextMissingPackshotSlug !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc($nextMissingPackshotSlug) ?>&amp;product_image_filter=missing">Dalsi produkt bez obrazka</a>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                  <p><strong>Najdeny obrazok z e-shopu:</strong> <?= esc((string) ($selectedProduct['image_remote_src'] ?? '')) ?></p>
                  <?php if (trim((string) ($selectedProduct['image_remote_src'] ?? '')) !== ''): ?>
                    <div class="admin-inline-actions">
                      <a class="btn btn-secondary btn-small" href="<?= esc((string) ($selectedProduct['image_remote_src'] ?? '')) ?>" target="_blank" rel="noopener">Otvorit obrazok z e-shopu</a>
                      <?php if (!$selectedProductPackshotReady): ?>
                        <form method="post" class="admin-inline-form" data-remote-packshot-form="true">
                          <input type="hidden" name="action" value="mirror_packshot_from_remote" />
                          <input type="hidden" name="product_slug" value="<?= esc($selectedProductSlug) ?>" />
                          <input type="hidden" name="return_section" value="<?= esc($returnSectionPrefill !== '' ? $returnSectionPrefill : 'products') ?>" />
                          <input type="hidden" name="return_slug" value="<?= esc($returnSlugPrefill) ?>" />
                          <button class="btn btn-secondary btn-small" type="submit">2. Ulozit obrazok produktu</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                  <?php if (trim((string) ($selectedProduct['fallback_url'] ?? '')) !== ''): ?>
                    <div class="admin-inline-actions">
                      <a class="btn btn-secondary btn-small" href="<?= esc((string) ($selectedProduct['fallback_url'] ?? '')) ?>" target="_blank" rel="noopener">Otvorit e-shop</a>
                      <form method="post" class="admin-inline-form">
                        <input type="hidden" name="action" value="enrich_product_from_source" />
                        <input type="hidden" name="product_slug" value="<?= esc($selectedProductSlug) ?>" />
                        <input type="hidden" name="return_section" value="<?= esc($returnSectionPrefill !== '' ? $returnSectionPrefill : 'products') ?>" />
                        <input type="hidden" name="return_slug" value="<?= esc($returnSlugPrefill) ?>" />
                          <button class="btn btn-secondary btn-small" type="submit">1. Najst obrazok z e-shopu</button>
                        </form>
                      <?php if (!$selectedProductPackshotReady): ?>
                        <form method="post" class="admin-inline-form">
                          <input type="hidden" name="action" value="autofill_product_from_source" />
                          <input type="hidden" name="product_slug" value="<?= esc($selectedProductSlug) ?>" />
                          <input type="hidden" name="return_section" value="<?= esc($returnSectionPrefill !== '' ? $returnSectionPrefill : 'products') ?>" />
                          <input type="hidden" name="return_slug" value="<?= esc($returnSlugPrefill) ?>" />
                          <button class="btn btn-secondary btn-small" type="submit">Skusit najst obrazok automaticky</button>
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

            <?php if (!$selectedProductPackshotReady && $selectedProductImageBrief !== []): ?>
              <section class="admin-subsection">
                <div class="admin-subsection-head">
                  <div>
                <h3>Vlastny obrazok produktu</h3>
                <p class="admin-meta">Pouzi iba vtedy, ked na e-shope alebo vo feede naozaj nie je normalny obrazok produktu. AI obrazok pri produktoch nepouzivaj, ak sa da zobrat povodny obrazok z obchodu.</p>
                  </div>
                </div>
                <div class="admin-brief-grid">
                  <div class="admin-brief-card">
                    <h3>Text pre Canvu</h3>
                    <p><?= esc((string) ($selectedProductImageBrief['prompt'] ?? '')) ?></p>
                    <div class="admin-inline-actions">
                      <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($selectedProductImageBrief['prompt'] ?? '')) ?>">Kopirovat text pre Canvu</button>
                      <?php if (trim((string) ($selectedProductImageBrief['reference_url'] ?? '')) !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="<?= esc((string) ($selectedProductImageBrief['reference_url'] ?? '')) ?>" target="_blank" rel="noopener">Otvorit e-shop</a>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="admin-brief-card">
                    <h3>Udaje pre export</h3>
                    <p><strong>Nazov suboru:</strong><br><?= esc((string) ($selectedProductImageBrief['file_name'] ?? '')) ?></p>
                    <p><strong>Alt text:</strong><br><?= esc((string) ($selectedProductImageBrief['alt_text'] ?? '')) ?></p>
                    <p><strong>Rozmer:</strong><br><?= esc((string) ($selectedProductImageBrief['dimensions'] ?? '1200x1200')) ?></p>
                    <p><strong>Kam sa ulozi:</strong><br><code><?= esc((string) ($selectedProductImageBrief['asset_path'] ?? '')) ?></code></p>
                    <?php if (trim((string) ($selectedProductImageBrief['merchant_note'] ?? '')) !== ''): ?>
                      <p><strong>Poznamka:</strong><br><?= esc((string) ($selectedProductImageBrief['merchant_note'] ?? '')) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              </section>
            <?php endif; ?>

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
                        <a class="btn btn-secondary btn-small" href="/admin?section=articles&amp;slug=<?= esc((string) ($usageRow['slug'] ?? '')) ?>">Otvorit clanok v admine</a>
                        <a class="btn btn-secondary btn-small" href="<?= esc((string) ($usageRow['url'] ?? '#')) ?>" target="_blank" rel="noopener">Otvorit clanok na webe</a>
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
                <label><span>Affiliate code</span><input type="text" name="affiliate_code" value="<?= esc((string) ($selectedProduct['affiliate_code'] ?? '')) ?>" /></label>
                <label><span>Fallback URL</span><input type="url" name="fallback_url" value="<?= esc((string) ($selectedProduct['fallback_url'] ?? '')) ?>" /></label>
                <label><span>Rating</span><input type="number" min="0" max="5" step="0.1" name="rating" value="<?= esc((string) ($selectedProduct['rating'] ?? '')) ?>" data-product-rating-input /></label>
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
                <p class="admin-kicker">Obrazky</p>
                <h2>Obrazky pre clanok</h2>
              <p class="admin-note">Na tejto stranke robis len 2 veci: hore riesis hlavny obrazok clanku, nizsie riesis obrazky produktov. Pri produktoch najprv pouzi obrazok z e-shopu. Manualny upload je az zalozny krok.</p>
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
                <h3>Hlavny obrazok clanku</h3>
              </div>
              <div class="admin-asset-preview__grid">
                <div class="admin-asset-preview__media">
                  <?= interessa_render_image($selectedArticleHero, ['class' => 'admin-asset-preview__image']) ?>
                </div>
                <div class="admin-asset-preview__body">
                  <p><strong>Odkial je obrazok:</strong> <?= esc($selectedArticleHeroSource) ?></p>
                  <p><strong>Aktualny subor:</strong> <code><?= esc((string) ($selectedArticleHero['asset'] ?? '')) ?></code></p>
                  <p><strong>Kam sa ulozi:</strong> <code><?= esc((string) ($articlePrompt['asset_path'] ?? '')) ?></code></p>
                  <p class="admin-note">Tu klikaj len v tomto poradi: 1. Skopiruj text pre Canvu. 2. V Canve sprav obrazok. 3. Nahraj obrazok sem. 4. Otvor clanok na webe a skontroluj ho.</p>
                  <div class="admin-inline-actions">
                    <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($articlePrompt['prompt'] ?? '')) ?>">1. Kopirovat text pre Canvu</button>
                    <a class="btn btn-secondary btn-small" href="<?= esc(article_url($selectedArticleSlug)) ?>" target="_blank" rel="noopener">4. Otvorit clanok na webe</a>
                  </div>
                  <form method="post" action="/admin" enctype="multipart/form-data" class="admin-form admin-form-stack">
                    <input type="hidden" name="action" value="upload_hero_only" />
                    <input type="hidden" name="slug" value="<?= esc($selectedArticleSlug) ?>" />
                    <label>
                      <span>2. Vyber hotovy obrazok z Canvy</span>
                      <input type="file" name="hero_image" accept="image/webp,image/png,image/jpeg" required />
                    </label>
                    <button class="btn btn-cta" type="submit">3. Nahraj hlavny obrazok clanku</button>
                  </form>
                  <?php if ($selectedHeroQueuePosition > 0): ?>
                    <p class="admin-note">Zostava spravit: <?= esc((string) $selectedHeroQueuePosition) ?> / <?= esc((string) count($missingHeroSlugs)) ?></p>
                  <?php endif; ?>
                  <?php if ($prevMissingHeroSlug !== '' || $nextMissingHeroSlug !== ''): ?>
                    <div class="admin-inline-actions">
                      <?php if ($prevMissingHeroSlug !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($prevMissingHeroSlug) ?>&amp;image_filter=missing">Predchadzajuci clanok bez obrazka</a>
                      <?php endif; ?>
                      <?php if ($nextMissingHeroSlug !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($nextMissingHeroSlug) ?>&amp;image_filter=missing">Dalsi clanok bez obrazka</a>
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
                    <h3>Produkty bez obrazka v tomto clanku</h3>
                    <p class="admin-meta">Ak vidis "Obrazok pripraveny", je hotovo. Ak vidis "Obrazok chyba", klikni najprv na najdenie obrazka z e-shopu. Vlastny upload pouzi az ako poslednu moznost.</p>
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
                        <?php if (is_array($queueRow['image_brief'] ?? null) && trim((string) (($queueRow['image_brief']['prompt'] ?? ''))) !== ''): ?>
                          <small class="admin-note">Odporucany subor: <?= esc((string) ($queueRow['image_brief']['file_name'] ?? 'main.webp')) ?> / <?= esc((string) ($queueRow['image_brief']['dimensions'] ?? '1200x1200')) ?></small>
                        <?php endif; ?>
                        <div class="admin-status-pills">
                          <span class="admin-status-pill<?= !empty($queueRow['affiliate_ready']) ? ' is-good' : ' is-warning' ?>"><?= !empty($queueRow['affiliate_ready']) ? 'Affiliate hotovy' : 'Affiliate chyba' ?></span>
                          <span class="admin-status-pill is-warning">Obrazok chyba</span>
                        </div>
                      </div>
                      <div class="admin-queue-actions">
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($queueRow['slug'] ?? '')) ?>&amp;product_image_filter=missing&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>&amp;focus=product_image">Otvorit produkt</a>
                        <?php if (trim((string) ($queueRow['fallback_url'] ?? '')) !== ''): ?>
                          <form method="post" class="admin-inline-form">
                            <input type="hidden" name="action" value="autofill_product_from_source" />
                            <input type="hidden" name="product_slug" value="<?= esc((string) ($queueRow['slug'] ?? '')) ?>" />
                            <input type="hidden" name="return_section" value="images" />
                            <input type="hidden" name="return_slug" value="<?= esc($selectedArticleSlug) ?>" />
                            <button class="btn btn-secondary btn-small" type="submit">1. Najst obrazok z e-shopu</button>
                          </form>
                        <?php endif; ?>
                        <?php if (trim((string) ($queueRow['fallback_url'] ?? '')) !== ''): ?>
                          <a class="btn btn-secondary btn-small" href="<?= esc((string) ($queueRow['fallback_url'] ?? '')) ?>" target="_blank" rel="noopener">Otvorit e-shop</a>
                        <?php endif; ?>
                        <?php if (trim((string) ($queueRow['remote_src'] ?? '')) !== ''): ?>
                          <form method="post" class="admin-inline-form" data-remote-packshot-form="true">
                            <input type="hidden" name="action" value="mirror_packshot_from_remote" />
                            <input type="hidden" name="product_slug" value="<?= esc((string) ($queueRow['slug'] ?? '')) ?>" />
                            <input type="hidden" name="return_section" value="images" />
                            <input type="hidden" name="return_slug" value="<?= esc($selectedArticleSlug) ?>" />
                            <button class="btn btn-secondary btn-small" type="submit">2. Ulozit obrazok produktu</button>
                          </form>
                        <?php endif; ?>
                        <form method="post" action="/admin" enctype="multipart/form-data" class="admin-inline-upload">
                          <input type="hidden" name="action" value="upload_packshot_only" />
                          <input type="hidden" name="product_slug" value="<?= esc((string) ($queueRow['slug'] ?? '')) ?>" />
                          <input type="hidden" name="return_section" value="images" />
                          <input type="hidden" name="return_slug" value="<?= esc($selectedArticleSlug) ?>" />
                          <label class="admin-inline-upload__label">
                            <span>Vlastny obrazok produktu</span>
                            <input type="file" name="product_image" accept="image/webp,image/png,image/jpeg" required />
                          </label>
                          <button class="btn btn-secondary btn-small" type="submit">Nahraj vlastny obrazok</button>
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
                    <h3>Produkty v tomto clanku - stav</h3>
                    <p class="admin-meta">Tu len kontrolujes stav. Ak je pri produkte "Obrazok pripraveny", nic uz nenahravas.</p>
                  </div>
                </div>
                <div class="admin-mini-product-grid">
                  <?php foreach ($recommendedProductPreview as $previewRow): ?>
                    <?php $previewSlug = trim((string) ($previewRow['slug'] ?? '')); ?>
                    <?php $previewStatus = $recommendedDiagnosticsBySlug[$previewSlug] ?? []; ?>
                    <?php $previewImageAsset = trim((string) ($previewStatus['image_local_asset'] ?? '')); ?>
                    <?php $previewImageUrl = (!empty($previewStatus['packshot_ready']) && $previewImageAsset !== '') ? asset($previewImageAsset) : ''; ?>
                    <article id="image-product-<?= esc($previewSlug) ?>" class="admin-mini-product-card<?= $focusProductSlug === $previewSlug ? ' is-focused' : '' ?>" data-focus-product="<?= $focusProductSlug === $previewSlug ? 'true' : 'false' ?>">
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
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc($previewSlug) ?>&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>&amp;focus=product_image">Otvorit produkt</a>
                        <?php if ($previewImageUrl !== ''): ?>
                          <a class="btn btn-secondary btn-small" href="<?= esc($previewImageUrl) ?>" target="_blank" rel="noopener">Otvorit ulozeny obrazok</a>
                        <?php else: ?>
                          <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc($previewSlug) ?>&amp;product_image_filter=missing&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>&amp;focus=product_image">Otvorit doplnenie obrazka</a>
                        <?php endif; ?>
                        <?php if (trim((string) ($previewStatus['affiliate_code'] ?? '')) !== ''): ?>
                          <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;code=<?= esc((string) ($previewStatus['affiliate_code'] ?? '')) ?>&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Affiliate</a>
                        <?php else: ?>
                          <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;prefill_code=<?= esc($previewSlug) ?>&amp;prefill_merchant=<?= esc((string) ($previewRow['merchant'] ?? '')) ?>&amp;prefill_merchant_slug=<?= esc(interessa_admin_slugify((string) ($previewRow['merchant'] ?? ''))) ?>&amp;prefill_product_slug=<?= esc($previewSlug) ?>&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Vytvorit affiliate</a>
                        <?php endif; ?>
                        <?php if (trim((string) ($previewStatus['image_remote_src'] ?? '')) !== '' && empty($previewStatus['packshot_ready'])): ?>
                          <form method="post" class="admin-inline-form" data-remote-packshot-form="true">
                            <input type="hidden" name="action" value="mirror_packshot_from_remote" />
                            <input type="hidden" name="product_slug" value="<?= esc((string) ($previewRow['slug'] ?? '')) ?>" />
                            <input type="hidden" name="return_section" value="images" />
                            <input type="hidden" name="return_slug" value="<?= esc($selectedArticleSlug) ?>" />
                            <button class="btn btn-secondary btn-small" type="submit">2. Ulozit obrazok produktu</button>
                          </form>
                        <?php endif; ?>
                      </div>
                        <?php if (empty($previewStatus['packshot_ready'])): ?>
                          <form method="post" action="/admin" enctype="multipart/form-data" class="admin-inline-upload">
                            <input type="hidden" name="action" value="upload_packshot_only" />
                            <input type="hidden" name="product_slug" value="<?= esc((string) ($previewRow['slug'] ?? '')) ?>" />
                            <input type="hidden" name="return_section" value="images" />
                            <input type="hidden" name="return_slug" value="<?= esc($selectedArticleSlug) ?>" />
                            <label class="admin-inline-upload__label">
                            <span>Vlastny obrazok produktu</span>
                            <input type="file" name="product_image" accept="image/webp,image/png,image/jpeg" required />
                          </label>
                            <button class="btn btn-secondary btn-small" type="submit">Nahraj vlastny obrazok</button>
                          </form>
                        <?php endif; ?>
                    </article>
                  <?php endforeach; ?>
                </div>
              </section>
            <?php endif; ?>

            <div class="admin-brief-grid">
              <div class="admin-brief-card">
                <h3>Text pre Canvu</h3>
                <p><strong>Text pre obrazok:</strong><br><?= esc((string) ($articlePrompt['prompt'] ?? '')) ?></p>
                <p><strong>Alt text:</strong><br><?= esc((string) ($articlePrompt['alt_text'] ?? '')) ?></p>
                <p><strong>Rozmer:</strong><br><?= esc((string) ($articlePrompt['dimensions'] ?? '1200x800')) ?></p>
                <p><strong>Styl:</strong><br><?= esc((string) ($articlePrompt['style_brief'] ?? '')) ?></p>
                <div class="admin-inline-actions">
                  <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($articlePrompt['prompt'] ?? '')) ?>">Kopirovat text pre Canvu</button>
                </div>
              </div>
              <div class="admin-brief-card">
                <h3>Ako to spravit</h3>
                <ol class="admin-workflow-list">
                  <li>Skopiruj text do Canvy.</li>
                  <li>Exportuj PNG, JPG alebo WebP v rozmere 1200x800.</li>
                  <li>Ak chces, pouzi odporucany nazov suboru.</li>
                  <li>Nahraj obrazok sem. Admin PNG/JPG automaticky prevedie na WebP este pred uploadom.</li>
                  <li>Clanok automaticky pouzije novy hlavny obrazok.</li>
                </ol>
                  <div class="admin-inline-actions">
                    <a class="btn btn-secondary btn-small" href="/hero-helper" target="_blank" rel="noopener">Pomocnik pre obrazok</a>
                    <a class="btn btn-secondary btn-small" href="<?= esc(article_url($selectedArticleSlug)) ?>" target="_blank" rel="noopener">Otvorit clanok na webe</a>
                  </div>
                  <p class="admin-note">Nahratie obrazku je teraz uz hore v prvom bloku pod tlacidlom <strong>1. Kopirovat text pre Canvu</strong>.</p>
              </div>
            </div>
          </section>

          <section class="admin-card">
            <div class="admin-card-head">
              <div>
                <p class="admin-kicker">Hlavne obrazky clankov</p>
                <h2>Clanky bez hlavneho obrazka</h2>
                <p class="admin-note">Ak chces spravit obrazok pre jeden clanok, klikni <strong>Otvorit tento clanok</strong>. Potom hore na stranke klikni <strong>Kopirovat text pre Canvu</strong> a nakoniec <strong>Nahraj hlavny obrazok clanku</strong>.</p>
              </div>
              <div class="admin-filter-pills">
                <a class="admin-filter-pill<?= $imageFilter === 'missing' ? ' is-active' : '' ?>" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>&amp;image_filter=missing">Chyba obrazok (<?= esc((string) $imageQueueCounts['missing']) ?>)</a>
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
                    <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc((string) $queueRow['slug']) ?>&amp;image_filter=<?= esc($imageFilter) ?>">Otvorit tento clanok</a>
                    <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($queueRow['prompt'] ?? '')) ?>">Skopirovat text pre Canvu</button>
                    <a class="btn btn-secondary btn-small" href="<?= esc((string) ($queueRow['article_url'] ?? article_url((string) $queueRow['slug']))) ?>" target="_blank" rel="noopener">Otvorit clanok na webe</a>
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

            <?php if (is_array($selectedAffiliate) && ($selectedAffiliateProduct !== null || trim((string) ($selectedAffiliate['url'] ?? '')) !== '')): ?>
              <section class="admin-subsection is-compact">
                <div class="admin-subsection-head">
                  <div>
                    <h3>Kontext vybraneho odkazu</h3>
                    <p class="admin-meta">Pred ulozenim si over, ze /go/ kod smeruje na spravny produkt a merchant.</p>
                  </div>
                </div>
                <div class="admin-help-grid">
                  <?php if ($selectedAffiliateProduct !== null): ?>
                    <article class="admin-help-card">
                      <h3>Naviazany produkt</h3>
                      <p><strong><?= esc((string) ($selectedAffiliateProduct['name'] ?? '')) ?></strong></p>
                      <p class="admin-note"><?= esc((string) ($selectedAffiliateProduct['slug'] ?? '')) ?><?php if (trim((string) ($selectedAffiliateProduct['merchant'] ?? '')) !== ''): ?> / <?= esc((string) ($selectedAffiliateProduct['merchant'] ?? '')) ?><?php endif; ?></p>
                      <div class="admin-inline-actions">
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($selectedAffiliateProduct['slug'] ?? '')) ?>">Produkt</a>
                        <?php if (trim((string) ($selectedAffiliateProduct['fallback_url'] ?? '')) !== ''): ?>
                          <a class="btn btn-secondary btn-small" href="<?= esc((string) ($selectedAffiliateProduct['fallback_url'] ?? '')) ?>" target="_blank" rel="noopener">Referencny produkt</a>
                        <?php endif; ?>
                      </div>
                    </article>
                  <?php endif; ?>
                  <article class="admin-help-card">
                    <h3>Kontrola ciela</h3>
                    <p class="admin-note">Najprv si skontroluj samotny Dognet deeplink a potom aj interny /go/ odkaz.</p>
                    <?php if (trim((string) ($selectedAffiliate['url'] ?? '')) !== ''): ?>
                      <p><code><?= esc((string) ($selectedAffiliate['url'] ?? '')) ?></code></p>
                    <?php endif; ?>
                    <div class="admin-inline-actions">
                      <?php if ($selectedAffiliateCode !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="/go/<?= rawurlencode($selectedAffiliateCode) ?>" target="_blank" rel="noopener">Otvorit /go/</a>
                      <?php endif; ?>
                      <?php if (is_array($selectedAffiliateResolvedTarget) && trim((string) ($selectedAffiliateResolvedTarget['href'] ?? '')) !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="<?= esc((string) ($selectedAffiliateResolvedTarget['href'] ?? '')) ?>" target="_blank" rel="noopener">Finalny ciel</a>
                      <?php endif; ?>
                    </div>
                  </article>
                </div>
              </section>
            <?php endif; ?>

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
              <div class="admin-status-grid">
                <article class="admin-status-card">
                  <strong><?= esc((string) $imageQueueCounts['missing']) ?></strong>
                  <span>Hero obrazky este chyba</span>
                  <small><a href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>&amp;image_filter=missing">Otvorit Images</a></small>
                </article>
                <article class="admin-status-card">
                  <strong><?= esc((string) $productImageQueueCounts['missing']) ?></strong>
                  <span>Produktove obrazky este chyba</span>
                  <small><a href="/admin?section=products&amp;product=<?= esc($selectedProductSlug) ?>&amp;product_image_filter=missing">Otvorit Produkty</a></small>
                </article>
                <article class="admin-status-card">
                  <strong><?= esc((string) $productAffiliateQueueCount) ?></strong>
                  <span>Affiliate odkazy treba dorobit</span>
                  <small><a href="/admin?section=affiliates&amp;code=<?= esc($selectedAffiliateCode) ?>">Otvorit Affiliate</a></small>
                </article>
                <article class="admin-status-card">
                  <strong><?= esc((string) ($moneyPageImageGapReport['missing_products'] ?? 0)) ?></strong>
                  <span>Money page image gaps</span>
                  <small><a href="/admin?section=tools">Otvorit Tools</a></small>
                </article>
              </div>
              <div class="admin-help-grid">
                <article class="admin-help-card">
                  <h3>Upravit clanok</h3>
                  <p class="admin-note">Nazov, intro, sekcie, SEO meta a odporucane produkty.</p>
                  <div class="admin-inline-actions">
                    <a class="btn btn-secondary btn-small" href="/admin?section=articles&amp;slug=<?= esc($selectedArticleSlug) ?>">Otvorit Clanky</a>
                    <a class="btn btn-secondary btn-small" href="<?= esc(article_url($selectedArticleSlug)) ?>" target="_blank" rel="noopener">Otvorit clanok na webe</a>
                  </div>
                </article>
                <article class="admin-help-card">
                  <h3>Doplnit hero obrazok</h3>
                  <p class="admin-note">Skopiruj text pre Canvu, sprav obrazok a nahraj ho do clanku.</p>
                  <div class="admin-inline-actions">
                    <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>">Otvorit Images</a>
                    <a class="btn btn-secondary btn-small" href="/hero-helper" target="_blank" rel="noopener">Pomocnik pre obrazok</a>
                  </div>
                </article>
                <article class="admin-help-card">
                  <h3>Doplnit obrazok produktu</h3>
                  <p class="admin-note">Pri produktoch najprv skus obrazok z e-shopu. Vlastny upload pouzi az ked nic ine nie je.</p>
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
                <?php if ($productAffiliateQueue !== []): ?>
                  <article class="admin-help-card">
                    <h3>Dognet linky, ktore treba doplnit</h3>
                    <p class="admin-note">Tu zacni, ked chces len dobehnut chybajuce affiliate napojenia.</p>
                    <div class="admin-queue-list">
                      <?php foreach (array_slice($productAffiliateQueue, 0, 4) as $queueRow): ?>
                        <article class="admin-queue-item">
                          <div>
                            <strong><?= esc((string) ($queueRow['name'] ?? '')) ?></strong>
                            <p><?= esc((string) ($queueRow['slug'] ?? '')) ?><?php if (trim((string) ($queueRow['merchant'] ?? '')) !== ''): ?> / <?= esc((string) ($queueRow['merchant'] ?? '')) ?><?php endif; ?></p>
                            <small class="admin-note"><?= esc((string) ($queueRow['status'] ?? '')) ?></small>
                          </div>
                          <div class="admin-queue-actions">
                            <?php if (trim((string) ($queueRow['affiliate_code'] ?? '')) !== ''): ?>
                              <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;code=<?= esc((string) ($queueRow['affiliate_code'] ?? '')) ?>">Affiliate</a>
                            <?php else: ?>
                              <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;prefill_code=<?= esc((string) ($queueRow['slug'] ?? '')) ?>&amp;prefill_merchant=<?= esc((string) ($queueRow['merchant'] ?? '')) ?>&amp;prefill_merchant_slug=<?= esc(interessa_admin_slugify((string) ($queueRow['merchant'] ?? ''))) ?>&amp;prefill_product_slug=<?= esc((string) ($queueRow['slug'] ?? '')) ?>">Vytvorit affiliate</a>
                            <?php endif; ?>
                            <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($queueRow['slug'] ?? '')) ?>">Produkt</a>
                          </div>
                        </article>
                      <?php endforeach; ?>
                    </div>
                  </article>
                <?php endif; ?>
                <article class="admin-help-card">
                  <h3>Pozriet co este chyba</h3>
                  <p class="admin-note">Najdolezitejsie obrazkove medzery na money pages najdes v nastrojoch.</p>
                  <div class="admin-inline-actions">
                    <a class="btn btn-secondary btn-small" href="/admin?section=tools">Otvorit Tools</a>
                    <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>">Image workflow</a>
                  </div>
                </article>
                <article class="admin-help-card">
                  <h3>Skusit automaticke doplnenie</h3>
                  <p class="admin-note">Ak ma produkt URL z e-shopu alebo priamo obrazok z e-shopu, admin vie stiahnut realny obrazok produktu bez AI generovania.</p>
                  <div class="admin-inline-actions">
                    <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc($selectedProductSlug) ?>&amp;product_image_filter=missing">Vybrany produkt</a>
                    <a class="btn btn-secondary btn-small" href="/admin?section=tools">Money page gaps</a>
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
                  <li>Otvor <a href="/admin?section=images&slug=<?= esc($selectedArticleSlug) ?>">Obrazky</a>.</li>
                  <li>Skopiruj <strong>prompt</strong>, <strong>filename</strong> a <strong>target path</strong>.</li>
                  <li>V Canve alebo AI nastroji vytvor obrazok bez textu a exportuj WebP.</li>
                  <li>Nahraj obrazok cez admin a skontroluj clanok na webe.</li>
                </ol>
              </article>

              <article class="admin-help-card">
                <h3>3. Chcem doplnit obrazok produktu</h3>
                <ol class="admin-quickstart-list">
                  <li>Otvor <a href="/admin?section=products&product=<?= esc($selectedProductSlug) ?>">Produkty</a> alebo queue chybajucich obrazkov v image workflowe.</li>
                  <li>Ak uz produkt ma najdeny obrazok z obchodu, klikni <strong>Ulozit obrazok produktu z e-shopu</strong>.</li>
                  <li>Manualny upload pouzi iba ako fallback, ked remote obrazok nie je k dispozicii.</li>
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
                                <a class="btn btn-secondary btn-small" href="/hero-helper" target="_blank" rel="noopener">Pomocnik pre obrazok</a>
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
                    <small>Tieto produkty uz maju najdeny obrazok z e-shopu, takze ich vies zvycajne dokoncit jednym klikom cez <strong>Ulozit obrazok produktu z e-shopu</strong>.</small>
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
                    <strong>Clanok na webe vyzera dobre</strong>
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
                <h3>Money page image gaps</h3>
                <p>
                  Sledujeme <?= esc((string) ($moneyPageImageGapReport['tracked_pages'] ?? 0)) ?> hlavnych money pages.
                  Aktualne chyba <?= esc((string) ($moneyPageImageGapReport['missing_products'] ?? 0)) ?> realnych produktovych obrazkov<?= ($moneyPageImageGapReport['merchant_filter'] ?? 'all') !== 'all' ? ' pre vybraneho merchanta' : '' ?>.
                </p>
                <p class="admin-note">Najrychlejsia cesta je otvorit image workflow konkretneho clanku a doplnat obrazky po clankoch.</p>
                <div class="admin-inline-actions">
                  <form method="post" class="admin-inline-form">
                    <input type="hidden" name="action" value="export_money_page_image_gap_csv" />
                    <input type="hidden" name="merchant_filter" value="<?= esc((string) ($moneyPageImageGapReport['merchant_filter'] ?? 'all')) ?>" />
                    <button class="btn btn-secondary" type="submit">Exportovat gaps + briefy CSV</button>
                  </form>
                  <form method="post" class="admin-inline-form">
                    <input type="hidden" name="action" value="autofill_gap_products_by_filter" />
                    <input type="hidden" name="merchant_filter" value="<?= esc((string) ($moneyPageImageGapReport['merchant_filter'] ?? 'all')) ?>" />
                    <button class="btn btn-secondary" type="submit">Skusit doplnit z produktu</button>
                  </form>
                </div>
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

            <?php if (($moneyPageImageGapReport['groups'] ?? []) !== []): ?>
              <div class="admin-subsection">
                <h3>Prehlad chybajucich obrazkov na money pages</h3>
                <?php if (($moneyPageImageGapReport['merchant_groups'] ?? []) !== []): ?>
                  <div class="admin-help-grid">
                    <?php foreach (($moneyPageImageGapReport['merchant_groups'] ?? []) as $merchantGroup): ?>
                      <article class="admin-help-card">
                        <h3><?= esc((string) ($merchantGroup['merchant'] ?? 'Merchant')) ?></h3>
                        <p class="admin-note"><?= esc((string) ($merchantGroup['count'] ?? 0)) ?> produktov / <?= esc((string) ($merchantGroup['article_count'] ?? 0)) ?> clankov</p>
                        <?php if (($merchantGroup['sample_products'] ?? []) !== []): ?>
                          <ul class="admin-quickstart-list">
                            <?php foreach ((array) ($merchantGroup['sample_products'] ?? []) as $sampleProduct): ?>
                              <li><?= esc((string) $sampleProduct) ?></li>
                            <?php endforeach; ?>
                          </ul>
                        <?php endif; ?>
                        <div class="admin-inline-actions">
                          <a class="btn btn-secondary btn-small" href="/admin?section=tools&amp;merchant_filter=<?= esc((string) ($merchantGroup['merchant_slug'] ?? '')) ?>">Otvorit vyrez</a>
                          <form method="post" class="admin-inline-form">
                            <input type="hidden" name="action" value="export_money_page_image_gap_csv" />
                            <input type="hidden" name="merchant_filter" value="<?= esc((string) ($merchantGroup['merchant_slug'] ?? '')) ?>" />
                            <button class="btn btn-secondary btn-small" type="submit">Export CSV</button>
                          </form>
                          <form method="post" class="admin-inline-form">
                            <input type="hidden" name="action" value="autofill_gap_products_by_filter" />
                            <input type="hidden" name="merchant_filter" value="<?= esc((string) ($merchantGroup['merchant_slug'] ?? '')) ?>" />
                            <button class="btn btn-secondary btn-small" type="submit">Auto doplnit</button>
                          </form>
                        </div>
                      </article>
                    <?php endforeach; ?>
                  </div>
                  <div class="admin-filter-pills">
                    <?php $gapAllActive = ($moneyPageImageGapReport['merchant_filter'] ?? 'all') === 'all'; ?>
                    <a class="admin-filter-pill<?= $gapAllActive ? ' is-active' : '' ?>" href="/admin?section=tools">Vsetci merchanti <span><?= esc((string) ($moneyPageImageGapReport['missing_products_total'] ?? 0)) ?></span></a>
                    <?php foreach (($moneyPageImageGapReport['merchant_groups'] ?? []) as $merchantGroup): ?>
                      <a class="admin-filter-pill<?= ($moneyPageImageGapReport['merchant_filter'] ?? 'all') === ($merchantGroup['merchant_slug'] ?? '') ? ' is-active' : '' ?>" href="/admin?section=tools&amp;merchant_filter=<?= esc((string) ($merchantGroup['merchant_slug'] ?? '')) ?>">
                        <?= esc((string) ($merchantGroup['merchant'] ?? 'Merchant')) ?>
                        <span><?= esc((string) ($merchantGroup['count'] ?? 0)) ?></span>
                      </a>
                    <?php endforeach; ?>
                  </div>
                  <div class="admin-status-grid">
                    <?php foreach (($moneyPageImageGapReport['merchant_groups'] ?? []) as $merchantGroup): ?>
                      <article class="admin-status-card">
                        <strong><?= esc((string) ($merchantGroup['count'] ?? 0)) ?></strong>
                        <span><?= esc((string) ($merchantGroup['merchant'] ?? 'Merchant')) ?></span>
                        <small><?= esc((string) ($merchantGroup['article_count'] ?? 0)) ?> clankov</small>
                      </article>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
                <?php if (($moneyPageImageGapReport['merchant_filter'] ?? 'all') !== 'all'): ?>
                  <p class="admin-note">Filter je zapnuty pre merchanta <strong><?= esc((string) ($moneyPageImageGapReport['merchant_filter'] ?? '')) ?></strong>. Export CSV aj tabulka nizsie uz beru len tento vyrez.</p>
                <?php endif; ?>
                <?php if (trim((string) ($moneyPageGapBriefPack['text'] ?? '')) !== ''): ?>
                  <div class="admin-brief-grid">
                    <article class="admin-brief-card">
                      <h3><?= esc((string) ($moneyPageGapBriefPack['title'] ?? 'Batch brief pack')) ?></h3>
                      <p class="admin-meta">Jednym klikom si vies skopirovat zadanie pre cely merchant batch a potom len postupne dorabat obrazky.</p>
                      <div class="admin-inline-actions">
                        <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($moneyPageGapBriefPack['text'] ?? '')) ?>">Kopirovat cely batch</button>
                      </div>
                      <label class="admin-form">
                        <span>Batch brief text</span>
                        <textarea rows="14" readonly><?= esc((string) ($moneyPageGapBriefPack['text'] ?? '')) ?></textarea>
                      </label>
                    </article>
                  </div>
                <?php endif; ?>
                <div class="admin-brief-table-wrap">
                  <table class="admin-brief-table">
                    <thead>
                      <tr>
                        <th>Clanok</th>
                        <th>Chyba</th>
                        <th>Produkty</th>
                        <th>Akcia</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach (($moneyPageImageGapReport['groups'] ?? []) as $group): ?>
                        <tr>
                          <td>
                            <strong><?= esc((string) ($group['article_title'] ?? '')) ?></strong><br>
                            <small><?= esc((string) ($group['article_slug'] ?? '')) ?></small>
                          </td>
                          <td><?= esc((string) ($group['count'] ?? 0)) ?> obrazky</td>
                          <td>
                            <ul class="admin-inline-list">
                              <?php foreach ((array) ($group['rows'] ?? []) as $row): ?>
                                <li>
                                  <strong><?= esc((string) ($row['product_name'] ?? '')) ?></strong>
                                  <?php if ((string) ($row['merchant'] ?? '') !== ''): ?>
                                    <span class="admin-note">(<?= esc((string) ($row['merchant'] ?? '')) ?>)</span>
                                  <?php endif; ?>
                                  <?php if ((string) ($row['target_asset'] ?? '') !== ''): ?>
                                    <br><small><?= esc((string) ($row['target_asset'] ?? '')) ?></small>
                                  <?php endif; ?>
                                  <?php if (is_array($row['image_brief'] ?? null) && trim((string) (($row['image_brief']['file_name'] ?? ''))) !== ''): ?>
                                    <br><small class="admin-note">Brief: <?= esc((string) ($row['image_brief']['file_name'] ?? 'main.webp')) ?> / <?= esc((string) ($row['image_brief']['dimensions'] ?? '1200x1200')) ?></small>
                                  <?php endif; ?>
                                  <div class="admin-inline-actions">
                                    <?php if ((string) ($row['target_asset'] ?? '') !== ''): ?>
                                      <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($row['target_asset'] ?? '')) ?>">Kopirovat cestu</button>
                                    <?php endif; ?>
                                    <?php if (is_array($row['image_brief'] ?? null) && trim((string) (($row['image_brief']['prompt'] ?? ''))) !== ''): ?>
                                      <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($row['image_brief']['prompt'] ?? '')) ?>">Kopirovat text pre Canvu</button>
                                    <?php endif; ?>
                                    <?php if ((string) ($row['fallback_url'] ?? '') !== ''): ?>
                                      <a class="btn btn-secondary btn-small" href="<?= esc((string) ($row['fallback_url'] ?? '')) ?>" target="_blank" rel="noopener">Referencny produkt</a>
                                      <?php if ((string) ($row['product_slug'] ?? '') !== ''): ?>
                                        <form method="post" class="admin-inline-form">
                                          <input type="hidden" name="action" value="autofill_product_from_source" />
                                          <input type="hidden" name="product_slug" value="<?= esc((string) ($row['product_slug'] ?? '')) ?>" />
                                          <button class="btn btn-secondary btn-small" type="submit">Auto doplnit</button>
                                        </form>
                                      <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ((string) ($row['product_url'] ?? '') !== ''): ?>
                                      <a class="btn btn-secondary btn-small" href="<?= esc((string) ($row['product_url'] ?? '')) ?>">Produkt</a>
                                    <?php endif; ?>
                                  </div>
                                </li>
                              <?php endforeach; ?>
                            </ul>
                          </td>
                          <td>
                            <div class="admin-inline-actions">
                              <a class="btn btn-secondary btn-small" href="<?= esc((string) ($group['workflow_url'] ?? '')) ?>">Image workflow</a>
                              <a class="btn btn-secondary btn-small" href="<?= esc((string) ($group['article_url'] ?? '')) ?>" target="_blank" rel="noreferrer">Otvorit clanok na webe</a>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php endif; ?>
          </section>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</section>
<style>
  .admin-copy-toast {
    position: fixed;
    right: 20px;
    bottom: 20px;
    z-index: 9999;
    max-width: min(360px, calc(100vw - 32px));
    padding: 12px 16px;
    border-radius: 12px;
    background: #133b2c;
    color: #fff;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.22);
    font-weight: 600;
    opacity: 0;
    transform: translateY(10px);
    pointer-events: none;
    transition: opacity .18s ease, transform .18s ease;
  }

  .admin-copy-toast.is-visible {
    opacity: 1;
    transform: translateY(0);
  }

  .admin-copy-toast.is-error {
    background: #8a1c1c;
  }

  .btn.is-copied {
    background: #15803d;
    border-color: #15803d;
    color: #fff;
  }

  .admin-queue-item.is-focused,
  .admin-mini-product-card.is-focused {
    outline: 3px solid #34d399;
    box-shadow: 0 20px 40px rgba(52, 211, 153, 0.18);
  }

  .admin-asset-preview.is-focused {
    outline: 3px solid #34d399;
    box-shadow: 0 20px 40px rgba(52, 211, 153, 0.18);
    border-radius: 20px;
  }
</style>
<script>
  (function () {
    if (window.__interessaAdminCopyInit) {
      return;
    }
    window.__interessaAdminCopyInit = true;
    window.__interessaAdminInlineUploadEnabled = true;

    let toastTimer = null;

    function ensureToast() {
      let toast = document.querySelector('[data-admin-copy-toast]');
      if (toast) {
        return toast;
      }

      toast = document.createElement('div');
      toast.className = 'admin-copy-toast';
      toast.setAttribute('data-admin-copy-toast', 'true');
      toast.setAttribute('aria-live', 'polite');
      toast.setAttribute('aria-atomic', 'true');
      document.body.appendChild(toast);
      return toast;
    }

    function showToast(message, isError) {
      const toast = ensureToast();
      toast.textContent = message;
      toast.classList.toggle('is-error', !!isError);
      toast.classList.add('is-visible');

      if (toastTimer) {
        window.clearTimeout(toastTimer);
      }

      toastTimer = window.setTimeout(function () {
        toast.classList.remove('is-visible');
      }, 2200);
    }

    function fallbackCopy(value) {
      const textarea = document.createElement('textarea');
      textarea.value = value;
      textarea.setAttribute('readonly', 'readonly');
      textarea.style.position = 'fixed';
      textarea.style.top = '-9999px';
      textarea.style.left = '-9999px';
      document.body.appendChild(textarea);
      textarea.focus();
      textarea.select();

      let ok = false;
      try {
        ok = document.execCommand('copy');
      } catch (error) {
        ok = false;
      }

      document.body.removeChild(textarea);
      return ok;
    }

    function markButton(button) {
      const originalText = button.getAttribute('data-copy-original-text') || button.textContent;
      button.setAttribute('data-copy-original-text', originalText);
      button.textContent = 'Skopirovane';
      button.classList.add('is-copied');

      window.setTimeout(function () {
        button.textContent = button.getAttribute('data-copy-original-text') || originalText;
        button.classList.remove('is-copied');
      }, 1400);
    }

    async function copyValue(value) {
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(value);
        return true;
      }

      return fallbackCopy(value);
    }

    function setFormBusy(form, busy) {
      if (!(form instanceof HTMLFormElement)) {
        return;
      }

      form.dataset.inlineUploadBusy = busy ? 'true' : 'false';
      form.querySelectorAll('button[type="submit"]').forEach(function (button) {
        if (button instanceof HTMLButtonElement) {
          if (!button.hasAttribute('data-original-label')) {
            button.setAttribute('data-original-label', button.textContent || 'Nahrat');
          }
          button.disabled = !!busy;
          button.textContent = busy ? 'Nahravam...' : (button.getAttribute('data-original-label') || 'Nahrat');
        }
      });
    }

    function normalizedUploadExtension(file) {
      if (!(file instanceof File)) {
        return '';
      }

      const parts = String(file.name || '').toLowerCase().split('.');
      if (parts.length < 2) {
        return '';
      }

      const ext = parts.pop() || '';
      return ext === 'jpeg' ? 'jpg' : ext;
    }

    async function readUploadHeader(file, length) {
      if (!(file instanceof File) || typeof file.slice !== 'function') {
        return new Uint8Array();
      }

      const buffer = await file.slice(0, length || 16).arrayBuffer();
      return new Uint8Array(buffer);
    }

    async function detectUploadFormat(file) {
      if (!(file instanceof File)) {
        return 'unknown';
      }

      const type = (file.type || '').toLowerCase();
      if (type === 'image/webp') {
        return 'webp';
      }
      if (type === 'image/png') {
        return 'png';
      }
      if (type === 'image/jpeg' || type === 'image/jpg') {
        return 'jpg';
      }

      const ext = normalizedUploadExtension(file);
      if (ext === 'webp' || ext === 'png' || ext === 'jpg') {
        return ext;
      }

      const header = await readUploadHeader(file, 16);
      if (
        header.length >= 8 &&
        header[0] === 0x89 &&
        header[1] === 0x50 &&
        header[2] === 0x4e &&
        header[3] === 0x47 &&
        header[4] === 0x0d &&
        header[5] === 0x0a &&
        header[6] === 0x1a &&
        header[7] === 0x0a
      ) {
        return 'png';
      }

      if (
        header.length >= 3 &&
        header[0] === 0xff &&
        header[1] === 0xd8 &&
        header[2] === 0xff
      ) {
        return 'jpg';
      }

      if (
        header.length >= 12 &&
        header[0] === 0x52 &&
        header[1] === 0x49 &&
        header[2] === 0x46 &&
        header[3] === 0x46 &&
        header[8] === 0x57 &&
        header[9] === 0x45 &&
        header[10] === 0x42 &&
        header[11] === 0x50
      ) {
        return 'webp';
      }

      return 'unknown';
    }

    function renameToWebp(name) {
      const base = (name || 'image').replace(/\.[^.]+$/u, '') || 'image';
      return base + '.webp';
    }

    function guessRemoteFileName(productSlug, contentType, disposition) {
      const dispositionMatch = typeof disposition === 'string'
        ? disposition.match(/filename\*?=(?:UTF-8''|\"?)([^\";]+)/i)
        : null;
      if (dispositionMatch && dispositionMatch[1]) {
        const decoded = dispositionMatch[1].replace(/\"/g, '').trim();
        if (decoded) {
          return decodeURIComponent(decoded);
        }
      }

      const normalizedType = String(contentType || '').toLowerCase();
      if (normalizedType.includes('png')) {
        return (productSlug || 'remote-packshot') + '.png';
      }
      if (normalizedType.includes('jpeg') || normalizedType.includes('jpg')) {
        return (productSlug || 'remote-packshot') + '.jpg';
      }
      if (normalizedType.includes('webp')) {
        return (productSlug || 'remote-packshot') + '.webp';
      }
      return (productSlug || 'remote-packshot') + '.img';
    }

    function loadImageFromFile(file) {
      return new Promise(function (resolve, reject) {
        const objectUrl = URL.createObjectURL(file);
        const image = new Image();
        image.onload = function () {
          URL.revokeObjectURL(objectUrl);
          resolve(image);
        };
        image.onerror = function () {
          URL.revokeObjectURL(objectUrl);
          reject(new Error('image-load-failed'));
        };
        image.src = objectUrl;
      });
    }

    async function blobHasWebpSignature(blob) {
      if (!(blob instanceof Blob)) {
        return false;
      }

      const header = new Uint8Array(await blob.slice(0, 16).arrayBuffer());
      return (
        header.length >= 12 &&
        header[0] === 0x52 &&
        header[1] === 0x49 &&
        header[2] === 0x46 &&
        header[3] === 0x46 &&
        header[8] === 0x57 &&
        header[9] === 0x45 &&
        header[10] === 0x42 &&
        header[11] === 0x50
      );
    }

    function canvasToBlob(canvas, type, quality) {
      return new Promise(function (resolve, reject) {
        canvas.toBlob(function (blob) {
          if (blob) {
            resolve(blob);
            return;
          }
          reject(new Error('canvas-blob-failed'));
        }, type, quality || 0.92);
      });
    }

    async function canvasToVerifiedWebpBlob(canvas) {
      if (typeof OffscreenCanvas !== 'undefined') {
        try {
          const offscreenCanvas = new OffscreenCanvas(canvas.width, canvas.height);
          const offscreenContext = offscreenCanvas.getContext('2d', { alpha: true });
          if (offscreenContext) {
            offscreenContext.drawImage(canvas, 0, 0);
            const offscreenBlob = await offscreenCanvas.convertToBlob({ type: 'image/webp', quality: 0.92 });
            if (await blobHasWebpSignature(offscreenBlob)) {
              return offscreenBlob;
            }
          }
        } catch (error) {
          console.error(error);
        }
      }

      const blob = await canvasToBlob(canvas, 'image/webp', 0.92);
      if (await blobHasWebpSignature(blob)) {
        return blob;
      }

      const dataUrl = canvas.toDataURL('image/webp', 0.92);
      if (typeof dataUrl === 'string' && dataUrl.startsWith('data:image/webp;base64,')) {
        const response = await fetch(dataUrl);
        const dataUrlBlob = await response.blob();
        if (await blobHasWebpSignature(dataUrlBlob)) {
          return dataUrlBlob;
        }
      }

      throw new Error('verified-webp-conversion-failed');
    }

    async function createWebpFileFromUpload(file) {
      if (!(file instanceof File)) {
        throw new Error('missing-upload-file');
      }

      const detectedFormat = await detectUploadFormat(file);
      if (detectedFormat === 'webp') {
        return file;
      }

      const image = await loadImageFromFile(file);
      const canvas = document.createElement('canvas');
      canvas.width = image.naturalWidth || image.width;
      canvas.height = image.naturalHeight || image.height;
      const context = canvas.getContext('2d', { alpha: true });
      if (!context) {
        throw new Error('canvas-context-failed');
      }

      context.drawImage(image, 0, 0);
      const blob = await canvasToVerifiedWebpBlob(canvas);
      return new File([blob], renameToWebp(file.name), {
        type: 'image/webp',
        lastModified: Date.now()
      });
    }

    async function fetchRemotePackshotFile(form) {
      const productSlugInput = form.querySelector('input[name="product_slug"]');
      const productSlug = productSlugInput instanceof HTMLInputElement ? (productSlugInput.value || '').trim() : '';
      if (!productSlug) {
        throw new Error('missing-product-slug');
      }

      const params = new URLSearchParams({
        section: 'products',
        action: 'product_remote_image_proxy',
        product: productSlug
      });
      const response = await fetch('/admin?' + params.toString(), {
        credentials: 'same-origin'
      });
      if (!response.ok) {
        const message = (await response.text()).trim();
        throw new Error(message || 'remote-packshot-fetch-failed');
      }

      const blob = await response.blob();
      if (!(blob instanceof Blob) || blob.size === 0) {
        throw new Error('empty-remote-packshot');
      }

      const contentType = response.headers.get('content-type') || blob.type || 'application/octet-stream';
      const disposition = response.headers.get('content-disposition') || '';
      const fileName = guessRemoteFileName(productSlug, contentType, disposition);
      return new File([blob], fileName, {
        type: contentType,
        lastModified: Date.now()
      });
    }

    async function submitInlineUploadForm(form) {
      const fileInput = form.querySelector('input[type="file"][name="hero_image"], input[type="file"][name="product_image"]');
      const selectedFile = fileInput && fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
      if (!(selectedFile instanceof File)) {
        showToast('Najprv vyber obrazok.', true);
        return;
      }

      setFormBusy(form, true);

      try {
        const webpFile = await createWebpFileFromUpload(selectedFile);
        const formData = new FormData(form);
        formData.set(fileInput.name, webpFile, webpFile.name);

        const response = await fetch('/admin', {
          method: (form.method || 'POST').toUpperCase(),
          body: formData,
          credentials: 'same-origin'
        });

        if (response.redirected && response.url) {
          window.location.assign(response.url);
          return;
        }

        const html = await response.text();
        if (typeof html === 'string' && html.trim() !== '') {
          document.open();
          document.write(html);
          document.close();
          return;
        }

        window.location.reload();
      } finally {
        setFormBusy(form, false);
      }
    }

    async function submitRemotePackshotForm(form) {
      setFormBusy(form, true);

      try {
        const remoteFile = await fetchRemotePackshotFile(form);
        const webpFile = await createWebpFileFromUpload(remoteFile);
        const formData = new FormData();
        const productSlugInput = form.querySelector('input[name="product_slug"]');
        const returnSectionInput = form.querySelector('input[name="return_section"]');
        const returnSlugInput = form.querySelector('input[name="return_slug"]');

        formData.set('action', 'upload_packshot_only');
        formData.set('product_slug', productSlugInput instanceof HTMLInputElement ? productSlugInput.value : '');
        if (returnSectionInput instanceof HTMLInputElement && returnSectionInput.value) {
          formData.set('return_section', returnSectionInput.value);
        }
        if (returnSlugInput instanceof HTMLInputElement && returnSlugInput.value) {
          formData.set('return_slug', returnSlugInput.value);
        }
        formData.set('product_image', webpFile, webpFile.name);

        const response = await fetch('/admin', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        });

        if (response.redirected && response.url) {
          window.location.assign(response.url);
          return;
        }

        const html = await response.text();
        if (typeof html === 'string' && html.trim() !== '') {
          document.open();
          document.write(html);
          document.close();
          return;
        }

        window.location.reload();
      } finally {
        setFormBusy(form, false);
      }
    }

    document.querySelectorAll('form.admin-inline-upload, form.admin-form').forEach(function (form) {
      const fileInput = form.querySelector('input[type="file"][name="hero_image"], input[type="file"][name="product_image"]');
      if (!(fileInput instanceof HTMLInputElement)) {
        return;
      }

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        event.stopPropagation();

        submitInlineUploadForm(form).catch(function (error) {
          console.error(error);
          showToast('Konverzia do WebP zlyhala. Nahraj iny obrazok.', true);
        });
        }, true);
    });

    document.querySelectorAll('form[data-remote-packshot-form="true"]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        event.stopPropagation();

        submitRemotePackshotForm(form).catch(function (error) {
          console.error(error);
          const message = error && error.message ? String(error.message) : '';
          showToast(message !== '' ? message : 'Packshot z e-shopu sa nepodarilo ulozit.', true);
        });
      }, true);
    });

    const focusedProductCard = document.querySelector('[data-focus-product="true"]');
    if (focusedProductCard instanceof HTMLElement) {
      window.setTimeout(function () {
        focusedProductCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 80);
    }

    const focusedPanel = document.getElementById('product-image-preview');
    if (focusedPanel instanceof HTMLElement && focusedPanel.classList.contains('is-focused')) {
      window.setTimeout(function () {
        focusedPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 80);
    }

    document.addEventListener('click', async function (event) {
      const button = event.target.closest('[data-copy-value]');
      if (!button) {
        return;
      }

      event.preventDefault();

      const value = button.getAttribute('data-copy-value') || '';
      const label = (button.getAttribute('data-copy-label') || button.textContent || 'Text').trim();
      if (!value) {
        showToast(label + ': nic na kopirovanie.', true);
        return;
      }

      try {
        const ok = await copyValue(value);
        if (!ok) {
          throw new Error('copy-failed');
        }

        markButton(button);
        showToast(label + ': skopirovane.', false);
      } catch (error) {
        showToast('Kopirovanie zlyhalo. Skus znova.', true);
      }
    });
  })();
</script>
<?php require dirname(__DIR__) . '/inc/footer.php'; ?>




