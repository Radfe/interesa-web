<?php declare(strict_types=1);

require_once dirname(__DIR__) . '/inc/functions.php';
require_once dirname(__DIR__) . '/inc/products.php';
require_once dirname(__DIR__) . '/inc/hero-prompts.php';
require_once dirname(__DIR__) . '/inc/admin-content.php';
require_once dirname(__DIR__) . '/inc/admin-auth.php';
require_once dirname(__DIR__) . '/inc/admin-feed-import.php';
require_once dirname(__DIR__) . '/inc/article-commerce.php';
require_once dirname(__DIR__) . '/inc/dognet-helper.php';

function interessa_admin_selected_section(): string {
    $section = strtolower(trim((string) ($_GET['section'] ?? '')));
    if ($section === '') {
        foreach (['candidate', 'batch', 'product', 'product_filter', 'product_image_filter'] as $productKey) {
            if (trim((string) ($_GET[$productKey] ?? '')) !== '') {
                return 'products';
            }
        }
        $saved = strtolower(trim((string) ($_GET['saved'] ?? '')));
        if (str_starts_with($saved, 'candidate-') || str_starts_with($saved, 'product-')) {
            return 'products';
        }
        return 'articles';
    }

    return in_array($section, ['articles', 'products', 'images', 'affiliates', 'brand', 'tools', 'help'], true) ? $section : 'articles';
}

function interessa_admin_redirect(string $section, array $query = []): never {
    $query = array_filter($query, static fn(mixed $value): bool => (string) $value !== '');
    $query['section'] = $section;
    header('Location: /admin?' . http_build_query($query), true, 303);
    exit;
}

function interessa_admin_redirect_fragment(string $section, array $query = [], string $fragment = ''): never {
    $query = array_filter($query, static fn(mixed $value): bool => (string) $value !== '');
    $query['section'] = $section;
    $suffix = trim($fragment) !== '' ? '#' . ltrim(trim($fragment), '#') : '';
    header('Location: /admin?' . http_build_query($query) . $suffix, true, 303);
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

function interessa_admin_product_article_slot_query(string $productSlug, string $articleSlug, int $slot, string $saved = '', string $focus = 'product_edit'): array {
    $articleSlug = canonical_article_slug($articleSlug);
    $slot = max(0, min(3, $slot));
    $query = [
        'product' => trim($productSlug),
        'article' => $articleSlug,
        'slot' => $slot > 0 ? (string) $slot : '',
        'return_section' => 'articles',
        'return_slug' => $articleSlug,
    ];
    if ($saved !== '') {
        $query['saved'] = $saved;
    }
    if ($focus !== '') {
        $query['focus'] = $focus;
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

function interessa_admin_product_click_state(array $product): array {
    $normalized = interessa_normalize_product($product);
    $affiliateCode = trim((string) ($normalized['affiliate_code'] ?? ''));
    $affiliateHref = $affiliateCode !== '' ? trim((string) (aff_resolve($affiliateCode) ?? '')) : '';
    $directUrl = trim((string) ($normalized['fallback_url'] ?? ''));
    $derivedProductUrl = $affiliateCode !== '' ? trim((string) aff_product_url_for_code($affiliateCode)) : '';

    $directReady = $directUrl !== '' && interessa_admin_looks_like_product_url($directUrl);
    $affiliateProductReady = $derivedProductUrl !== '' && interessa_admin_looks_like_product_url($derivedProductUrl);
    $affiliateReady = $affiliateHref !== '' && $affiliateProductReady;
    $ready = $directReady || $affiliateReady;

    return [
        'ready' => $ready,
        'direct_ready' => $directReady,
        'affiliate_ready' => $affiliateReady,
        'href' => $affiliateReady ? $affiliateHref : ($directReady ? $directUrl : ''),
        'product_url' => $directReady ? $directUrl : ($affiliateProductReady ? $derivedProductUrl : ''),
        'affiliate_href' => $affiliateHref,
    ];
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
        $clickState = interessa_admin_product_click_state($normalized);
        $target = interessa_affiliate_target($normalized);
        $affiliateCode = trim((string) ($normalized['affiliate_code'] ?? ''));
        $affiliateReady = !empty($clickState['ready']);
        $hasClickTarget = !empty($clickState['ready']);
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
            'href' => trim((string) ($clickState['href'] ?? ($target['href'] ?? ''))),
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

function interessa_admin_empty_product_stub(string $slug): array {
    return [
        'slug' => $slug,
        'name' => '',
        'brand' => '',
        'merchant' => '',
        'merchant_slug' => '',
        'category' => '',
        'affiliate_code' => '',
        'fallback_url' => '',
        'summary' => '',
        'rating' => '',
        'pros' => [],
        'cons' => [],
        'image_remote_src' => '',
        'image_mode' => 'missing',
        'image_local_asset' => '',
        'image_target_asset' => '',
        'has_local_image' => false,
        'image' => null,
    ];
}

function interessa_admin_article_default_product_plan(string $slug, array $catalog): array {
    $defaults = interessa_admin_article_default_products($slug, $catalog);
    if ($defaults === []) {
        return [];
    }

    $whitelist = function_exists('interessa_article_comparison_table_whitelist')
        ? interessa_article_comparison_table_whitelist()
        : [];
    $comparisonAllowed = in_array(canonical_article_slug($slug), $whitelist, true);

    $rows = [];
    foreach ($defaults as $index => $productSlug) {
        $rows[] = [
            'product_slug' => (string) $productSlug,
            'order' => $index + 1,
            'role' => $index === 0 ? 'featured' : 'standard',
            'show_in_top' => true,
            'show_in_comparison' => $comparisonAllowed,
        ];
    }

    return $rows;
}

function interessa_admin_collect_article_product_plan(): array {
    $slotSelections = $_POST['article_product_slot'] ?? [];
    if (is_array($slotSelections)) {
        $slotFeatured = max(1, min(3, (int) ($_POST['article_product_featured_slot'] ?? 1)));
        $roles = $_POST['article_product_role'] ?? [];
        $placement = $_POST['article_product_placement'] ?? [];
        $articleSlug = canonical_article_slug((string) ($_POST['slug'] ?? ''));
        $whitelist = function_exists('interessa_article_comparison_table_whitelist')
            ? interessa_article_comparison_table_whitelist()
            : [];
        $comparisonAllowed = in_array($articleSlug, $whitelist, true);
        $rows = [];
        $seen = [];
        $featuredProductSlug = interessa_admin_slugify((string) ($slotSelections[$slotFeatured] ?? ''));

        for ($slot = 1; $slot <= 3; $slot++) {
            $productSlug = interessa_admin_slugify((string) ($slotSelections[$slot] ?? ''));
            if ($productSlug === '' || isset($seen[$productSlug])) {
                continue;
            }
            $seen[$productSlug] = true;

            $role = interessa_admin_slugify((string) ($roles[$productSlug] ?? 'standard'));
            if (!in_array($role, ['featured', 'value', 'alternative', 'vegan', 'clean', 'standard'], true)) {
                $role = 'standard';
            }
            if ($featuredProductSlug !== '' && $productSlug === $featuredProductSlug) {
                $role = 'featured';
            }

            $placementValue = interessa_admin_slugify((string) ($placement[$productSlug] ?? ''));
            if (!in_array($placementValue, ['recommended', 'comparison', 'both', 'hidden'], true)) {
                $placementValue = $comparisonAllowed ? 'both' : 'recommended';
            }

            $showInTop = in_array($placementValue, ['recommended', 'both'], true);
            $showInComparison = in_array($placementValue, ['comparison', 'both'], true);
            if ($featuredProductSlug !== '' && $productSlug === $featuredProductSlug) {
                $showInTop = true;
            }

            $rows[] = [
                'product_slug' => $productSlug,
                'order' => $slot,
                'role' => $role,
                'show_in_top' => $showInTop,
                'show_in_comparison' => $showInComparison,
            ];
        }

        if ($rows !== []) {
            $hasFeatured = false;
            foreach ($rows as $row) {
                if ((string) ($row['role'] ?? 'standard') === 'featured') {
                    $hasFeatured = true;
                    break;
                }
            }
            if (!$hasFeatured) {
                $rows[0]['role'] = 'featured';
                $rows[0]['show_in_top'] = true;
            }
        }

        return $rows;
    }

    $enabled = $_POST['article_product_enabled'] ?? [];
    $orders = $_POST['article_product_order'] ?? [];
    $roles = $_POST['article_product_role'] ?? [];
    $placement = $_POST['article_product_placement'] ?? [];
    $featuredSlug = interessa_admin_slugify((string) ($_POST['article_product_featured_slug'] ?? ''));
    $top = $_POST['article_product_top'] ?? [];
    $comparison = $_POST['article_product_comparison'] ?? [];

    if (!is_array($enabled)) {
        return [];
    }

    $rows = [];
    $nextAutoOrder = 1;
    foreach ($enabled as $productSlug => $value) {
        $productSlug = interessa_admin_slugify((string) $productSlug);
        if ($productSlug === '' || (string) $value !== '1') {
            continue;
        }

        $role = interessa_admin_slugify((string) ($roles[$productSlug] ?? 'standard'));
        if (!in_array($role, ['featured', 'value', 'alternative', 'vegan', 'clean', 'standard'], true)) {
            $role = 'standard';
        }
        if ($featuredSlug !== '' && $productSlug === $featuredSlug) {
            $role = 'featured';
        }

        $placementValue = interessa_admin_slugify((string) ($placement[$productSlug] ?? ''));
        $showInTop = isset($top[$productSlug]);
        $showInComparison = isset($comparison[$productSlug]);
        if (in_array($placementValue, ['recommended', 'comparison', 'both', 'hidden'], true)) {
            $showInTop = in_array($placementValue, ['recommended', 'both'], true);
            $showInComparison = in_array($placementValue, ['comparison', 'both'], true);
        }
        if ($featuredSlug !== '' && $productSlug === $featuredSlug) {
            $showInTop = true;
        }

        $orderValue = (int) ($orders[$productSlug] ?? 0);
        if ($orderValue <= 0) {
            $orderValue = $nextAutoOrder;
        }
        $nextAutoOrder = max($nextAutoOrder, $orderValue) + 1;

        $rows[] = [
            'product_slug' => $productSlug,
            'order' => max(1, $orderValue),
            'role' => $role,
            'show_in_top' => $showInTop,
            'show_in_comparison' => $showInComparison,
        ];
    }

    usort($rows, static function (array $left, array $right): int {
        $orderCompare = ((int) ($left['order'] ?? 999)) <=> ((int) ($right['order'] ?? 999));
        if ($orderCompare !== 0) {
            return $orderCompare;
        }

        return strcmp((string) ($left['product_slug'] ?? ''), (string) ($right['product_slug'] ?? ''));
    });

    return array_values($rows);
}

function interessa_admin_product_plan_recommended_slugs(array $plan): array {
    $slugs = [];
    foreach ($plan as $row) {
        if (!is_array($row) || empty($row['show_in_top'])) {
            continue;
        }
        $slug = interessa_admin_slugify((string) ($row['product_slug'] ?? ''));
        if ($slug !== '') {
            $slugs[] = $slug;
        }
    }

    return array_values(array_unique($slugs));
}

function interessa_admin_product_plan_comparison_rows(array $plan): array {
    $rows = [];
    foreach ($plan as $row) {
        if (!is_array($row) || empty($row['show_in_comparison'])) {
            continue;
        }

        $slug = interessa_admin_slugify((string) ($row['product_slug'] ?? ''));
        if ($slug === '') {
            continue;
        }

        $rows[] = [
            'product_slug' => $slug,
            'best_for' => interessa_admin_role_label((string) ($row['role'] ?? 'standard')),
        ];
    }

    return $rows;
}

function interessa_admin_default_comparison_columns(): array {
    return [
        ['key' => 'name', 'label' => 'Produkt', 'type' => 'product'],
        ['key' => 'best_for', 'label' => 'Najlepsie pre', 'type' => 'text'],
        ['key' => 'rating', 'label' => 'Hodnotenie', 'type' => 'text'],
        ['key' => 'code', 'label' => 'Odkaz', 'type' => 'cta'],
    ];
}

function interessa_admin_role_label(string $role): string {
    return match ($role) {
        'featured' => 'Hlavny tip',
        'value' => 'Vyhodna volba',
        'alternative' => 'Ina moznost',
        'vegan' => 'Veganska moznost',
        'clean' => 'Cista moznost',
        default => 'Bez oznacenia',
    };
}

function interessa_admin_role_hint(string $role): string {
    return match ($role) {
        'featured' => 'Pouzi, ked je to hlavna odporucana volba pre vacsinu ludi.',
        'value' => 'Pouzi, ked ma produkt dobry pomer cena a vykon.',
        'alternative' => 'Pouzi, ked je to rozumna druha alebo tretia moznost.',
        'vegan' => 'Pouzi, ked je produkt vhodny pre veganov.',
        'clean' => 'Pouzi, ked ma jednoduche alebo ciste zlozenie.',
        default => 'Pouzi, ked nechces pri produkte ziadny maly stitok.',
    };
}

function interessa_admin_article_product_help(string $articleSlug): array {
    return match ($articleSlug) {
        'najlepsie-proteiny-2026' => [
            'summary' => 'Pri prvom importe sem davaju len proteinovych kandidatov. Zatial nechaj Bez oznacenia, horny vyber vypnuty a porovnavaciu tabulku vypnutu.',
            'top_label' => 'Ukazat v hornom vybere',
            'comparison_label' => 'Ukazat v porovnavacej tabulke',
        ],
        'kreatin-porovnanie' => [
            'summary' => 'Pri prvom importe sem davaju len kreatinovych kandidatov. Kofein, pre-workout alebo ine performance veci sem nepatria. Zatial nechaj Bez oznacenia a obidve volby vypnute.',
            'top_label' => 'Ukazat v hornom vybere',
            'comparison_label' => 'Ukazat v porovnavacej tabulke',
        ],
        'doplnky-vyzivy' => [
            'summary' => 'Pri prvom importe sem davaju len zakladne doplnky ako multivitamin, vitamin D, horcik, probiotika alebo kreatin. Zatial nechaj Bez oznacenia a obidve volby vypnute.',
            'top_label' => 'Ukazat v hornom vybere',
            'comparison_label' => 'Ukazat v porovnavacej tabulke',
        ],
        default => [
            'summary' => 'Pri prvom importe produkt len prirad ku spravnemu clanku ako kandidata. Finalny vyber urobi neskor web vlakno.',
            'top_label' => 'Ukazat v hornom vybere',
            'comparison_label' => 'Ukazat v porovnavacej tabulke',
        ],
    };
}

function interessa_admin_text_keyword_hits(string $text, array $keywords): array {
    $hits = [];
    $normalizedText = strtolower(trim($text));
    if ($normalizedText === '') {
        return $hits;
    }

    foreach ($keywords as $keyword) {
        $needle = strtolower(trim((string) $keyword));
        if ($needle === '') {
            continue;
        }
        if (str_contains($normalizedText, $needle)) {
            $hits[] = $needle;
        }
    }

    return array_values(array_unique($hits));
}

function interessa_admin_guess_candidate_article(array $candidate): array {
    $text = strtolower(trim(implode(' ', array_filter([
        (string) ($candidate['name'] ?? ''),
        (string) ($candidate['category'] ?? ''),
        (string) ($candidate['url'] ?? ''),
    ]))));

    if ($text === '') {
        return ['slug' => '', 'reason' => '', 'hits' => []];
    }

    $rules = [
        'najlepsie-proteiny-2026' => [
            'keywords' => ['whey', 'concentrate', 'isolate', 'clear', 'vegan'],
            'blocked' => ['gainer', 'bcaa', 'eaa', 'kolagen', 'collagen', 'pre-workout', 'caffeine', 'kofein', 'bar', 'tycinka', 'cookie', 'snack', 'brownie', 'pudding', 'porridge', 'oatmeal', 'kasa'],
            'reason' => 'vyzera ako proteinovy kandidat',
        ],
        'kreatin-porovnanie' => [
            'keywords' => ['creatine', 'kreatin', 'monohydrate', 'monohydrat', 'creapure', 'hcl'],
            'blocked' => ['caffeine', 'kofein', 'pre-workout', 'bcaa', 'eaa', 'burner', 'spalovac', 'stim', 'bar', 'tycinka', 'snack'],
            'reason' => 'vyzera ako kreatinovy kandidat',
        ],
        'doplnky-vyzivy' => [
            'keywords' => ['magnesium', 'horcik', 'd3', 'vitamin c', 'zinok', 'zinc', 'multivitamin', 'probiotic', 'probiotik', 'k2'],
            'blocked' => ['pre-workout', 'caffeine', 'kofein', 'gainer', 'bcaa', 'eaa', 'bar', 'tycinka', 'snack'],
            'reason' => 'vyzera ako zakladny doplnok vyzivy',
        ],
    ];

    $bestSlug = '';
    $bestHits = [];
    $bestReason = '';
    $bestScore = 0;
    $scoreTie = false;

    foreach ($rules as $slug => $rule) {
        $blockedHits = interessa_admin_text_keyword_hits($text, (array) ($rule['blocked'] ?? []));
        if ($blockedHits !== []) {
            continue;
        }

        $hits = interessa_admin_text_keyword_hits($text, (array) ($rule['keywords'] ?? []));
        $score = count($hits);
        if ($score === 0) {
            continue;
        }

        if ($score > $bestScore) {
            $bestSlug = $slug;
            $bestHits = $hits;
            $bestReason = (string) ($rule['reason'] ?? '');
            $bestScore = $score;
            $scoreTie = false;
            continue;
        }

        if ($score === $bestScore) {
            $scoreTie = true;
        }
    }

    if ($bestSlug === '' || $scoreTie) {
        return ['slug' => '', 'reason' => '', 'hits' => []];
    }

    return [
        'slug' => $bestSlug,
        'reason' => $bestReason,
        'hits' => $bestHits,
    ];
}

function interessa_admin_candidate_phase_one_fit(array $candidate): array {
    $guess = interessa_admin_guess_candidate_article($candidate);
    if (trim((string) ($guess['slug'] ?? '')) !== '') {
        return [
            'status' => 'fit',
            'slug' => (string) ($guess['slug'] ?? ''),
            'reason' => (string) ($guess['reason'] ?? ''),
            'hits' => (array) ($guess['hits'] ?? []),
            'blocked' => [],
        ];
    }

    $text = strtolower(trim(implode(' ', array_filter([
        (string) ($candidate['name'] ?? ''),
        (string) ($candidate['category'] ?? ''),
        (string) ($candidate['url'] ?? ''),
    ]))));
    $blocked = interessa_admin_text_keyword_hits($text, [
        'bar',
        'tycinka',
        'cookie',
        'snack',
        'brownie',
        'pudding',
        'caffeine',
        'kofein',
        'pre-workout',
        'bcaa',
        'eaa',
        'gainer',
    ]);

    return [
        'status' => 'no-fit',
        'slug' => '',
        'reason' => $blocked !== []
            ? 'vyzera skor ako iny typ produktu: ' . implode(', ', $blocked)
            : 'admin ho nevie bezpecne zaradit do prvych troch clankov',
        'hits' => [],
        'blocked' => $blocked,
    ];
}

function interessa_admin_candidate_import_presets(): array {
    return [
        'najlepsie-proteiny-2026' => [
            'title' => 'Najlepsie proteiny 2026',
            'merchant_defaults' => ['gymbeam', 'protein-sk'],
            'recommended_filters' => ['whey', 'concentrate', 'isolate', 'clear', 'vegan'],
            'exclude_terms' => ['bar', 'tycinka', 'cookie', 'snack', 'brownie', 'pudding', 'porridge', 'oatmeal', 'kasa', 'gainer', 'bcaa', 'eaa', 'kolagen', 'collagen', 'pre-workout', 'caffeine', 'kofein'],
            'what_belongs' => 'whey, concentrate, isolate, clear protein, vegan blend',
            'what_not' => 'tycinky, snacky, gainery, BCAA, EAA, kolagen, pre-workout',
            'warning' => 'Nepouzivaj siroky filter protein. Taha aj tycinky a iny balast.',
        ],
        'kreatin-porovnanie' => [
            'title' => 'Kreatin - porovnanie',
            'merchant_defaults' => ['gymbeam', 'protein-sk', 'ironaesthetics'],
            'recommended_filters' => ['creatine', 'kreatin', 'monohydrate', 'creapure', 'hcl'],
            'exclude_terms' => ['pre-workout', 'caffeine', 'kofein', 'bcaa', 'eaa', 'amino', 'burner', 'spalovac', 'stim', 'bar', 'tycinka', 'snack'],
            'what_belongs' => 'kreatin, monohydrat, Creapure, HCl',
            'what_not' => 'predtreningovky, kofein, aminokyseliny, spalovace, snacky',
            'warning' => 'Sem nepatria kofeinove tablety ani ine performance doplnky bez jasneho kreatinoveho focusu.',
        ],
        'doplnky-vyzivy' => [
            'title' => 'Doplnky vyzivy',
            'merchant_defaults' => ['gymbeam', 'imunoklub', 'symprove', 'protein-sk'],
            'recommended_filters' => ['multivitamin', 'magnesium', 'horcik', 'vitamin d', 'd3', 'zinc', 'zinok', 'probiotic'],
            'exclude_terms' => ['pre-workout', 'gainer', 'bar', 'tycinka', 'snack', 'bcaa', 'eaa'],
            'what_belongs' => 'multivitamin, horcik, vitamin D, zinok, probiotika, pripadne kreatin ako doplnok',
            'what_not' => 'snacky, gainery, predtreningovky, uzke performance produkty',
            'warning' => 'Sem idu zakladne doplnky pre bezny start, nie sportove snacky a nahodny balast.',
        ],
    ];
}

function interessa_admin_candidate_import_preset(string $articleSlug): array {
    $presets = interessa_admin_candidate_import_presets();
    $articleSlug = canonical_article_slug($articleSlug);
    return $presets[$articleSlug] ?? [
        'title' => $articleSlug,
        'merchant_defaults' => [],
        'recommended_filters' => [],
        'exclude_terms' => [],
        'what_belongs' => '',
        'what_not' => '',
        'warning' => '',
    ];
}

function interessa_admin_article_slot_relevance_preset(string $articleSlug): array {
    $articleSlug = canonical_article_slug($articleSlug);
    $preset = interessa_admin_candidate_import_preset($articleSlug);
    $preferredCategories = function_exists('interessa_article_product_categories')
        ? interessa_article_product_categories($articleSlug)
        : [];

    if (str_contains($articleSlug, 'pre-workout')) {
        return [
            'strict_filter' => true,
            'recommended_filters' => ['pre-workout', 'predtrening', 'stim', 'pump', 'citrulline', 'citrulin', 'beta-alanine', 'beta alanine', 'caffeine', 'kofein', 'nitric oxide', 'pump support', 'arginine', 'aakg', 'no booster'],
            'exclude_terms' => ['creatine', 'kreatin', 'magnesium', 'horcik', 'zinc', 'zinok', 'probiotic', 'probiotik', 'whey', 'protein', 'collagen', 'kolagen', 'klby', 'joint', 'imunita', 'immunity', 'vitamin', 'multivitamin', 'minerals', 'mineraly'],
            'preferred_categories' => ['pre-workout'],
        ];
    }

    return [
        'strict_filter' => false,
        'recommended_filters' => array_values(array_unique(array_map('strval', (array) ($preset['recommended_filters'] ?? [])))),
        'exclude_terms' => array_values(array_unique(array_map('strval', (array) ($preset['exclude_terms'] ?? [])))),
        'preferred_categories' => array_values(array_unique(array_map('strval', $preferredCategories))),
    ];
}

function interessa_admin_article_slot_product_relevance(array $product, string $articleSlug): array {
    $normalized = interessa_normalize_product($product);
    $preset = interessa_admin_article_slot_relevance_preset($articleSlug);
    $productCategory = normalize_category_slug((string) ($normalized['category'] ?? ''));
    $text = strtolower(trim(implode(' ', array_filter([
        (string) ($normalized['slug'] ?? ''),
        (string) ($normalized['name'] ?? ''),
        (string) ($normalized['summary'] ?? ''),
        (string) ($normalized['category'] ?? ''),
        (string) ($normalized['merchant'] ?? ''),
        (string) ($normalized['brand'] ?? ''),
        (string) ($normalized['fallback_url'] ?? ''),
    ]))));

    $hits = interessa_admin_text_keyword_hits($text, (array) ($preset['recommended_filters'] ?? []));
    $blockedHits = interessa_admin_text_keyword_hits($text, (array) ($preset['exclude_terms'] ?? []));
    $preferredCategories = array_values(array_filter(array_map('strval', (array) ($preset['preferred_categories'] ?? []))));
    $categoryPriority = array_search($productCategory, $preferredCategories, true);
    $strictFilter = !empty($preset['strict_filter']);
    $passesStrict = $blockedHits === [] && ($categoryPriority !== false || $hits !== []);

    $score = 0;
    if ($strictFilter) {
        if ($passesStrict) {
            if ($categoryPriority !== false) {
                $score += 320 - (((int) $categoryPriority) * 40);
            }
            $score += count($hits) * 55;
        } else {
            $score -= 1000;
        }
    } else {
        if ($categoryPriority !== false) {
            $score += 220 - (((int) $categoryPriority) * 40);
        }
        $score += count($hits) * 35;
        $score -= count($blockedHits) * 120;
    }

    return [
        'score' => $score,
        'hits' => $hits,
        'blocked_hits' => $blockedHits,
        'category_match' => $categoryPriority !== false,
        'strict_filter' => $strictFilter,
        'passes_strict' => $passesStrict,
        'preferred_categories' => $preferredCategories,
    ];
}

function interessa_admin_candidate_row_text(array $row): string {
    return strtolower(trim(implode(' ', array_filter([
        (string) ($row['name'] ?? ''),
        (string) ($row['category'] ?? ''),
        (string) ($row['product_type'] ?? ''),
        (string) ($row['summary'] ?? ''),
        (string) ($row['url'] ?? ''),
    ]))));
}

function interessa_admin_candidate_matches_article_preset(array $row, string $articleSlug, array $preset): bool {
    $text = interessa_admin_candidate_row_text($row);
    if ($text === '') {
        return false;
    }

    $excludeHits = interessa_admin_text_keyword_hits($text, (array) ($preset['exclude_terms'] ?? []));
    if ($excludeHits !== []) {
        return false;
    }

    $includeHits = interessa_admin_text_keyword_hits($text, (array) ($preset['recommended_filters'] ?? []));
    if ($includeHits === []) {
        return false;
    }

    $fit = interessa_admin_candidate_phase_one_fit($row);
    return canonical_article_slug((string) ($fit['slug'] ?? '')) === canonical_article_slug($articleSlug);
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

function interessa_admin_category_image_brief(string $slug, string $variant = 'hero'): array {
    $slug = normalize_category_slug($slug);
    $variant = interessa_admin_slugify($variant);
    if ($variant === '') {
        $variant = 'hero';
    }
    $meta = category_meta($slug) ?? ['title' => humanize_slug($slug), 'description' => ''];
    $title = interessa_admin_clean_label((string) ($meta['title'] ?? humanize_slug($slug)));
    $description = interessa_admin_clean_label((string) ($meta['description'] ?? ''));
    $assetPath = function_exists('interessa_admin_category_image_asset')
        ? interessa_admin_category_image_asset($slug, $variant, 'webp')
        : 'img/categories/' . $slug . '/' . $variant . '.webp';

    $colorAccents = [
        'proteiny' => 'jemna modra a kremova',
        'vyziva' => 'tepla zelena a bezova',
        'mineraly' => 'svetla modra a pieskova',
        'imunita' => 'svieza zelena a zlta',
        'sila' => 'tmavsia modra a tehlova',
        'klby-koza' => 'svetla kremova a broskynova',
        'aminokyseliny' => 'svetla ruzova a modra',
        'chudnutie' => 'svieza ruzova a kremova',
        'doplnkove-prislusenstvo' => 'cista sediva a svetla modra',
        'kreatin' => 'jemna modra a tmavsia sediva',
        'pre-workout' => 'energicka ruzova a tyrkysova',
        'probiotika-travenie' => 'cista zelena a svetla modra',
    ];
    $motifs = [
        'proteiny' => 'shaker, protein alebo funkcny food detail',
        'vyziva' => 'potraviny, snacky alebo funkcny food editorial',
        'mineraly' => 'doplnky, tablety, kapsuly alebo cisty wellness detail',
        'imunita' => 'wellness doplnky a sviezi editorial lifestyle',
        'sila' => 'vykon, treningovy doplnok alebo silovy editorial',
        'klby-koza' => 'kolagen, kozmeticky doplnok alebo clean wellness produkt',
        'aminokyseliny' => 'sportovy shaker, aminokyseliny alebo sportovy editorial',
        'chudnutie' => 'lahky fitness editorial a clean produktovy doplnok',
        'doplnkove-prislusenstvo' => 'saker, krabicka, doplnkove prislusenstvo alebo clean desk setup',
        'kreatin' => 'kreatinovy produkt, shaker a silovy editorial',
        'pre-workout' => 'predtrenigovka, shaker alebo energicky workout editorial',
        'probiotika-travenie' => 'probiotika, travenie, wellness editorial alebo clean supplement detail',
    ];

    $promptParts = [
        'Editorialny hlavny obrazok pre temu "' . $title . '" na redakcny web o doplnkoch vyzivy.',
        $description !== '' ? 'Tema: ' . $description . '.' : '',
        'Vizualny smer: clean premium health and fitness look, bez textu, bez loga, bez kolaze.',
        'Farebny akcent: ' . ($colorAccents[$slug] ?? 'jemne prirodzene farby') . '.',
        'Motiv: ' . ($motifs[$slug] ?? 'clean editorial wellness motiv') . '.',
        $variant === 'thumb'
            ? 'Kompozicia: jednoduchy cisty motiv, dobre citatelny aj v mensom stvorcovom vyreze.'
            : 'Kompozicia: sirsi obrazok s hlavnym motivom pekne v strede, nech sa hodi na web aj do karticiek.',
    ];

    return [
        'slug' => $slug,
        'title' => $title,
        'prompt' => implode(' ', array_values(array_filter($promptParts))),
        'alt_text' => $title,
        'dimensions' => $variant === 'thumb' ? '1200x1200' : '1600x900',
        'asset_path' => $assetPath,
        'file_name' => $slug . '-' . $variant . '.webp',
        'variant' => $variant,
    ];
}

function interessa_admin_category_visual_direction(string $slug): array {
    $map = [
        'proteiny' => [
            'style' => 'Produktovy a sportovy',
            'accent' => 'Modra, biela, ciste svetlo',
            'motif' => 'Shaker, protein, fit postava, cisty treningovy moment',
            'message' => 'Sila, regeneracia a dovera v produkt',
        ],
        'vyziva' => [
            'style' => 'Editorial a clean',
            'accent' => 'Zelena, bezova, jemne prirodzene farby',
            'motif' => 'Jedlo, doplnky, stolovy detail, prirodzene svetlo',
            'message' => 'Kazdodenne zdrave rozhodnutia a prakticka vyziva',
        ],
        'mineraly' => [
            'style' => 'Ingredient a detail',
            'accent' => 'Modra, tyrkysova, svetla seda',
            'motif' => 'Kapsuly, tablety, voda, cisty povrch',
            'message' => 'Doplnenie, presnost a zakladna starostlivost o telo',
        ],
        'imunita' => [
            'style' => 'Editorial a ochrana',
            'accent' => 'Zlta, oranzova, svetla kremova',
            'motif' => 'Vitaminy, citrusy, domaci wellness moment',
            'message' => 'Istota, podpora imunity a kazdodenne zdravie',
        ],
        'sila' => [
            'style' => 'Lifestyle a vykon',
            'accent' => 'Tmava modra, cierna, cervena',
            'motif' => 'Trening, shaker, svalovy detail, pohyb',
            'message' => 'Energia, vykon a sportovy ciel',
        ],
        'klby-koza' => [
            'style' => 'Clean a premium',
            'accent' => 'Bezova, broskynova, svetla modra',
            'motif' => 'Plet, kolagen, wellness detail, kapsuly',
            'message' => 'Starostlivost o telo, krasu a komfort',
        ],
        'aminokyseliny' => [
            'style' => 'Sportovy detail',
            'accent' => 'Ruzova, modra, biela',
            'motif' => 'Shaker, aminokyseliny, atleticka postava',
            'message' => 'Regeneracia, vykon a svalova podpora',
        ],
        'chudnutie' => [
            'style' => 'Lifestyle a clean',
            'accent' => 'Svetla zelena, biela, jemna ruzova',
            'motif' => 'Pohyb, lahkost, wellness, zdrava zmena',
            'message' => 'Lahkost, disciplina a zdrava premena',
        ],
        'doplnkove-prislusenstvo' => [
            'style' => 'Produktovy detail',
            'accent' => 'Seda, cierna, biela',
            'motif' => 'Shakery, flasky, doplnky, cisty studiovy stol',
            'message' => 'Praktickost, poriadok a jednoduche pouzitie',
        ],
        'kreatin' => [
            'style' => 'Produktovy a silovy',
            'accent' => 'Modra, fialova, biela',
            'motif' => 'Kreatin, shaker, treningovy detail',
            'message' => 'Sila, vykon a overeny zaklad doplnkov',
        ],
        'pre-workout' => [
            'style' => 'Energicky lifestyle',
            'accent' => 'Ruzova, modra, cierna',
            'motif' => 'Predtreningovka, shaker, priprava na trening',
            'message' => 'Energia, fokus a start vykonu',
        ],
        'probiotika-travenie' => [
            'style' => 'Editorial a jemny health look',
            'accent' => 'Mentolova, svetla zelena, kremova',
            'motif' => 'Kapsuly, travenie, lahke jedlo, kludna scena',
            'message' => 'Rovnovaha, travenie a klud v tele',
        ],
    ];

    return $map[$slug] ?? [
        'style' => 'Clean editorial',
        'accent' => 'Jemne prirodzene farby',
        'motif' => 'Produkt alebo lifestyle scena k teme',
        'message' => 'Dovera, jasno a profesionalny zdravotny web',
    ];
}

function interessa_admin_category_image_queue(array $categoryOptions): array {
    $rows = [];
    foreach ($categoryOptions as $slug => $item) {
        $localAsset = function_exists('interessa_category_local_asset')
            ? interessa_category_local_asset((string) $slug, 'hero')
            : null;
        $imageMeta = interessa_category_image_meta((string) $slug, 'hero', true);
        $brief = interessa_admin_category_image_brief((string) $slug);
        $rows[] = [
            'slug' => (string) $slug,
            'title' => interessa_admin_clean_label((string) ($item['title'] ?? $brief['title'] ?? $slug)),
            'asset_path' => (string) ($brief['asset_path'] ?? ''),
            'file_name' => (string) ($brief['file_name'] ?? ''),
            'dimensions' => (string) ($brief['dimensions'] ?? '1600x900'),
            'prompt' => (string) ($brief['prompt'] ?? ''),
            'has_local_theme_image' => $localAsset !== null,
            'local_asset' => $localAsset ?? '',
            'source_type' => is_array($imageMeta) ? (string) ($imageMeta['source_type'] ?? 'placeholder') : 'placeholder',
            'theme_url' => category_url((string) $slug),
        ];
    }

    usort($rows, static function (array $left, array $right): int {
        $statusSort = ((int) $left['has_local_theme_image']) <=> ((int) $right['has_local_theme_image']);
        if ($statusSort !== 0) {
            return $statusSort;
        }

        return strcasecmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
    });

    return $rows;
}

function interessa_admin_category_asset_manifest(array $categoryOptions): array {
    $rows = [];
    foreach ($categoryOptions as $slug => $item) {
        $themeSlug = (string) $slug;
        $title = interessa_admin_clean_label((string) ($item['title'] ?? $themeSlug));
        $heroAsset = function_exists('interessa_category_local_asset') ? (interessa_category_local_asset($themeSlug, 'hero') ?? '') : '';
        $thumbAsset = function_exists('interessa_category_local_asset') ? (interessa_category_local_asset($themeSlug, 'thumb') ?? '') : '';

        $rows[] = [
            'slug' => $themeSlug,
            'title' => $title,
            'theme_url' => category_url($themeSlug),
            'items' => [
                [
                    'label' => 'Hlavny obrazok temy',
                    'variant' => 'hero',
                    'required' => true,
                    'dimensions' => '1600x900',
                    'asset_path' => function_exists('interessa_admin_category_image_asset')
                        ? interessa_admin_category_image_asset($themeSlug, 'hero', 'webp')
                        : 'img/categories/' . $themeSlug . '/hero.webp',
                    'asset' => $heroAsset,
                    'ready' => $heroAsset !== '',
                ],
                [
                    'label' => 'Mensi obrazok temy',
                    'variant' => 'thumb',
                    'required' => false,
                    'dimensions' => '1200x1200',
                    'asset_path' => function_exists('interessa_admin_category_image_asset')
                        ? interessa_admin_category_image_asset($themeSlug, 'thumb', 'webp')
                        : 'img/categories/' . $themeSlug . '/thumb.webp',
                    'asset' => $thumbAsset,
                    'ready' => $thumbAsset !== '',
                ],
            ],
        ];
    }

    usort($rows, static function (array $left, array $right): int {
        return strcasecmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
    });

    return $rows;
}

function interessa_admin_image_queue(array $articleOptions, string $filter = 'missing', int $limit = 12): array {
    $rows = [];
    foreach ($articleOptions as $slug => $item) {
        $imageState = function_exists('interessa_article_image_state')
            ? interessa_article_image_state((string) $slug, 'hero')
            : ['status' => 'missing', 'label' => 'naozaj chyba', 'meta' => null];
        $meta = is_array($imageState['meta'] ?? null) ? $imageState['meta'] : null;
        $src = (string) ($meta['src'] ?? '');
        $promptMeta = interessa_hero_prompt_meta((string) $slug);
        $rows[] = [
            'slug' => (string) $slug,
            'title' => interessa_admin_clean_label((string) ($item['title'] ?? $slug)),
            'asset_path' => (string) ($promptMeta['asset_path'] ?? ''),
            'file_name' => (string) ($promptMeta['file_name'] ?? ''),
            'alt_text' => (string) ($promptMeta['alt_text'] ?? ''),
            'dimensions' => (string) ($promptMeta['dimensions'] ?? '1200x800'),
            'prompt' => (string) ($promptMeta['prompt'] ?? ''),
            'has_article_image' => ($imageState['status'] ?? 'missing') === 'article',
            'has_theme_fallback' => ($imageState['status'] ?? 'missing') === 'theme-fallback',
            'image_state' => (string) ($imageState['status'] ?? 'missing'),
            'image_state_label' => (string) ($imageState['label'] ?? 'naozaj chyba'),
            'source_type' => (string) ($meta['source_type'] ?? 'placeholder'),
            'src' => $src,
            'article_url' => article_url((string) $slug),
        ];
    }

    if ($filter === 'missing') {
        $rows = array_values(array_filter($rows, static fn(array $row): bool => ($row['image_state'] ?? 'missing') === 'missing'));
    } elseif ($filter === 'theme-fallback') {
        $rows = array_values(array_filter($rows, static fn(array $row): bool => ($row['image_state'] ?? 'missing') === 'theme-fallback'));
    } elseif ($filter === 'article') {
        $rows = array_values(array_filter($rows, static fn(array $row): bool => ($row['image_state'] ?? 'missing') === 'article'));
    }

    usort($rows, static function (array $left, array $right): int {
        $statusWeight = static function (string $status): int {
            return match ($status) {
                'missing' => 0,
                'theme-fallback' => 1,
                'article' => 2,
                default => 3,
            };
        };
        $statusSort = $statusWeight((string) ($left['image_state'] ?? 'missing')) <=> $statusWeight((string) ($right['image_state'] ?? 'missing'));
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
        $clickState = interessa_admin_product_click_state($normalized);
        $status = '';

        if ($affiliateCode === '') {
            $status = 'chyba affiliate kod';
        } elseif (!is_array($record)) {
            $status = 'kod nie je v registry';
        } elseif (empty($clickState['affiliate_href'])) {
            $status = 'link sa neda vyriesit';
        } elseif (empty($clickState['product_url'])) {
            $status = 'link nevedie na konkretny produkt';
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
        $clickState = interessa_admin_product_click_state($normalized);
        $affiliateReady = !empty($clickState['ready']);
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
$page_description = 'Interny admin panel pre clanky, produkty, obrazky a odkazy do obchodov.';
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
$error = trim((string) ($_GET['error'] ?? ''));

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
                $productPlan = interessa_admin_collect_article_product_plan();
                $visualComparison = interessa_admin_collect_comparison_visual();
                $comparisonColumns = $visualComparison['columns'] !== []
                    ? $visualComparison['columns']
                    : interessa_admin_decode_json_textarea((string) ($_POST['comparison_columns_json'] ?? ''), 'Stlpce porovnania');
                $comparisonRows = $visualComparison['rows'] !== []
                    ? $visualComparison['rows']
                    : interessa_admin_decode_json_textarea((string) ($_POST['comparison_rows_json'] ?? ''), 'Riadky porovnania');
                $recommended = $productPlan !== []
                    ? interessa_admin_product_plan_recommended_slugs($productPlan)
                    : interessa_admin_recommended_selection();
                if ($productPlan !== []) {
                    $comparisonRows = interessa_admin_product_plan_comparison_rows($productPlan);
                    if ($comparisonRows !== [] && $comparisonColumns === []) {
                        $comparisonColumns = interessa_admin_default_comparison_columns();
                    }
                }
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
                    'product_plan' => $productPlan,
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
                $returnArticleSlug = canonical_article_slug(trim((string) ($_POST['article_slug'] ?? $returnSlug)));
                $targetSlot = max(0, min(3, (int) ($_POST['target_slot'] ?? 0)));
                if ($returnArticleSlug !== '' && $targetSlot > 0) {
                    interessa_admin_assign_product_to_article_slot($returnArticleSlug, $slug, $targetSlot);
                    interessa_admin_redirect_fragment('articles', ['slug' => $returnArticleSlug, 'saved' => 'product'], 'slot-' . $targetSlot);
                }
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

            if ($action === 'save_brand_logo') {
                if (empty($_FILES['brand_logo_file']['tmp_name'])) {
                    throw new RuntimeException('Najprv vyber subor s logom.');
                }

                interessa_admin_store_uploaded_brand_file('logo-full', $_FILES['brand_logo_file'], ['svg', 'png', 'jpg', 'jpeg', 'webp'], 'svg');
                interessa_admin_redirect('brand', ['saved' => 'logo']);
            }

            if ($action === 'save_brand_icon_bundle') {
                interessa_admin_store_brand_icon_bundle($_FILES);
                interessa_admin_redirect('brand', ['saved' => 'icon']);
            }

            if ($action === 'save_brand_og_default') {
                if (empty($_FILES['brand_og_file']['tmp_name'])) {
                    throw new RuntimeException('Najprv vyber obrazok pre zdielanie.');
                }

                interessa_admin_store_uploaded_brand_file('og-default', $_FILES['brand_og_file'], ['svg', 'png', 'jpg', 'jpeg', 'webp'], 'png');
                interessa_admin_redirect('brand', ['saved' => 'og']);
            }

            if ($action === 'prepare_product_from_link') {
                $slug = trim((string) ($_POST['product_slug'] ?? ''));
                $returnSection = trim((string) ($_POST['return_section'] ?? ''));
                $returnSlug = canonical_article_slug(trim((string) ($_POST['return_slug'] ?? '')));
                $returnArticleSlug = canonical_article_slug(trim((string) ($_POST['article_slug'] ?? $returnSlug)));
                $targetSlot = max(0, min(3, (int) ($_POST['target_slot'] ?? 0)));
                $result = interessa_admin_prepare_product_from_input_link($slug, (string) ($_POST['source_link'] ?? ''));
                $savedKey = 'product-link-ready';

                $preparedProduct = interessa_product($slug);
                if (is_array($preparedProduct)) {
                    $normalizedPreparedProduct = interessa_normalize_product($preparedProduct);
                    $hasLocalImage = !empty($normalizedPreparedProduct['has_local_image']);
                    $remoteSrc = trim((string) ($normalizedPreparedProduct['image_remote_src'] ?? ''));
                    if (!empty($result['auto_image_saved']) && !empty($result['click_ready'])) {
                        $savedKey = 'product-ready';
                    } elseif (!empty($result['auto_image_saved'])) {
                        $savedKey = 'product-autofill';
                    } elseif (!$hasLocalImage && $remoteSrc !== '') {
                        $savedKey = 'product-remote-ready';
                    }
                }

                if ($returnArticleSlug !== '' && $targetSlot > 0) {
                    interessa_admin_redirect('products', interessa_admin_product_article_slot_query((string) ($result['slug'] ?? $slug), $returnArticleSlug, $targetSlot, $savedKey, 'product_image'));
                }
                if ($returnSection === 'images' && $returnSlug !== '') {
                    interessa_admin_redirect('products', interessa_admin_product_image_redirect_query((string) ($result['slug'] ?? $slug), $savedKey, 'images', $returnSlug));
                }
                if ($returnSection === 'articles' && $returnSlug !== '') {
                    interessa_admin_redirect('articles', ['slug' => $returnSlug, 'saved' => $savedKey]);
                }

                interessa_admin_redirect('products', [
                    'product' => (string) ($result['slug'] ?? $slug),
                    'saved' => $savedKey,
                    'focus' => 'product_image',
                    'focus_product' => (string) ($result['slug'] ?? $slug),
                ]);
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
                header('Location: ' . article_url($slug), true, 303);
                exit;
            }

            if ($action === 'upload_category_image_only') {
                $slug = normalize_category_slug(trim((string) ($_POST['category_slug'] ?? '')));
                $variant = interessa_admin_slugify((string) ($_POST['category_variant'] ?? 'hero'));
                if (!in_array($variant, ['hero', 'thumb'], true)) {
                    $variant = 'hero';
                }
                $uploadField = $variant === 'thumb' ? 'category_thumb_image' : 'category_image';
                interessa_admin_store_uploaded_category_image($slug, $variant, $_FILES[$uploadField] ?? []);
                header('Location: ' . category_url($slug), true, 303);
                exit;
            }

            if ($action === 'upload_packshot_only') {
                $slug = trim((string) ($_POST['product_slug'] ?? ''));
                $returnSection = trim((string) ($_POST['return_section'] ?? 'products'));
                $returnArticleSlug = canonical_article_slug(trim((string) ($_POST['return_slug'] ?? '')));
                $targetArticleSlug = canonical_article_slug(trim((string) ($_POST['article_slug'] ?? $returnArticleSlug)));
                $targetSlot = max(0, min(3, (int) ($_POST['target_slot'] ?? 0)));
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

                if ($targetArticleSlug !== '' && $targetSlot > 0) {
                    interessa_admin_redirect('products', interessa_admin_product_article_slot_query($slug, $targetArticleSlug, $targetSlot, 'packshot', 'product_image'));
                }
                if ($returnSection === 'images' && $returnArticleSlug !== '') {
                    interessa_admin_redirect('products', interessa_admin_product_image_redirect_query($slug, 'packshot', 'images', $returnArticleSlug));
                }

                interessa_admin_redirect('products', interessa_admin_product_image_redirect_query($slug, 'packshot'));
            }

            if ($action === 'mirror_packshot_from_remote') {
                $slug = trim((string) ($_POST['product_slug'] ?? ''));
                $returnSection = trim((string) ($_POST['return_section'] ?? 'products'));
                $returnArticleSlug = canonical_article_slug(trim((string) ($_POST['return_slug'] ?? '')));
                $targetArticleSlug = canonical_article_slug(trim((string) ($_POST['article_slug'] ?? $returnArticleSlug)));
                $targetSlot = max(0, min(3, (int) ($_POST['target_slot'] ?? 0)));
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

                if ($targetArticleSlug !== '' && $targetSlot > 0) {
                    interessa_admin_redirect('products', interessa_admin_product_article_slot_query($slug, $targetArticleSlug, $targetSlot, 'packshot-mirrored', 'product_image'));
                }
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
                $targetArticleSlug = canonical_article_slug(trim((string) ($_POST['article_slug'] ?? $returnArticleSlug)));
                $targetSlot = max(0, min(3, (int) ($_POST['target_slot'] ?? 0)));

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
                if ($targetArticleSlug !== '' && $targetSlot > 0) {
                    interessa_admin_redirect('products', interessa_admin_product_article_slot_query($slug, $targetArticleSlug, $targetSlot, $savedKey, 'product_image'));
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
                    throw new RuntimeException('Vyber subor s produktmi.');
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

            if ($action === 'import_product_candidates') {
                $candidateTargetArticleSlug = canonical_article_slug(trim((string) ($_POST['candidate_target_article_slug'] ?? '')));
                if ($candidateTargetArticleSlug === '' || !isset($articleOptions[$candidateTargetArticleSlug])) {
                    throw new RuntimeException('Najprv vyber clanok, pre ktory ides importovat produkty.');
                }
                $candidatePreset = interessa_admin_candidate_import_preset($candidateTargetArticleSlug);
                if (($candidatePreset['recommended_filters'] ?? []) === []) {
                    $candidatePreset = [
                        'title' => 'Najlepsie proteiny 2026',
                        'merchant_defaults' => ['gymbeam', 'protein-sk'],
                        'recommended_filters' => ['whey', 'concentrate', 'isolate', 'clear', 'vegan'],
                        'exclude_terms' => ['bar', 'tycinka', 'cookie', 'snack', 'brownie', 'pudding', 'porridge', 'oatmeal', 'kasa', 'gainer', 'bcaa', 'eaa', 'kolagen', 'collagen', 'pre-workout', 'caffeine', 'kofein'],
                        'what_belongs' => 'whey, concentrate, isolate, clear protein, vegan blend',
                        'what_not' => 'tycinky, snacky, gainery, BCAA, EAA, kolagen, pre-workout',
                        'warning' => 'Nepouzivaj siroky filter protein. Taha aj tycinky a iny balast.',
                    ];
                }
                $merchantSlug = interessa_admin_slugify((string) ($_POST['candidate_merchant_slug'] ?? ''));
                if ($merchantSlug === '') {
                    throw new RuntimeException('Vyber obchod, z ktoreho idu produkty.');
                }
                $candidateImportLimit = function_exists('interessa_admin_candidate_import_limit')
                    ? interessa_admin_candidate_import_limit()
                    : 40;
                $candidateFeedUrl = trim((string) ($_POST['candidate_feed_url'] ?? ''));
                $candidateRecommendedFilters = array_values(array_filter(array_map('strval', (array) ($candidatePreset['recommended_filters'] ?? []))));
                if ($candidateRecommendedFilters === []) {
                    $candidateRecommendedFilters = ['whey', 'concentrate', 'isolate', 'clear', 'vegan'];
                }
                $candidateFilterText = trim((string) ($_POST['candidate_filter_text'] ?? ''));
                if ($candidateFilterText === '' || $candidateFilterText === '__auto__') {
                    $candidateFilterText = '__auto__';
                }
                if ($candidateFilterText !== '__auto__' && $candidateRecommendedFilters !== [] && !in_array($candidateFilterText, $candidateRecommendedFilters, true)) {
                    $candidateFilterText = '__auto__';
                }
                $effectiveCandidateFilter = $candidateFilterText !== ''
                    ? $candidateFilterText
                    : implode(', ', (array) ($candidatePreset['recommended_filters'] ?? []));
                if ($candidateFilterText === '__auto__') {
                    $effectiveCandidateFilter = implode(', ', $candidateRecommendedFilters);
                }
                $candidateSourceName = '';
                if ($candidateFeedUrl !== '') {
                    $rows = interessa_admin_parse_feed_url($candidateFeedUrl, $merchantSlug, 0, $effectiveCandidateFilter);
                    $candidateSourceName = $candidateFeedUrl;
                } elseif (!empty($_FILES['candidate_file']['tmp_name']) && is_uploaded_file($_FILES['candidate_file']['tmp_name'])) {
                    $rows = interessa_admin_parse_feed_file((string) $_FILES['candidate_file']['tmp_name'], $merchantSlug, 0, $effectiveCandidateFilter);
                    $candidateSourceName = (string) ($_FILES['candidate_file']['name'] ?? '');
                } else {
                    throw new RuntimeException('Vloz URL feedu alebo vyber subor s produktmi.');
                }
                $rows = array_values(array_filter($rows, static function (array $row) use ($candidateTargetArticleSlug, $candidatePreset): bool {
                    return interessa_admin_candidate_matches_article_preset($row, $candidateTargetArticleSlug, $candidatePreset);
                }));
                if (count($rows) > $candidateImportLimit) {
                    $rows = array_slice($rows, 0, $candidateImportLimit);
                }
                if ($rows === []) {
                    $candidateArticleTitle = (string) ($articleOptions[$candidateTargetArticleSlug]['title'] ?? $candidateTargetArticleSlug);
                    $candidateMerchantName = (string) ($candidateMerchantOptions[$merchantSlug] ?? $merchantSlug);
                    throw new RuntimeException(
                        'Pre clanok "' . $candidateArticleTitle . '" sa v obchode "' . $candidateMerchantName . '" sa nenasli vhodne produkty pre tento pilot. '
                        . 'Tento pilot pusta len ciste proteiny, nie tycinky, snacky, porridge ani iny balast.'
                    );
                }
                $importResult = interessa_admin_import_product_candidates(
                    $rows,
                    'feed',
                    (string) ($candidateMerchantOptions[$merchantSlug] ?? $merchantSlug),
                    $candidateSourceName,
                    [
                        'target_article_slug' => $candidateTargetArticleSlug,
                        'import_filter_text' => $effectiveCandidateFilter,
                    ]
                );
                $imported = is_array($importResult['ids'] ?? null) ? $importResult['ids'] : [];
                $batchId = (string) ($importResult['batch_id'] ?? '');
                $firstImportedCandidate = trim((string) ($imported[0] ?? ''));
                if ($firstImportedCandidate !== '') {
                    interessa_admin_redirect_fragment('products', [
                        'candidate' => $firstImportedCandidate,
                        'saved' => 'candidate-imported',
                        'batch' => $batchId,
                        'import_article' => $candidateTargetArticleSlug,
                    ], 'products-current-candidate');
                }
                interessa_admin_redirect_fragment('products', [
                    'saved' => 'candidate-imported',
                    'batch' => $batchId,
                    'import_article' => $candidateTargetArticleSlug,
                ], 'products-current-candidate');
            }

            if ($action === 'prepare_candidate_click') {
                $candidateId = trim((string) ($_POST['candidate_id'] ?? ''));
                $result = interessa_admin_prepare_candidate_click($candidateId);
                $candidateRow = interessa_admin_product_candidate_record((string) ($result['id'] ?? $candidateId));
                interessa_admin_redirect_fragment('products', [
                    'candidate' => (string) ($result['id'] ?? $candidateId),
                    'saved' => 'candidate-click',
                    'batch' => interessa_admin_slugify((string) ($candidateRow['batch_id'] ?? $_POST['batch'] ?? '')),
                ], 'products-current-candidate');
            }

            if ($action === 'save_candidate_assignment') {
                $candidateId = trim((string) ($_POST['candidate_id'] ?? ''));
                $result = interessa_admin_save_candidate_assignment($candidateId, [
                    'article_slug' => (string) ($_POST['candidate_article_slug'] ?? ''),
                    'order' => (int) ($_POST['candidate_order'] ?? 1),
                    'role' => (string) ($_POST['candidate_role'] ?? 'standard'),
                    'show_in_top' => !empty($_POST['candidate_show_in_top']),
                    'show_in_comparison' => !empty($_POST['candidate_show_in_comparison']),
                ]);
                $candidateRow = interessa_admin_product_candidate_record((string) ($result['id'] ?? $candidateId));
                interessa_admin_redirect_fragment('products', [
                    'candidate' => (string) ($result['id'] ?? $candidateId),
                    'saved' => 'candidate-assignment',
                    'batch' => interessa_admin_slugify((string) ($candidateRow['batch_id'] ?? $_POST['batch'] ?? '')),
                ], 'products-current-candidate');
            }

            if ($action === 'approve_candidate_for_web') {
                $candidateId = trim((string) ($_POST['candidate_id'] ?? ''));
                $result = interessa_admin_approve_candidate_for_web($candidateId);
                $candidateRow = interessa_admin_product_candidate_record((string) ($result['id'] ?? $candidateId));
                interessa_admin_redirect_fragment('products', [
                    'candidate' => (string) ($result['id'] ?? $candidateId),
                    'product' => (string) ($result['product_slug'] ?? ''),
                    'article_product_slug' => (string) ($result['article_slug'] ?? ''),
                    'saved' => 'candidate-approved',
                    'batch' => interessa_admin_slugify((string) ($candidateRow['batch_id'] ?? $_POST['batch'] ?? '')),
                ], 'products-current-candidate');
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

        if (in_array($action, [
            'create_product',
            'save_product',
            'prepare_product_from_link',
            'upload_packshot_only',
            'mirror_packshot_from_remote',
            'enrich_product_from_source',
            'autofill_product_from_source',
            'import_product_candidates',
            'prepare_candidate_click',
            'save_candidate_assignment',
            'approve_candidate_for_web',
        ], true)) {
            $productSlug = trim((string) ($_POST['product_slug'] ?? $_POST['new_product_slug'] ?? ''));
            $candidateId = trim((string) ($_POST['candidate_id'] ?? ''));
            $returnSection = trim((string) ($_POST['return_section'] ?? ''));
            $returnSlug = canonical_article_slug(trim((string) ($_POST['return_slug'] ?? '')));
            $returnArticleSlug = canonical_article_slug(trim((string) ($_POST['article_slug'] ?? $returnSlug)));
            $targetSlot = max(0, min(3, (int) ($_POST['target_slot'] ?? 0)));
            $query = [
                'error' => $error,
            ];
            if ($productSlug !== '') {
                $query['product'] = $productSlug;
                $query['focus_product'] = $productSlug;
            }
            if (in_array($action, [
                'upload_packshot_only',
                'mirror_packshot_from_remote',
                'enrich_product_from_source',
                'autofill_product_from_source',
            ], true)) {
                $query['focus'] = 'product_image';
            }
            if ($candidateId !== '') {
                $query['candidate'] = $candidateId;
            }
            $batchId = interessa_admin_slugify((string) ($_POST['batch'] ?? ''));
            if ($batchId !== '') {
                $query['batch'] = $batchId;
            }
            if ($returnSection !== '' && $returnSlug !== '') {
                $query['return_section'] = $returnSection;
                $query['return_slug'] = $returnSlug;
            }
            if ($productSlug !== '' && $returnArticleSlug !== '' && $targetSlot > 0) {
                interessa_admin_redirect('products', interessa_admin_product_article_slot_query($productSlug, $returnArticleSlug, $targetSlot, '', 'product_link') + [
                    'error' => $error,
                    'focus_product' => $productSlug,
                ]);
            }
            interessa_admin_redirect('products', $query);
        }

        if (in_array($action, [
            'create_affiliate',
            'save_affiliate',
        ], true)) {
            $affiliateCode = trim((string) ($_POST['code'] ?? $_POST['new_affiliate_code'] ?? ''));
            $returnSection = trim((string) ($_POST['return_section'] ?? ''));
            $returnSlug = canonical_article_slug(trim((string) ($_POST['return_slug'] ?? '')));
            $query = [
                'error' => $error,
            ];
            if ($affiliateCode !== '') {
                $query['code'] = $affiliateCode;
            }
            if ($returnSection !== '' && $returnSlug !== '') {
                $query['return_section'] = $returnSection;
                $query['return_slug'] = $returnSlug;
            }
            interessa_admin_redirect('affiliates', $query);
        }
    }
}
$articleOptions = interessa_admin_article_options();
$priorityArticleSlugs = array_values(array_filter([
    'najlepsie-proteiny-2026',
    'protein-na-chudnutie',
    'najlepsi-protein-na-chudnutie-wpc-vs-wpi',
    'kreatin-porovnanie',
    'doplnky-vyzivy',
    'veganske-proteiny-top-vyber-2026',
    'horcik-ktory-je-najlepsi-a-preco',
    'kolagen-na-klby-porovnanie',
    'kolagen-recenzia',
    'pre-workout-ako-vybrat',
    'probiotika-ako-vybrat',
], static fn(string $slug): bool => isset($articleOptions[$slug])));
$phaseOneArticleSlugs = array_values(array_filter([
    'najlepsie-proteiny-2026',
    'kreatin-porovnanie',
    'doplnky-vyzivy',
], static fn(string $slug): bool => isset($articleOptions[$slug])));
$selectedArticleSlug = canonical_article_slug(trim((string) ($_GET['slug'] ?? array_key_first($articleOptions) ?? '')));
$selectedArticleMeta = $selectedArticleSlug !== '' ? article_meta($selectedArticleSlug) : ['title' => '', 'description' => '', 'category' => ''];
$categoryOptions = category_registry();
$selectedThemeSlug = normalize_category_slug(trim((string) ($_GET['topic'] ?? (string) ($selectedArticleMeta['category'] ?? ''))));
if ($selectedThemeSlug === '' || !isset($categoryOptions[$selectedThemeSlug])) {
    $selectedThemeSlug = (string) (array_key_first($categoryOptions) ?? '');
}
$selectedThemeMeta = $selectedThemeSlug !== '' ? (category_meta($selectedThemeSlug) ?? ['title' => '', 'description' => '']) : ['title' => '', 'description' => ''];
$selectedThemePrompt = $selectedThemeSlug !== '' ? interessa_admin_category_image_brief($selectedThemeSlug, 'hero') : [];
$selectedThemeThumbPrompt = $selectedThemeSlug !== '' ? interessa_admin_category_image_brief($selectedThemeSlug, 'thumb') : [];
$selectedThemeDirection = $selectedThemeSlug !== '' ? interessa_admin_category_visual_direction($selectedThemeSlug) : [];
$selectedThemeImage = $selectedThemeSlug !== '' ? interessa_category_image_meta($selectedThemeSlug, 'hero', true) : null;
$selectedThemeThumbImage = $selectedThemeSlug !== '' ? interessa_category_image_meta($selectedThemeSlug, 'thumb', true) : null;
$selectedThemeLocalAsset = $selectedThemeSlug !== '' && function_exists('interessa_category_local_asset')
    ? (interessa_category_local_asset($selectedThemeSlug, 'hero') ?? '')
    : '';
$selectedThemeThumbLocalAsset = $selectedThemeSlug !== '' && function_exists('interessa_category_local_asset')
    ? (interessa_category_local_asset($selectedThemeSlug, 'thumb') ?? '')
    : '';
$selectedThemeImageSource = $selectedThemeLocalAsset !== '' ? 'vlastny obrazok temy' : 'docasny fallback';
$selectedThemeThumbImageSource = $selectedThemeThumbLocalAsset !== '' ? 'vlastny mensi obrazok temy' : 'docasny fallback';
$brandLogoImage = interessa_brand_image_meta('logo-full', true);
$brandIconImage = interessa_brand_image_meta('logo-icon', true);
$brandOgImage = interessa_brand_image_meta('og-default', true);
$brandFaviconImage = interessa_brand_image_meta('favicon-32', false);
$brandAppleTouchImage = interessa_brand_image_meta('apple-touch-icon', false);
$brandFaviconAsset = is_file(dirname(__DIR__) . '/assets/img/brand/favicon-32.png') ? 'img/brand/favicon-32.png' : '';
$brandAppleTouchAsset = is_file(dirname(__DIR__) . '/assets/img/brand/apple-touch-icon.png') ? 'img/brand/apple-touch-icon.png' : '';
$brandPromptLibrary = [
    'logo' => [
        'title' => 'Hlavne logo',
        'note' => 'Vytvor ciste, doveryhodne a lahko citatelne logo pre Interesa.sk. Ma fungovat na svetlom pozadi, bez zbytocnych detailov.',
    ],
    'icon' => [
        'title' => 'Maly symbol a ikonka stranky',
        'note' => 'Vytvor jednoduchy symbol pre Interesa.sk. Musi byt citatelny aj v malom rozmere 32x32 a bez textu.',
    ],
    'og' => [
        'title' => 'Obrazok pri zdielani',
        'note' => 'Vytvor cisty obrazok pre zdielanie webu Interesa.sk. Bez drobneho textu, s jasnym motivom, v profesionalnom a doveryhodnom style.',
    ],
];
$selectedArticleOverride = $selectedArticleSlug !== '' ? interessa_admin_article_content($selectedArticleSlug) : interessa_admin_normalize_article_override('', []);
$articlePrompt = $selectedArticleSlug !== '' ? interessa_hero_prompt_meta($selectedArticleSlug) : [];
$selectedArticleHero = $selectedArticleSlug !== '' ? interessa_article_image_meta($selectedArticleSlug, 'hero', true) : null;
$selectedArticleHeroSource = is_array($selectedArticleHero) ? (string) ($selectedArticleHero['source_type'] ?? 'placeholder') : 'missing';


$catalog = interessa_product_catalog();
$productSlugs = array_keys($catalog);
sort($productSlugs);
$manualProductRequested = trim((string) ($_GET['product'] ?? '')) !== '';
$selectedProductSlug = trim((string) ($_GET['product'] ?? ''));
$selectedProduct = $selectedProductSlug !== '' ? interessa_product($selectedProductSlug) : null;
$selectedProduct = is_array($selectedProduct)
    ? interessa_normalize_product($selectedProduct)
    : ($selectedProductSlug !== '' ? interessa_admin_empty_product_stub($selectedProductSlug) : null);
$selectedProductImage = is_array($selectedProduct) ? ($selectedProduct['image'] ?? null) : null;
$selectedProductImageSource = is_array($selectedProduct) ? (string) ($selectedProduct['image_mode'] ?? 'placeholder') : 'missing';
$selectedProductImageSourceLabel = match ($selectedProductImageSource) {
    'local' => 'hotovy obrazok produktu',
    'remote' => 'nasiel sa obrazok z obchodu',
    'placeholder' => 'docasny obrazok',
    default => 'bez obrazka',
};
$selectedProductLocalAsset = is_array($selectedProduct) ? trim((string) ($selectedProduct['image_local_asset'] ?? '')) : '';
$selectedProductLocalImageUrl = $selectedProductLocalAsset !== '' ? asset($selectedProductLocalAsset) : '';
$selectedProductImageBrief = is_array($selectedProduct) ? interessa_admin_product_image_brief($selectedProduct) : [];
$selectedProductAffiliate = is_array($selectedProduct) && trim((string) ($selectedProduct['affiliate_code'] ?? '')) !== '' ? aff_record((string) $selectedProduct['affiliate_code']) : null;
$selectedProductClickState = is_array($selectedProduct) ? interessa_admin_product_click_state($selectedProduct) : ['ready' => false, 'href' => '', 'product_url' => '', 'affiliate_href' => ''];
$selectedProductAffiliateUrl = trim((string) ($selectedProductClickState['affiliate_href'] ?? ''));
$selectedProductDirectUrl = is_array($selectedProduct) ? trim((string) ($selectedProduct['fallback_url'] ?? '')) : '';
$selectedProductDerivedUrl = trim((string) ($selectedProductClickState['product_url'] ?? ''));
$selectedProductSourceUrl = '';
if ($selectedProductDirectUrl !== '' && interessa_admin_looks_like_product_url($selectedProductDirectUrl)) {
    $selectedProductSourceUrl = $selectedProductDirectUrl;
} elseif ($selectedProductDerivedUrl !== '' && interessa_admin_looks_like_product_url($selectedProductDerivedUrl)) {
    $selectedProductSourceUrl = $selectedProductDerivedUrl;
}
$selectedProductHasUsableSourceUrl = $selectedProductSourceUrl !== '';
$selectedProductAffiliateType = $selectedProductAffiliate !== null ? aff_link_type($selectedProductAffiliate) : '';
$selectedProductTarget = is_array($selectedProduct) ? interessa_affiliate_target($selectedProduct) : ['href' => '', 'label' => ''];
$selectedProductPros = is_array($selectedProduct) && is_array($selectedProduct['pros'] ?? null) ? array_values(array_filter(array_map('strval', $selectedProduct['pros']))) : [];
$selectedProductCons = is_array($selectedProduct) && is_array($selectedProduct['cons'] ?? null) ? array_values(array_filter(array_map('strval', $selectedProduct['cons']))) : [];
$selectedProductSummaryReady = is_array($selectedProduct) && trim((string) ($selectedProduct['summary'] ?? '')) !== '';
$selectedProductRatingReady = is_array($selectedProduct) && trim((string) ($selectedProduct['rating'] ?? '')) !== '';
$selectedProductAffiliateReady = !empty($selectedProductClickState['ready']);
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
$returnArticlePrefill = canonical_article_slug(trim((string) ($_GET['article'] ?? $returnSlugPrefill)));
$returnArticleSlotPrefill = max(0, min(3, (int) ($_GET['slot'] ?? 0)));
$articleSlotMode = $section === 'products' && $returnArticlePrefill !== '' && $returnArticleSlotPrefill > 0;
$articleSlotModeTitle = $articleSlotMode ? (string) ($articleOptions[$returnArticlePrefill]['title'] ?? humanize_slug($returnArticlePrefill)) : '';
$productReturnSection = $articleSlotMode ? 'articles' : ($returnSectionPrefill !== '' ? $returnSectionPrefill : 'products');
$productReturnSlug = $articleSlotMode ? $returnArticlePrefill : $returnSlugPrefill;
$articleSlotBackHref = $articleSlotMode
    ? '/admin?section=articles&slug=' . rawurlencode($returnArticlePrefill) . '#slot-' . rawurlencode((string) $returnArticleSlotPrefill)
    : '';
$prefillNewProductName = trim((string) ($_GET['prefill_product_name'] ?? ''));
$prefillNewProductSlug = trim((string) ($_GET['prefill_product_slug'] ?? ''));
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
$allThemeImageQueue = interessa_admin_category_image_queue($categoryOptions);
$themeAssetManifest = interessa_admin_category_asset_manifest($categoryOptions);
$themeManifestTotal = count($themeAssetManifest);
$themeHeroReadyCount = 0;
$themeThumbReadyCount = 0;
$themeFullyReadyCount = 0;
foreach ($themeAssetManifest as $themeManifestRow) {
    $heroReady = false;
    $thumbReady = false;
    foreach ((array) ($themeManifestRow['items'] ?? []) as $assetItem) {
        $variant = (string) ($assetItem['variant'] ?? '');
        $ready = !empty($assetItem['ready']);
        if ($variant === 'hero') {
            $heroReady = $ready;
        }
        if ($variant === 'thumb') {
            $thumbReady = $ready;
        }
    }
    if ($heroReady) {
        $themeHeroReadyCount++;
    }
    if ($thumbReady) {
        $themeThumbReadyCount++;
    }
    if ($heroReady && $thumbReady) {
        $themeFullyReadyCount++;
    }
}
$themeHeroMissingCount = max(0, $themeManifestTotal - $themeHeroReadyCount);
$themeThumbMissingCount = max(0, $themeManifestTotal - $themeThumbReadyCount);
$imageQueue = interessa_admin_image_queue($articleOptions, $imageFilter, $imageFilter === 'all' ? max(count($articleOptions), 1) : 16);
$productImageQueue = interessa_admin_product_image_queue($catalog, $productImageFilter, $productImageFilter === 'all' ? max(count($catalog), 1) : 16);
$imageQueueCounts = [
    'all' => count($allImageQueue),
    'article' => count(array_filter($allImageQueue, static fn(array $row): bool => ($row['image_state'] ?? 'missing') === 'article')),
    'theme_fallback' => count(array_filter($allImageQueue, static fn(array $row): bool => ($row['image_state'] ?? 'missing') === 'theme-fallback')),
    'missing' => count(array_filter($allImageQueue, static fn(array $row): bool => ($row['image_state'] ?? 'missing') === 'missing')),
    'needs_article' => count(array_filter($allImageQueue, static fn(array $row): bool => ($row['image_state'] ?? 'missing') !== 'article')),
];
$productImageQueueCounts = [
    'all' => count($allProductImageQueue),
    'missing' => count(array_filter($allProductImageQueue, static fn(array $row): bool => !empty($row['needs_local_packshot']))),
    'ready' => count(array_filter($allProductImageQueue, static fn(array $row): bool => empty($row['needs_local_packshot']))),
    'remote' => count(array_filter($allProductImageQueue, static fn(array $row): bool => ($row['image_mode'] ?? '') === 'remote')),
    'placeholder' => count(array_filter($allProductImageQueue, static fn(array $row): bool => ($row['image_mode'] ?? '') === 'placeholder')),
];
$helpPriorityHeroes = array_slice(array_values(array_filter($allImageQueue, static fn(array $row): bool => ($row['image_state'] ?? 'missing') !== 'article')), 0, 5);
$helpPriorityProductImages = array_slice(array_values(array_filter($allProductImageQueue, static fn(array $row): bool => !empty($row['needs_local_packshot']) && trim((string) ($row['remote_src'] ?? '')) !== '')), 0, 5);

$missingHeroSlugs = array_values(array_filter(array_map(static fn(array $row): string => (string) ($row['slug'] ?? ''), array_filter($allImageQueue, static fn(array $row): bool => ($row['image_state'] ?? 'missing') !== 'article'))));
$selectedHeroQueueIndex = array_search($selectedArticleSlug, $missingHeroSlugs, true);
$prevMissingHeroSlug = $selectedHeroQueueIndex !== false && $selectedHeroQueueIndex > 0 ? (string) $missingHeroSlugs[$selectedHeroQueueIndex - 1] : '';
$nextMissingHeroSlug = $selectedHeroQueueIndex !== false && $selectedHeroQueueIndex < count($missingHeroSlugs) - 1 ? (string) $missingHeroSlugs[$selectedHeroQueueIndex + 1] : '';
$selectedHeroQueuePosition = $selectedHeroQueueIndex !== false ? ($selectedHeroQueueIndex + 1) : 0;
$missingThemeSlugs = array_values(array_filter(array_map(static fn(array $row): string => (string) ($row['slug'] ?? ''), array_filter($allThemeImageQueue, static fn(array $row): bool => empty($row['has_local_theme_image'])))));
$firstMissingThemeSlug = $missingThemeSlugs[0] ?? '';
$selectedThemeQueueIndex = array_search($selectedThemeSlug, $missingThemeSlugs, true);
$prevMissingThemeSlug = $selectedThemeQueueIndex !== false && $selectedThemeQueueIndex > 0 ? (string) $missingThemeSlugs[$selectedThemeQueueIndex - 1] : '';
$nextMissingThemeSlug = $selectedThemeQueueIndex !== false && $selectedThemeQueueIndex < count($missingThemeSlugs) - 1 ? (string) $missingThemeSlugs[$selectedThemeQueueIndex + 1] : '';
$selectedThemeQueuePosition = $selectedThemeQueueIndex !== false ? ($selectedThemeQueueIndex + 1) : 0;
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
    'product-link-ready' => 'Link bol ulozeny. Produkt uz ma fungujuci klik a admin pozna aj stranku produktu v obchode.',
    'product-ready' => 'Produkt je pripraveny. Link aj obrazok su hotove.',
    'product-reset' => 'Override produktu bol zmazany.',
    'affiliate' => 'Affiliate zaznam bol ulozeny.',
    'affiliate-created' => 'Novy affiliate zaznam bol vytvoreny.',
    'affiliate-reset' => 'Affiliate override bol zmazany.',
    'hero' => 'Hero obrazok bol nahraty.',
    'theme-image' => 'Obrazok temy bol nahraty.',
    'packshot' => 'Produktovy obrazok bol nahraty.',
    'packshot-mirrored' => 'Remote obrazok bol zrkadleny do lokalneho assetu.',
    'product-enriched' => 'Produkt bol doplneny z referencnej produktovej stranky.',
    'product-autofill' => 'Produkt bol automaticky doplneny a obrazok sa pokusil zrkadlit.',
    'product-remote-ready' => 'Produkt ma najdeny obrazok z e-shopu. Teraz klikni 2. Ulozit obrazok z e-shopu a vznikne lokalny WebP.',
    'candidate-imported' => 'Produkty su nacitane. Dalsi krok je otvorit jeden produkt z posledneho importu.',
    'candidate-click' => 'Odkaz do obchodu je pripraveny. Pokracuj na tom istom produkte.',
    'candidate-assignment' => 'Produkt je pridany k clanku. Pokracuj na tom istom produkte a uloz ho do systemu.',
    'candidate-approved' => 'Produkt je ulozeny v systeme a caka na finalny vyber vo web vlakne.',
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
$selectedArticleProductState = $selectedArticleSlug !== ''
    ? interessa_admin_article_product_state($selectedArticleSlug, $selectedArticleOverride)
    : ['product_plan' => [], 'recommended_products' => [], 'source' => 'article_override'];
$articleProductPlan = is_array($selectedArticleProductState['product_plan'] ?? null)
    ? array_values($selectedArticleProductState['product_plan'])
    : [];
$articleProductPlanHasExplicitSource = !empty($selectedArticleProductState['product_plan']);
$articleEditorProductSlugs = [];
foreach ($articleProductPlan as $planRow) {
    if (!is_array($planRow)) {
        continue;
    }
    $planSlug = interessa_admin_slugify((string) ($planRow['product_slug'] ?? ''));
    if ($planSlug !== '') {
        $articleEditorProductSlugs[] = $planSlug;
    }
}
$articleEditorProductSlugs = array_values(array_unique($articleEditorProductSlugs));
$articleProductPlanMap = [];
$articleSlotSelections = [
    1 => '',
    2 => '',
    3 => '',
];
foreach ($articleProductPlan as $planRow) {
    if (!is_array($planRow)) {
        continue;
    }
    $planSlug = interessa_admin_slugify((string) ($planRow['product_slug'] ?? ''));
    if ($planSlug === '') {
        continue;
    }
    $articleProductPlanMap[$planSlug] = [
        'order' => max(1, (int) ($planRow['order'] ?? 1)),
        'role' => interessa_admin_slugify((string) ($planRow['role'] ?? 'standard')) ?: 'standard',
        'show_in_top' => !empty($planRow['show_in_top']),
        'show_in_comparison' => !empty($planRow['show_in_comparison']),
    ];
    $planOrder = max(1, min(3, (int) ($planRow['order'] ?? 1)));
    if ($articleSlotSelections[$planOrder] === '') {
        $articleSlotSelections[$planOrder] = $planSlug;
    }
}
$articleEditorInjectedProduct = trim((string) ($_GET['add_product'] ?? ''));
if ($articleEditorInjectedProduct !== '' && interessa_product($articleEditorInjectedProduct) !== null && !in_array($articleEditorInjectedProduct, $articleEditorProductSlugs, true)) {
    $articleEditorProductSlugs[] = $articleEditorInjectedProduct;
}
$articleEditorProductSlugs = array_values(array_slice(array_unique(array_map('strval', $articleEditorProductSlugs)), 0, 3));
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
$articleSelectedProductSlugs = array_values(array_filter($articleSlotSelections, static fn(string $slug): bool => $slug !== ''));
$articleSelectedSlotBySlug = [];
foreach ($articleSlotSelections as $slotIndex => $slotSlug) {
    $slotSlug = interessa_admin_slugify((string) $slotSlug);
    if ($slotSlug !== '') {
        $articleSelectedSlotBySlug[$slotSlug] = (int) $slotIndex;
    }
}
$articleSelectedCount = count($articleSelectedProductSlugs);
$articleSelectedImageMissingCount = 0;
$articleSelectedClickMissingCount = 0;
$articleSelectedReadyCount = 0;
$articleSelectedMissingProductCount = 0;
$articleSelectedActionRows = [];
foreach ($articleSelectedProductSlugs as $articleSelectedSlug) {
    $articleSelectedRow = $recommendedDiagnosticsBySlug[$articleSelectedSlug] ?? [];
    $articleSelectedExists = !empty($articleSelectedRow['exists']) || interessa_product($articleSelectedSlug) !== null;
    $articleSelectedPackshotReady = !empty($articleSelectedRow['packshot_ready']);
    $articleSelectedAffiliateReady = !empty($articleSelectedRow['affiliate_ready']);
    if (!$articleSelectedExists) {
        $articleSelectedMissingProductCount++;
    }
    if (!$articleSelectedPackshotReady) {
        $articleSelectedImageMissingCount++;
    }
    if (!$articleSelectedAffiliateReady) {
        $articleSelectedClickMissingCount++;
    }
    if ($articleSelectedExists && $articleSelectedPackshotReady && $articleSelectedAffiliateReady) {
        $articleSelectedReadyCount++;
    }
      $articleSelectedActionSlot = (int) ($articleSelectedSlotBySlug[$articleSelectedSlug] ?? 0);
      $articleSelectedActionHref = '/admin?section=products&product=' . rawurlencode($articleSelectedSlug)
          . ($articleSelectedActionSlot > 0 ? '&article=' . rawurlencode($selectedArticleSlug) . '&slot=' . rawurlencode((string) $articleSelectedActionSlot) : '')
          . '&return_section=articles&return_slug=' . rawurlencode($selectedArticleSlug) . '&focus=product_edit#product-edit-form';
      $articleSelectedActionLabel = 'Doplnit produkt';
      $articleSelectedActionNote = 'Tomuto produktu este chyba doplnenie.';
      if ($articleSelectedExists && $articleSelectedPackshotReady && !$articleSelectedAffiliateReady) {
          $articleSelectedCode = trim((string) ($articleSelectedRow['affiliate_code'] ?? ''));
          if ($articleSelectedCode !== '') {
              $articleSelectedActionHref = '/dognet-helper?code=' . rawurlencode($articleSelectedCode);
          } else {
              $articleSelectedActionHref = '/admin?section=affiliates&prefill_code=' . rawurlencode($articleSelectedSlug) . '&prefill_merchant=' . rawurlencode((string) ($articleSelectedRow['merchant'] ?? '')) . '&prefill_merchant_slug=' . rawurlencode(interessa_admin_slugify((string) ($articleSelectedRow['merchant'] ?? ''))) . '&prefill_product_slug=' . rawurlencode($articleSelectedSlug) . '&return_section=articles&return_slug=' . rawurlencode($selectedArticleSlug);
          }
          $articleSelectedActionLabel = 'Doplnit odkaz';
          $articleSelectedActionNote = 'Obrazok je hotovy. Chyba uz len klik do obchodu.';
      } elseif ($articleSelectedExists && !$articleSelectedPackshotReady) {
        $articleSelectedActionLabel = 'Doplnit obrazok';
        $articleSelectedActionNote = 'Produkt uz existuje. Treba este doplnit obrazok.';
    } elseif ($articleSelectedExists && $articleSelectedPackshotReady && $articleSelectedAffiliateReady) {
        $articleSelectedActionHref = '/admin?section=affiliates&code=' . rawurlencode((string) ($articleSelectedRow['affiliate_code'] ?? '')) . '&return_section=articles&return_slug=' . rawurlencode($selectedArticleSlug);
        $articleSelectedActionLabel = 'Hotovo';
        $articleSelectedActionNote = 'Tento produkt je pripraveny.';
    }
    $articleSelectedActionRows[] = [
        'slug' => $articleSelectedSlug,
        'name' => (string) ($articleSelectedRow['name'] ?? ($catalog[$articleSelectedSlug]['name'] ?? $articleSelectedSlug)),
        'exists' => $articleSelectedExists,
        'packshot_ready' => $articleSelectedPackshotReady,
        'affiliate_ready' => $articleSelectedAffiliateReady,
        'next_href' => $articleSelectedActionHref,
        'next_label' => $articleSelectedActionLabel,
        'next_note' => $articleSelectedActionNote,
    ];
}
$articleSelectedActionRowsBySlug = [];
foreach ($articleSelectedActionRows as $articleSelectedActionRow) {
    $actionSlug = trim((string) ($articleSelectedActionRow['slug'] ?? ''));
    if ($actionSlug !== '') {
        $articleSelectedActionRowsBySlug[$actionSlug] = $articleSelectedActionRow;
    }
}
$articleScopedProductOptionSlugs = [];
$articleScopedProductOptionSources = [];
$articleAddScopedProductOption = static function (string $slug, string $source) use (&$articleScopedProductOptionSlugs, &$articleScopedProductOptionSources, $catalog): void {
    $slug = interessa_admin_slugify($slug);
    if ($slug === '' || !isset($catalog[$slug])) {
        return;
    }

    $articleScopedProductOptionSlugs[] = $slug;
    if (!isset($articleScopedProductOptionSources[$slug])) {
        $articleScopedProductOptionSources[$slug] = [];
    }
    $articleScopedProductOptionSources[$slug][$source] = true;
};
foreach ($articleSelectedProductSlugs as $selectedSlug) {
    $articleAddScopedProductOption((string) $selectedSlug, 'selected');
}
foreach (interessa_admin_product_candidates() as $candidateRow) {
    if (!is_array($candidateRow)) {
        continue;
    }
    $candidateTargetArticleSlug = canonical_article_slug((string) ($candidateRow['target_article_slug'] ?? $candidateRow['article_slug'] ?? ''));
    if ($candidateTargetArticleSlug !== $selectedArticleSlug) {
        continue;
    }
    $candidateProductSlug = interessa_admin_slugify((string) ($candidateRow['product_slug'] ?? $candidateRow['slug'] ?? ''));
    $articleAddScopedProductOption($candidateProductSlug, 'candidate');
}
$articleSlotRelevancePreset = interessa_admin_article_slot_relevance_preset($selectedArticleSlug);
$articleSlotPreferredCategories = array_values(array_filter(array_map('strval', (array) ($articleSlotRelevancePreset['preferred_categories'] ?? []))));
$articleSlotStrictFilter = !empty($articleSlotRelevancePreset['strict_filter']);
if (count($articleScopedProductOptionSlugs) < 12) {
    foreach ($catalog as $catalogSlug => $catalogRow) {
        $catalogSlug = (string) $catalogSlug;
        $normalizedCatalogRow = interessa_normalize_product(is_array($catalogRow) ? $catalogRow : []);
        $relevance = interessa_admin_article_slot_product_relevance($normalizedCatalogRow, $selectedArticleSlug);
        if ($articleSlotStrictFilter) {
            if (!empty($relevance['passes_strict'])) {
                $articleAddScopedProductOption($catalogSlug, 'relevant');
            }
        } elseif (($relevance['score'] ?? 0) > 0) {
            $articleAddScopedProductOption($catalogSlug, 'relevant');
        } elseif ($articleSlotPreferredCategories !== []) {
            $catalogCategory = normalize_category_slug((string) ($normalizedCatalogRow['category'] ?? ''));
            if ($catalogCategory !== '' && in_array($catalogCategory, $articleSlotPreferredCategories, true)) {
                $articleAddScopedProductOption($catalogSlug, 'category');
            }
        }

        if (count(array_unique($articleScopedProductOptionSlugs)) >= 18) {
            break;
        }
    }
}
if ($articleScopedProductOptionSlugs === [] && !$articleSlotStrictFilter) {
    foreach (array_slice(array_keys($catalog), 0, 12) as $fallbackSlug) {
        $articleAddScopedProductOption((string) $fallbackSlug, 'fallback');
    }
}
$articleScopedProductOptionSlugs = array_values(array_unique(array_map('strval', $articleScopedProductOptionSlugs)));
$articleScopedProductOptions = [];
foreach ($articleScopedProductOptionSlugs as $optionSlug) {
    if (!isset($catalog[$optionSlug])) {
        continue;
    }
    $optionProduct = interessa_normalize_product(is_array($catalog[$optionSlug]) ? $catalog[$optionSlug] : []);
    $optionAffiliateCode = trim((string) ($optionProduct['affiliate_code'] ?? ''));
    $optionPackshotReady = !empty($optionProduct['has_local_image']);
    $optionClickState = interessa_admin_product_click_state($optionProduct);
    $optionAffiliateReady = !empty($optionClickState['ready']);
    $optionRelevance = interessa_admin_article_slot_product_relevance($optionProduct, $selectedArticleSlug);
    $optionSources = $articleScopedProductOptionSources[$optionSlug] ?? [];
    $optionRank = (int) ($optionRelevance['score'] ?? 0);
    if (!empty($optionSources['selected'])) {
        $optionRank += 1000;
    }
    if (!empty($optionSources['candidate'])) {
        $optionRank += 320;
    }
    if (!empty($optionSources['relevant'])) {
        $optionRank += 80;
    }
    if ($optionPackshotReady) {
        $optionRank += 12;
    }
    if ($optionAffiliateReady) {
        $optionRank += 12;
    }
    if ($optionPackshotReady && $optionAffiliateReady) {
        $optionStateLabel = 'Hotovo';
        $optionStateClass = ' is-good';
    } elseif ($optionPackshotReady && !$optionAffiliateReady) {
        $optionStateLabel = 'Chyba konkretny produktovy odkaz';
        $optionStateClass = ' is-warning';
    } elseif (!$optionPackshotReady && $optionAffiliateReady) {
        $optionStateLabel = 'Chyba obrazok';
        $optionStateClass = ' is-warning';
    } else {
        $optionStateLabel = 'Chyba obrazok a konkretny produktovy odkaz';
        $optionStateClass = ' is-warning';
    }
    $optionNextHref = '/admin?section=products&product=' . rawurlencode((string) $optionSlug) . '&return_section=articles&return_slug=' . rawurlencode($selectedArticleSlug) . '&focus=product_edit#product-edit-form';
    $optionNextLabel = 'Doplnit produkt';
    if ($optionPackshotReady && !$optionAffiliateReady) {
        if ($optionAffiliateCode !== '') {
            $optionNextHref = '/dognet-helper?code=' . rawurlencode($optionAffiliateCode);
        } else {
            $optionNextHref = '/admin?section=affiliates&prefill_code=' . rawurlencode((string) $optionSlug) . '&prefill_merchant=' . rawurlencode((string) ($optionProduct['merchant'] ?? '')) . '&prefill_merchant_slug=' . rawurlencode(interessa_admin_slugify((string) ($optionProduct['merchant'] ?? ''))) . '&prefill_product_slug=' . rawurlencode((string) $optionSlug) . '&return_section=articles&return_slug=' . rawurlencode($selectedArticleSlug);
        }
        $optionNextLabel = 'Doplnit odkaz';
    } elseif ($optionPackshotReady && $optionAffiliateReady) {
        $optionNextLabel = 'Hotovo';
    }
    $articleScopedProductOptions[$optionSlug] = [
        'slug' => (string) $optionSlug,
        'name' => (string) ($catalog[$optionSlug]['name'] ?? $optionSlug),
        'state_label' => $optionStateLabel,
        'state_class' => $optionStateClass,
        'next_href' => $optionNextHref,
        'next_label' => $optionNextLabel,
        'rank' => $optionRank,
        'role' => (string) ($articleProductPlanMap[$optionSlug]['role'] ?? 'standard'),
        'placement' => !empty($articleProductPlanMap[$optionSlug]['show_in_top']) && !empty($articleProductPlanMap[$optionSlug]['show_in_comparison'])
            ? 'both'
            : (!empty($articleProductPlanMap[$optionSlug]['show_in_top'])
                ? 'recommended'
                : (!empty($articleProductPlanMap[$optionSlug]['show_in_comparison']) ? 'comparison' : 'hidden')),
    ];
}
uasort($articleScopedProductOptions, static function (array $left, array $right): int {
    $rankCompare = ((int) ($right['rank'] ?? 0)) <=> ((int) ($left['rank'] ?? 0));
    if ($rankCompare !== 0) {
        return $rankCompare;
    }

    return strcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
});
$productArticleOptionSlugs = $phaseOneArticleSlugs !== []
    ? $phaseOneArticleSlugs
    : ($priorityArticleSlugs !== [] ? $priorityArticleSlugs : array_keys($articleOptions));
$selectedProductArticleSlug = canonical_article_slug(trim((string) ($_GET['article_product_slug'] ?? ($selectedArticleSlug !== '' ? $selectedArticleSlug : ($productArticleOptionSlugs[0] ?? '')))));
if ($selectedProductArticleSlug === '' || !in_array($selectedProductArticleSlug, $productArticleOptionSlugs, true)) {
    $selectedProductArticleSlug = (string) ($productArticleOptionSlugs[0] ?? '');
}
$selectedProductArticleHelp = interessa_admin_article_product_help($selectedProductArticleSlug);
$selectedProductArticleOverride = $selectedProductArticleSlug !== '' ? interessa_admin_article_content($selectedProductArticleSlug) : interessa_admin_normalize_article_override('', []);
$selectedProductArticleState = $selectedProductArticleSlug !== ''
    ? interessa_admin_article_product_state($selectedProductArticleSlug, $selectedProductArticleOverride)
    : ['product_plan' => [], 'recommended_products' => [], 'source' => 'article_override'];
$productPageArticleProductPlan = is_array($selectedProductArticleState['product_plan'] ?? null)
    ? array_values($selectedProductArticleState['product_plan'])
    : [];
$productPageHasExplicitPlan = !empty($selectedProductArticleState['product_plan']);
$productPageArticleProductSlugs = [];
foreach ($productPageArticleProductPlan as $planRow) {
    if (!is_array($planRow)) {
        continue;
    }
    $planSlug = interessa_admin_slugify((string) ($planRow['product_slug'] ?? ''));
    if ($planSlug !== '') {
        $productPageArticleProductSlugs[] = $planSlug;
    }
}
$productPageArticleProductSlugs = array_values(array_unique($productPageArticleProductSlugs));
$productPageArticlePlanMap = [];
foreach ($productPageArticleProductPlan as $planRow) {
    if (!is_array($planRow)) {
        continue;
    }
    $planSlug = interessa_admin_slugify((string) ($planRow['product_slug'] ?? ''));
    if ($planSlug === '') {
        continue;
    }
    $productPageArticlePlanMap[$planSlug] = [
        'order' => max(1, (int) ($planRow['order'] ?? 99)),
        'role' => interessa_admin_slugify((string) ($planRow['role'] ?? 'standard')) ?: 'standard',
        'show_in_top' => !empty($planRow['show_in_top']),
        'show_in_comparison' => !empty($planRow['show_in_comparison']),
    ];
}
$productPageSelectedSlugs = array_values(array_unique(array_merge(array_keys($productPageArticlePlanMap), $productPageArticleProductSlugs)));
$productPageDiagnostics = interessa_admin_recommended_diagnostics($productPageSelectedSlugs);
$productPageDiagnosticsRows = is_array($productPageDiagnostics['rows'] ?? null) ? $productPageDiagnostics['rows'] : [];
$productPageDiagnosticsBySlug = [];
foreach ($productPageDiagnosticsRows as $diagnosticRow) {
    $diagnosticSlug = trim((string) ($diagnosticRow['slug'] ?? ''));
    if ($diagnosticSlug !== '') {
        $productPageDiagnosticsBySlug[$diagnosticSlug] = $diagnosticRow;
    }
}
$productPageReadyCount = 0;
$productPageMissingImageCount = 0;
$productPageMissingClickCount = 0;
$productPageActionRows = [];
foreach ($productPageSelectedSlugs as $productPageSlug) {
    $productPageRow = $productPageDiagnosticsBySlug[$productPageSlug] ?? [];
    $productPageExists = !empty($productPageRow['exists']) || interessa_product($productPageSlug) !== null;
    $productPagePackshotReady = !empty($productPageRow['packshot_ready']);
    $productPageAffiliateReady = !empty($productPageRow['affiliate_ready']);
    $productPageAffiliateCode = trim((string) ($productPageRow['affiliate_code'] ?? ''));
    if (!$productPagePackshotReady) {
        $productPageMissingImageCount++;
    }
    if (!$productPageAffiliateReady) {
        $productPageMissingClickCount++;
    }
    if ($productPageExists && $productPagePackshotReady && $productPageAffiliateReady) {
        $productPageReadyCount++;
    }
    $productPageActionHref = '/admin?section=products&product=' . rawurlencode($productPageSlug) . '&article_product_slug=' . rawurlencode($selectedProductArticleSlug) . '#product-main-flow';
    $productPageActionLabel = 'Doplnit produkt';
    $productPageActionNote = 'Tomuto produktu este nieco chyba.';
    if ($productPageExists && !$productPagePackshotReady) {
        $productPageActionLabel = 'Doplnit obrazok';
        $productPageActionNote = 'Produkt uz existuje. Chyba mu obrazok.';
    } elseif ($productPageExists && $productPagePackshotReady && !$productPageAffiliateReady) {
        if ($productPageAffiliateCode !== '') {
            $productPageActionHref = '/dognet-helper?code=' . rawurlencode($productPageAffiliateCode);
        }
        $productPageActionLabel = 'Doplnit odkaz';
        $productPageActionNote = 'Obrazok je hotovy. Chyba uz len klik do obchodu.';
    } elseif ($productPageExists && $productPagePackshotReady && $productPageAffiliateReady) {
        $productPageActionHref = '/admin?section=products&product=' . rawurlencode($productPageSlug) . '&article_product_slug=' . rawurlencode($selectedProductArticleSlug) . '#product-main-flow';
        $productPageActionLabel = 'Hotovo';
        $productPageActionNote = 'Tento produkt je pripraveny.';
    }
    $productPageActionRows[] = [
        'slug' => $productPageSlug,
        'name' => (string) ($productPageRow['name'] ?? ($catalog[$productPageSlug]['name'] ?? $productPageSlug)),
        'exists' => $productPageExists,
        'packshot_ready' => $productPagePackshotReady,
        'affiliate_ready' => $productPageAffiliateReady,
        'next_href' => $productPageActionHref,
        'next_label' => $productPageActionLabel,
        'next_note' => $productPageActionNote,
    ];
}
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

$candidateMerchantOptions = [
    'gymbeam' => 'GymBeam.sk',
    'protein-sk' => 'Protein.sk',
    'ironaesthetics' => 'IronAesthetics.sk',
    'symprove' => 'Symprove.sk',
    'imunoklub' => 'Imunoklub.sk',
    'kloubus' => 'Kloubus.sk',
];
$allCandidateRowsById = interessa_admin_product_candidates();
uasort($allCandidateRowsById, static function (array $a, array $b): int {
    return strcmp((string) ($b['updated_at'] ?? ''), (string) ($a['updated_at'] ?? ''));
});
$requestedCandidateId = interessa_admin_slugify((string) ($_GET['candidate'] ?? ''));
$requestedCandidateRow = $requestedCandidateId !== '' && isset($allCandidateRowsById[$requestedCandidateId])
    ? $allCandidateRowsById[$requestedCandidateId]
    : null;
$candidateImportArticleSlug = canonical_article_slug(trim((string) ($_GET['import_article'] ?? '')));
if ($candidateImportArticleSlug === '' && is_array($requestedCandidateRow)) {
    $candidateImportArticleSlug = canonical_article_slug((string) ($requestedCandidateRow['target_article_slug'] ?? ''));
}
if ($candidateImportArticleSlug === '' && $selectedArticleSlug !== '' && isset($articleOptions[$selectedArticleSlug])) {
    $candidateImportArticleSlug = $selectedArticleSlug;
}
if ($candidateImportArticleSlug === '' || !isset($articleOptions[$candidateImportArticleSlug])) {
    $candidateImportArticleSlug = (string) (array_key_first($articleOptions) ?? 'najlepsie-proteiny-2026');
}
$candidateImportPreset = interessa_admin_candidate_import_preset($candidateImportArticleSlug);
$candidateImportArticleTitle = (string) ($articleOptions[$candidateImportArticleSlug]['title'] ?? $candidateImportArticleSlug);
$candidateImportMerchantOptions = [];
foreach ((array) ($candidateImportPreset['merchant_defaults'] ?? []) as $presetMerchantSlug) {
    $presetMerchantSlug = interessa_admin_slugify((string) $presetMerchantSlug);
    if ($presetMerchantSlug !== '' && isset($candidateMerchantOptions[$presetMerchantSlug])) {
        $candidateImportMerchantOptions[$presetMerchantSlug] = $candidateMerchantOptions[$presetMerchantSlug];
    }
}
if ($candidateImportMerchantOptions === []) {
    $candidateImportMerchantOptions = $candidateMerchantOptions;
}
if ($candidateImportMerchantOptions === []) {
    $candidateImportMerchantOptions = [
        'gymbeam' => 'GymBeam.sk',
    ];
}
$candidateImportSingleMerchantSlug = (string) (array_key_first($candidateImportMerchantOptions) ?? '');
$candidateImportSingleMerchantName = (string) ($candidateImportMerchantOptions[$candidateImportSingleMerchantSlug] ?? $candidateImportSingleMerchantSlug);
$candidateImportShowMerchantSelect = count($candidateImportMerchantOptions) > 1;
$candidateRowsById = [];
foreach ($allCandidateRowsById as $candidateRowId => $candidateRowValue) {
    $candidateRowTargetArticleSlug = canonical_article_slug((string) ($candidateRowValue['target_article_slug'] ?? ''));
    if ($candidateRowTargetArticleSlug !== $candidateImportArticleSlug) {
        continue;
    }
    $candidateRowsById[$candidateRowId] = $candidateRowValue;
}
$candidateRows = array_values($candidateRowsById);
$recentCandidateBatchId = interessa_admin_slugify((string) ($_GET['batch'] ?? ''));
if ($recentCandidateBatchId === '' && $candidateRows !== []) {
    $recentCandidateBatchId = interessa_admin_slugify((string) ($candidateRows[0]['batch_id'] ?? ''));
}
$selectedCandidateId = $requestedCandidateId;
if ($selectedCandidateId !== '' && !isset($candidateRowsById[$selectedCandidateId])) {
    $selectedCandidateId = '';
}
$selectedCandidate = $selectedCandidateId !== '' && isset($candidateRowsById[$selectedCandidateId])
    ? $candidateRowsById[$selectedCandidateId]
    : null;
$candidateFocusRequested = trim((string) ($_GET['candidate'] ?? '')) !== '';
$productCandidateFocusMode = $section === 'products' && $candidateFocusRequested && is_array($selectedCandidate);
$candidateImportedCount = count($candidateRows);
$candidateClickReadyCount = 0;
$candidateAssignedCount = 0;
$candidateApprovedCount = 0;
$candidateImageKnownCount = 0;
$candidateListRows = [];
foreach ($candidateRows as $candidateRow) {
    $candidateId = trim((string) ($candidateRow['id'] ?? ''));
    $candidateHasClick = trim((string) ($candidateRow['click_code'] ?? '')) !== '';
    $candidateHasArticle = trim((string) ($candidateRow['article_slug'] ?? '')) !== '';
    $candidateApproved = !empty($candidateRow['approved']);
    $candidateHasImage = trim((string) ($candidateRow['image_remote_src'] ?? '')) !== '';
    $candidateClickStatusValue = trim((string) ($candidateRow['click_status'] ?? 'missing'));
    $candidateProductStatusValue = trim((string) ($candidateRow['product_status'] ?? ''));
    $candidateAffiliateStatusValue = trim((string) ($candidateRow['affiliate_status'] ?? 'missing'));
    $candidateFit = interessa_admin_candidate_phase_one_fit($candidateRow);
    $candidateFitSlug = canonical_article_slug((string) ($candidateFit['slug'] ?? ''));
    $candidateAllowedForPilot = $candidateFitSlug === $candidateImportArticleSlug;
    if ($candidateHasClick) {
        $candidateClickReadyCount++;
    }
    if ($candidateHasArticle) {
        $candidateAssignedCount++;
    }
    if ($candidateApproved) {
        $candidateApprovedCount++;
    }
    if ($candidateHasImage) {
        $candidateImageKnownCount++;
    }
    $candidateSummaryLabel = 'Missing click';
    $candidateSummaryTone = 'is-warning';
    if ($candidateClickStatusValue === 'ready' && !$candidateHasArticle) {
        $candidateSummaryLabel = 'Missing article';
    } elseif ($candidateClickStatusValue === 'ready' && $candidateHasArticle && $candidateAffiliateStatusValue === 'missing') {
        $candidateSummaryLabel = 'Missing affiliate';
    } elseif ($candidateClickStatusValue === 'ready' && $candidateHasArticle && $candidateAffiliateStatusValue !== 'missing' && $candidateApproved) {
        $candidateSummaryLabel = 'Ready for article';
        $candidateSummaryTone = 'is-good';
    } elseif ($candidateClickStatusValue === 'ready' && $candidateHasArticle) {
        $candidateSummaryLabel = 'Ready to approve';
    }
    $candidateNextLabel = 'Krok 2: Pripravit klik';
    if (!$candidateHasClick && trim((string) ($candidateRow['url'] ?? '')) === '') {
        $candidateNextLabel = 'Krok 1: Chyba link produktu';
    } elseif ($candidateHasClick && !$candidateHasArticle) {
        $candidateNextLabel = 'Krok 3: Priradit ku clanku';
    } elseif ($candidateHasClick && $candidateHasArticle && !$candidateApproved) {
        $candidateNextLabel = 'Krok 4: Schvalit pre web';
    } elseif ($candidateApproved) {
        $candidateNextLabel = 'Hotovo';
    }
    $candidateListRows[] = [
        'id' => $candidateId,
        'name' => (string) ($candidateRow['name'] ?? $candidateId),
        'merchant' => (string) ($candidateRow['merchant'] ?? ''),
        'batch_id' => (string) ($candidateRow['batch_id'] ?? ''),
        'category' => (string) ($candidateRow['category'] ?? ''),
        'price' => (string) ($candidateRow['price'] ?? ''),
        'has_click' => $candidateHasClick,
        'has_article' => $candidateHasArticle,
        'approved' => $candidateApproved,
        'has_image' => $candidateHasImage,
        'next_label' => $candidateNextLabel,
        'fit_status' => $candidateAllowedForPilot ? 'allowed' : 'blocked',
        'fit_reason' => (string) ($candidateFit['reason'] ?? ''),
        'approved_label' => $candidateApproved ? 'Schvaleny' : 'Neschvaleny',
        'approved_tone' => $candidateApproved ? 'is-good' : 'is-warning',
        'click_status' => $candidateClickStatusValue === 'ready' ? 'Click: ready' : 'Click: missing',
        'click_status_tone' => $candidateClickStatusValue === 'ready' ? 'is-good' : 'is-warning',
        'product_status' => $candidateProductStatusValue !== '' ? ('Produkt: ' . $candidateProductStatusValue) : 'Produkt: caka',
        'product_status_tone' => $candidateProductStatusValue !== '' ? 'is-good' : 'is-warning',
        'affiliate_status' => 'Affiliate: ' . ($candidateAffiliateStatusValue !== '' ? $candidateAffiliateStatusValue : 'missing'),
        'affiliate_status_tone' => in_array($candidateAffiliateStatusValue, ['created', 'updated'], true) ? 'is-good' : 'is-warning',
        'summary_label' => $candidateSummaryLabel,
        'summary_tone' => $candidateSummaryTone,
        'is_ready_for_article' => $candidateSummaryLabel === 'Ready for article',
    ];
}

$recentImportedRows = [];
if ($recentCandidateBatchId !== '') {
    foreach ($candidateListRows as $candidateListRow) {
        if (interessa_admin_slugify((string) ($candidateListRow['batch_id'] ?? '')) === $recentCandidateBatchId) {
            $recentImportedRows[] = $candidateListRow;
        }
    }
}
$recentImportedPilotRows = array_values(array_filter($recentImportedRows, static function (array $row): bool {
    return (string) ($row['fit_status'] ?? 'blocked') === 'allowed';
}));
$recentImportedReadyCount = count(array_filter($recentImportedPilotRows, static function (array $row): bool {
    return !empty($row['is_ready_for_article']);
}));
$recentImportedVisibleRows = $recentImportedPilotRows;
$firstRecentImportedVisibleRow = $recentImportedVisibleRows[0] ?? null;
$recentImportedBlockedRows = array_values(array_filter($recentImportedRows, static function (array $row): bool {
    return (string) ($row['fit_status'] ?? 'allowed') !== 'allowed';
}));
$candidateSelectorRows = $recentImportedVisibleRows !== [] ? $recentImportedVisibleRows : ($recentImportedRows !== [] ? $recentImportedRows : $candidateListRows);
$candidateImportedDisplayCount = $candidateImportedCount;
$candidateClickReadyDisplayCount = $candidateClickReadyCount;
$candidateAssignedDisplayCount = $candidateAssignedCount;
$candidateApprovedDisplayCount = $candidateApprovedCount;
if ($recentImportedVisibleRows !== []) {
    $candidateImportedDisplayCount = count($recentImportedVisibleRows);
    $candidateClickReadyDisplayCount = 0;
    $candidateAssignedDisplayCount = 0;
    $candidateApprovedDisplayCount = 0;
    foreach ($recentImportedVisibleRows as $recentImportedRow) {
        if (!empty($recentImportedRow['has_click'])) {
            $candidateClickReadyDisplayCount++;
        }
        if (!empty($recentImportedRow['has_article'])) {
            $candidateAssignedDisplayCount++;
        }
        if (!empty($recentImportedRow['approved'])) {
            $candidateApprovedDisplayCount++;
        }
    }
}

$selectedCandidateHasClick = is_array($selectedCandidate) && trim((string) ($selectedCandidate['click_code'] ?? '')) !== '';
$selectedCandidateHasArticle = is_array($selectedCandidate) && trim((string) ($selectedCandidate['article_slug'] ?? '')) !== '';
$selectedCandidateApproved = is_array($selectedCandidate) && !empty($selectedCandidate['approved']);
$selectedCandidateHasImage = is_array($selectedCandidate) && trim((string) ($selectedCandidate['image_remote_src'] ?? '')) !== '';
$selectedCandidateClickStatus = !$selectedCandidateHasClick
    ? 'missing'
    : (((string) ($selectedCandidate['click_status'] ?? '') === 'ready') ? 'ready' : 'missing');
$selectedCandidateClickStatusLabel = $selectedCandidateClickStatus;
$selectedCandidateAffiliateStatus = is_array($selectedCandidate)
    ? trim((string) ($selectedCandidate['affiliate_status'] ?? 'missing'))
    : 'missing';
$selectedCandidateSummaryLabel = 'Missing click';
$selectedCandidateSummaryTone = 'is-warning';
if ($selectedCandidateClickStatus === 'ready' && !$selectedCandidateHasArticle) {
    $selectedCandidateSummaryLabel = 'Missing article';
} elseif ($selectedCandidateClickStatus === 'ready' && $selectedCandidateHasArticle && $selectedCandidateAffiliateStatus === 'missing') {
    $selectedCandidateSummaryLabel = 'Missing affiliate';
} elseif ($selectedCandidateClickStatus === 'ready' && $selectedCandidateHasArticle && $selectedCandidateAffiliateStatus !== 'missing' && $selectedCandidateApproved) {
    $selectedCandidateSummaryLabel = 'Ready for article';
    $selectedCandidateSummaryTone = 'is-good';
} elseif ($selectedCandidateClickStatus === 'ready' && $selectedCandidateHasArticle) {
    $selectedCandidateSummaryLabel = 'Ready to approve';
}
$selectedCandidateArticleFit = is_array($selectedCandidate)
    ? interessa_admin_candidate_phase_one_fit($selectedCandidate)
    : ['status' => 'no-fit', 'slug' => '', 'reason' => '', 'hits' => [], 'blocked' => []];
$selectedCandidateTargetArticleSlug = is_array($selectedCandidate)
    ? canonical_article_slug(trim((string) ($selectedCandidate['target_article_slug'] ?? '')))
    : '';
$selectedCandidateArticleSlug = is_array($selectedCandidate) ? canonical_article_slug(trim((string) ($selectedCandidate['article_slug'] ?? ''))) : '';
if ($selectedCandidateArticleSlug !== '' && !in_array($selectedCandidateArticleSlug, $productArticleOptionSlugs, true)) {
    $selectedCandidateArticleSlug = '';
}
if ($selectedCandidateArticleSlug === '') {
    if ($selectedCandidateTargetArticleSlug !== '' && in_array($selectedCandidateTargetArticleSlug, $productArticleOptionSlugs, true)) {
        $selectedCandidateArticleSlug = $selectedCandidateTargetArticleSlug;
    }
}
if ($selectedCandidateArticleSlug === '') {
    $guessedArticleSlug = canonical_article_slug((string) ($selectedCandidateArticleFit['slug'] ?? ''));
    if (($selectedCandidateArticleFit['status'] ?? 'no-fit') === 'fit' && $guessedArticleSlug !== '' && in_array($guessedArticleSlug, $productArticleOptionSlugs, true)) {
        $selectedCandidateArticleSlug = $guessedArticleSlug;
    }
}
$selectedCandidateArticleHelp = interessa_admin_article_product_help($selectedCandidateArticleSlug);
$candidateRoleOptions = ['standard', 'vegan', 'clean'];
$selectedCandidateRole = is_array($selectedCandidate)
    ? interessa_admin_slugify((string) ($selectedCandidate['role'] ?? 'standard'))
    : 'standard';
if (!in_array($selectedCandidateRole, $candidateRoleOptions, true)) {
    $selectedCandidateRole = 'standard';
}
$selectedCandidateOrderRaw = is_array($selectedCandidate)
    ? (int) ($selectedCandidate['order'] ?? 0)
    : 0;
$selectedCandidateOrder = 10;
if ($selectedCandidateHasArticle) {
    $selectedCandidateOrder = max(1, $selectedCandidateOrderRaw);
} elseif ($selectedCandidateOrderRaw >= 10 && $selectedCandidateOrderRaw % 10 === 0) {
    $selectedCandidateOrder = $selectedCandidateOrderRaw;
}
if ($selectedCandidateOrder <= 0) {
    $selectedCandidateOrder = 10;
}
$selectedCandidateSuggestedArticleTitle = $selectedCandidateArticleSlug !== ''
    ? (string) ($articleOptions[$selectedCandidateArticleSlug]['title'] ?? $selectedCandidateArticleSlug)
    : '';
$selectedCandidateCanUseSimpleAssignment = $selectedCandidateHasClick
    && !$selectedCandidateHasArticle
    && $selectedCandidateArticleSlug !== ''
    && ($selectedCandidateArticleFit['status'] ?? 'no-fit') === 'fit';


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
      <?php if (!$productsPilotMode && !$productCandidateFocusMode && !$brandFocusMode): ?>
      <aside class="admin-sidebar">
        <div class="admin-sidebar-head">
          <div>
            <h1>Admin</h1>
            <p class="admin-meta">Jednoducha sprava clankov, produktov, obrazkov a odkazu.</p>
          </div>
          <form method="post">
            <input type="hidden" name="action" value="logout" />
            <button class="btn btn-secondary btn-small" type="submit">Odhlasit</button>
          </form>
        </div>
        <nav class="admin-nav">
          <a class="<?= $section === 'articles' ? 'is-active' : '' ?>" href="/admin?section=articles&slug=<?= esc($selectedArticleSlug) ?>">Clanky</a>
          <a class="<?= $section === 'products' ? 'is-active' : '' ?>" href="/admin?section=products">Produkty</a>
          <a class="<?= $section === 'images' ? 'is-active' : '' ?>" href="/admin?section=images&slug=<?= esc($selectedArticleSlug) ?>">Obrazky</a>
          <a class="<?= $section === 'affiliates' ? 'is-active' : '' ?>" href="/admin?section=affiliates&code=<?= esc($selectedAffiliateCode) ?>">Odkazy do obchodu</a>
          <a class="<?= $section === 'brand' ? 'is-active' : '' ?>" href="/admin?section=brand">Logo a ikonka</a>
          <a class="<?= $section === 'tools' ? 'is-active' : '' ?>" href="/admin?section=tools">Import / export</a>
          <a class="<?= $section === 'help' ? 'is-active' : '' ?>" href="/admin?section=help">Pomoc / quickstart</a>
          <a href="/admin/ai-status">AI status</a>
        </nav>
        <div class="admin-note">
          Tu spravujes obsah webu. V kazdej casti mas len tie kroky, ktore teraz naozaj potrebujes.
        </div>
        <section class="admin-quickstart">
          <h2>Rychly start</h2>
          <?php if ($section === 'articles'): ?>
            <ol class="admin-quickstart-list">
              <li>Vyber clanok, ktory prave plnis.</li>
              <li>Ak riesis produkty, klikni hore na tlacidlo Produkty v clanku.</li>
              <li>Text clanku a dalsie pokrocile casti otvor len vtedy, ked ich naozaj potrebujes menit.</li>
            </ol>
          <?php elseif ($section === 'images'): ?>
            <ol class="admin-quickstart-list">
              <li>Vyber, ci riesis clanok, temu alebo produkt.</li>
              <li>Skopiruj text pre Canvu a sprav obrazok.</li>
              <li>Nahraj obrazok sem a skontroluj vysledok na webe.</li>
            </ol>
          <?php elseif ($section === 'products'): ?>
            <ol class="admin-quickstart-list">
              <li>Tu riesis produkt na webe: nazov, obrazok a URL produktu.</li>
              <li>Najprv vloz link produktu alebo Dognet link.</li>
              <li>Potom nacitaj data z e-shopu a uloz obrazok.</li>
            </ol>
          <?php elseif ($section === 'affiliates'): ?>
            <ol class="admin-quickstart-list">
              <li>Tu riesis kam clovek po kliknuti odide.</li>
              <li>Vyber alebo vytvor interny /go/ odkaz.</li>
              <li>Vloz finalnu cielovu URL a potom skontroluj klik na webe.</li>
            </ol>
          <?php elseif ($section === 'brand'): ?>
            <ol class="admin-quickstart-list">
              <li>Nahraj hlavne logo, ak chces zmenit logo v hlavicke webu.</li>
              <li>Nahraj zdrojovy obrazok pre ikonku stranky.</li>
              <li>Admin z nej pripravi male verzie pre prehliadac a mobil.</li>
            </ol>
          <?php elseif ($section === 'help'): ?>
            <ol class="admin-quickstart-list">
              <li>Ak ides upravit obsah, zacni v Clankoch.</li>
              <li>Ak ides riesit obrazky, otvor Obrazky.</li>
              <li>Ak ides riesit CTA a produkty, pouzi Produkty a Odkazy do obchodu.</li>
            </ol>
          <?php else: ?>
            <ol class="admin-quickstart-list">
              <li>Clanky: texty, porovnania a odporucane produkty.</li>
              <li>Produkty: nazvy, obrazky a URL produktov.</li>
              <li>Obrazky: obrazky clankov, tem a produktov.</li>
            </ol>
          <?php endif; ?>
        </section>
      </aside>
      <?php endif; ?>

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
        <?php if ($section === 'brand' && $saved === 'logo'): ?>
          <div class="admin-flash is-success">Hlavne logo bolo nahrate.</div>
        <?php endif; ?>
        <?php if ($section === 'brand' && $saved === 'icon'): ?>
          <div class="admin-flash is-success">Ikonka stranky a male verzie boli pripravene.</div>
        <?php endif; ?>
        <?php if ($section === 'brand' && $saved === 'og'): ?>
          <div class="admin-flash is-success">Obrazok pri zdielani bol nahraty.</div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>

          <div class="admin-flash is-error"><?= esc($error) ?></div>
        <?php endif; ?>

        <?php if (!$productCandidateFocusMode && $section !== 'products' && $section !== 'brand'): ?>
        <details class="admin-subsection is-compact">
          <summary><strong>Zakladny prehlad webu</strong> - bezne netreba otvarat</summary>
          <section class="admin-dashboard-grid">
            <article class="admin-stat-card">
              <span class="admin-stat-label">Clanky s admin zmenou</span>
              <strong><?= esc((string) $dashboardStats['article_overrides']) ?></strong>
            </article>
            <article class="admin-stat-card">
              <span class="admin-stat-label">Produkty ulozene v admine</span>
              <strong><?= esc((string) $dashboardStats['products']) ?></strong>
            </article>
            <article class="admin-stat-card">
              <span class="admin-stat-label">Klikacie odkazy</span>
              <strong><?= esc((string) $dashboardStats['affiliate_codes']) ?></strong>
            </article>
            <article class="admin-stat-card">
              <span class="admin-stat-label">Hlavne obrazky clankov hotove</span>
              <strong><?= esc((string) $dashboardStats['hero_ready']) ?></strong>
            </article>
          </section>
        </details>
        <?php endif; ?>

        <?php if ($section === 'articles'): ?>
          <section class="admin-card">
            <div class="admin-card-head">
              <div>
                <p class="admin-kicker">Clanok</p>
                <h2>Uprava vybraneho clanku</h2>
              </div>
              <div class="admin-inline-actions">
                <a class="btn btn-cta btn-small" href="#article-products-block">Produkty v clanku</a>
                <a class="btn btn-secondary btn-small" href="<?= esc(article_url($selectedArticleSlug)) ?>" target="_blank" rel="noopener">Otvorit clanok na webe</a>
                <a class="btn btn-secondary btn-small" href="#article-check-block">Skontrolovat clanok</a>
                <details class="admin-inline-more">
                  <summary>Dalsie akcie</summary>
                  <div class="admin-inline-actions">
                    <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>">Otvorit obrazky</a>
                    <a class="btn btn-secondary btn-small" href="/hero-helper" target="_blank" rel="noopener">Pomocnik pre obrazok</a>
                  </div>
                </details>
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

            <details class="admin-subsection">
              <summary>Vytvorit novy clanok (bezne netreba)</summary>
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
            </details>

            <form method="post" enctype="multipart/form-data" class="admin-form admin-form-stack">
              <input type="hidden" name="action" value="save_article" />
              <input type="hidden" name="slug" value="<?= esc($selectedArticleSlug) ?>" />

              <section class="admin-subsection is-compact">
                <div class="admin-subsection-head">
                  <div>
                    <h3>Hlavny produktovy workflow</h3>
                    <p class="admin-meta">Tu riesis len 3 sloty, hlavny produkt a ulozenie produktov v clanku.</p>
                  </div>
                </div>
              <div class="admin-grid two-up">
                <div>
                  <span class="admin-label-like">Clanok</span>
                  <p class="admin-note"><?= esc((string) ($selectedArticleOverride['title'] ?: $selectedArticleMeta['title'])) ?></p>
                </div>
                <?php $selectedCategory = (string) ($selectedArticleOverride['category'] ?: $selectedArticleMeta['category']); ?>
                <div>
                  <span class="admin-label-like">Kategoria clanku</span>
                  <p class="admin-note"><?= esc((string) ($categoryOptions[$selectedCategory]['title'] ?? ($selectedCategory !== '' ? $selectedCategory : 'Bez kategorie'))) ?></p>
                </div>
              </div>
              </section>

              <details class="admin-subsection is-compact">
                <summary><strong>Redakcny obsah clanku</strong> - otvor len ked menis titulok, intro alebo kategoriu</summary>
                <div class="admin-grid two-up">
                  <label>
                    <span>Titulok</span>
                    <input type="text" name="title" value="<?= esc((string) ($selectedArticleOverride['title'] ?: $selectedArticleMeta['title'])) ?>" />
                  </label>
                  <label>
                    <span>Kategoria</span>
                    <select name="category">
                      <option value="">Bez kategorie</option>
                      <?php foreach ($categoryOptions as $categorySlug => $categoryRow): ?>
                        <option value="<?= esc((string) $categorySlug) ?>" <?= $selectedCategory === (string) $categorySlug ? 'selected' : '' ?>><?= esc((string) ($categoryRow['title'] ?? $categorySlug)) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                  <div></div>
                </div>
                <label>
                  <span>Intro</span>
                  <textarea name="intro" rows="3"><?= esc((string) ($selectedArticleOverride['intro'] ?: $selectedArticleMeta['description'])) ?></textarea>
                </label>
              </details>

              <section class="admin-subsection is-compact" id="article-check-block">
                <div class="admin-subsection-head">
                  <div>
                    <h3>Co v tomto clanku este chyba</h3>
                    <p class="admin-meta">Tu hned vidis, kolko produktov je vybranych, co je hotove a na co mas kliknut dalej.</p>
                  </div>
                  <div class="admin-inline-actions">
                    <a class="btn btn-secondary btn-small" href="#article-products-block">Prejst na produkty v clanku</a>
                  </div>
                </div>
                <div class="admin-status-grid">
                  <article class="admin-status-card">
                    <strong><?= esc((string) $articleSelectedCount) ?></strong>
                    <span>Vybrane produkty</span>
                  </article>
                  <article class="admin-status-card">
                    <strong><?= esc((string) $articleSelectedReadyCount) ?></strong>
                    <span>Uplne hotove</span>
                  </article>
                  <article class="admin-status-card">
                    <strong><?= esc((string) $articleSelectedImageMissingCount) ?></strong>
                    <span>Chyba obrazok</span>
                  </article>
                  <article class="admin-status-card">
                    <strong><?= esc((string) $articleSelectedClickMissingCount) ?></strong>
                    <span>Chyba klik do obchodu</span>
                  </article>
                </div>
                <?php if ($articleSelectedMissingProductCount > 0): ?>
                  <p class="admin-note">Pozor: <?= esc((string) $articleSelectedMissingProductCount) ?> vybranych produktov este nie je poriadne doplnenych v katalogu.</p>
                <?php endif; ?>
                <?php if ($articleSelectedActionRows !== []): ?>
                  <div class="admin-queue-list">
                    <?php foreach ($articleSelectedActionRows as $articleActionRow): ?>
                      <article class="admin-queue-item<?= ($articleActionRow['exists'] && $articleActionRow['packshot_ready'] && $articleActionRow['affiliate_ready']) ? ' is-done' : '' ?>">
                        <div>
                          <strong><?= esc((string) ($articleActionRow['name'] ?? '')) ?></strong>
                          <p><?= esc((string) ($articleActionRow['slug'] ?? '')) ?></p>
                          <div class="admin-status-pills">
                            <span class="admin-status-pill<?= !empty($articleActionRow['exists']) ? ' is-good' : ' is-warning' ?>"><?= !empty($articleActionRow['exists']) ? 'Produkt hotovy' : 'Produkt chyba' ?></span>
                            <span class="admin-status-pill<?= !empty($articleActionRow['packshot_ready']) ? ' is-good' : ' is-warning' ?>"><?= !empty($articleActionRow['packshot_ready']) ? 'Obrazok hotovy' : 'Obrazok chyba' ?></span>
                            <span class="admin-status-pill<?= !empty($articleActionRow['affiliate_ready']) ? ' is-good' : ' is-warning' ?>"><?= !empty($articleActionRow['affiliate_ready']) ? 'Odkaz hotovy' : 'Chyba konkretny produktovy odkaz' ?></span>
                          </div>
                          <small class="admin-note"><?= esc((string) ($articleActionRow['next_note'] ?? '')) ?></small>
                        </div>
                        <div class="admin-inline-actions">
                          <a class="btn btn-secondary btn-small" href="<?= esc((string) ($articleActionRow['next_href'] ?? '#')) ?>"><?= esc((string) ($articleActionRow['next_label'] ?? 'Otvorit')) ?></a>
                        </div>
                      </article>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <p class="admin-note">Clanok zatial nema explicitne priradene produkty. Nizsie vypln 3 sloty v casti <strong>Produkty v tomto clanku</strong>.</p>
                <?php endif; ?>
              </section>

              <div class="admin-subsection" id="article-products-block">
                <div class="admin-subsection-head">
                  <h3>Produkty v tomto clanku</h3>
                </div>
                <p class="admin-note">Tento clanok ma pevne 3 sloty. Vyber do nich produkty, oznac hlavny produkt a potom uloz produkty v clanku.</p>
                <?php if ($articleProductPlanHasExplicitSource === false): ?>
                  <p class="admin-note">Clanok zatial nema explicitne priradene produkty.</p>
                <?php endif; ?>
                <p class="admin-meta"><strong>Debug zdroj slotov:</strong> <?= $articleProductPlanHasExplicitSource ? 'explicit' : 'none' ?></p>
                <?php
                  $articleFeaturedSlot = 1;
                  foreach ($articleSlotSelections as $slotIndex => $slotSlug) {
                      if ($slotSlug !== '' && (string) ($articleProductPlanMap[$slotSlug]['role'] ?? 'standard') === 'featured') {
                          $articleFeaturedSlot = (int) $slotIndex;
                          break;
                      }
                  }
                  if ((string) ($articleSlotSelections[$articleFeaturedSlot] ?? '') === '') {
                      foreach ($articleSlotSelections as $slotIndex => $slotSlug) {
                          if ($slotSlug !== '') {
                              $articleFeaturedSlot = (int) $slotIndex;
                              break;
                          }
                      }
                  }
                ?>
                <div class="admin-grid one-up">
                  <?php for ($slotIndex = 1; $slotIndex <= 3; $slotIndex++): ?>
                    <?php
                      $slotSlug = (string) ($articleSlotSelections[$slotIndex] ?? '');
                      $slotRow = $slotSlug !== '' ? ($articleScopedProductOptions[$slotSlug] ?? null) : null;
                      $slotActionRow = $slotSlug !== '' ? ($articleSelectedActionRowsBySlug[$slotSlug] ?? []) : [];
                    ?>
                    <section class="admin-subsection is-compact article-product-slot" id="slot-<?= esc((string) $slotIndex) ?>">
                      <div class="admin-subsection-head">
                        <div>
                          <h4>Slot <?= esc((string) $slotIndex) ?></h4>
                          <p class="admin-meta"><?= $slotIndex === $articleFeaturedSlot && $slotSlug !== '' ? 'Hlavny produkt' : 'Vyber 1 produkt pre tento slot' ?></p>
                          <p class="admin-meta"><strong>Debug:</strong> slot <?= esc((string) $slotIndex) ?> / <?= $slotSlug !== '' ? esc($slotSlug) : 'NULL' ?> / source <?= $slotSlug !== '' ? 'explicit' : 'none' ?></p>
                        </div>
                      </div>
                      <label>
                        <span>Produkt pre slot <?= esc((string) $slotIndex) ?></span>
                        <select name="article_product_slot[<?= esc((string) $slotIndex) ?>]">
                          <option value="">Nechat prazdny slot</option>
                          <?php foreach ($articleScopedProductOptions as $optionSlug => $optionRow): ?>
                            <option value="<?= esc((string) $optionSlug) ?>" <?= $slotSlug === (string) $optionSlug ? 'selected' : '' ?>><?= esc((string) ($optionRow['name'] ?? $optionSlug)) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </label>
                      <?php if (is_array($slotRow)): ?>
                        <input type="hidden" name="article_product_role[<?= esc((string) $slotSlug) ?>]" value="<?= esc((string) ($slotRow['role'] ?? 'standard')) ?>" />
                        <input type="hidden" name="article_product_placement[<?= esc((string) $slotSlug) ?>]" value="<?= esc((string) ($slotRow['placement'] ?? 'recommended')) ?>" />
                        <div class="admin-status-pills">
                          <span class="admin-status-pill<?= esc((string) ($slotRow['state_class'] ?? ' is-warning')) ?>"><?= esc((string) ($slotRow['state_label'] ?? 'Chyba')) ?></span>
                        </div>
                        <label class="admin-check-card admin-check-card--inline">
                          <input type="radio" name="article_product_featured_slot" value="<?= esc((string) $slotIndex) ?>" <?= $slotIndex === $articleFeaturedSlot ? 'checked' : '' ?> />
                          <span><strong><?= $slotIndex === $articleFeaturedSlot ? 'Toto je hlavny produkt' : 'Nastavit ako hlavny produkt' ?></strong></span>
                        </label>
                        <div class="admin-inline-actions">
                          <a class="btn btn-secondary btn-small" href="<?= esc((string) ($slotActionRow['next_href'] ?? ($slotRow['next_href'] ?? '#'))) ?>"><?= esc((string) ($slotActionRow['next_label'] ?? ($slotRow['next_label'] ?? 'Doplnit produkt'))) ?></a>
                          <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) $slotSlug) ?>&amp;article=<?= esc($selectedArticleSlug) ?>&amp;slot=<?= esc((string) $slotIndex) ?>&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>&amp;focus=product_edit#product-edit-form">Vybrat produkt pre slot</a>
                        </div>
                      <?php else: ?>
                        <p class="admin-note">Ziadny produkt nie je priradeny.</p>
                        <div class="admin-inline-actions">
                          <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;article=<?= esc($selectedArticleSlug) ?>&amp;slot=<?= esc((string) $slotIndex) ?>&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Vybrat produkt pre slot</a>
                        </div>
                      <?php endif; ?>
                    </section>
                  <?php endfor; ?>
                </div>
                <details class="admin-subsection is-compact">
                  <summary><strong>Zdroj produktov pre tieto sloty</strong> - otvor len ked chces skontrolovat kandidatov</summary>
                  <p class="admin-note">Na vyber mas <?= esc((string) count($articleScopedProductOptions)) ?> produktov. Zoznam sa sklada z explicitnych produktov clanku, kandidatov pre tento clanok a tematicky relevantnych produktov z katalogu. Pri uzkych temach ako pre-workout sa nerelevantny katalogovy fallback neukazuje.</p>
                </details>
              </div>

              <details class="admin-subsection">
                <summary><strong>Dalsie nastavenia clanku</strong> - otvor len ked menis text alebo pokrocile casti</summary>
                <div class="admin-grid one-up">
                  <label>
                    <span>Hlavny obrazok clanku (pokrocile)</span>
                    <input type="text" name="hero_asset" value="<?= esc((string) ($selectedArticleOverride['hero_asset'] ?? '')) ?>" placeholder="img/articles/heroes/slug.webp" />
                  </label>
                </div>
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
                    <h3>Text clanku</h3>
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
                    <summary>Pokrocile JSON polia</summary>
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
                  <span>Odporucane produkty (pokrocile)</span>
                  <textarea name="recommended_products" rows="6"><?= esc($recommendedProductsText) ?></textarea>
                  <span>Bezny postup je vyssie v bloku Produkty v tomto clanku. Toto otvor len ked opravujes starsie data.</span>
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
                      <?php $productClickState = interessa_admin_product_click_state($productNormalized); ?>
                      <?php $productAffiliateReady = !empty($productClickState['ready']); ?>
                      <div class="admin-check-card-wrap">
                        <label class="admin-check-card">
                          <input type="checkbox" name="recommended_product_checks[]" value="<?= esc((string) $productSlug) ?>" data-product-name="<?= esc((string) ($productNormalized['name'] ?? $productSlug)) ?>" data-product-bestfor="<?= esc((string) ($productNormalized['summary'] ?? '')) ?>" data-product-merchant="<?= esc((string) ($productNormalized['merchant'] ?? '')) ?>" data-product-rating="<?= esc((string) ($productNormalized['rating'] ?? '')) ?>" data-product-summary="<?= esc((string) ($productNormalized['summary'] ?? '')) ?>" data-product-affiliate-ready="<?= $productAffiliateReady ? 'true' : 'false' ?>" data-product-packshot-ready="<?= $productPackshotReady ? 'true' : 'false' ?>" data-product-summary-ready="<?= trim((string) ($productNormalized['summary'] ?? '')) !== '' ? 'true' : 'false' ?>" data-product-rating-ready="<?= trim((string) ($productNormalized['rating'] ?? '')) !== '' ? 'true' : 'false' ?>" data-product-pros-ready="<?= (is_array($productNormalized['pros'] ?? null) ? array_values(array_filter(array_map('strval', $productNormalized['pros']))) : []) !== [] ? 'true' : 'false' ?>" data-product-cons-ready="<?= (is_array($productNormalized['cons'] ?? null) ? array_values(array_filter(array_map('strval', $productNormalized['cons']))) : []) !== [] ? 'true' : 'false' ?>" data-product-card-ready="<?= ($productAffiliateReady && $productPackshotReady && trim((string) ($productNormalized['summary'] ?? '')) !== '' && trim((string) ($productNormalized['rating'] ?? '')) !== '' && (is_array($productNormalized['pros'] ?? null) ? array_values(array_filter(array_map('strval', $productNormalized['pros']))) : []) !== [] && (is_array($productNormalized['cons'] ?? null) ? array_values(array_filter(array_map('strval', $productNormalized['cons']))) : []) !== []) ? 'true' : 'false' ?>" <?= $checked ? 'checked' : '' ?> />
                          <span><strong><?= esc((string) ($productRow['name'] ?? $productSlug)) ?></strong><small><?= esc((string) $productSlug) ?></small></span>
                        </label>
                        <div class="admin-status-pills">
                          <span class="admin-status-pill<?= $productAffiliateReady ? ' is-good' : ' is-warning' ?>"><?= $productAffiliateReady ? 'Odkaz hotovy' : 'Chyba konkretny produktovy odkaz' ?></span>
                          <span class="admin-status-pill<?= $productPackshotReady ? ' is-good' : ' is-warning' ?>"><?= $productPackshotReady ? 'Obrazok pripraveny' : 'Obrazok chyba' ?></span>
                        </div>
                        <div class="admin-inline-actions admin-check-card__actions">
                          <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) $productSlug) ?>&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>&amp;focus=product_edit#product-edit-form">Doplnit produkt</a>
                          <?php if ($productAffiliateCode !== ''): ?>
                            <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;code=<?= esc($productAffiliateCode) ?>&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Doplnit odkaz</a>
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
                  <span>Nahrat hlavny obrazok clanku</span>
                  <input type="file" name="hero_image" accept="image/webp,image/png,image/jpeg" />
                  <small class="admin-note">Kam sa ulozi hlavny obrazok: <code><?= esc((string) ($articlePrompt['asset_path'] ?? '')) ?></code></small>
                  <div class="admin-inline-actions">
                    <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>">Otvorit obrazky</a>
                    <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($articlePrompt['asset_path'] ?? '')) ?>">Kopirovat cestu</button>
                  </div>
                </label>
              </div>

              <section class="admin-subsection is-compact">
                <div class="admin-subsection-head">
                  <h3>Aktualny hlavny obrazok</h3>
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
                    <h3>Kontrola pripravenosti produktov</h3>
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
                    <span>Odkazy hotove</span>
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
                            <span class="admin-status-pill<?= !empty($actionRow['affiliate_ready']) ? ' is-good' : ' is-warning' ?>"><?= !empty($actionRow['affiliate_ready']) ? 'Odkaz hotovy' : 'Chyba konkretny produktovy odkaz' ?></span>
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
                            <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($actionRow['slug'] ?? '')) ?>&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Upravit produkt</a>
                            <?php if (trim((string) ($actionRow['affiliate_code'] ?? '')) !== ''): ?>
                              <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;code=<?= esc((string) ($actionRow['affiliate_code'] ?? '')) ?>&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Upravit odkaz do obchodu</a>
                            <?php else: ?>
                              <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;prefill_code=<?= esc((string) ($actionRow['slug'] ?? '')) ?>&amp;prefill_merchant=<?= esc((string) ($actionRow['merchant'] ?? '')) ?>&amp;prefill_merchant_slug=<?= esc(interessa_admin_slugify((string) ($actionRow['merchant'] ?? ''))) ?>&amp;prefill_product_slug=<?= esc((string) ($actionRow['slug'] ?? '')) ?>&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Vytvorit odkaz do obchodu</a>
                            <?php endif; ?>
                            <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($actionRow['slug'] ?? '')) ?>&amp;product_image_filter=missing&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Doplnit obrazok produktu</a>
                            <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>">Otvorit obrazky clanku</a>
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
                          <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($previewRow['slug'] ?? '')) ?>&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Upravit produkt</a>
                          <?php if (trim((string) ($previewRow['code'] ?? $previewRow['affiliate_code'] ?? '')) !== ''): ?>
                            <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;code=<?= esc((string) ($previewRow['code'] ?? $previewRow['affiliate_code'] ?? '')) ?>&amp;return_section=articles&amp;return_slug=<?= esc($selectedArticleSlug) ?>">Upravit odkaz do obchodu</a>
                          <?php endif; ?>
                          <?php if (trim((string) ($previewRow['href'] ?? '')) !== ''): ?>
                            <a class="btn btn-secondary btn-small" href="<?= esc((string) ($previewRow['href'] ?? '')) ?>" target="_blank" rel="noopener">Ciel</a>
                          <?php endif; ?>
                          <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>">Otvorit obrazky clanku</a>
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

              </details>

              <div class="admin-actions">
                <button class="btn btn-cta" type="submit">Ulozit produkty v clanku</button>
                <a class="btn btn-secondary" href="<?= esc(article_url($selectedArticleSlug)) ?>" target="_blank" rel="noopener">Otvorit clanok na webe</a>
                <details class="admin-inline-more">
                  <summary>Pokrocile</summary>
                  <div class="admin-inline-actions">
                    <button class="btn btn-secondary" type="submit" name="action" value="save_article">Ulozit cely clanok</button>
                    <button class="btn btn-secondary" type="submit" name="action" value="delete_article_override" onclick="return confirm('Naozaj resetovat admin override pre tento clanok?');">Reset override</button>
                  </div>
                </details>
              </div>
            </form>
          </section>
        <?php endif; ?>

        <?php if ($section === 'products'): ?>
          <?php
            $selectedProductAffiliateCode = trim((string) ($selectedProduct['affiliate_code'] ?? ''));
            $selectedProductRemoteSrc = trim((string) ($selectedProduct['image_remote_src'] ?? ''));
            $selectedProductAffiliateInputUrl = is_array($selectedProductAffiliate) ? trim((string) ($selectedProductAffiliate['url'] ?? '')) : '';
            $selectedProductClickReady = !empty($selectedProductClickState['ready']);
            $selectedProductHasDognetLink = $selectedProductAffiliateInputUrl !== '' && str_contains(strtolower($selectedProductAffiliateInputUrl), 'dognet');
            $selectedProductQuickInputUrl = $selectedProductAffiliateInputUrl !== '' ? $selectedProductAffiliateInputUrl : $selectedProductSourceUrl;
            $selectedProductNextStep = 'enter_link';
            $selectedProductNextStepLabel = '1. VLOZIT LINK PRODUKTU ALEBO DOGNET LINK';
            $selectedProductNextStepNote = 'Sem vloz obycajny link produktu alebo Dognet link. Admin z neho pripravi produkt a hned vytvori fungujuci klik.';

            if ($selectedProductHasUsableSourceUrl && !$selectedProductClickReady) {
              $selectedProductNextStep = 'prepare';
              $selectedProductNextStepLabel = '1. PRIPRAVIT PRODUKT Z ULOZENEHO LINKU';
              $selectedProductNextStepNote = 'Link uz je vyplneny. Teraz z neho admin pripravi klik, adresu produktu a skusi najst obrazok.';
            } elseif ($selectedProductHasUsableSourceUrl) {
              $selectedProductNextStep = 'enrich';
              $selectedProductNextStepLabel = '2. NACITAT UDAJE Z OBCHODU';
              $selectedProductNextStepNote = 'Produkt uz ma fungujuci klik. Teraz admin skusi doplnit nazov, text a obrazok.';
            }
            if ($selectedProductRemoteSrc !== '' && !$selectedProductPackshotReady) {
              $selectedProductNextStep = 'save_image';
              $selectedProductNextStepLabel = '3. ULOZIT OBRAZOK Z E-SHOPU';
              $selectedProductNextStepNote = 'Obrazok uz je najdeny. Teraz ho treba len ulozit k produktu.';
            }
            if ($selectedProductPackshotReady && $selectedProductClickReady && !$selectedProductHasDognetLink) {
              $selectedProductNextStep = 'dognet';
              $selectedProductNextStepLabel = 'VOLITELNE: OTVORIT DOGNET POMOCNIKA';
              $selectedProductNextStepNote = 'Produkt uz funguje aj bez Dognetu. Ked chces, mozes obycajny klik neskor vymenit za Dognet link.';
            }
            if ($selectedProductPackshotReady && $selectedProductClickReady && $selectedProductHasDognetLink) {
              $selectedProductNextStep = 'done';
              $selectedProductNextStepLabel = 'Hotovo';
              $selectedProductNextStepNote = 'Produkt uz ma obrazok aj klik do obchodu. Tu uz netreba nic robit.';
            }
          ?>
          <?php if ($articleSlotMode): ?>
          <section class="admin-card">
            <div class="admin-card-head">
              <div>
                <p class="admin-kicker">Clanok je hlavny kontext</p>
                <h2>Slot <?= esc((string) $returnArticleSlotPrefill) ?> pre clanok <?= esc($articleSlotModeTitle) ?></h2>
                <p class="admin-note">Tu doplnas len jeden produkt pre konkretny slot. Po ulozeni sa vratis spat na clanok.</p>
                <p class="admin-meta">article: <?= esc($returnArticlePrefill) ?> / slot: <?= esc((string) $returnArticleSlotPrefill) ?> / mode: article-slot</p>
              </div>
              <div class="admin-inline-actions">
                <form method="get" action="/admin" class="admin-inline-form">
                  <input type="hidden" name="section" value="products" />
                  <input type="hidden" name="article" value="<?= esc($returnArticlePrefill) ?>" />
                  <input type="hidden" name="slot" value="<?= esc((string) $returnArticleSlotPrefill) ?>" />
                  <input type="hidden" name="return_section" value="articles" />
                  <input type="hidden" name="return_slug" value="<?= esc($returnArticlePrefill) ?>" />
                  <label class="admin-inline-select">
                    <span>Produkt pre tento slot</span>
                    <select name="product" onchange="this.form.submit()">
                      <option value="">Vyber produkt</option>
                      <?php foreach ($productSlugs as $slug): ?>
                        <option value="<?= esc($slug) ?>" <?= $slug === $selectedProductSlug ? 'selected' : '' ?>><?= esc((string) ($catalog[$slug]['name'] ?? $slug)) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                </form>
                <a class="btn btn-secondary" href="<?= esc($articleSlotBackHref) ?>">Spat na clanok</a>
                <a class="btn btn-secondary" href="<?= esc(article_url($returnArticlePrefill)) ?>" target="_blank" rel="noopener">Otvorit clanok na webe</a>
              </div>
            </div>
            <?php if ($selectedProductSlug !== ''): ?>
            <div class="admin-status-grid">
              <article class="admin-status-card">
                <strong><?= esc((string) $returnArticleSlotPrefill) ?></strong>
                <span>Aktualny slot</span>
              </article>
              <article class="admin-status-card">
                <strong><?= trim((string) ($selectedProduct['name'] ?? '')) !== '' ? 'Ano' : 'Nie' ?></strong>
                <span>Nazov produktu</span>
              </article>
              <article class="admin-status-card">
                <strong><?= $selectedProductPackshotReady ? 'Hotovo' : 'Chyba' ?></strong>
                <span>Obrazok produktu</span>
              </article>
              <article class="admin-status-card">
                <strong><?= $selectedProductClickReady ? 'Odkaz hotovy' : 'Chyba konkretny produktovy odkaz' ?></strong>
                <span>Klik do obchodu</span>
              </article>
            </div>
            <?php else: ?>
            <p class="admin-note">Najprv vyber produkt pre tento slot. Az potom sa otvori editor produktu pre clanok a slot.</p>
            <?php endif; ?>
          </section>
          <?php endif; ?>
          <?php if (!$articleSlotMode): ?>
          <section class="admin-card<?= $productCandidateFocusMode ? ' is-candidate-focus-root' : '' ?><?= !$productCandidateFocusMode ? ' is-primary-flow' : '' ?>" id="products-candidate-steps">
            <?php if (!$productCandidateFocusMode): ?>
            <div class="admin-card-head">
              <div>
                <p class="admin-kicker">Import produktov</p>
                <h2>1. Nacitaj produkty pre clanok</h2>
                <p class="admin-note"><strong>Vybrany clanok:</strong> <?= esc($candidateImportArticleTitle) ?></p>
                <p class="admin-note">Postup je jednoduchy: vyber clanok, vloz URL feedu, klikni <strong>Nacitat produkty</strong> a hned pokracuj na prvy produkt.</p>
              </div>
              <form method="get" action="/admin" class="admin-inline-form">
                <input type="hidden" name="section" value="products" />
                <label class="admin-inline-select">
                  <span>Clanok</span>
                  <select name="import_article" onchange="this.form.submit()">
                    <?php foreach ($articleOptions as $articleSlug => $articleOption): ?>
                      <option value="<?= esc($articleSlug) ?>" <?= $articleSlug === $candidateImportArticleSlug ? 'selected' : '' ?>><?= esc((string) ($articleOption['title'] ?? $articleSlug)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
              </form>
            </div>
            <?php if ($recentImportedRows !== []): ?>
            <div class="admin-status-grid">
              <article class="admin-status-card">
                <strong><?= esc((string) $candidateImportedDisplayCount) ?></strong>
                <span>Produkty z posledneho importu</span>
              </article>
              <article class="admin-status-card">
                <strong><?= esc((string) $candidateClickReadyDisplayCount) ?></strong>
                <span>Odkaz do obchodu hotovy</span>
              </article>
              <article class="admin-status-card">
                <strong><?= esc((string) $candidateAssignedDisplayCount) ?></strong>
                <span>Pridane k clanku</span>
              </article>
              <article class="admin-status-card">
                <strong><?= esc((string) $candidateApprovedDisplayCount) ?></strong>
                <span>Ulozene v systeme</span>
              </article>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if (!$productCandidateFocusMode): ?>
            <section class="admin-subsection is-compact">
              <div class="admin-subsection-head">
                <div>
                  <h3>Import</h3>
                  <p class="admin-meta">Sem vloz URL feedu a nacitaj produkty pre clanok <strong><?= esc($candidateImportArticleTitle) ?></strong>.</p>
                </div>
              </div>
                <form method="post" enctype="multipart/form-data" class="admin-form admin-form-stack">
                  <input type="hidden" name="action" value="import_product_candidates" />
                  <input type="hidden" name="candidate_target_article_slug" value="<?= esc($candidateImportArticleSlug) ?>" />
                  <input type="hidden" name="candidate_filter_text" value="__auto__" />
                  <div class="admin-grid<?= $candidateImportShowMerchantSelect ? ' two-up' : ' one-up' ?>">
                    <?php if ($candidateImportShowMerchantSelect): ?>
                      <label>
                        <span>Obchod</span>
                        <select name="candidate_merchant_slug">
                          <?php foreach ($candidateImportMerchantOptions as $candidateMerchantSlug => $candidateMerchantName): ?>
                            <option value="<?= esc($candidateMerchantSlug) ?>"><?= esc($candidateMerchantName) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </label>
                    <?php else: ?>
                      <input type="hidden" name="candidate_merchant_slug" value="<?= esc($candidateImportSingleMerchantSlug) ?>" />
                      <div>
                        <span class="admin-label-like">Obchod</span>
                        <p class="admin-note"><?= esc($candidateImportSingleMerchantName) ?></p>
                      </div>
                    <?php endif; ?>
                    <div>
                      <span class="admin-label-like">Clanok</span>
                      <p class="admin-note"><?= esc($candidateImportArticleTitle) ?></p>
                    </div>
                  </div>
                  <div class="admin-grid one-up">
                    <label>
                      <span>URL feedu z Dognetu</span>
                      <input type="url" name="candidate_feed_url" placeholder="Sem vloz URL feedu z tlacidla Kopirovat URL" />
                    </label>
                  </div>
                  <p class="admin-note">V Dognete chod do <strong>Produktove feedy</strong>, klikni <strong>Kopirovat URL</strong> a vloz ten link sem.</p>
                  <div class="admin-actions">
                    <button class="btn btn-cta" type="submit">Nacitat produkty</button>
                  </div>
              </form>
            </section>
            <?php endif; ?>

            <section class="admin-subsection is-compact is-primary-step" id="products-current-candidate">
              <div class="admin-subsection-head">
                  <div>
                  <h3><?= $productCandidateFocusMode ? 'Otvoreny produkt' : '2. Produkty z posledneho importu' ?></h3>
                  <p class="admin-meta"><?= $productCandidateFocusMode ? 'Mas otvoreny jeden produkt. Nizsie ho dokoncis a hned vidis aj ostatne produkty z toho isteho importu.' : 'Po importe tu vidis posledne nacitane produkty pre clanok. Klikni na jeden produkt a pokracuj.' ?></p>
                  <?php if (is_array($selectedCandidate)): ?>
                    <p class="admin-note"><strong>Prave otvoreny produkt:</strong> <?= esc((string) ($selectedCandidate['name'] ?? 'Produkt')) ?><?= trim((string) ($selectedCandidate['merchant'] ?? '')) !== '' ? ' / ' . esc((string) ($selectedCandidate['merchant'] ?? '')) : '' ?></p>
                  <?php endif; ?>
                  </div>
                  <div class="admin-inline-actions">
                    <?php if ($productCandidateFocusMode): ?>
                      <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;import_article=<?= esc($candidateImportArticleSlug) ?><?= $recentCandidateBatchId !== '' ? '&amp;batch=' . esc($recentCandidateBatchId) : '' ?>">Spat na import produktov</a>
                    <?php endif; ?>
                  </div>
              </div>
                <?php if ($recentImportedRows !== [] && !$productCandidateFocusMode): ?>
                  <p class="admin-note"><strong>Import je hotovy.</strong> Dole mas zoznam produktov z posledneho importu pre clanok <strong><?= esc($candidateImportArticleTitle) ?></strong>.</p>
                <?php endif; ?>

              <?php if (!is_array($selectedCandidate)): ?>
                <?php if ($recentImportedRows !== [] && !$productCandidateFocusMode): ?>
                  <?php if ($recentImportedPilotRows === []): ?>
                    <p class="admin-note"><strong>Import prebehol, ale v tomto batchi nie je ziadny vhodny produkt pre clanok <?= esc($candidateImportArticleTitle) ?>.</strong></p>
                  <?php else: ?>
                    <p class="admin-note"><strong>Import je hotovy.</strong> Otvor jeden produkt z tohto zoznamu a pokracuj.</p>
                    <?php if (is_array($firstRecentImportedVisibleRow)): ?>
                      <div class="admin-actions">
                        <a class="btn btn-cta" href="/admin?section=products&amp;import_article=<?= esc($candidateImportArticleSlug) ?>&amp;batch=<?= esc($recentCandidateBatchId) ?>&amp;candidate=<?= esc((string) ($firstRecentImportedVisibleRow['id'] ?? '')) ?>#products-current-candidate">Otvorit prvy produkt z importu</a>
                      </div>
                    <?php endif; ?>
                    <div class="admin-queue-list">
                      <?php foreach ($recentImportedVisibleRows as $recentImportedIndex => $recentImportedRow): ?>
                        <article class="admin-queue-item<?= !empty($recentImportedRow['is_ready_for_article']) ? ' is-ready-for-article' : '' ?>">
                          <div>
                            <strong><?= esc((string) ($recentImportedIndex + 1)) ?>. <?= esc((string) $recentImportedRow['name']) ?></strong>
                            <p><?= esc((string) ($recentImportedRow['merchant'] ?? '')) ?></p>
                            <?php
                              $recentImportedStepDetails = [];
                              if (trim((string) ($recentImportedRow['category'] ?? '')) !== '') {
                                  $recentImportedStepDetails[] = 'Typ: ' . trim((string) ($recentImportedRow['category'] ?? ''));
                              }
                              if (trim((string) ($recentImportedRow['price'] ?? '')) !== '') {
                                  $recentImportedStepDetails[] = 'Cena: ' . trim((string) ($recentImportedRow['price'] ?? ''));
                              }
                            ?>
                            <?php if ($recentImportedStepDetails !== []): ?>
                              <p class="admin-meta"><?= esc(implode(' / ', $recentImportedStepDetails)) ?></p>
                            <?php endif; ?>
                            <p class="admin-meta"><strong>Summary:</strong> <?= esc((string) ($recentImportedRow['summary_label'] ?? 'Missing click')) ?></p>
                            <div class="admin-status-pills">
                              <span class="admin-status-pill is-good">Nacitany</span>
                              <span class="admin-status-pill <?= esc((string) ($recentImportedRow['summary_tone'] ?? 'is-warning')) ?>"><?= esc((string) ($recentImportedRow['summary_label'] ?? 'Missing click')) ?></span>
                              <span class="admin-status-pill<?= !empty($recentImportedRow['has_image']) ? ' is-good' : ' is-warning' ?>"><?= !empty($recentImportedRow['has_image']) ? 'Obrazok sa nasiel' : 'Obrazok chyba' ?></span>
                              <span class="admin-status-pill<?= !empty($recentImportedRow['has_click']) ? ' is-good' : ' is-warning' ?>"><?= !empty($recentImportedRow['has_click']) ? 'Odkaz hotovy' : 'Odkaz chyba' ?></span>
                              <span class="admin-status-pill <?= esc((string) ($recentImportedRow['approved_tone'] ?? 'is-warning')) ?>"><?= esc((string) ($recentImportedRow['approved_label'] ?? 'Neschvaleny')) ?></span>
                              <span class="admin-status-pill <?= esc((string) ($recentImportedRow['click_status_tone'] ?? 'is-warning')) ?>"><?= esc((string) ($recentImportedRow['click_status'] ?? 'Click: missing')) ?></span>
                              <span class="admin-status-pill <?= esc((string) ($recentImportedRow['product_status_tone'] ?? 'is-warning')) ?>"><?= esc((string) ($recentImportedRow['product_status'] ?? 'Produkt: caka')) ?></span>
                              <span class="admin-status-pill <?= esc((string) ($recentImportedRow['affiliate_status_tone'] ?? 'is-warning')) ?>"><?= esc((string) ($recentImportedRow['affiliate_status'] ?? 'Affiliate: missing')) ?></span>
                            </div>
                          </div>
                          <div class="admin-inline-actions">
                            <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;import_article=<?= esc($candidateImportArticleSlug) ?>&amp;batch=<?= esc($recentCandidateBatchId) ?>&amp;candidate=<?= esc((string) $recentImportedRow['id']) ?>#products-current-candidate">Otvorit tento produkt</a>
                          </div>
                        </article>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                <?php else: ?>
                  <p class="admin-note">Zatial tu nie je otvoreny ziadny produkt. Najprv pouzi Krok 1.</p>
                <?php endif; ?>
              <?php else: ?>
                <div class="admin-status-grid">
                  <article class="admin-status-card">
                    <strong>Ano</strong>
                    <span>Produkt je nacitany</span>
                  </article>
                  <article class="admin-status-card">
                    <strong><?= $selectedCandidateHasClick ? 'Ano' : 'Nie' ?></strong>
                    <span>Odkaz do obchodu</span>
                  </article>
                  <article class="admin-status-card">
                    <strong><?= $selectedCandidateHasArticle ? 'Ano' : 'Nie' ?></strong>
                    <span>Pridany k clanku</span>
                  </article>
                  <article class="admin-status-card">
                    <strong><?= $selectedCandidateApproved ? 'Ano' : 'Nie' ?></strong>
                    <span>Ulozeny v systeme</span>
                  </article>
                </div>
                <div class="admin-brief-grid">
                  <div class="admin-brief-card">
                    <h3><?= esc((string) ($selectedCandidate['name'] ?? 'Produkt')) ?></h3>
                    <p><strong>Obchod:</strong> <?= esc((string) ($selectedCandidate['merchant'] ?? '')) ?></p>
                    <?php if (trim((string) ($selectedCandidate['category'] ?? '')) !== ''): ?>
                      <p><strong>Typ:</strong> <?= esc((string) ($selectedCandidate['category'] ?? '')) ?></p>
                    <?php endif; ?>
                    <?php if (trim((string) ($selectedCandidate['price'] ?? '')) !== ''): ?>
                      <p><strong>Cena:</strong> <?= esc((string) ($selectedCandidate['price'] ?? '')) ?></p>
                    <?php endif; ?>
                    <p><strong>product_url:</strong><br><code><?= esc((string) ($selectedCandidate['url'] ?? '')) ?></code></p>
                    <p><strong>Obrazok z obchodu:</strong> <?= $selectedCandidateHasImage ? 'nasiel sa' : 'zatial chyba' ?></p>
                  </div>
                  <div class="admin-brief-card">
                    <h3>Co je dalsi krok</h3>
                    <p class="admin-note"><strong>Summary:</strong> <?= esc($selectedCandidateSummaryLabel) ?></p>
                    <div class="admin-status-pills">
                      <span class="admin-status-pill <?= esc($selectedCandidateSummaryTone) ?>"><?= esc($selectedCandidateSummaryLabel) ?></span>
                    </div>
                    <?php if (!$selectedCandidateHasClick): ?>
                    <p>Chyba len technicky klik do obchodu. Klikni tlacidlo nizsie a admin ho pripravi z product_url.</p>
                    <?php elseif (!$selectedCandidateHasArticle): ?>
                      <p>Klik do obchodu je pripraveny. Dalsi krok je uz len technicke pridanie k pilotnemu clanku, ak tam obsahovo patri.</p>
                    <?php elseif (!$selectedCandidateApproved): ?>
                    <p>Produkt uz ma odkaz aj clanok. Posledny krok je ulozit ho do systemu, aby ho potom vedelo posudit web vlakno.</p>
                    <?php else: ?>
                      <p>Tento produkt je technicky pripraveny. Finalny vyber pre web urobi web vlakno.</p>
                    <?php endif; ?>
                    <p><strong>click_status:</strong> <?= esc($selectedCandidateClickStatusLabel) ?></p>
                  </div>
                </div>

                <section class="admin-subsection is-compact" id="products-next-step">
                  <div class="admin-subsection-head">
                    <div>
                      <h3>Co spravit teraz</h3>
                      <p class="admin-meta">Tu mas len jeden dalsi krok. Klikni toto tlacidlo a potom sa vrat sem.</p>
                    </div>
                  </div>
                  <?php if (!$selectedCandidateHasClick): ?>
                    <p class="admin-note">Najprv treba pripravit odkaz do obchodu z ulozeneho linku produktu.</p>
                    <form method="post" class="admin-form admin-form-stack">
                      <input type="hidden" name="action" value="prepare_candidate_click" />
                      <input type="hidden" name="candidate_id" value="<?= esc($selectedCandidateId) ?>" />
                      <input type="hidden" name="batch" value="<?= esc($recentCandidateBatchId) ?>" />
                      <div class="admin-actions">
                        <button class="btn btn-cta" type="submit">Pripravit odkaz do obchodu</button>
                      </div>
                    </form>
                  <?php elseif (!$selectedCandidateHasArticle): ?>
                    <?php if ($selectedCandidateCanUseSimpleAssignment): ?>
                      <p class="admin-note">Tento produkt patri do clanku <strong><?= esc($selectedCandidateSuggestedArticleTitle) ?></strong>.</p>
                      <p class="admin-note">Po kliknuti sa len prida k tomuto clanku. Finalny vyber sa bude robit az neskor.</p>
                      <form method="post" class="admin-form admin-form-stack">
                        <input type="hidden" name="action" value="save_candidate_assignment" />
                        <input type="hidden" name="candidate_id" value="<?= esc($selectedCandidateId) ?>" />
                        <input type="hidden" name="batch" value="<?= esc($recentCandidateBatchId) ?>" />
                        <input type="hidden" name="candidate_article_slug" value="<?= esc($selectedCandidateArticleSlug) ?>" />
                        <input type="hidden" name="candidate_order" value="10" />
                        <input type="hidden" name="candidate_role" value="standard" />
                        <div class="admin-actions">
                          <button class="btn btn-cta" type="submit">Pridat tento produkt k clanku</button>
                        </div>
                      </form>
                    <?php elseif (($selectedCandidateArticleFit['status'] ?? 'no-fit') !== 'fit'): ?>
                      <p class="admin-note"><strong>Tento produkt zatial nepatri do clanku <?= esc($candidateImportArticleTitle) ?>.</strong></p>
                      <?php if (!empty($selectedCandidateArticleFit['reason'])): ?>
                        <p class="admin-meta"><?= esc((string) $selectedCandidateArticleFit['reason']) ?><?= !empty($selectedCandidateArticleFit['blocked']) ? ': ' . esc(implode(', ', (array) $selectedCandidateArticleFit['blocked'])) : '' ?>.</p>
                      <?php endif; ?>
                      <p class="admin-meta">Vyber iny produkt z posledneho importu. Tento produkt zatial len zostane medzi nacitanymi produktmi.</p>
                      <div class="admin-actions">
                        <a class="btn btn-secondary" href="/admin?section=products&amp;import_article=<?= esc($candidateImportArticleSlug) ?><?= $recentCandidateBatchId !== '' ? '&amp;batch=' . esc($recentCandidateBatchId) : '' ?>#products-current-candidate">Spat na posledny import</a>
                      </div>
                    <?php else: ?>
                      <p class="admin-note"><strong>Admin si pri tomto produkte nie je isty obsahovym fitom.</strong> Zatial ho nepridavaj. Vrat sa na posledny import a vyber iny produkt.</p>
                      <div class="admin-actions">
                        <a class="btn btn-secondary" href="/admin?section=products&amp;import_article=<?= esc($candidateImportArticleSlug) ?><?= $recentCandidateBatchId !== '' ? '&amp;batch=' . esc($recentCandidateBatchId) : '' ?>#products-current-candidate">Spat na posledny import</a>
                      </div>
                    <?php endif; ?>
                  <?php elseif (!$selectedCandidateApproved): ?>
                    <p class="admin-note">Produkt uz ma odkaz aj clanok. Posledny krok je ulozit ho do systemu. Este to neznamena finalny vyber pre web.</p>
                    <form method="post" class="admin-form admin-form-stack">
                      <input type="hidden" name="action" value="approve_candidate_for_web" />
                      <input type="hidden" name="candidate_id" value="<?= esc($selectedCandidateId) ?>" />
                      <input type="hidden" name="batch" value="<?= esc($recentCandidateBatchId) ?>" />
                      <div class="admin-actions">
                        <button class="btn btn-cta" type="submit">Ulozit tento produkt do systemu</button>
                      </div>
                    </form>
                  <?php else: ?>
                    <p class="admin-note">Tento produkt je uz hotovy a pusteny na web.</p>
                    <div class="admin-inline-actions">
                      <?php if (trim((string) ($selectedCandidate['article_slug'] ?? '')) !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=articles&amp;slug=<?= esc((string) $selectedCandidate['article_slug']) ?>#article-product-binding">Otvorit clanok</a>
                      <?php endif; ?>
                      <?php if (trim((string) ($selectedCandidate['product_slug'] ?? '')) !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) $selectedCandidate['product_slug']) ?>#product-main-flow">Otvorit produkt</a>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </section>

                <details class="admin-subsection is-compact">
                  <summary><strong>Podrobne nastavenia tohto produktu</strong> - pomocne a pokrocile pouzitie</summary>
                  <div class="admin-grid two-up">
                    <form method="post" class="admin-form admin-form-stack">
                      <input type="hidden" name="action" value="prepare_candidate_click" />
                      <input type="hidden" name="candidate_id" value="<?= esc($selectedCandidateId) ?>" />
                      <input type="hidden" name="batch" value="<?= esc($recentCandidateBatchId) ?>" />
                      <div class="admin-subsection is-compact">
                        <div class="admin-subsection-head"><h3>Pripravit odkaz do obchodu</h3></div>
                        <p class="admin-note">Admin pouzije ulozeny link produktu a pripravi odkaz, na ktory bude clovek klikat na webe.</p>
                        <div class="admin-actions">
                          <button class="btn btn-secondary" type="submit">Znovu pripravit odkaz</button>
                        </div>
                      </div>
                    </form>

                    <form method="post" class="admin-form admin-form-stack">
                      <input type="hidden" name="action" value="approve_candidate_for_web" />
                      <input type="hidden" name="candidate_id" value="<?= esc($selectedCandidateId) ?>" />
                      <input type="hidden" name="batch" value="<?= esc($recentCandidateBatchId) ?>" />
                      <div class="admin-subsection is-compact">
                        <div class="admin-subsection-head"><h3>Schvalit pre web</h3></div>
                        <p class="admin-note">Toto pouzi len ked chces znovu zapisat tento produkt do hlavneho systemu.</p>
                        <div class="admin-actions">
                          <button class="btn btn-secondary" type="submit">Znovu schvalit pre web</button>
                        </div>
                      </div>
                    </form>
                  </div>
                </details>
              <?php endif; ?>
            </section>

              <?php if ($recentImportedRows !== [] && $productCandidateFocusMode): ?>
                <section class="admin-subsection is-compact" id="products-imported-list">
                  <div class="admin-subsection-head">
                    <div>
                      <h3>Produkty z posledneho importu</h3>
                      <p class="admin-meta">Otvoreny je jeden produkt. Tu mozes hned prepnut na iny produkt z toho isteho importu.</p>
                      <?php if ($recentImportedBlockedRows !== []): ?>
                        <p class="admin-note"><?= esc((string) count($recentImportedBlockedRows)) ?> produktov z tohto batchu sa do clanku <?= esc($candidateImportArticleTitle) ?> nehodilo.</p>
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php if ($recentImportedVisibleRows !== []): ?>
                    <div class="admin-queue-list">
                      <?php foreach ($recentImportedVisibleRows as $recentImportedIndex => $recentImportedRow): ?>
                        <article class="admin-queue-item<?= !empty($recentImportedRow['is_ready_for_article']) ? ' is-ready-for-article' : '' ?>">
                          <div>
                            <strong><?= esc((string) ($recentImportedIndex + 1)) ?>. <?= esc((string) $recentImportedRow['name']) ?></strong>
                            <p><?= esc((string) ($recentImportedRow['merchant'] ?? '')) ?></p>
                            <p class="admin-meta"><strong>Summary:</strong> <?= esc((string) ($recentImportedRow['summary_label'] ?? 'Missing click')) ?></p>
                            <div class="admin-status-pills">
                              <span class="admin-status-pill <?= esc((string) ($recentImportedRow['summary_tone'] ?? 'is-warning')) ?>"><?= esc((string) ($recentImportedRow['summary_label'] ?? 'Missing click')) ?></span>
                              <span class="admin-status-pill <?= esc((string) ($recentImportedRow['approved_tone'] ?? 'is-warning')) ?>"><?= esc((string) ($recentImportedRow['approved_label'] ?? 'Neschvaleny')) ?></span>
                              <span class="admin-status-pill <?= esc((string) ($recentImportedRow['click_status_tone'] ?? 'is-warning')) ?>"><?= esc((string) ($recentImportedRow['click_status'] ?? 'Click: missing')) ?></span>
                              <span class="admin-status-pill <?= esc((string) ($recentImportedRow['product_status_tone'] ?? 'is-warning')) ?>"><?= esc((string) ($recentImportedRow['product_status'] ?? 'Produkt: caka')) ?></span>
                              <span class="admin-status-pill <?= esc((string) ($recentImportedRow['affiliate_status_tone'] ?? 'is-warning')) ?>"><?= esc((string) ($recentImportedRow['affiliate_status'] ?? 'Affiliate: missing')) ?></span>
                            </div>
                          </div>
                          <div class="admin-inline-actions">
                            <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;import_article=<?= esc($candidateImportArticleSlug) ?>&amp;batch=<?= esc($recentCandidateBatchId) ?>&amp;candidate=<?= esc((string) $recentImportedRow['id']) ?>#products-current-candidate"><?= (string) ($recentImportedRow['id'] ?? '') === $selectedCandidateId ? 'Tento produkt je otvoreny' : 'Otvorit tento produkt' ?></a>
                          </div>
                        </article>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </section>
              <?php endif; ?>
          </section>
          <?php endif; ?>

          <?php if ((($articleSlotMode && $selectedProductSlug !== '') || (!$articleSlotMode && !$productCandidateFocusMode && $manualProductRequested && $selectedProductSlug !== ''))): ?>
          <?php if ($articleSlotMode): ?>
          <section class="admin-card" id="products-main-page">
          <?php else: ?>
          <details class="admin-card" id="products-main-page">
            <summary><strong>Rucne opravy a starsie nastavenia</strong> - otvor len vtedy, ked potrebujes opravovat jednotlive produkty po jednom</summary>
          <?php endif; ?>
            <div class="admin-card-head">
              <div>
                <p class="admin-kicker"><?= $articleSlotMode ? 'Editor produktu pre slot' : 'Rucne opravy' ?></p>
                <h2><?= $articleSlotMode ? 'Doplnit produkt pre clanok' : 'Jednotlive produkty' ?></h2>
                <p class="admin-note"><?= $articleSlotMode ? 'V tomto rezime vidis len to, co treba doplnit pre jeden konkretne vybrany produkt v clanku.' : 'Toto otvor len vtedy, ked potrebujes opravovat jeden konkretny produkt rucne. Bezny postup je vyssie v 4 krokoch.' ?></p>
              </div>
              <?php if ($articleSlotMode): ?>
              <div class="admin-inline-actions">
                <a class="btn btn-secondary btn-small" href="<?= esc($articleSlotBackHref) ?>">Spat na clanok</a>
              </div>
              <?php else: ?>
              <form method="get" action="/admin" class="admin-inline-form">
                <input type="hidden" name="section" value="products" />
                <select name="product" onchange="this.form.submit()">
                  <?php foreach ($productSlugs as $slug): ?>
                    <option value="<?= esc($slug) ?>" <?= $slug === $selectedProductSlug ? 'selected' : '' ?>><?= esc((string) ($catalog[$slug]['name'] ?? $slug)) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
              <?php endif; ?>
            </div>

            <?php if (!$articleSlotMode): ?>
            <section class="admin-subsection is-compact" id="products-article-scan">
              <div class="admin-subsection-head">
                <div>
                  <h3>Produkty z vybraneho clanku</h3>
                  <p class="admin-meta">Tu vidis len produkty, ktore patria do vybraneho clanku z prvej fazy. Pri kazdom produkte mas uz len jeden dalsi krok.</p>
                </div>
                <form method="get" action="/admin" class="admin-inline-form">
                  <input type="hidden" name="section" value="products" />
                  <input type="hidden" name="product" value="<?= esc($selectedProductSlug) ?>" />
                  <select name="article_product_slug" onchange="this.form.submit()">
                    <?php foreach ($productArticleOptionSlugs as $articleSlug): ?>
                      <option value="<?= esc($articleSlug) ?>" <?= $articleSlug === $selectedProductArticleSlug ? 'selected' : '' ?>><?= esc((string) ($articleOptions[$articleSlug]['title'] ?? $articleSlug)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </div>
              <div class="admin-status-grid">
                <article class="admin-status-card">
                  <strong><?= esc((string) count($productPageSelectedSlugs)) ?></strong>
                  <span>Produkty v tomto clanku</span>
                </article>
                <article class="admin-status-card">
                  <strong><?= esc((string) $productPageReadyCount) ?></strong>
                  <span>Uplne hotove</span>
                </article>
                <article class="admin-status-card">
                  <strong><?= esc((string) $productPageMissingImageCount) ?></strong>
                  <span>Chyba obrazok</span>
                </article>
                <article class="admin-status-card">
                  <strong><?= esc((string) $productPageMissingClickCount) ?></strong>
                  <span>Chyba klik do obchodu</span>
                </article>
              </div>
              <?php if ($productPageActionRows === []): ?>
                <p class="admin-note">Tento clanok zatial nema v admine pripravene ziadne produkty.</p>
              <?php else: ?>
                <div class="admin-queue-list">
                  <?php foreach ($productPageActionRows as $productPageActionRow): ?>
                    <article class="admin-queue-item<?= (!empty($productPageActionRow['exists']) && !empty($productPageActionRow['packshot_ready']) && !empty($productPageActionRow['affiliate_ready'])) ? ' is-done' : '' ?>">
                      <div>
                        <strong><?= esc((string) ($productPageActionRow['name'] ?? '')) ?></strong>
                        <p><?= esc((string) ($productPageActionRow['slug'] ?? '')) ?></p>
                        <div class="admin-status-pills">
                          <span class="admin-status-pill<?= !empty($productPageActionRow['exists']) ? ' is-good' : ' is-warning' ?>"><?= !empty($productPageActionRow['exists']) ? 'Produkt hotovy' : 'Produkt chyba' ?></span>
                          <span class="admin-status-pill<?= !empty($productPageActionRow['packshot_ready']) ? ' is-good' : ' is-warning' ?>"><?= !empty($productPageActionRow['packshot_ready']) ? 'Obrazok hotovy' : 'Obrazok chyba' ?></span>
                          <span class="admin-status-pill<?= !empty($productPageActionRow['affiliate_ready']) ? ' is-good' : ' is-warning' ?>"><?= !empty($productPageActionRow['affiliate_ready']) ? 'Klik hotovy' : 'Klik chyba' ?></span>
                        </div>
                        <small class="admin-note"><?= esc((string) ($productPageActionRow['next_note'] ?? '')) ?></small>
                      </div>
                      <div class="admin-inline-actions">
                        <a class="btn btn-secondary btn-small" href="<?= esc((string) ($productPageActionRow['next_href'] ?? '#')) ?>"><?= esc((string) ($productPageActionRow['next_label'] ?? 'Otvorit')) ?></a>
                      </div>
                    </article>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </section>
            <?php endif; ?>

            <?php if (!$articleSlotMode && $returnSectionPrefill !== "" && $returnSlugPrefill !== ""): ?>
              <section class="admin-subsection is-compact">
                <p class="admin-note">Tento editor bol otvoreny z workflowu pre clanok <strong><?= esc($returnSlugPrefill) ?></strong>.</p>
                <div class="admin-inline-actions">
                  <a class="btn btn-secondary btn-small" href="/admin?section=<?= esc($returnSectionPrefill) ?>&amp;slug=<?= esc($returnSlugPrefill) ?>">Spat do workflowu clanku</a>
                </div>
              </section>
            <?php endif; ?>

            <section class="admin-subsection is-compact" id="product-main-flow">
              <div class="admin-subsection-head">
                <div>
                  <h3>Stav tohto produktu</h3>
                  <p class="admin-meta"><?= $articleSlotMode ? 'Toto je produkt, ktory sa po ulozeni priradi do vybraneho slotu.' : 'Tu vidis len vybrany produkt. Ked kliknes na produkt z clanku vyssie, otvoris prave tuto cast.' ?></p>
                </div>
              </div>
              <div class="admin-status-grid">
                <article class="admin-status-card">
                  <strong><?= trim((string) ($selectedProduct['name'] ?? '')) !== '' ? 'Ano' : 'Nie' ?></strong>
                  <span>Produkt je vytvoreny</span>
                </article>
                <article class="admin-status-card">
                  <strong><?= $selectedProductHasUsableSourceUrl ? 'Ano' : 'Nie' ?></strong>
                  <span>Pozname stranku produktu v obchode</span>
                </article>
                <article class="admin-status-card">
                  <strong><?= $selectedProductPackshotReady ? 'Ano' : 'Nie' ?></strong>
                  <span>Obrazok produktu je hotovy</span>
                </article>
                <article class="admin-status-card">
                  <strong><?= $selectedProductClickReady ? 'Ano' : 'Nie' ?></strong>
                  <span>Klik do obchodu je hotovy</span>
                </article>
              </div>
              <p class="admin-note"><strong>Co spravit dalej:</strong> <?= esc($selectedProductNextStepNote) ?></p>
            </section>

            <?php if (!$articleSlotMode): ?>
            <section class="admin-subsection is-compact">
              <div class="admin-subsection-head">
                <div>
                  <h3>Ako na produkt bez chaosu</h3>
                  <p class="admin-meta">Tu je len bezny postup. Zbytocne technicke veci su schovane nizsie.</p>
                </div>
              </div>
              <ol class="admin-quickstart-list">
                <li><strong>Krok 1:</strong> hore si vyber clanok a klikni na produkt, ktory este nie je hotovy.</li>
                <li><strong>Krok 2:</strong> pozri blok <strong>Co kliknut teraz</strong>. Tam mas vzdy len jeden dalsi krok.</li>
                <li><strong>Krok 3:</strong> vloz sem obycajny link produktu alebo Dognet link. Nemusis hladat dalsie polia.</li>
                <li><strong>Krok 4:</strong> po tomto kroku uz vznikne fungujuci klik do obchodu. Dognet je len volitelne vylepsenie neskor.</li>
                <li><strong>Krok 5:</strong> ak sa nasiel obrazok, admin ho skusi ulozit sam. Ked to nepojde, ponukne dalsi krok.</li>
              </ol>
              <p class="admin-note">Dolezite: bezny start je uz tu v Produktoch. Cast Odkazy do obchodov otvor len vtedy, ked chces neskor vymenit obycajny klik za Dognet link.</p>
              <div class="admin-inline-actions">
                <?php if ($selectedAffiliateCode !== ''): ?>
                  <a class="btn btn-secondary btn-small" href="/dognet-helper?code=<?= esc($selectedAffiliateCode) ?>">Otvorit Dognet pomocnika</a>
                <?php else: ?>
                  <a class="btn btn-secondary btn-small" href="#product-link-form">Najprv vlozit link produktu</a>
                <?php endif; ?>
                <a class="btn btn-secondary btn-small" href="#product-link-form">Vlozit link produktu</a>
              </div>
            </section>
            <?php endif; ?>

            <section id="product-link-form" class="admin-subsection is-compact">
              <div class="admin-subsection-head">
                <div>
                  <h3><?= $articleSlotMode ? 'Link produktu a klik do obchodu' : '1. Vloz link produktu' ?></h3>
                  <p class="admin-meta"><?= $articleSlotMode ? 'Sem vloz priamy link produktu alebo Dognet link. Tento krok pripravi produkt pre vybrany slot.' : 'Sem vloz bud priamu stranku produktu, alebo Dognet link. Admin sa pokusi sam doplnit zvysok.' ?></p>
                </div>
              </div>
              <form method="post" class="admin-form admin-form-stack">
                <input type="hidden" name="action" value="prepare_product_from_link" />
                <input type="hidden" name="product_slug" value="<?= esc($selectedProductSlug) ?>" />
                <input type="hidden" name="return_section" value="<?= esc($productReturnSection) ?>" />
                <input type="hidden" name="return_slug" value="<?= esc($productReturnSlug) ?>" />
                <?php if ($articleSlotMode): ?><input type="hidden" name="article_slug" value="<?= esc($returnArticlePrefill) ?>" /><?php endif; ?>
                <?php if ($articleSlotMode): ?><input type="hidden" name="target_slot" value="<?= esc((string) $returnArticleSlotPrefill) ?>" /><?php endif; ?>
                <label>
                  <span>Link produktu alebo Dognet link</span>
                  <input type="url" name="source_link" value="<?= esc($selectedProductQuickInputUrl) ?>" placeholder="https://go.dognet.com/... alebo https://obchod.sk/konkretny-produkt" />
                </label>
                <p class="admin-note">Sem patri bud Dognet link pre tento produkt, alebo priamo stranka produktu v obchode. Aj obycajny link produktu staci na rozbehnutie. Dognet mozes doplnit neskor.</p>
                <div class="admin-actions">
                  <button class="btn btn-cta" type="submit"><?= $articleSlotMode ? 'Pripravit produkt pre tento slot' : '1. Vlozit link a pripravit produkt aj klik' ?></button>
                </div>
              </form>
            </section>

            <section class="admin-subsection is-compact">
              <div class="admin-subsection-head">
                <div>
                  <h3>2. Co spravit teraz</h3>
                  <p class="admin-meta">Tu mas vzdy len jeden dalsi krok. Klikni len toto jedno tlacidlo.</p>
                </div>
              </div>
              <div class="admin-next-step-card">
                <strong><?= esc($selectedProductNextStepLabel) ?></strong>
                <p><?= esc($selectedProductNextStepNote) ?></p>
                <div class="admin-inline-actions">
                  <?php if ($selectedProductNextStep === 'enter_link'): ?>
                    <a class="btn btn-secondary btn-small" href="#product-link-form">1. Vlozit link produktu alebo Dognet link</a>
                  <?php elseif ($selectedProductNextStep === 'prepare'): ?>
                    <form method="post" class="admin-inline-form">
                      <input type="hidden" name="action" value="prepare_product_from_link" />
                      <input type="hidden" name="product_slug" value="<?= esc($selectedProductSlug) ?>" />
                      <input type="hidden" name="return_section" value="<?= esc($productReturnSection) ?>" />
                      <input type="hidden" name="return_slug" value="<?= esc($productReturnSlug) ?>" />
                      <?php if ($articleSlotMode): ?><input type="hidden" name="article_slug" value="<?= esc($returnArticlePrefill) ?>" /><?php endif; ?>
                      <?php if ($articleSlotMode): ?><input type="hidden" name="target_slot" value="<?= esc((string) $returnArticleSlotPrefill) ?>" /><?php endif; ?>
                      <input type="hidden" name="source_link" value="<?= esc($selectedProductQuickInputUrl) ?>" />
                      <button class="btn btn-secondary btn-small" type="submit">1. Pripravit produkt z ulozeneho linku</button>
                    </form>
                  <?php elseif ($selectedProductNextStep === 'enrich'): ?>
                    <form method="post" class="admin-inline-form">
                      <input type="hidden" name="action" value="enrich_product_from_source" />
                      <input type="hidden" name="product_slug" value="<?= esc($selectedProductSlug) ?>" />
                      <input type="hidden" name="return_section" value="<?= esc($productReturnSection) ?>" />
                      <input type="hidden" name="return_slug" value="<?= esc($productReturnSlug) ?>" />
                      <?php if ($articleSlotMode): ?><input type="hidden" name="article_slug" value="<?= esc($returnArticlePrefill) ?>" /><?php endif; ?>
                      <?php if ($articleSlotMode): ?><input type="hidden" name="target_slot" value="<?= esc((string) $returnArticleSlotPrefill) ?>" /><?php endif; ?>
                      <button class="btn btn-secondary btn-small" type="submit">2. Nacitat udaje z obchodu</button>
                    </form>
                  <?php elseif ($selectedProductNextStep === 'save_image'): ?>
                    <form method="post" class="admin-inline-form" data-remote-packshot-form="true">
                      <input type="hidden" name="action" value="mirror_packshot_from_remote" />
                      <input type="hidden" name="product_slug" value="<?= esc($selectedProductSlug) ?>" />
                      <input type="hidden" name="return_section" value="<?= esc($productReturnSection) ?>" />
                      <input type="hidden" name="return_slug" value="<?= esc($productReturnSlug) ?>" />
                      <?php if ($articleSlotMode): ?><input type="hidden" name="article_slug" value="<?= esc($returnArticlePrefill) ?>" /><?php endif; ?>
                      <?php if ($articleSlotMode): ?><input type="hidden" name="target_slot" value="<?= esc((string) $returnArticleSlotPrefill) ?>" /><?php endif; ?>
                      <button class="btn btn-secondary btn-small" type="submit">3. Ulozit obrazok z e-shopu</button>
                    </form>
                  <?php elseif ($selectedProductNextStep === 'affiliate'): ?>
                    <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;prefill_code=<?= esc((string) ($selectedProduct['slug'] ?? '')) ?>&amp;prefill_merchant=<?= esc((string) ($selectedProduct['merchant'] ?? '')) ?>&amp;prefill_merchant_slug=<?= esc((string) ($selectedProduct['merchant_slug'] ?? '')) ?>&amp;prefill_product_slug=<?= esc((string) ($selectedProduct['slug'] ?? '')) ?>">4. Dokoncit odkaz do obchodu</a>
                  <?php elseif ($selectedProductNextStep === 'dognet'): ?>
                    <a class="btn btn-secondary btn-small" href="/dognet-helper?code=<?= esc($selectedProductAffiliateCode) ?>">Otvorit Dognet pomocnika</a>
                  <?php else: ?>
                    <a class="btn btn-secondary btn-small" href="<?= esc((string) ($selectedProductTarget['href'] ?? '#')) ?>" target="_blank" rel="noopener">Otvorit aktualny produkt na webe</a>
                  <?php endif; ?>
                </div>
              </div>
            </section>

            <?php if (!$articleSlotMode): ?>
            <details class="admin-subsection is-compact">
              <summary><strong>Vytvorit produkt rucne</strong> - otvor len vtedy, ked produkt este v admine vobec neexistuje</summary>
              <div class="admin-subsection-head">
                <h3>Toto pouzi len ak produkt este neexistuje</h3>
              </div>
              <form method="post" class="admin-form admin-form-stack">
                <input type="hidden" name="action" value="create_product" />
                <input type="hidden" name="return_section" value="<?= esc((string) ($_GET['return_section'] ?? '')) ?>" />
                <input type="hidden" name="return_slug" value="<?= esc((string) ($_GET['return_slug'] ?? '')) ?>" />
                <div class="admin-grid three-up">
                  <label><span>Nazov produktu</span><input type="text" name="new_product_name" value="<?= esc($prefillNewProductName) ?>" placeholder="Napriklad GymBeam Magnesium Citrate" data-auto-slug-source="product" /></label>
                  <label><span>Slug</span><input type="text" name="new_product_slug" value="<?= esc($prefillNewProductSlug) ?>" placeholder="gymbeam-magnesium-citrate" data-auto-slug-target="product" /></label>
                  <label><span>Brand</span><input type="text" name="new_product_brand" value="<?= esc($prefillNewProductBrand) ?>" placeholder="GymBeam" /></label>
                </div>
                <div class="admin-grid three-up">
                  <label><span>Obchod / merchant</span><input type="text" name="new_product_merchant" value="<?= esc($prefillNewProductMerchant) ?>" placeholder="GymBeam" data-auto-slug-source="merchant" /></label>
                  <label><span>Merchant slug</span><input type="text" name="new_product_merchant_slug" value="<?= esc($prefillNewProductMerchantSlug) ?>" placeholder="gymbeam" data-auto-slug-target="merchant" /></label>
                  <label><span>Kategoria</span><input type="text" name="new_product_category" value="<?= esc($prefillNewProductCategory) ?>" placeholder="mineraly" /></label>
                </div>
                <div class="admin-grid two-up">
                  <label><span>Link produktu alebo priamy link na produkt</span><input type="url" name="new_product_fallback_url" value="<?= esc($prefillNewProductFallbackUrl) ?>" placeholder="https://merchant.example.com/produkt alebo Dognet link" /></label>
                  <label><span>Najdeny obrazok z obchodu (pokrocile)</span><input type="url" name="new_product_image_remote_src" value="<?= esc($prefillNewProductImageRemoteSrc) ?>" placeholder="https://merchant.example.com/image.webp" /></label>
                </div>
                <label><span>Kratky popis</span><textarea name="new_product_summary" rows="3" placeholder="Strucne zhrnutie produktu pre karty a odporucania"><?= esc($prefillNewProductSummary) ?></textarea></label>
                <label><span>Affiliate code (volitelne)</span><input type="text" name="new_product_affiliate_code" value="<?= esc($prefillNewProductAffiliateCode) ?>" placeholder="horcik-ktory-je-najlepsi-a-preco-gymbeam" /></label>
                  <small class="admin-note">Toto pouzi len vtedy, ked produkt este v admine neexistuje. Sem patri bud konkretna stranka produktu, alebo Dognet link pre ten produkt.</small>
                <div class="admin-actions">
                  <button class="btn btn-secondary" type="submit">Vytvorit produkt</button>
                </div>
              </form>
            </details>
            <?php endif; ?>

            <?php if (!$articleSlotMode): ?>
            <details class="admin-subsection">
              <summary><strong>Produkty bez hotoveho obrazka</strong> - otvor len ked chces doplnat obrazky po jednom</summary>
              <div class="admin-subsection-head">
                <div>
                  <h3>Produkty bez hotoveho obrazka</h3>
                  <p class="admin-meta">Tu robis len jednu vec: doplnis obrazok produktu. Najprv skus obrazok z e-shopu. Vlastny upload je len zaloha.</p>
                </div>
                <div class="admin-filter-pills">
                  <a class="admin-filter-pill<?= $productImageFilter === 'missing' ? ' is-active' : '' ?>" href="/admin?section=products&amp;product=<?= esc($selectedProductSlug) ?>&amp;product_image_filter=missing">Treba obrazok (<?= esc((string) $productImageQueueCounts['missing']) ?>)</a>
                  <a class="admin-filter-pill<?= $productImageFilter === 'remote' ? ' is-active' : '' ?>" href="/admin?section=products&amp;product=<?= esc($selectedProductSlug) ?>&amp;product_image_filter=remote">Nasiel sa obrazok (<?= esc((string) $productImageQueueCounts['remote']) ?>)</a>
                  <a class="admin-filter-pill<?= $productImageFilter === 'placeholder' ? ' is-active' : '' ?>" href="/admin?section=products&amp;product=<?= esc($selectedProductSlug) ?>&amp;product_image_filter=placeholder">Docasny obrazok (<?= esc((string) $productImageQueueCounts['placeholder']) ?>)</a>
                  <a class="admin-filter-pill<?= $productImageFilter === 'ready' ? ' is-active' : '' ?>" href="/admin?section=products&amp;product=<?= esc($selectedProductSlug) ?>&amp;product_image_filter=ready">Hotovo (<?= esc((string) $productImageQueueCounts['ready']) ?>)</a>
                  <a class="admin-filter-pill<?= $productImageFilter === 'all' ? ' is-active' : '' ?>" href="/admin?section=products&amp;product=<?= esc($selectedProductSlug) ?>&amp;product_image_filter=all">Vsetko (<?= esc((string) $productImageQueueCounts['all']) ?>)</a>
                </div>
              </div>
              <div class="admin-queue-list">
                <?php foreach ($productImageQueue as $queueRow): ?>
                  <?php
                    $queueFallbackUrl = trim((string) ($queueRow['fallback_url'] ?? ''));
                    $queueAffiliateCode = trim((string) ($queueRow['affiliate_code'] ?? ''));
                    $queueDerivedUrl = $queueAffiliateCode !== '' ? aff_product_url_for_code($queueAffiliateCode) : '';
                    $queueSourceUrl = '';
                    if ($queueFallbackUrl !== '' && interessa_admin_looks_like_product_url($queueFallbackUrl)) {
                        $queueSourceUrl = $queueFallbackUrl;
                    } elseif ($queueDerivedUrl !== '' && interessa_admin_looks_like_product_url($queueDerivedUrl)) {
                        $queueSourceUrl = $queueDerivedUrl;
                    }
                    $queueHasUsableSourceUrl = $queueSourceUrl !== '';
                    $queueRemoteSrc = trim((string) ($queueRow['remote_src'] ?? ''));
                    $queueNeedsLocal = !empty($queueRow['needs_local_packshot']);
                    $queueImageMode = trim((string) ($queueRow['image_mode'] ?? ''));
                    $queueImageModeLabel = 'bez obrazka';
                    if ($queueImageMode === 'placeholder') {
                        $queueImageModeLabel = 'docasny obrazok';
                    } elseif ($queueImageMode === 'remote') {
                        $queueImageModeLabel = 'nasiel sa obrazok z obchodu';
                    } elseif ($queueImageMode === 'local') {
                        $queueImageModeLabel = 'hotovy obrazok';
                    } elseif ($queueImageMode !== '') {
                        $queueImageModeLabel = $queueImageMode;
                    }
                  ?>
                  <article class="admin-queue-item<?= empty($queueRow['needs_local_packshot']) ? ' is-done' : '' ?>">
                    <div>
                      <strong><?= esc((string) $queueRow['name']) ?></strong>
                      <p><?= esc((string) $queueRow['slug']) ?><?php if ($queueRow['merchant'] !== ''): ?> / <?= esc((string) $queueRow['merchant']) ?><?php endif; ?></p>
                      <details class="admin-subsection is-compact">
                        <summary><strong>Technicke info</strong> - bezne netreba otvarat</summary>
                        <small class="admin-note">Kam sa obrazok ulozi: <code><?= esc((string) $queueRow['target_asset']) ?></code></small>
                      </details>
                    </div>
                    <div class="admin-queue-actions">
                      <span class="admin-note"><?= esc($queueImageModeLabel) ?></span>
                      <?php if (!$queueHasUsableSourceUrl): ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) $queueRow['slug']) ?>&amp;product_image_filter=<?= esc($productImageFilter) ?>#product-link-form">1. Vlozit link produktu</a>
                        <span class="admin-note">Sem patri bud Dognet link, alebo priamo link na tento produkt v obchode. Az potom admin vie hladat obrazok.</span>
                      <?php elseif ($queueNeedsLocal && $queueRemoteSrc === ''): ?>
                        <form method="post" class="admin-inline-form">
                          <input type="hidden" name="action" value="autofill_product_from_source" />
                          <input type="hidden" name="product_slug" value="<?= esc((string) ($queueRow['slug'] ?? '')) ?>" />
                          <button class="btn btn-secondary btn-small" type="submit">2. Nacitat udaje z obchodu</button>
                        </form>
                        <span class="admin-note">Admin sa teraz pokusi najst nazov, text a obrazok z obchodu.</span>
                      <?php elseif ($queueNeedsLocal && $queueRemoteSrc !== ''): ?>
                        <form method="post" class="admin-inline-form" data-remote-packshot-form="true">
                          <input type="hidden" name="action" value="mirror_packshot_from_remote" />
                          <input type="hidden" name="product_slug" value="<?= esc((string) ($queueRow['slug'] ?? '')) ?>" />
                          <button class="btn btn-secondary btn-small" type="submit">3. Ulozit obrazok z e-shopu</button>
                        </form>
                        <span class="admin-note">Obrazok sa uz nasiel. Teraz ho len ulozime k produktu.</span>
                      <?php elseif (trim((string) ($queueRow['affiliate_code'] ?? '')) === ''): ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;prefill_code=<?= esc((string) ($queueRow['slug'] ?? '')) ?>&amp;prefill_merchant=<?= esc((string) ($queueRow['merchant'] ?? '')) ?>&amp;prefill_merchant_slug=<?= esc((string) ($queueRow['merchant_slug'] ?? '')) ?>&amp;prefill_product_slug=<?= esc((string) ($queueRow['slug'] ?? '')) ?>">4. Dokoncit klik do obchodu</a>
                        <span class="admin-note">Produkt uz ma obrazok. Chyba uz len klikaci odkaz.</span>
                      <?php else: ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) $queueRow['slug']) ?>&amp;product_image_filter=<?= esc($productImageFilter) ?>&amp;focus=product_edit#product-edit-form">Hotovo - len skontrolovat</a>
                        <span class="admin-note">Tento produkt je uz skoro hotovy. Tu ho vies uz len skontrolovat.</span>
                      <?php endif; ?>
                    </div>
                    </div>
                  </article>

                <?php endforeach; ?>
              </div>
            </details>
            <?php endif; ?>
            <?php if (!$articleSlotMode): ?>
            <details class="admin-subsection is-compact">
              <summary><strong>Produkty bez odkazu do obchodu</strong> - otvor len ked chces dokoncit odkazy do obchodov</summary>
              <div class="admin-subsection-head">
                <div>
                  <h3>Produkty bez odkazu do obchodu</h3>
                  <p class="admin-meta">Toto ries az potom, ked je produkt a obrazok hotovy. Tu nastavujes kam clovek po kliknuti odide.</p>
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
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($queueRow['slug'] ?? '')) ?>&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>&amp;focus=product_edit#product-edit-form">Upravit produkt</a>
                        <?php if (trim((string) ($queueRow['affiliate_code'] ?? '')) === ''): ?>
                          <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;prefill_code=<?= esc((string) ($queueRow['slug'] ?? '')) ?>&amp;prefill_merchant=<?= esc((string) ($queueRow['merchant'] ?? '')) ?>&amp;prefill_merchant_slug=<?= esc(interessa_admin_slugify((string) ($queueRow['merchant'] ?? ''))) ?>&amp;prefill_product_slug=<?= esc((string) ($queueRow['slug'] ?? '')) ?>">Vytvorit odkaz do obchodu</a>
                        <?php endif; ?>
                        <?php if (trim((string) ($queueRow['affiliate_code'] ?? '')) !== ''): ?>
                          <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;code=<?= esc((string) ($queueRow['affiliate_code'] ?? '')) ?>">Upravit odkaz do obchodu</a>
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
            </details>
            <?php endif; ?>

            <?php if (!$articleSlotMode): ?>
            <details class="admin-subsection is-compact">
              <summary><strong>Co este pri produktoch chyba</strong> - otvor len ked chces doplnat texty a hodnotenia</summary>
              <div class="admin-subsection-head">
                <div>
                  <h3>Produkty, ktore este treba doplnit</h3>
                  <p class="admin-meta">Tu vidis, co este pri produkte chyba: text, hodnotenie, plusy, minusy, odkaz alebo obrazok.</p>
                </div>
                <span class="admin-note"><?= esc((string) $productQualityQueueCount) ?> zaznamov</span>
              </div>
              <?php if ($productQualityQueue === []): ?>
                <p class="admin-note">Produkty uz maju vyplneny popis, hodnotenie, plusy, minusy aj zakladne obchodne udaje.</p>
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
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($queueRow['slug'] ?? '')) ?>&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>&amp;focus=product_edit#product-edit-form">Otvorit doplnenie produktu</a>
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
            </details>
            <?php endif; ?>

            <section id="product-image-preview" class="admin-subsection admin-asset-preview<?= $focusPanel === 'product_image' ? ' is-focused' : '' ?>">
              <div class="admin-subsection-head">
                <h3>Co je pri tomto produkte uz hotove</h3>
              </div>
              <div class="admin-asset-preview__grid">
                <div class="admin-asset-preview__media">
                  <?= interessa_render_image($selectedProductImage, ['class' => 'admin-asset-preview__image']) ?>
                </div>
                <div class="admin-asset-preview__body">
                  <p><strong>Co sa teraz zobrazuje:</strong> <?= esc($selectedProductImageSourceLabel) ?></p>
                  <p><strong>Ulozeny obrazok u nas:</strong> <code><?= esc($selectedProductLocalAsset !== '' ? $selectedProductLocalAsset : 'zatial chyba') ?></code></p>
                  <p><strong>Kam sa ulozi hotovy obrazok:</strong> <code><?= esc((string) ($selectedProduct['image_target_asset'] ?? '')) ?></code></p>
                  <?php if ($selectedProductPackshotReady): ?>
                    <p class="admin-note">Toto je hotovy obrazok produktu, ktory sa zobrazi na webe.</p>
                  <?php elseif (trim((string) ($selectedProduct['image_remote_src'] ?? '')) !== ''): ?>
                      <p class="admin-note">Nasiel sa obrazok z obchodu. Teraz klikni <strong>3. Ulozit obrazok z e-shopu</strong>.</p>
                    <?php elseif (!$selectedProductHasUsableSourceUrl): ?>
                      <p class="admin-note">Najprv hore vloz <strong>link produktu alebo Dognet link</strong>. Az potom bude admin vediet hladat obrazok.</p>
                    <?php else: ?>
                      <p class="admin-note">Produkt este nema obrazok ulozeny u nas. Najprv klikni <strong>2. Nacitat udaje z obchodu</strong>.</p>
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
                    <?php if (!$articleSlotMode): ?>
                    <div class="admin-inline-actions">
                      <?php if ($prevMissingPackshotSlug !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc($prevMissingPackshotSlug) ?>&amp;product_image_filter=missing">Predchadzajuci produkt bez obrazka</a>
                      <?php endif; ?>
                      <?php if ($nextMissingPackshotSlug !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc($nextMissingPackshotSlug) ?>&amp;product_image_filter=missing">Dalsi produkt bez obrazka</a>
                      <?php endif; ?>
                    </div>
                    <?php endif; ?>
                  <?php endif; ?>
                  <p><strong>Najdeny obrazok z obchodu:</strong> <?= esc((string) ($selectedProduct['image_remote_src'] ?? '')) ?></p>
                  <?php if (trim((string) ($selectedProduct['image_remote_src'] ?? '')) !== ''): ?>
                    <div class="admin-inline-actions">
                      <a class="btn btn-secondary btn-small" href="<?= esc((string) ($selectedProduct['image_remote_src'] ?? '')) ?>" target="_blank" rel="noopener">Otvorit obrazok z e-shopu</a>
                      <?php if (!$selectedProductPackshotReady): ?>
                        <form method="post" class="admin-inline-form" data-remote-packshot-form="true">
                          <input type="hidden" name="action" value="mirror_packshot_from_remote" />
                          <input type="hidden" name="product_slug" value="<?= esc($selectedProductSlug) ?>" />
                          <input type="hidden" name="return_section" value="<?= esc($productReturnSection) ?>" />
                          <input type="hidden" name="return_slug" value="<?= esc($productReturnSlug) ?>" />
                          <?php if ($articleSlotMode): ?><input type="hidden" name="article_slug" value="<?= esc($returnArticlePrefill) ?>" /><?php endif; ?>
                          <?php if ($articleSlotMode): ?><input type="hidden" name="target_slot" value="<?= esc((string) $returnArticleSlotPrefill) ?>" /><?php endif; ?>
                          <button class="btn btn-secondary btn-small" type="submit">3. Ulozit obrazok z e-shopu</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                  <?php if ($selectedProductSourceUrl !== ''): ?>
                    <div class="admin-inline-actions">
                      <a class="btn btn-secondary btn-small" href="<?= esc($selectedProductSourceUrl) ?>" target="_blank" rel="noopener">Otvorit produkt v obchode</a>
                      <?php if ($selectedProductHasUsableSourceUrl): ?>
                        <form method="post" class="admin-inline-form">
                          <input type="hidden" name="action" value="enrich_product_from_source" />
                          <input type="hidden" name="product_slug" value="<?= esc($selectedProductSlug) ?>" />
                          <input type="hidden" name="return_section" value="<?= esc($productReturnSection) ?>" />
                          <input type="hidden" name="return_slug" value="<?= esc($productReturnSlug) ?>" />
                          <?php if ($articleSlotMode): ?><input type="hidden" name="article_slug" value="<?= esc($returnArticlePrefill) ?>" /><?php endif; ?>
                          <?php if ($articleSlotMode): ?><input type="hidden" name="target_slot" value="<?= esc((string) $returnArticleSlotPrefill) ?>" /><?php endif; ?>
                          <button class="btn btn-secondary btn-small" type="submit">2. Nacitat udaje z obchodu</button>
                        </form>
                      <?php else: ?>
                      <span class="admin-note">Ak admin este nema link produktu, vloz ho hore do bloku Najjednoduchsia cesta.</span>
                      <?php endif; ?>
                      <?php if (!$selectedProductPackshotReady && $selectedProductHasUsableSourceUrl): ?>
                        <form method="post" class="admin-inline-form">
                          <input type="hidden" name="action" value="autofill_product_from_source" />
                          <input type="hidden" name="product_slug" value="<?= esc($selectedProductSlug) ?>" />
                          <input type="hidden" name="return_section" value="<?= esc($productReturnSection) ?>" />
                          <input type="hidden" name="return_slug" value="<?= esc($productReturnSlug) ?>" />
                          <?php if ($articleSlotMode): ?><input type="hidden" name="article_slug" value="<?= esc($returnArticlePrefill) ?>" /><?php endif; ?>
                          <?php if ($articleSlotMode): ?><input type="hidden" name="target_slot" value="<?= esc((string) $returnArticleSlotPrefill) ?>" /><?php endif; ?>
                          <button class="btn btn-secondary btn-small" type="submit">Skusit doplnit produkt automaticky</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                  <details class="admin-subsection is-compact">
                    <summary><strong>Viac detailov o odkaze a produkte</strong> - otvor len ked to naozaj potrebujes</summary>
                    <div class="admin-diagnostic-list">
                      <p><strong>Klikaci kod:</strong> <?= esc((string) ($selectedProduct['affiliate_code'] ?? '')) ?: 'zatial chyba' ?></p>
                      <p><strong>Typ odkazu:</strong> <?= esc($selectedProductAffiliateType !== '' ? $selectedProductAffiliateType : 'neznamy') ?></p>
                      <p><strong>Odkial je zaznam:</strong> <?= esc((string) ($selectedProductAffiliate['source'] ?? 'bez zaznamu')) ?></p>
                      <p><strong>Kam teraz klik smeruje:</strong> <?= esc((string) ($selectedProductAffiliateUrl ?? $selectedProductSourceUrl)) ?></p>
                    </div>
                  </details>
                    <?php if (!$articleSlotMode && trim((string) ($selectedProduct['affiliate_code'] ?? '')) !== ''): ?>
                      <div class="admin-inline-actions">
                        <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;code=<?= esc((string) ($selectedProduct['affiliate_code'] ?? '')) ?>">Otvorit klikaci odkaz</a>
                        <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($selectedProduct['affiliate_code'] ?? '')) ?>">Kopirovat affiliate kod</button>
                      </div>
                    <?php elseif (!$articleSlotMode): ?>
                      <div class="admin-inline-actions">
                        <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;prefill_code=<?= esc((string) ($selectedProduct['slug'] ?? '')) ?>&amp;prefill_merchant=<?= esc((string) ($selectedProduct['merchant'] ?? '')) ?>&amp;prefill_merchant_slug=<?= esc((string) ($selectedProduct['merchant_slug'] ?? '')) ?>&amp;prefill_product_slug=<?= esc((string) ($selectedProduct['slug'] ?? '')) ?>">Vytvorit klikaci odkaz</a>
                      </div>
                    <?php endif; ?>
                  <?php if (!$articleSlotMode && trim((string) ($selectedProductTarget['href'] ?? '')) !== ''): ?>
                    <div class="admin-actions">
                      <a class="btn btn-secondary btn-small" href="<?= esc((string) $selectedProductTarget['href']) ?>" target="_blank" rel="noopener">Otvorit stranku, kam teraz smeruje klik</a>
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
                <p class="admin-meta">Toto je len zalozna cesta. Pouzi ju iba vtedy, ked obchod naozaj nema pouzitelny obrazok produktu.</p>
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

            <?php if (!$articleSlotMode): ?>
            <details class="admin-subsection is-compact">
              <summary><strong>Kde sa tento produkt pouziva</strong> - otvor len ked chces skontrolovat clanky</summary>
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
            </details>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="admin-form admin-form-stack">
              <input type="hidden" name="action" value="save_product" />
              <input type="hidden" name="return_section" value="<?= esc($productReturnSection) ?>" />
              <?php if ($productReturnSlug !== ""): ?><input type="hidden" name="return_slug" value="<?= esc($productReturnSlug) ?>" /><?php endif; ?>
              <?php if ($returnArticlePrefill !== ""): ?><input type="hidden" name="article_slug" value="<?= esc($returnArticlePrefill) ?>" /><?php endif; ?>
              <?php if ($returnArticleSlotPrefill > 0): ?><input type="hidden" name="target_slot" value="<?= esc((string) $returnArticleSlotPrefill) ?>" /><?php endif; ?>
              <div id="product-edit-form" class="admin-subsection is-compact<?= $focusPanel === 'product_edit' ? ' is-focused' : '' ?>">
                <div class="admin-subsection-head">
                  <div>
                    <h3><?= $articleSlotMode ? 'Ulozit produkt pre tento slot' : 'Tu doplnis produkt' ?></h3>
                    <p class="admin-meta"><?= $articleSlotMode ? 'Tu ulozis produktove udaje. Po ulozeni sa vratis priamo naspat na clanok a slot sa hned aktualizuje.' : 'Sem ta posielaju tlacidla vyssie. Najprv sem vlozis link produktu alebo Dognet link. Admin sa potom pokusi sam doplnit zvysok.' ?></p>
                    <?php if ($returnArticlePrefill !== '' && $returnArticleSlotPrefill > 0): ?>
                      <p class="admin-note"><strong>Po ulozeni sa tento produkt priradi do clanku <?= esc($returnArticlePrefill) ?> / Slot <?= esc((string) $returnArticleSlotPrefill) ?>.</strong></p>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="admin-flash is-success" style="margin-bottom:16px;"><?= $articleSlotMode ? 'Odporucany postup: dopln link produktu, nacitaj udaje z obchodu, uloz obrazok a potom produkt uloz naspat do clanku.' : 'Bezny postup: 1. vloz link produktu alebo Dognet link -> 2. klikni Ulozit produkt -> 3. klikni Nacitat udaje z obchodu -> 4. klikni Ulozit obrazok z e-shopu.' ?></div>
                <input type="hidden" name="product_slug" value="<?= esc((string) ($selectedProduct['slug'] ?? $selectedProductSlug)) ?>" />
                <input type="hidden" name="merchant_slug" value="<?= esc((string) ($selectedProduct['merchant_slug'] ?? '')) ?>" />
                <p class="admin-note"><strong>Kod produktu:</strong> <?= esc((string) ($selectedProduct['slug'] ?? $selectedProductSlug)) ?><?php if (trim((string) ($selectedProduct['merchant_slug'] ?? '')) !== ''): ?> / <strong>Kod obchodu:</strong> <?= esc((string) ($selectedProduct['merchant_slug'] ?? '')) ?><?php endif; ?></p>
                <div class="admin-grid three-up">
                  <label><span>Nazov produktu na webe</span><input type="text" name="name" value="<?= esc((string) ($selectedProduct['name'] ?? '')) ?>" /></label>
                  <label><span>Znacka</span><input type="text" name="brand" value="<?= esc((string) ($selectedProduct['brand'] ?? '')) ?>" /></label>
                  <label><span>Obchod</span><input type="text" name="merchant" value="<?= esc((string) ($selectedProduct['merchant'] ?? '')) ?>" /></label>
                </div>
                <div class="admin-grid one-up">
                  <label><span>Link produktu alebo priamy link na produkt</span><input type="url" name="fallback_url" value="<?= esc((string) ($selectedProduct['fallback_url'] ?? '')) ?>" placeholder="https://obchod.sk/konkretny-produkt alebo Dognet link" /></label>
                </div>
                <p class="admin-note">Sem mozes vlozit bud priamu stranku produktu v obchode, alebo Dognet link pre tento produkt. Ak admin link uz pozna, doplnit ho netreba.</p>
                <details class="admin-subsection is-compact">
                  <summary><strong>Dalsie nastavenia produktu</strong> - otvor len ked ich naozaj potrebujes</summary>
                  <div class="admin-grid three-up">
                    <label><span>Tema produktu</span><input type="text" name="category" value="<?= esc((string) ($selectedProduct['category'] ?? '')) ?>" /></label>
                    <label><span>Rating</span><input type="number" min="0" max="5" step="0.1" name="rating" value="<?= esc((string) ($selectedProduct['rating'] ?? '')) ?>" data-product-rating-input /></label>
                    <label><span>Kod klikacieho odkazu (/go/)</span><input type="text" name="affiliate_code" value="<?= esc((string) ($selectedProduct['affiliate_code'] ?? '')) ?>" /></label>
                  </div>
                  <div class="admin-grid one-up">
                    <label><span>Priama adresa obrazka (pokrocile, netreba bezne vyplnat)</span><input type="url" name="image_remote_src" value="<?= esc((string) ($selectedProduct['image_remote_src'] ?? '')) ?>" /></label>
                  </div>
                  <div class="admin-subsection-head">
                    <h3>Dalsie veci pre neskor</h3>
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
                  <div class="admin-subsection-head">
                    <h3>Pomocnik pre texty</h3>
                    <div class="admin-inline-actions">
                      <button class="btn btn-secondary btn-small" type="button" data-fill-product-empty>Iba doplnit prazdne</button>
                      <button class="btn btn-secondary btn-small" type="button" data-fill-product-summary>Starter summary</button>
                      <button class="btn btn-secondary btn-small" type="button" data-fill-product-pros>Starter plusy</button>
                      <button class="btn btn-secondary btn-small" type="button" data-fill-product-cons>Starter minusy</button>
                      <button class="btn btn-secondary btn-small" type="button" data-fill-product-all>Vyplnit vsetko</button>
                    </div>
                  </div>
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
                </details>
              <details class="admin-subsection is-compact">
                <summary><strong>Rucne nahrat vlastny obrazok</strong> - pouzi len ked obrazok z obchodu nefunguje</summary>
                <div class="admin-grid one-up">
                  <label>
                    <span>Vyber vlastny obrazok produktu</span>
                    <input type="file" name="product_image" accept="image/webp,image/png,image/jpeg" />
                    <small class="admin-note">Toto je zalozny krok. Bezne najprv skus obrazok z obchodu.</small>
                  </label>
                </div>
              </details>
              <div class="admin-actions">
                <button class="btn btn-cta" type="submit"><?= $articleSlotMode ? 'Ulozit produkt a vratit sa do clanku' : 'Ulozit produkt' ?></button>
                <?php if ($articleSlotMode): ?>
                  <a class="btn btn-secondary" href="<?= esc($articleSlotBackHref) ?>">Spat na clanok bez ulozenia</a>
                <?php else: ?>
                  <button class="btn btn-secondary" type="submit" name="action" value="delete_product_override" onclick="return confirm('Naozaj zmazat admin override produktu?');">Zmazat override produktu</button>
                <?php endif; ?>
              </div>
              </div>
            </form>
          <?php if ($articleSlotMode): ?>
          </section>
          <?php else: ?>
          </details>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($section === 'images'): ?>
          <section class="admin-card">
            <div class="admin-card-head">
              <div>
                <p class="admin-kicker">Obrazky</p>
                <h2>Obrazky pre clanok</h2>
              <p class="admin-note">Na tejto stranke riesis 3 veci: hlavny obrazok clanku, obrazok temy a obrazky produktov. Pri produktoch najprv pouzi obrazok z e-shopu. Manualny upload je az zalozny krok.</p>
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

            <section class="admin-subsection is-compact">
              <div class="admin-subsection-head">
                <div>
                  <h3>Stav tem pred launchom</h3>
                  <p class="admin-meta">Toto je rychly prehlad, kolko z 12 tem uz ma hotove vlastne obrazky.</p>
                </div>
              </div>
                <div class="admin-status-grid">
                  <article class="admin-status-card">
                    <strong><?= esc((string) $themeFullyReadyCount) ?> / <?= esc((string) $themeManifestTotal) ?></strong>
                    <span>Temy komplet hotove</span>
                    <small>Hlavny aj mensi obrazok</small>
                </article>
                <article class="admin-status-card">
                  <strong><?= esc((string) $themeHeroReadyCount) ?> / <?= esc((string) $themeManifestTotal) ?></strong>
                  <span>Hlavny obrazok temy hotovy</span>
                  <small><?= esc((string) $themeHeroMissingCount) ?> este chyba</small>
                </article>
                <article class="admin-status-card">
                  <strong><?= esc((string) $themeThumbReadyCount) ?> / <?= esc((string) $themeManifestTotal) ?></strong>
                    <span>Mensi obrazok temy hotovy</span>
                    <small><?= esc((string) $themeThumbMissingCount) ?> este chyba</small>
                  </article>
                </div>
                <?php if ($firstMissingThemeSlug !== ''): ?>
                  <div class="admin-inline-actions">
                    <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>&amp;topic=<?= esc($firstMissingThemeSlug) ?>&amp;image_filter=<?= esc($imageFilter) ?>">Otvorit prvu temu bez obrazka</a>
                    <?php if ($nextMissingThemeSlug !== ''): ?>
                      <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>&amp;topic=<?= esc($nextMissingThemeSlug) ?>&amp;image_filter=<?= esc($imageFilter) ?>">Dalsia tema bez obrazka</a>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </section>

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
                  <p class="admin-note">Tu klikaj len v tomto poradi: 1. Skopiruj text pre Canvu. 2. V Canve sprav obrazok. 3. Nahraj obrazok sem. 4. Po nahrati sa clanok sam otvori na webe. Admin pri nahrati sam upravi rozmer na 1200 x 800.</p>
                  <div class="admin-inline-actions">
                    <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($articlePrompt['prompt'] ?? '')) ?>">1. Kopirovat text pre Canvu</button>
                    <a class="btn btn-secondary btn-small" href="<?= esc(article_url($selectedArticleSlug)) ?>" target="_blank" rel="noopener">4. Otvorit clanok na webe</a>
                  </div>
                  <form method="post" action="/admin" enctype="multipart/form-data" class="admin-form admin-form-stack">
                    <input type="hidden" name="action" value="upload_hero_only" />
                    <input type="hidden" name="slug" value="<?= esc($selectedArticleSlug) ?>" />
                    <input type="hidden" name="hero_crop_mode" value="center" />
                    <label>
                      <span>2. Vyber hotovy obrazok z Canvy</span>
                      <input type="file" name="hero_image" accept="image/webp,image/png,image/jpeg" required />
                    </label>
                    <section class="admin-crop-picker" data-hero-crop-picker hidden>
                      <div class="admin-crop-picker__head">
                        <strong>Vyber najlepsi vyrez obrazka</strong>
                        <small>Po vybrati obrazka klikni na ten nahlad, ktory vyzera najlepsie.</small>
                      </div>
                      <div class="admin-crop-picker__grid">
                        <button class="admin-crop-option is-selected" type="button" data-crop-mode="center">
                          <span class="admin-crop-option__preview" data-crop-preview="center"></span>
                          <span class="admin-crop-option__label">Na stred</span>
                        </button>
                        <button class="admin-crop-option" type="button" data-crop-mode="top">
                          <span class="admin-crop-option__preview" data-crop-preview="top"></span>
                          <span class="admin-crop-option__label">Drzat vrch</span>
                        </button>
                        <button class="admin-crop-option" type="button" data-crop-mode="bottom">
                          <span class="admin-crop-option__preview" data-crop-preview="bottom"></span>
                          <span class="admin-crop-option__label">Drzat spodok</span>
                        </button>
                      </div>
                    </section>
                    <button class="btn btn-cta" type="submit">3. Nahraj obrazok a otvor clanok</button>
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
                <div class="admin-grid two-up">
                  <article class="admin-brief-card">
                    <h3>Co ma tento obrazok komunikovat</h3>
                    <ul class="admin-quickstart-list">
                      <li><strong>Styl:</strong> <?= esc((string) ($selectedThemeDirection['style'] ?? '')) ?></li>
                      <li><strong>Farba:</strong> <?= esc((string) ($selectedThemeDirection['accent'] ?? '')) ?></li>
                      <li><strong>Motiv:</strong> <?= esc((string) ($selectedThemeDirection['motif'] ?? '')) ?></li>
                      <li><strong>Co ma clovek citit:</strong> <?= esc((string) ($selectedThemeDirection['message'] ?? '')) ?></li>
                    </ul>
                  </article>
                  <article class="admin-brief-card">
                    <h3>Jednoduche pravidlo pre Canvu</h3>
                    <ul class="admin-quickstart-list">
                      <li>Hlavny motiv nech je velky a jasny.</li>
                      <li>Nedavaj text priamo do obrazka.</li>
                      <li>Nenechavaj tvar ani produkt prilis pri hornom okraji.</li>
                      <li>Ak si nie si isty, drz hlavny motiv v strede obrazu.</li>
                    </ul>
                  </article>
                </div>
              </section>

              <section class="admin-subsection admin-asset-preview">
              <div class="admin-subsection-head">
                <div>
                  <h3>Obrazok temy</h3>
                  <p class="admin-meta">Toto je hlavny obrazok pre temu na homepage a na stranke temy. Postup je rovnaky ako pri clanku: skopiruj text, vyber obrazok, nahraj ho a otvor temu na webe.</p>
                </div>
                <form method="get" action="/admin" class="admin-inline-form">
                  <input type="hidden" name="section" value="images" />
                  <input type="hidden" name="slug" value="<?= esc($selectedArticleSlug) ?>" />
                  <select name="topic" onchange="this.form.submit()">
                    <?php foreach ($categoryOptions as $themeSlug => $themeItem): ?>
                      <option value="<?= esc((string) $themeSlug) ?>" <?= (string) $themeSlug === $selectedThemeSlug ? 'selected' : '' ?>><?= esc((string) ($themeItem['title'] ?? $themeSlug)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </div>
              <div class="admin-asset-preview__grid">
                <div class="admin-asset-preview__media">
                  <?= interessa_render_image($selectedThemeImage, ['class' => 'admin-asset-preview__image']) ?>
                </div>
                <div class="admin-asset-preview__body">
                  <p><strong>Tema:</strong> <?= esc((string) ($selectedThemeMeta['title'] ?? '')) ?></p>
                  <?php if (trim((string) ($selectedThemeMeta['description'] ?? '')) !== ''): ?>
                    <p><?= esc((string) ($selectedThemeMeta['description'] ?? '')) ?></p>
                  <?php endif; ?>
                  <p><strong>Odkial je obrazok:</strong> <?= esc($selectedThemeImageSource) ?></p>
                  <p><strong>Aktualny subor:</strong> <code><?= esc($selectedThemeLocalAsset !== '' ? $selectedThemeLocalAsset : (string) ($selectedThemeImage['asset'] ?? '')) ?></code></p>
                  <p><strong>Kam sa ulozi:</strong> <code><?= esc((string) ($selectedThemePrompt['asset_path'] ?? '')) ?></code></p>
                  <p class="admin-note">Tu klikaj len v tomto poradi: 1. Skopiruj text pre Canvu. 2. V Canve sprav obrazok. 3. Nahraj obrazok sem. 4. Po nahrati sa tema sama otvori na webe. Admin pri nahrati sam upravi rozmer na 1600 x 900.</p>
                  <div class="admin-inline-actions">
                    <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($selectedThemePrompt['prompt'] ?? '')) ?>">1. Kopirovat text pre Canvu</button>
                    <a class="btn btn-secondary btn-small" href="<?= esc(category_url($selectedThemeSlug)) ?>" target="_blank" rel="noopener">4. Otvorit temu na webe</a>
                  </div>
                  <form method="post" action="/admin" enctype="multipart/form-data" class="admin-form admin-form-stack">
                    <input type="hidden" name="action" value="upload_category_image_only" />
                    <input type="hidden" name="category_slug" value="<?= esc($selectedThemeSlug) ?>" />
                    <input type="hidden" name="category_variant" value="hero" />
                    <input type="hidden" name="category_crop_mode" value="center" />
                    <label>
                      <span>2. Vyber hotovy obrazok z Canvy</span>
                      <input type="file" name="category_image" accept="image/webp,image/png,image/jpeg" required />
                    </label>
                    <section class="admin-crop-picker" data-hero-crop-picker hidden>
                      <div class="admin-crop-picker__head">
                        <strong>Vyber najlepsi vyrez obrazka</strong>
                        <small>Po vybrati obrazka klikni na ten nahlad, ktory vyzera najlepsie.</small>
                      </div>
                      <div class="admin-crop-picker__grid">
                        <button class="admin-crop-option is-selected" type="button" data-crop-mode="center">
                          <span class="admin-crop-option__preview" data-crop-preview="center"></span>
                          <span class="admin-crop-option__label">Na stred</span>
                        </button>
                        <button class="admin-crop-option" type="button" data-crop-mode="top">
                          <span class="admin-crop-option__preview" data-crop-preview="top"></span>
                          <span class="admin-crop-option__label">Drzat vrch</span>
                        </button>
                        <button class="admin-crop-option" type="button" data-crop-mode="bottom">
                          <span class="admin-crop-option__preview" data-crop-preview="bottom"></span>
                          <span class="admin-crop-option__label">Drzat spodok</span>
                        </button>
                      </div>
                    </section>
                    <button class="btn btn-cta" type="submit">3. Nahraj obrazok a otvor temu</button>
                  </form>
                  <?php if ($selectedThemeQueuePosition > 0): ?>
                    <p class="admin-note">Zostava spravit: <?= esc((string) $selectedThemeQueuePosition) ?> / <?= esc((string) count($missingThemeSlugs)) ?></p>
                  <?php endif; ?>
                  <?php if ($prevMissingThemeSlug !== '' || $nextMissingThemeSlug !== ''): ?>
                    <div class="admin-inline-actions">
                      <?php if ($prevMissingThemeSlug !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>&amp;topic=<?= esc($prevMissingThemeSlug) ?>&amp;image_filter=<?= esc($imageFilter) ?>">Predchadzajuca tema bez obrazka</a>
                      <?php endif; ?>
                      <?php if ($nextMissingThemeSlug !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>&amp;topic=<?= esc($nextMissingThemeSlug) ?>&amp;image_filter=<?= esc($imageFilter) ?>">Dalsia tema bez obrazka</a>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </section>

            <section class="admin-subsection admin-asset-preview">
              <div class="admin-subsection-head">
                <div>
                  <h3>Mensi obrazok temy</h3>
                  <p class="admin-meta">Toto je druhy variant pre karty, mensie nahlady a neskorsie pouzitie na webe. Zatial ho pripravujeme v admine, aby bol launch-ready bez chaosu.</p>
                </div>
              </div>
              <div class="admin-asset-preview__grid">
                <div class="admin-asset-preview__media">
                  <?= interessa_render_image($selectedThemeThumbImage, ['class' => 'admin-asset-preview__image']) ?>
                </div>
                <div class="admin-asset-preview__body">
                  <p><strong>Tema:</strong> <?= esc((string) ($selectedThemeMeta['title'] ?? '')) ?></p>
                  <p><strong>Odkial je obrazok:</strong> <?= esc($selectedThemeThumbImageSource) ?></p>
                  <p><strong>Aktualny subor:</strong> <code><?= esc($selectedThemeThumbLocalAsset !== '' ? $selectedThemeThumbLocalAsset : (string) ($selectedThemeThumbImage['asset'] ?? '')) ?></code></p>
                  <p><strong>Kam sa ulozi:</strong> <code><?= esc((string) ($selectedThemeThumbPrompt['asset_path'] ?? '')) ?></code></p>
                  <p class="admin-note">Toto je mensi stvorcovy variant. Pouzi ho vtedy, ked chces mat pre temu aj cistejsi obrazok do karticiek a mensich blokov. Admin pri nahrati sam upravi rozmer na 1200 x 1200.</p>
                  <div class="admin-inline-actions">
                    <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($selectedThemeThumbPrompt['prompt'] ?? '')) ?>">1. Kopirovat text pre Canvu</button>
                    <a class="btn btn-secondary btn-small" href="<?= esc(category_url($selectedThemeSlug)) ?>" target="_blank" rel="noopener">Otvorit temu na webe</a>
                  </div>
                  <form method="post" action="/admin" enctype="multipart/form-data" class="admin-form admin-form-stack">
                    <input type="hidden" name="action" value="upload_category_image_only" />
                    <input type="hidden" name="category_slug" value="<?= esc($selectedThemeSlug) ?>" />
                    <input type="hidden" name="category_variant" value="thumb" />
                    <label>
                      <span>2. Vyber hotovy obrazok z Canvy</span>
                      <input type="file" name="category_thumb_image" accept="image/webp,image/png,image/jpeg" required />
                    </label>
                    <button class="btn btn-cta" type="submit">3. Nahraj mensi obrazok temy</button>
                  </form>
                  <p class="admin-note">Poznamka: ak chceme tento mensi variant aj realne zobrazovat na webe, bude to uz mala nasledna uloha pre web vlakno.</p>
                </div>
              </div>
            </section>

            <section class="admin-subsection is-compact">
              <div class="admin-subsection-head">
                <div>
                  <h3>Checklist assetov pre tuto temu</h3>
                  <p class="admin-meta">Minimalny launch-ready standard pre jednu temu su 2 subory: hlavny obrazok a mensi obrazok. Hlavny obrazok je povinny, mensi obrazok je odporucany.</p>
                </div>
              </div>
              <div class="admin-brief-grid">
                <article class="admin-brief-card">
                  <h3><?= esc((string) ($selectedThemeMeta['title'] ?? 'Tema')) ?></h3>
                  <ul class="admin-quickstart-list">
                    <li><strong>Hlavny obrazok temy</strong>: 1600x900, <code><?= esc((string) ($selectedThemePrompt['asset_path'] ?? '')) ?></code></li>
                    <li><strong>Mensi obrazok temy</strong>: 1200x1200, <code><?= esc((string) ($selectedThemeThumbPrompt['asset_path'] ?? '')) ?></code></li>
                    <li><strong>Canva naming</strong>: pouzivaj nazvy <code><?= esc((string) ($selectedThemePrompt['file_name'] ?? '')) ?></code> a <code><?= esc((string) ($selectedThemeThumbPrompt['file_name'] ?? '')) ?></code></li>
                    <li><strong>Jednoduche pravidlo</strong>: hlavny obrazok robi temu, mensi obrazok robi kartu.</li>
                  </ul>
                </article>
                <article class="admin-brief-card">
                  <h3>Stav tejto temy</h3>
                  <div class="admin-status-pills">
                    <span class="admin-status-pill<?= $selectedThemeLocalAsset !== '' ? ' is-good' : ' is-warning' ?>"><?= $selectedThemeLocalAsset !== '' ? 'Hlavny obrazok hotovy' : 'Hlavny obrazok chyba' ?></span>
                    <span class="admin-status-pill<?= $selectedThemeThumbLocalAsset !== '' ? ' is-good' : ' is-warning' ?>"><?= $selectedThemeThumbLocalAsset !== '' ? 'Mensi obrazok hotovy' : 'Mensi obrazok chyba' ?></span>
                  </div>
                  <p class="admin-note">Ak chces spravit temu uplne hotovu, dorob oba varianty. Ak chces ist najrychlejsie na launch, priorita je hlavny obrazok temy.</p>
                </article>
              </div>
            </section>

            <?php if ($selectedArticlePackshotGaps !== []): ?>
              <section class="admin-subsection is-compact">
                <div class="admin-subsection-head">
                  <div>
                    <h3>Produkty bez obrazka v tomto clanku</h3>
                    <p class="admin-meta">Ak vidis "Obrazok pripraveny", je hotovo. Ak vidis "Obrazok chyba", klikni najprv na najdenie obrazka z e-shopu. Vlastny upload pouzi az ako poslednu moznost. Admin pri nahrati sam upravi rozmer na 1200 x 1200.</p>
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
                        <?php
                          $queueFallbackUrl = trim((string) ($queueRow['fallback_url'] ?? ''));
                          $queueAffiliateCode = trim((string) ($queueRow['affiliate_code'] ?? ''));
                          $queueDerivedUrl = $queueAffiliateCode !== '' ? aff_product_url_for_code($queueAffiliateCode) : '';
                          $queueSourceUrl = '';
                          if ($queueFallbackUrl !== '' && interessa_admin_looks_like_product_url($queueFallbackUrl)) {
                              $queueSourceUrl = $queueFallbackUrl;
                          } elseif ($queueDerivedUrl !== '' && interessa_admin_looks_like_product_url($queueDerivedUrl)) {
                              $queueSourceUrl = $queueDerivedUrl;
                          }
                        ?>
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($queueRow['slug'] ?? '')) ?>&amp;product_image_filter=missing&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>&amp;focus=product_edit#product-edit-form">Otvorit produkt</a>
                        <?php if ($queueSourceUrl !== ''): ?>
                          <form method="post" class="admin-inline-form">
                              <input type="hidden" name="action" value="autofill_product_from_source" />
                              <input type="hidden" name="product_slug" value="<?= esc((string) ($queueRow['slug'] ?? '')) ?>" />
                              <input type="hidden" name="return_section" value="images" />
                              <input type="hidden" name="return_slug" value="<?= esc($selectedArticleSlug) ?>" />
                              <button class="btn btn-secondary btn-small" type="submit">1. Nacitat data z e-shopu</button>
                            </form>
                        <?php else: ?>
                          <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($queueRow['slug'] ?? '')) ?>&amp;product_image_filter=missing&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>#product-link-form">1. Vlozit link produktu</a>
                        <?php endif; ?>
                        <?php if ($queueSourceUrl !== ''): ?>
                          <a class="btn btn-secondary btn-small" href="<?= esc($queueSourceUrl) ?>" target="_blank" rel="noopener">Otvorit produkt v obchode</a>
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
                        <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc($previewSlug) ?>&amp;return_section=images&amp;return_slug=<?= esc($selectedArticleSlug) ?>&amp;focus=product_edit#product-edit-form">Otvorit produkt</a>
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
                  <li>Exportuj PNG, JPG alebo WebP. Presny rozmer nemusis trafit.</li>
                  <li>Ak chces, pouzi odporucany nazov suboru.</li>
                  <li>Nahraj obrazok sem. Admin ho automaticky prevedie na WebP a upravi na 1200 x 800.</li>
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
                <h2>Clanky bez vlastneho hlavneho obrazka</h2>
                <p class="admin-note">Tu riesis len clanky, ktore este nemaju vlastny obrazok clanku. Fallback temy sa pocita zvlast, nie ako hotovy obrazok clanku.</p>
              </div>
              <div class="admin-filter-pills">
                <a class="admin-filter-pill<?= $imageFilter === 'missing' ? ' is-active' : '' ?>" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>&amp;image_filter=missing">Naozaj chyba (<?= esc((string) $imageQueueCounts['missing']) ?>)</a>
                <a class="admin-filter-pill<?= $imageFilter === 'theme-fallback' ? ' is-active' : '' ?>" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>&amp;image_filter=theme-fallback">Len fallback temy (<?= esc((string) $imageQueueCounts['theme_fallback']) ?>)</a>
                <a class="admin-filter-pill<?= $imageFilter === 'article' ? ' is-active' : '' ?>" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>&amp;image_filter=article">Vlastny obrazok (<?= esc((string) $imageQueueCounts['article']) ?>)</a>
                <a class="admin-filter-pill<?= $imageFilter === 'all' ? ' is-active' : '' ?>" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>&amp;image_filter=all">Vsetko (<?= esc((string) $imageQueueCounts['all']) ?>)</a>
              </div>
            </div>
            <div class="admin-queue-list">
              <?php foreach ($imageQueue as $queueRow): ?>
                <article class="admin-queue-item<?= !empty($queueRow['has_article_image']) ? ' is-done' : '' ?>">
                  <div>
                    <strong><?= esc((string) $queueRow['title']) ?></strong>
                    <p><?= esc((string) $queueRow['slug']) ?> / <?= esc((string) ($queueRow['image_state_label'] ?? 'naozaj chyba')) ?></p>
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

        <?php if ($section === 'images'): ?>
          <section class="admin-card">
            <div class="admin-card-head">
              <div>
                <p class="admin-kicker">Temy webu</p>
                <h2>Temy bez vlastneho obrazka</h2>
                <p class="admin-note">Tu vidis 12 hlavnych tem webu. Pri kazdej teme je ciel mat vlastny finalny obrazok, nie len docasny fallback.</p>
              </div>
            </div>
            <div class="admin-queue-list">
              <?php foreach ($allThemeImageQueue as $themeRow): ?>
                <article class="admin-queue-item<?= !empty($themeRow['has_local_theme_image']) ? ' is-done' : '' ?>">
                  <div>
                    <strong><?= esc((string) ($themeRow['title'] ?? '')) ?></strong>
                    <p><?= esc((string) ($themeRow['slug'] ?? '')) ?> / <?= !empty($themeRow['has_local_theme_image']) ? 'vlastny obrazok' : 'docasny fallback' ?></p>
                    <code><?= esc((string) ($themeRow['asset_path'] ?? '')) ?></code>
                    <small class="admin-note"><?= esc((string) ($themeRow['file_name'] ?? '')) ?> / <?= esc((string) ($themeRow['dimensions'] ?? '1600x900')) ?></small>
                  </div>
                  <div class="admin-queue-actions">
                    <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>&amp;topic=<?= esc((string) ($themeRow['slug'] ?? '')) ?>&amp;image_filter=<?= esc($imageFilter) ?>">Otvorit tuto temu</a>
                    <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($themeRow['prompt'] ?? '')) ?>">Skopirovat text pre Canvu</button>
                    <a class="btn btn-secondary btn-small" href="<?= esc((string) ($themeRow['theme_url'] ?? '#')) ?>" target="_blank" rel="noopener">Otvorit temu na webe</a>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="admin-card">
            <div class="admin-card-head">
              <div>
                <p class="admin-kicker">Launch checklist</p>
                <h2>Minimalny balik suborov pre vsetky temy</h2>
                <p class="admin-note">Tu mas uplne jednoduchy prehlad: co ma mat kazda tema pripravene a ako sa to ma volat. Nemusis si to pamatat, admin ti to ukazuje priamo.</p>
              </div>
            </div>
            <div class="admin-brief-table-wrap">
              <table class="admin-brief-table">
                <thead>
                  <tr>
                    <th>Tema</th>
                    <th>Subor</th>
                    <th>Rozmer</th>
                    <th>Stav</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($themeAssetManifest as $themeManifestRow): ?>
                    <?php foreach ((array) ($themeManifestRow['items'] ?? []) as $index => $assetItem): ?>
                      <tr>
                        <td>
                          <?php if ($index === 0): ?>
                            <strong><?= esc((string) ($themeManifestRow['title'] ?? '')) ?></strong><br>
                            <small><?= esc((string) ($themeManifestRow['slug'] ?? '')) ?></small>
                          <?php endif; ?>
                        </td>
                        <td>
                          <strong><?= esc((string) ($assetItem['label'] ?? '')) ?></strong><br>
                          <code><?= esc((string) ($assetItem['asset_path'] ?? '')) ?></code>
                        </td>
                        <td><?= esc((string) ($assetItem['dimensions'] ?? '')) ?></td>
                        <td>
                          <span class="admin-status-pill<?= !empty($assetItem['ready']) ? ' is-good' : ' is-warning' ?>">
                            <?= !empty($assetItem['ready']) ? 'Hotovo' : (!empty($assetItem['required']) ? 'Chyba - dolezite' : 'Chyba - odporucane') ?>
                          </span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>
        <?php endif; ?>

        <?php if ($section === 'brand'): ?>
          <section class="admin-card">
            <div class="admin-card-head">
              <div>
                <p class="admin-kicker">Logo a ikonka stranky</p>
                <h2>Logo a ikonka</h2>
                <p class="admin-note">Tu vyriesis tri veci: hlavne logo, malu ikonku stranky a obrazok pri zdielani. Vzdy najprv skopiruj zadanie do Canvy, potom nahraj hotovy subor.</p>
              </div>
            </div>

            <section class="admin-subsection">
              <div class="admin-subsection-head">
                <div>
                  <h3>Hlavne logo</h3>
                  <p class="admin-meta">Toto je logo v hlavicke webu. Nahraj sem hotove logo z Canvy alebo od grafika.</p>
                </div>
              </div>
              <div class="admin-brief-grid">
                <div class="admin-brief-card">
                  <h3>Aktualne logo</h3>
                  <div class="admin-asset-preview__media">
                    <?= interessa_render_image($brandLogoImage, ['class' => 'admin-asset-preview__image']) ?>
                  </div>
                  <p><strong>Aktivny subor:</strong><br><code><?= esc((string) ($brandLogoImage['asset'] ?? 'img/brand/logo-full.svg')) ?></code></p>
                </div>
                <div class="admin-brief-card">
                  <h3>Text pre Canvu</h3>
                  <p><?= esc((string) ($brandPromptLibrary['logo']['note'] ?? '')) ?></p>
                  <div class="admin-inline-actions">
                    <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($brandPromptLibrary['logo']['note'] ?? '')) ?>">Skopirovat zadanie</button>
                  </div>
                  <form method="post" action="/admin" enctype="multipart/form-data" class="admin-form admin-form-stack admin-inline-upload">
                    <input type="hidden" name="action" value="save_brand_logo" />
                    <label>
                      <span>Vyber hotove logo</span>
                      <input type="file" name="brand_logo_file" accept=".svg,image/svg+xml,image/png,image/jpeg,image/webp" required />
                    </label>
                    <div class="admin-actions">
                      <button class="btn btn-cta" type="submit">Nahraj hlavne logo</button>
                    </div>
                  </form>
                </div>
              </div>
            </section>

            <section class="admin-subsection">
              <div class="admin-subsection-head">
                <div>
                  <h3>Ikonka stranky</h3>
                  <p class="admin-meta">Sem nahraj jeden zdrojovy obrazok. Admin z neho sam pripravi malu ikonku pre prehliadac aj mobil.</p>
                </div>
              </div>
              <div class="admin-brief-grid">
                <div class="admin-brief-card">
                  <h3>Aktualne male verzie</h3>
                  <div class="admin-brand-preview-grid">
                    <div class="admin-brand-preview-tile">
                      <strong>Logo icon</strong>
                      <div class="admin-asset-preview__media">
                        <?= interessa_render_image($brandIconImage, ['class' => 'admin-asset-preview__image']) ?>
                      </div>
                    </div>
                    <div class="admin-brand-preview-tile">
                      <strong>Favicon 32</strong>
                      <div class="admin-asset-preview__media">
                        <?php if ($brandFaviconAsset !== ''): ?>
                          <img class="admin-asset-preview__image" src="<?= esc(asset($brandFaviconAsset)) ?>" alt="Favicon 32" />
                        <?php else: ?>
                          <div class="admin-note">Zatial chyba</div>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="admin-brand-preview-tile">
                      <strong>Ikona pre mobil</strong>
                      <div class="admin-asset-preview__media">
                        <?php if ($brandAppleTouchAsset !== ''): ?>
                          <img class="admin-asset-preview__image" src="<?= esc(asset($brandAppleTouchAsset)) ?>" alt="Apple touch icon" />
                        <?php else: ?>
                          <div class="admin-note">Zatial chyba</div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="admin-brief-card">
                  <h3>Text pre Canvu</h3>
                  <p><?= esc((string) ($brandPromptLibrary['icon']['note'] ?? '')) ?></p>
                  <div class="admin-inline-actions">
                    <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($brandPromptLibrary['icon']['note'] ?? '')) ?>">Skopirovat zadanie</button>
                  </div>
                  <form method="post" action="/admin" enctype="multipart/form-data" class="admin-form admin-form-stack" data-brand-icon-form="true">
                    <input type="hidden" name="action" value="save_brand_icon_bundle" />
                    <label>
                      <span>Vyber zdrojovy obrazok pre ikonku</span>
                      <input type="file" name="brand_icon_source" accept="image/png,image/jpeg,image/webp" required />
                    </label>
                    <p class="admin-note">Staci jeden cisty stvorcovy obrazok. Admin z neho pripravi male verzie automaticky.</p>
                    <div class="admin-actions">
                      <button class="btn btn-cta" type="submit">Nahraj ikonku a priprav male verzie</button>
                    </div>
                  </form>
                </div>
              </div>
            </section>

            <section class="admin-subsection">
              <div class="admin-subsection-head">
                <div>
                  <h3>Obrazok pri zdielani</h3>
                  <p class="admin-meta">Toto je obrazok, ktory sa ukaze pri zdielani webu. Hodil sa aj ako zaklad pre uvodny obrazok hlavnej stranky.</p>
                </div>
              </div>
              <div class="admin-brief-grid">
                <div class="admin-brief-card">
                  <h3>Aktualny obrazok</h3>
                  <div class="admin-asset-preview__media">
                    <?= interessa_render_image($brandOgImage, ['class' => 'admin-asset-preview__image']) ?>
                  </div>
                  <p><strong>Aktivny subor:</strong><br><code><?= esc((string) ($brandOgImage['asset'] ?? 'img/brand/og-default.svg')) ?></code></p>
                </div>
                <div class="admin-brief-card">
                  <h3>Text pre Canvu</h3>
                  <p><?= esc((string) ($brandPromptLibrary['og']['note'] ?? '')) ?></p>
                  <div class="admin-inline-actions">
                    <button class="btn btn-secondary btn-small" type="button" data-copy-value="<?= esc((string) ($brandPromptLibrary['og']['note'] ?? '')) ?>">Skopirovat zadanie</button>
                  </div>
                  <form method="post" action="/admin" enctype="multipart/form-data" class="admin-form admin-form-stack admin-inline-upload">
                    <input type="hidden" name="action" value="save_brand_og_default" />
                    <label>
                      <span>Vyber hotovy obrazok pri zdielani</span>
                      <input type="file" name="brand_og_file" accept=".svg,image/svg+xml,image/png,image/jpeg,image/webp" required />
                    </label>
                    <div class="admin-actions">
                      <button class="btn btn-cta" type="submit">Nahraj obrazok pri zdielani</button>
                    </div>
                  </form>
                </div>
              </div>
            </section>
          </section>
<?php endif; ?>

<?php if ($section === 'affiliates'): ?>
          <section class="admin-card">
            <div class="admin-card-head">
              <div>
                <p class="admin-kicker">Affiliate management</p>
                <h2>Odkazy do obchodov</h2>
                <p class="admin-note">Tu riesis len jednu vec: kam clovek odide po kliknuti z tlacidla, obrazka alebo odkazu na webe.</p>
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
                    <h3>Skontroluj tento odkaz</h3>
                    <p class="admin-meta">Pred ulozenim si over, ze odkaz smeruje na spravny produkt a spravny obchod.</p>
                  </div>
                </div>
                <div class="admin-help-grid">
                  <?php if ($selectedAffiliateProduct !== null): ?>
                    <article class="admin-help-card">
                      <h3>Produkt na webe</h3>
                      <p><strong><?= esc((string) ($selectedAffiliateProduct['name'] ?? '')) ?></strong></p>
                      <p class="admin-note"><?= esc((string) ($selectedAffiliateProduct['slug'] ?? '')) ?><?php if (trim((string) ($selectedAffiliateProduct['merchant'] ?? '')) !== ''): ?> / <?= esc((string) ($selectedAffiliateProduct['merchant'] ?? '')) ?><?php endif; ?></p>
                      <div class="admin-inline-actions">
                      <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($selectedAffiliateProduct['slug'] ?? '')) ?>">Upravit produkt</a>
                        <?php if (trim((string) ($selectedAffiliateProduct['fallback_url'] ?? '')) !== ''): ?>
                          <a class="btn btn-secondary btn-small" href="<?= esc((string) ($selectedAffiliateProduct['fallback_url'] ?? '')) ?>" target="_blank" rel="noopener">Produkt v obchode</a>
                        <?php endif; ?>
                      </div>
                    </article>
                  <?php endif; ?>
                  <article class="admin-help-card">
                      <h3>Kam clovek po kliknuti odide</h3>
                      <p class="admin-note">Najprv si skontroluj finalny link do obchodu a potom aj interny /go/ odkaz.</p>
                    <?php if (trim((string) ($selectedAffiliate['url'] ?? '')) !== ''): ?>
                      <p><code><?= esc((string) ($selectedAffiliate['url'] ?? '')) ?></code></p>
                    <?php endif; ?>
                    <div class="admin-inline-actions">
                      <?php if ($selectedAffiliateCode !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="/go/<?= rawurlencode($selectedAffiliateCode) ?>" target="_blank" rel="noopener">Otvorit interny /go/ odkaz</a>
                      <?php endif; ?>
                      <?php if (is_string($selectedAffiliateResolvedTarget) && trim($selectedAffiliateResolvedTarget) !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="<?= esc($selectedAffiliateResolvedTarget) ?>" target="_blank" rel="noopener">Otvorit finalny link do obchodu</a>
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
                <div>
                  <h3>Ako na odkaz do obchodu bez chaosu</h3>
                  <p class="admin-meta">Tu riesis kam clovek realne odide po kliknuti z clanku, tlacidla alebo obrazka.</p>
                </div>
              </div>
              <ol class="admin-quickstart-list">
                <li>Vyber alebo vytvor interny <strong>/go/ odkaz</strong>.</li>
                <li>Do pola <strong>Finalny odkaz do obchodu</strong> vloz finalny Dognet link alebo iny finalny odkaz.</li>
                <li>Skontroluj nazov obchodu a pripadne produkt.</li>
                <li>Na zaver klikni <strong>Otvorit interny /go/ odkaz</strong> a over, kam clovek realne odide.</li>
              </ol>
            </section>

            <details class="admin-subsection is-compact">
              <summary><strong>Vytvorit novy odkaz rucne</strong> - otvor len ked odkaz este vobec neexistuje</summary>
              <div class="admin-subsection-head">
                <h3>Vytvorit novy odkaz rucne</h3>
              </div>
              <form method="post" class="admin-form admin-form-stack">
                <input type="hidden" name="action" value="create_affiliate" />
                <?php if ($returnSectionPrefill !== ""): ?><input type="hidden" name="return_section" value="<?= esc($returnSectionPrefill) ?>" /><?php endif; ?>
                <?php if ($returnSlugPrefill !== ""): ?><input type="hidden" name="return_slug" value="<?= esc($returnSlugPrefill) ?>" /><?php endif; ?>
                <div class="admin-grid three-up">
                  <label><span>Interny kod odkazu</span><input type="text" name="new_affiliate_code" value="<?= esc($prefillAffiliateCode) ?>" placeholder="kolagen-recenzia-gymbeam" /></label>
                  <label><span>Obchod</span><input type="text" name="new_affiliate_merchant" value="<?= esc($prefillAffiliateMerchant) ?>" placeholder="GymBeam" /></label>
                  <label><span>Kod obchodu</span><input type="text" name="new_affiliate_merchant_slug" value="<?= esc($prefillAffiliateMerchantSlug) ?>" placeholder="gymbeam" /></label>
                </div>
                <div class="admin-grid two-up">
                  <label><span>Kod produktu</span><input type="text" name="new_affiliate_product_slug" value="<?= esc($prefillAffiliateProductSlug) ?>" placeholder="gymbeam-collagen-complex" /></label>
                  <label><span>Typ odkazu</span>
                    <select name="new_affiliate_link_type">
                      <option value="affiliate">affiliate</option>
                      <option value="product">product</option>
                    </select>
                  </label>
                </div>
                <div class="admin-actions">
                  <button class="btn btn-secondary" type="submit">Vytvorit odkaz do obchodu</button>
                </div>
              </form>
            </details>

            <form method="post" class="admin-form admin-form-stack">
              <input type="hidden" name="action" value="save_affiliate" />
              <div class="admin-subsection-head">
                <div>
                  <h3>Sem vloz finalny odkaz do obchodu</h3>
                  <p class="admin-meta">Ak uz mas hotovy Dognet link, tu ho len vlozis a ulozis. Ostatne polia su len pomocne.</p>
                </div>
              </div>
              <?php if ($returnSectionPrefill !== ""): ?><input type="hidden" name="return_section" value="<?= esc($returnSectionPrefill) ?>" /><?php endif; ?>
              <?php if ($returnSlugPrefill !== ""): ?><input type="hidden" name="return_slug" value="<?= esc($returnSlugPrefill) ?>" /><?php endif; ?>
              <div class="admin-grid two-up">
                <label><span>Interny kod odkazu</span><input type="text" name="code" value="<?= esc((string) ($selectedAffiliate['code'] ?? $selectedAffiliateCode)) ?>" /></label>
                <label><span>Typ odkazu</span>
                  <select name="link_type">
                    <?php $linkType = (string) ($selectedAffiliate['link_type'] ?? 'affiliate'); ?>
                    <option value="affiliate" <?= $linkType === 'affiliate' ? 'selected' : '' ?>>affiliate</option>
                    <option value="product" <?= $linkType === 'product' ? 'selected' : '' ?>>product</option>
                  </select>
                </label>
              </div>
              <label><span>Finalny odkaz do obchodu</span><input type="url" name="url" value="<?= esc((string) ($selectedAffiliate['url'] ?? '')) ?>" /></label>
              <div class="admin-grid three-up">
                <label><span>Obchod</span><input type="text" name="merchant" value="<?= esc((string) ($selectedAffiliate['merchant'] ?? '')) ?>" /></label>
                <label><span>Kod obchodu</span><input type="text" name="merchant_slug" value="<?= esc((string) ($selectedAffiliate['merchant_slug'] ?? '')) ?>" /></label>
                <label><span>Kod produktu</span><input type="text" name="product_slug" value="<?= esc((string) ($selectedAffiliate['product_slug'] ?? '')) ?>" /></label>
              </div>
              <div class="admin-actions">
                <button class="btn btn-cta" type="submit">Ulozit odkaz do obchodu</button>
                <?php if ($selectedAffiliateCode !== ''): ?>
                  <a class="btn btn-secondary" href="/go/<?= rawurlencode($selectedAffiliateCode) ?>" target="_blank" rel="noopener">Otvorit interny /go/ odkaz</a>
                <?php endif; ?>
                <button class="btn btn-secondary" type="submit" name="action" value="delete_affiliate_override" onclick="return confirm('Naozaj zmazat admin override odkazu?');">Zmazat tuto upravu</button>
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
                  <strong><?= esc((string) $imageQueueCounts['needs_article']) ?></strong>
                  <span>Clanky bez vlastneho obrazka</span>
                  <small><a href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>&amp;image_filter=missing">Otvorit Images</a></small>
                </article>
                <article class="admin-status-card">
                  <strong><?= esc((string) $productImageQueueCounts['missing']) ?></strong>
                  <span>Produktove obrazky este chyba</span>
                  <small><a href="/admin?section=products&amp;product=<?= esc($selectedProductSlug) ?>&amp;product_image_filter=missing">Otvorit Produkty</a></small>
                </article>
                <article class="admin-status-card">
                  <strong><?= esc((string) $productAffiliateQueueCount) ?></strong>
                  <span>Odkazy do obchodov treba dorobit</span>
                  <small><a href="/admin?section=affiliates&amp;code=<?= esc($selectedAffiliateCode) ?>">Otvorit odkazy</a></small>
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
                    <a class="btn btn-secondary btn-small" href="/admin?section=images&amp;slug=<?= esc($selectedArticleSlug) ?>">Obrazky clanku</a>
                  </div>
                </article>
                <article class="admin-help-card">
                  <h3>Doplnit odkaz do obchodu</h3>
                  <p class="admin-note">Finalny Dognet link alebo iny finalny odkaz do obchodu patri sem.</p>
                  <div class="admin-inline-actions">
                    <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;code=<?= esc($selectedAffiliateCode) ?>">Odkazy do obchodov</a>
                    <?php if ($selectedAffiliateCode !== ''): ?><a class="btn btn-secondary btn-small" href="/go/<?= rawurlencode($selectedAffiliateCode) ?>" target="_blank" rel="noopener">Otvorit /go/ odkaz</a><?php endif; ?>
                  </div>
                </article>
                <?php if ($productAffiliateQueue !== []): ?>
                  <article class="admin-help-card">
                    <h3>Odkazy do obchodov, ktore este chyba doplnit</h3>
                    <p class="admin-note">Tu zacni, ked chces len dobehnut chybajuce odkazy do obchodov.</p>
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
                              <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;code=<?= esc((string) ($queueRow['affiliate_code'] ?? '')) ?>">Upravit odkaz</a>
                            <?php else: ?>
                              <a class="btn btn-secondary btn-small" href="/admin?section=affiliates&amp;prefill_code=<?= esc((string) ($queueRow['slug'] ?? '')) ?>&amp;prefill_merchant=<?= esc((string) ($queueRow['merchant'] ?? '')) ?>&amp;prefill_merchant_slug=<?= esc(interessa_admin_slugify((string) ($queueRow['merchant'] ?? ''))) ?>&amp;prefill_product_slug=<?= esc((string) ($queueRow['slug'] ?? '')) ?>">Vytvorit odkaz</a>
                            <?php endif; ?>
                            <a class="btn btn-secondary btn-small" href="/admin?section=products&amp;product=<?= esc((string) ($queueRow['slug'] ?? '')) ?>">Upravit produkt</a>
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
                    <li>Ak uz produkt ma najdeny obrazok z obchodu, klikni <strong>2. Ulozit obrazok z e-shopu</strong>.</li>
                  <li>Manualny upload pouzi iba ako fallback, ked remote obrazok nie je k dispozicii.</li>
                  <li>Vrat sa na clanok a skontroluj, ci karta uz ukazuje finalny produktovy obrazok.</li>
                </ol>
              </article>

              <article class="admin-help-card">
                <h3>4. Chcem doplnit Dognet link</h3>
                <ol class="admin-quickstart-list">
                  <li>Otvor <a href="/admin?section=affiliates&code=<?= esc($selectedAffiliateCode) ?>">Odkazy do obchodov</a>.</li>
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
                      <small>Tieto produkty uz maju najdeny obrazok z e-shopu, takze ich vies zvycajne dokoncit jednym klikom cez <strong>2. Ulozit obrazok z e-shopu</strong>.</small>
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

  .admin-asset-preview.is-focused,
  #product-edit-form.is-focused {
    outline: 3px solid #34d399;
    box-shadow: 0 20px 40px rgba(52, 211, 153, 0.18);
    border-radius: 20px;
  }

  .admin-brand-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
  }

  .admin-brand-preview-tile {
    display: flex;
    flex-direction: column;
    gap: 10px;
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

    function cropAnchorForMode(mode) {
      const normalizedMode = String(mode || 'center').toLowerCase();
      if (normalizedMode === 'top') {
        return 0.1;
      }
      if (normalizedMode === 'bottom') {
        return 0.9;
      }
      return 0.5;
    }

    function resolveHeroCropMode(form) {
      if (!(form instanceof HTMLFormElement)) {
        return 'center';
      }

      const input = form.querySelector('input[name="hero_crop_mode"], input[name="category_crop_mode"]');
      return input instanceof HTMLInputElement ? String(input.value || 'center').toLowerCase() : 'center';
    }

    function resolveHeroCropAnchor(form) {
      return cropAnchorForMode(resolveHeroCropMode(form));
    }

    function createCropPreviewDataUrl(image, mode) {
      const canvas = drawImageCoverToCanvas(image, 360, 240, {
        cropAnchorY: cropAnchorForMode(mode)
      });
      return canvas.toDataURL('image/jpeg', 0.88);
    }

    async function refreshHeroCropPreviews(form, file) {
      if (!(form instanceof HTMLFormElement) || !(file instanceof File)) {
        return;
      }

      const picker = form.querySelector('[data-hero-crop-picker]');
      if (!(picker instanceof HTMLElement)) {
        return;
      }

      const image = await loadImageFromFile(file);
      ['center', 'top', 'bottom'].forEach(function (mode) {
        const preview = picker.querySelector('[data-crop-preview="' + mode + '"]');
        if (preview instanceof HTMLElement) {
          preview.style.backgroundImage = 'url(\"' + createCropPreviewDataUrl(image, mode) + '\")';
        }
      });

      picker.hidden = false;
      updateHeroCropSelection(form, resolveHeroCropMode(form));
    }

    function updateHeroCropSelection(form, mode) {
      if (!(form instanceof HTMLFormElement)) {
        return;
      }

      const normalizedMode = ['center', 'top', 'bottom'].includes(String(mode || '').toLowerCase())
        ? String(mode).toLowerCase()
        : 'center';
      const input = form.querySelector('input[name="hero_crop_mode"]');
      if (input instanceof HTMLInputElement) {
        input.value = normalizedMode;
      }

      form.querySelectorAll('.admin-crop-option').forEach(function (button) {
        if (!(button instanceof HTMLElement)) {
          return;
        }
        button.classList.toggle('is-selected', button.getAttribute('data-crop-mode') === normalizedMode);
      });
    }

    function bindHeroCropPicker(form) {
      if (!(form instanceof HTMLFormElement)) {
        return;
      }

      const fileInput = form.querySelector('input[type="file"][name="hero_image"], input[type="file"][name="category_image"]');
      if (!(fileInput instanceof HTMLInputElement)) {
        return;
      }

      updateHeroCropSelection(form, resolveHeroCropMode(form));

      fileInput.addEventListener('change', function () {
        const selectedFile = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        if (!(selectedFile instanceof File)) {
          return;
        }

        refreshHeroCropPreviews(form, selectedFile).catch(function (error) {
          console.error(error);
          showToast('Nepodarilo sa pripravit nahlad vyrezu obrazka.', true);
        });
      });

      form.querySelectorAll('.admin-crop-option').forEach(function (button) {
        button.addEventListener('click', function () {
          updateHeroCropSelection(form, button.getAttribute('data-crop-mode') || 'center');
        });
      });
    }

    function resolveUploadTarget(fileInput) {
      const name = fileInput && typeof fileInput.name === 'string' ? fileInput.name : '';
      if (name === 'hero_image') {
        const form = fileInput instanceof HTMLInputElement ? fileInput.closest('form') : null;
        return {
          width: 1200,
          height: 800,
          label: 'hlavny obrazok clanku',
          cropAnchorY: resolveHeroCropAnchor(form)
        };
      }
      if (name === 'category_thumb_image') {
        return {
          width: 1200,
          height: 1200,
          label: 'mensi obrazok temy',
          cropAnchorY: 0.5
        };
      }
      if (name === 'category_image') {
        const form = fileInput instanceof HTMLInputElement ? fileInput.closest('form') : null;
        return {
          width: 1600,
          height: 900,
          label: 'obrazok temy',
          cropAnchorY: resolveHeroCropAnchor(form)
        };
      }

      return {
        width: 1200,
        height: 1200,
        label: 'obrazok produktu',
        cropAnchorY: 0.5
      };
    }

    function drawImageCoverToCanvas(image, targetWidth, targetHeight, options) {
      const sourceWidth = image.naturalWidth || image.width || 0;
      const sourceHeight = image.naturalHeight || image.height || 0;
      if (sourceWidth <= 0 || sourceHeight <= 0) {
        throw new Error('image-size-missing');
      }

      const cropAnchorY = options && Number.isFinite(Number(options.cropAnchorY))
        ? Math.max(0, Math.min(1, Number(options.cropAnchorY)))
        : 0.5;

      const canvas = document.createElement('canvas');
      canvas.width = targetWidth;
      canvas.height = targetHeight;
      const context = canvas.getContext('2d', { alpha: true });
      if (!context) {
        throw new Error('canvas-context-failed');
      }

      const sourceRatio = sourceWidth / sourceHeight;
      const targetRatio = targetWidth / targetHeight;
      let cropWidth = sourceWidth;
      let cropHeight = sourceHeight;
      let cropX = 0;
      let cropY = 0;

      if (sourceRatio > targetRatio) {
        cropWidth = Math.round(sourceHeight * targetRatio);
        cropX = Math.round((sourceWidth - cropWidth) / 2);
      } else if (sourceRatio < targetRatio) {
        cropHeight = Math.round(sourceWidth / targetRatio);
        cropY = Math.round((sourceHeight - cropHeight) * cropAnchorY);
      }

      context.clearRect(0, 0, targetWidth, targetHeight);
      context.drawImage(
        image,
        cropX,
        cropY,
        cropWidth,
        cropHeight,
        0,
        0,
        targetWidth,
        targetHeight
      );

      return canvas;
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

    async function createWebpFileFromUpload(file, target) {
      if (!(file instanceof File)) {
        throw new Error('missing-upload-file');
      }

      const image = await loadImageFromFile(file);
      const normalizedTarget = target && Number(target.width) > 0 && Number(target.height) > 0
        ? {
            width: Number(target.width),
            height: Number(target.height),
            cropAnchorY: Number.isFinite(Number(target.cropAnchorY)) ? Number(target.cropAnchorY) : 0.5
          }
        : {
            width: image.naturalWidth || image.width,
            height: image.naturalHeight || image.height,
            cropAnchorY: 0.5
          };
      const canvas = drawImageCoverToCanvas(image, normalizedTarget.width, normalizedTarget.height, normalizedTarget);
      const blob = await canvasToVerifiedWebpBlob(canvas);
      return new File([blob], renameToWebp(file.name), {
        type: 'image/webp',
        lastModified: Date.now()
      });
    }

    function drawImageContainToCanvas(image, targetWidth, targetHeight, options) {
      const canvas = document.createElement('canvas');
      canvas.width = targetWidth;
      canvas.height = targetHeight;

      const context = canvas.getContext('2d', { alpha: true });
      if (!context) {
        return canvas;
      }

      context.clearRect(0, 0, targetWidth, targetHeight);

      const sourceWidth = image.naturalWidth || image.width || targetWidth;
      const sourceHeight = image.naturalHeight || image.height || targetHeight;
      const scale = Math.min(targetWidth / sourceWidth, targetHeight / sourceHeight);
      const drawWidth = Math.max(1, Math.round(sourceWidth * scale));
      const drawHeight = Math.max(1, Math.round(sourceHeight * scale));
      const offsetX = Math.round((targetWidth - drawWidth) / 2);
      const offsetY = Math.round((targetHeight - drawHeight) / 2);

      context.imageSmoothingEnabled = true;
      context.imageSmoothingQuality = 'high';
      context.drawImage(image, offsetX, offsetY, drawWidth, drawHeight);
      return canvas;
    }

    async function createPngFileFromUpload(file, width, height, name) {
      if (!(file instanceof File)) {
        throw new Error('missing-upload-file');
      }

      const image = await loadImageFromFile(file);
      const canvas = drawImageContainToCanvas(image, width, height, {});
      const blob = await canvasToBlob(canvas, 'image/png');
      return new File([blob], name, {
        type: 'image/png',
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
      const fileInput = form.querySelector('input[type="file"][name="hero_image"], input[type="file"][name="category_image"], input[type="file"][name="category_thumb_image"], input[type="file"][name="product_image"]');
      const selectedFile = fileInput && fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
      if (!(selectedFile instanceof File)) {
        showToast('Najprv vyber obrazok.', true);
        return;
      }

      const uploadTarget = resolveUploadTarget(fileInput);

      setFormBusy(form, true);

      try {
        const webpFile = await createWebpFileFromUpload(selectedFile, uploadTarget);
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
        const webpFile = await createWebpFileFromUpload(remoteFile, {
          width: 1200,
          height: 1200,
          label: 'obrazok produktu'
        });
        const formData = new FormData();
        const productSlugInput = form.querySelector('input[name="product_slug"]');
        const returnSectionInput = form.querySelector('input[name="return_section"]');
        const returnSlugInput = form.querySelector('input[name="return_slug"]');
        const articleSlugInput = form.querySelector('input[name="article_slug"]');
        const targetSlotInput = form.querySelector('input[name="target_slot"]');

        formData.set('action', 'upload_packshot_only');
        formData.set('product_slug', productSlugInput instanceof HTMLInputElement ? productSlugInput.value : '');
        if (returnSectionInput instanceof HTMLInputElement && returnSectionInput.value) {
          formData.set('return_section', returnSectionInput.value);
        }
        if (returnSlugInput instanceof HTMLInputElement && returnSlugInput.value) {
          formData.set('return_slug', returnSlugInput.value);
        }
        if (articleSlugInput instanceof HTMLInputElement && articleSlugInput.value) {
          formData.set('article_slug', articleSlugInput.value);
        }
        if (targetSlotInput instanceof HTMLInputElement && targetSlotInput.value) {
          formData.set('target_slot', targetSlotInput.value);
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

    async function submitBrandIconForm(form) {
      const fileInput = form.querySelector('input[type="file"][name="brand_icon_source"]');
      if (!(fileInput instanceof HTMLInputElement) || !fileInput.files || !fileInput.files.length) {
        showToast('Najprv vyber obrazok.', true);
        return;
      }

      const sourceFile = fileInput.files[0];
      setFormBusy(form, true);

      try {
        const logoIconFile = await createPngFileFromUpload(sourceFile, 512, 512, 'logo-icon.png');
        const favicon32File = await createPngFileFromUpload(sourceFile, 32, 32, 'favicon-32.png');
        const favicon48File = await createPngFileFromUpload(sourceFile, 48, 48, 'favicon-48.png');
        const appleTouchFile = await createPngFileFromUpload(sourceFile, 180, 180, 'apple-touch-icon.png');

        const formData = new FormData();
        formData.set('action', 'save_brand_icon_bundle');
        formData.set('logo_icon', logoIconFile, logoIconFile.name);
        formData.set('favicon_32', favicon32File, favicon32File.name);
        formData.set('favicon_48', favicon48File, favicon48File.name);
        formData.set('apple_touch_icon', appleTouchFile, appleTouchFile.name);

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
      const fileInput = form.querySelector('input[type="file"][name="hero_image"], input[type="file"][name="category_image"], input[type="file"][name="category_thumb_image"], input[type="file"][name="product_image"]');
      if (!(fileInput instanceof HTMLInputElement)) {
        return;
      }
      const actionInput = form.querySelector('input[name="action"]');
      const actionValue = actionInput instanceof HTMLInputElement ? actionInput.value : '';
      const shouldHandleInlineUpload = form.classList.contains('admin-inline-upload')
        || ['upload_hero_only', 'upload_category_image_only', 'upload_packshot_only', 'save_brand_logo', 'save_brand_og_default'].includes(actionValue);

      if (!shouldHandleInlineUpload) {
        return;
      }

      if (fileInput.name === 'hero_image' || fileInput.name === 'category_image') {
        bindHeroCropPicker(form);
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

    document.querySelectorAll('form[data-brand-icon-form="true"]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        event.stopPropagation();

        submitBrandIconForm(form).catch(function (error) {
          console.error(error);
          showToast('Ikonku sa nepodarilo pripravit.', true);
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

    const focusedProductEdit = document.getElementById('product-edit-form');
    if (focusedProductEdit instanceof HTMLElement && focusedProductEdit.classList.contains('is-focused')) {
      window.setTimeout(function () {
        focusedProductEdit.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 80);
    }

    const params = new URLSearchParams(window.location.search);
    const savedState = params.get('saved') || '';
    const savedTargetMap = {
      'candidate-imported': 'products-current-candidate',
      'candidate-click': 'products-current-candidate',
      'candidate-assignment': 'products-current-candidate',
      'candidate-approved': 'products-current-candidate'
    };
    const savedTargetId = savedTargetMap[savedState] || '';
    if (savedTargetId !== '') {
      const savedTarget = document.getElementById(savedTargetId);
      if (savedTarget instanceof HTMLElement) {
        window.setTimeout(function () {
          savedTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 120);
      }
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
<?php endif; ?>
<?php require dirname(__DIR__) . '/inc/footer.php'; ?>




