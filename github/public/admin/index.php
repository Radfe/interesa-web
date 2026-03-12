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
    return in_array($section, ['articles', 'products', 'images', 'affiliates', 'tools'], true) ? $section : 'articles';
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
        $hasLocalPackshot = $targetAsset !== '' && (($assetPath = interessa_asset_file_path($targetAsset)) !== null) && is_file($assetPath);
        $rows[] = [
            'slug' => (string) ($normalized['slug'] ?? $slug),
            'name' => interessa_admin_clean_label((string) ($normalized['name'] ?? $slug)),
            'merchant' => (string) ($normalized['merchant'] ?? ''),
            'affiliate_code' => (string) ($normalized['affiliate_code'] ?? ''),
            'image_mode' => $mode,
            'target_asset' => $targetAsset,
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
                $slug = interessa_admin_slugify($slugInput !== '' ? $slugInput : $nameInput);
                if ($slug === '') {
                    throw new RuntimeException('Vypln slug alebo nazov noveho produktu.');
                }

                interessa_admin_save_product_record($slug, [
                    'name' => $nameInput,
                    'brand' => (string) ($_POST['new_product_brand'] ?? ''),
                    'merchant' => (string) ($_POST['new_product_merchant'] ?? ''),
                    'merchant_slug' => (string) ($_POST['new_product_merchant_slug'] ?? ''),
                    'category' => (string) ($_POST['new_product_category'] ?? ''),
                    'affiliate_code' => (string) ($_POST['new_product_affiliate_code'] ?? ''),
                    'fallback_url' => '',
                    'summary' => '',
                    'rating' => '0',
                    'pros' => '',
                    'cons' => '',
                    'image_remote_src' => '',
                ]);
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



$affiliateRegistry = aff_registry();
$affiliateCodes = array_keys($affiliateRegistry);
sort($affiliateCodes);
$selectedAffiliateCode = trim((string) ($_GET['code'] ?? ($affiliateCodes[0] ?? '')));
$selectedAffiliate = $selectedAffiliateCode !== '' ? aff_record($selectedAffiliateCode) : null;

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
$recommendedProductsText = implode(PHP_EOL, is_array($selectedArticleOverride['recommended_products'] ?? null) ? $selectedArticleOverride['recommended_products'] : []);

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
          <a href="/admin/ai-status">AI status</a>
        </nav>
        <div class="admin-note">
          Frontend ostava flat-file. Admin uklada len override data a obrazky.
        </div>
      </aside>

      <div class="admin-main">
        <?php if ($flash !== ''): ?>
          <div class="admin-flash is-success">Ulozene: <?= esc($flash) ?></div>
        <?php endif; ?>
        <?php if ($importSummary !== ''): ?>
          <div class="admin-flash is-success"><?= esc($importSummary) ?></div>
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
                    <button class="btn btn-secondary btn-small" type="button" data-apply-preset="duel">Preset duel</button>
                    <button class="btn btn-secondary btn-small" type="button" data-fill-from-products>Riadky z odporucanych produktov</button>
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
                  <div class="admin-check-grid">
                    <?php foreach ($catalog as $productSlug => $productRow): ?>
                      <?php $checked = in_array((string) $productSlug, is_array($selectedArticleOverride['recommended_products'] ?? null) ? $selectedArticleOverride['recommended_products'] : [], true); ?>
                      <label class="admin-check-card">
                        <input type="checkbox" name="recommended_product_checks[]" value="<?= esc((string) $productSlug) ?>" <?= $checked ? 'checked' : '' ?> />
                        <span><strong><?= esc((string) ($productRow['name'] ?? $productSlug)) ?></strong><small><?= esc((string) $productSlug) ?></small></span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </label>
                <label>
                  <span>Nahrat hero obrazok</span>
                  <input type="file" name="hero_image" accept="image/webp,image/png,image/jpeg" />
                </label>
              </div>

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

            <section class="admin-subsection is-compact">
              <div class="admin-subsection-head">
                <h3>Rychlo vytvorit novy produkt</h3>
              </div>
              <form method="post" class="admin-form admin-form-stack">
                <input type="hidden" name="action" value="create_product" />
                <div class="admin-grid three-up">
                  <label><span>Nazov</span><input type="text" name="new_product_name" placeholder="Napriklad GymBeam Magnesium Citrate" /></label>
                  <label><span>Slug</span><input type="text" name="new_product_slug" placeholder="gymbeam-magnesium-citrate" /></label>
                  <label><span>Brand</span><input type="text" name="new_product_brand" placeholder="GymBeam" /></label>
                </div>
                <div class="admin-grid three-up">
                  <label><span>Obchod</span><input type="text" name="new_product_merchant" placeholder="GymBeam" /></label>
                  <label><span>Merchant slug</span><input type="text" name="new_product_merchant_slug" placeholder="gymbeam" /></label>
                  <label><span>Kategoria</span><input type="text" name="new_product_category" placeholder="mineraly" /></label>
                </div>
                <label><span>Affiliate code (volitelne)</span><input type="text" name="new_product_affiliate_code" placeholder="horcik-ktory-je-najlepsi-a-preco-gymbeam" /></label>
                <div class="admin-actions">
                  <button class="btn btn-secondary" type="submit">Vytvorit produkt</button>
                </div>
              </form>
            </section>

            <section class="admin-subsection">
              <div class="admin-subsection-head">
                <div>
                  <h3>Queue chybajucich packshotov</h3>
                  <p class="admin-meta">Preferujeme lokalny WebP packshot v cielovej asset ceste. Remote obrazok je len prechodny fallback.</p>
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
                      <?php if (trim((string) ($queueRow['remote_src'] ?? '')) !== ''): ?>
                        <a class="btn btn-secondary btn-small" href="<?= esc((string) $queueRow['remote_src']) ?>" target="_blank" rel="noopener">Remote preview</a>
                      <?php endif; ?>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            </section>

            <section class="admin-subsection admin-asset-preview">
              <div class="admin-subsection-head">
                <h3>Aktualny packshot a zdroj</h3>
              </div>
              <div class="admin-asset-preview__grid">
                <div class="admin-asset-preview__media">
                  <?= interessa_render_image($selectedProductImage, ['class' => 'admin-asset-preview__image']) ?>
                </div>
                <div class="admin-asset-preview__body">
                  <p><strong>Zdroj:</strong> <?= esc($selectedProductImageSource) ?></p>
                  <p><strong>Target asset:</strong> <code><?= esc((string) ($selectedProduct['image_target_asset'] ?? '')) ?></code></p>
                  <p><strong>Remote source:</strong> <?= esc((string) ($selectedProduct['image_remote_src'] ?? '')) ?></p>
                  <div class="admin-diagnostic-list">
                    <p><strong>Affiliate kod:</strong> <?= esc((string) ($selectedProduct['affiliate_code'] ?? '')) ?: 'chyba' ?></p>
                    <p><strong>Typ linku:</strong> <?= esc($selectedProductAffiliateType !== '' ? $selectedProductAffiliateType : 'neznamy') ?></p>
                    <p><strong>Registry source:</strong> <?= esc((string) ($selectedProductAffiliate['source'] ?? 'bez registry')) ?></p>
                    <p><strong>Cielova URL:</strong> <?= esc((string) ($selectedProductAffiliateUrl ?? ($selectedProduct['fallback_url'] ?? ''))) ?></p>
                  </div>
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
                <label><span>Rating</span><input type="number" min="0" max="5" step="0.1" name="rating" value="<?= esc((string) ($selectedProduct['rating'] ?? '')) ?>" /></label>
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
              <div class="admin-grid two-up">
                <label>
                  <span>Remote image URL</span>
                  <input type="url" name="image_remote_src" value="<?= esc((string) ($selectedProduct['image_remote_src'] ?? '')) ?>" />
                </label>
                <label>
                  <span>Nahrat lokalny packshot</span>
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
                </div>
              </div>
            </section>

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

            <section class="admin-subsection is-compact">
              <div class="admin-subsection-head">
                <h3>Rychlo vytvorit novy affiliate kod</h3>
              </div>
              <form method="post" class="admin-form admin-form-stack">
                <input type="hidden" name="action" value="create_affiliate" />
                <div class="admin-grid three-up">
                  <label><span>Kod</span><input type="text" name="new_affiliate_code" placeholder="kolagen-recenzia-gymbeam" /></label>
                  <label><span>Merchant</span><input type="text" name="new_affiliate_merchant" placeholder="GymBeam" /></label>
                  <label><span>Merchant slug</span><input type="text" name="new_affiliate_merchant_slug" placeholder="gymbeam" /></label>
                </div>
                <div class="admin-grid two-up">
                  <label><span>Product slug</span><input type="text" name="new_affiliate_product_slug" placeholder="gymbeam-collagen-complex" /></label>
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
                <p>Stiahne zoznam chybajucich hero obrazkov a packshotov aj s cielovymi asset cestami.</p>
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
