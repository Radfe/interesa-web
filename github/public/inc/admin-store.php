<?php

declare(strict_types=1);

require_once __DIR__ . '/affiliates.php';

const INTERESSA_ADMIN_STORAGE_DIR = __DIR__ . '/../storage/admin';
const INTERESSA_ADMIN_ARTICLES_DIR = INTERESSA_ADMIN_STORAGE_DIR . '/articles';
const INTERESSA_ADMIN_PRODUCTS_FILE = INTERESSA_ADMIN_STORAGE_DIR . '/products.json';
const INTERESSA_ADMIN_AFFILIATE_LINKS_FILE = INTERESSA_ADMIN_STORAGE_DIR . '/affiliate-links.json';
const INTERESSA_ADMIN_PRODUCT_CANDIDATES_FILE = INTERESSA_ADMIN_STORAGE_DIR . '/product-candidates.json';

if (!function_exists('interessa_admin_ensure_dir')) {
    function interessa_admin_ensure_dir(string $dir): void {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}

if (!function_exists('interessa_admin_brand_dir')) {
    function interessa_admin_brand_dir(): string {
        return dirname(__DIR__) . '/assets/img/brand';
    }
}

if (!function_exists('interessa_admin_delete_asset_variants')) {
    function interessa_admin_delete_asset_variants(string $basePathWithoutExtension): void {
        foreach (['svg', 'png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $candidate = $basePathWithoutExtension . '.' . $ext;
            if (is_file($candidate)) {
                @unlink($candidate);
            }
        }
    }
}

if (!function_exists('interessa_admin_brand_uploaded_extension')) {
    function interessa_admin_brand_uploaded_extension(array $file, array $allowed = ['svg', 'png', 'jpg', 'jpeg', 'webp'], string $default = 'png'): string {
        $default = strtolower(trim($default)) ?: 'png';
        $allowed = array_values(array_unique(array_map(static fn(string $ext): string => strtolower(trim($ext)), $allowed)));
        $tmp = (string) ($file['tmp_name'] ?? '');
        $name = strtolower((string) ($file['name'] ?? ''));

        if ($name !== '') {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
            if (in_array($ext, $allowed, true)) {
                return $ext;
            }
        }

        if ($tmp !== '' && is_file($tmp)) {
            $prefix = @file_get_contents($tmp, false, null, 0, 256);
            if (is_string($prefix) && preg_match('~<svg[\s>]~i', $prefix)) {
                if (in_array('svg', $allowed, true)) {
                    return 'svg';
                }
            }
        }

        $ext = interessa_admin_uploaded_image_extension($file, $default);
        if (in_array($ext, $allowed, true)) {
            return $ext;
        }

        return $default;
    }
}

if (!function_exists('interessa_admin_store_uploaded_brand_file')) {
    function interessa_admin_store_uploaded_brand_file(string $baseName, array $file, array $allowed = ['svg', 'png', 'jpg', 'jpeg', 'webp'], string $default = 'png'): string {
        $baseName = interessa_admin_slugify($baseName);
        if ($baseName === '') {
            throw new RuntimeException('Chyba nazov suboru pre logo alebo ikonku.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Subor pre logo alebo ikonku nebol korektne nahraty.');
        }

        $ext = interessa_admin_brand_uploaded_extension($file, $allowed, $default);
        $targetBase = interessa_admin_brand_dir() . '/' . $baseName;
        interessa_admin_ensure_dir(dirname($targetBase));
        interessa_admin_delete_asset_variants($targetBase);
        $target = $targetBase . '.' . $ext;

        if (!move_uploaded_file($tmp, $target)) {
            throw new RuntimeException('Nepodarilo sa ulozit logo alebo ikonku.');
        }

        return 'img/brand/' . $baseName . '.' . $ext;
    }
}

if (!function_exists('interessa_admin_store_brand_icon_bundle')) {
    function interessa_admin_store_brand_icon_bundle(array $files): array {
        $expected = [
            'logo_icon' => 'logo-icon.png',
            'favicon_32' => 'favicon-32.png',
            'favicon_48' => 'favicon-48.png',
            'apple_touch_icon' => 'apple-touch-icon.png',
        ];

        $saved = [];
        interessa_admin_ensure_dir(interessa_admin_brand_dir());
        interessa_admin_delete_asset_variants(interessa_admin_brand_dir() . '/logo-icon');

        foreach ($expected as $field => $fileName) {
            $file = $files[$field] ?? null;
            if (!is_array($file)) {
                throw new RuntimeException('Chyba pripravena verzia ikonky: ' . $field . '.');
            }

            $tmp = (string) ($file['tmp_name'] ?? '');
            if ($tmp === '' || !is_uploaded_file($tmp)) {
                throw new RuntimeException('Ikonka nebola korektne pripravena: ' . $field . '.');
            }

            $ext = interessa_admin_brand_uploaded_extension($file, ['png'], 'png');
            if ($ext !== 'png') {
                throw new RuntimeException('Ikonka stranky musi byt pripravena ako PNG.');
            }

            $target = interessa_admin_brand_dir() . '/' . $fileName;
            if (!move_uploaded_file($tmp, $target)) {
                throw new RuntimeException('Nepodarilo sa ulozit subor ' . $fileName . '.');
            }
            $saved[$field] = 'img/brand/' . $fileName;
        }

        return $saved;
    }
}

if (!function_exists('interessa_admin_read_json')) {
    function interessa_admin_read_json(string $path, array $default = []): array {
        if (!is_file($path)) {
            return $default;
        }

        $json = file_get_contents($path);
        if (!is_string($json) || trim($json) === '') {
            return $default;
        }

        $data = json_decode($json, true);
        return is_array($data) ? $data : $default;
    }
}

if (!function_exists('interessa_admin_write_json')) {
    function interessa_admin_write_json(string $path, array $data): void {
        interessa_admin_ensure_dir(dirname($path));
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new RuntimeException('Nepodarilo sa pripravit JSON data pre admin.');
        }

        $tmpPath = $path . '.tmp';
        file_put_contents($tmpPath, $json . PHP_EOL);
        if (!rename($tmpPath, $path)) {
            @unlink($tmpPath);
            throw new RuntimeException('Nepodarilo sa ulozit admin data.');
        }
    }
}

if (!function_exists('interessa_admin_normalize_text')) {
    function interessa_admin_normalize_text(mixed $value): string {
        $text = trim((string) $value);
        return function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake($text) : $text;
    }
}

if (!function_exists('interessa_admin_slugify')) {
    function interessa_admin_slugify(mixed $value): string {
        $slug = strtolower(trim((string) $value));
        $slug = preg_replace('~[^a-z0-9-]+~', '-', $slug) ?? '';
        $slug = preg_replace('~-+~', '-', $slug) ?? $slug;
        return trim($slug, '-');
    }
}

if (!function_exists('interessa_admin_lines_to_array')) {
    function interessa_admin_lines_to_array(mixed $value): array {
        if (is_array($value)) {
            $items = [];
            foreach ($value as $item) {
                $item = interessa_admin_normalize_text($item);
                if ($item !== '') {
                    $items[] = $item;
                }
            }
            return array_values($items);
        }

        $lines = preg_split('~\R+~', (string) $value) ?: [];
        $items = [];
        foreach ($lines as $line) {
            $line = interessa_admin_normalize_text($line);
            if ($line !== '') {
                $items[] = $line;
            }
        }

        return array_values($items);
    }
}

if (!function_exists('interessa_admin_article_override_path')) {
    function interessa_admin_article_override_path(string $slug): string {
        $slug = interessa_admin_slugify($slug);
        return INTERESSA_ADMIN_ARTICLES_DIR . '/' . $slug . '.json';
    }
}

if (!function_exists('interessa_admin_normalize_sections')) {
    function interessa_admin_normalize_sections(array $sections): array {
        $normalized = [];
        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            $heading = interessa_admin_normalize_text($section['heading'] ?? '');
            $body = trim((string) ($section['body'] ?? ''));
            $body = function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake($body) : $body;

            if ($heading === '' && $body === '') {
                continue;
            }

            $normalized[] = [
                'heading' => $heading,
                'body' => $body,
            ];
        }

        return array_values($normalized);
    }
}

if (!function_exists('interessa_admin_normalize_comparison')) {
    function interessa_admin_normalize_comparison(array $comparison): array {
        $title = interessa_admin_normalize_text($comparison['title'] ?? '');
        $intro = interessa_admin_normalize_text($comparison['intro'] ?? '');
        $columns = [];
        foreach (($comparison['columns'] ?? []) as $column) {
            if (!is_array($column)) {
                continue;
            }

            $key = interessa_admin_slugify($column['key'] ?? '');
            $label = interessa_admin_normalize_text($column['label'] ?? '');
            $type = strtolower(trim((string) ($column['type'] ?? 'text')));
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

        $rows = [];
        foreach (($comparison['rows'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $cleanRow = [];
            foreach ($row as $key => $value) {
                $cleanKey = interessa_admin_slugify((string) $key);
                if ($cleanKey === '') {
                    continue;
                }

                if (is_array($value)) {
                    $cleanRow[$cleanKey] = array_values(array_map('strval', $value));
                } else {
                    $cleanRow[$cleanKey] = interessa_admin_normalize_text($value);
                }
            }

            if ($cleanRow !== []) {
                $rows[] = $cleanRow;
            }
        }

        return [
            'title' => $title,
            'intro' => $intro,
            'columns' => $columns,
            'rows' => $rows,
        ];
    }
}

if (!function_exists('interessa_admin_normalize_article_override')) {
    function interessa_admin_normalize_article_override(string $slug, array $data): array {
        $canonicalSlug = function_exists('canonical_article_slug') ? canonical_article_slug($slug) : $slug;

        return [
            'slug' => $canonicalSlug,
            'title' => interessa_admin_normalize_text($data['title'] ?? ''),
            'intro' => interessa_admin_normalize_text($data['intro'] ?? ''),
            'meta_title' => interessa_admin_normalize_text($data['meta_title'] ?? ''),
            'meta_description' => interessa_admin_normalize_text($data['meta_description'] ?? ''),
            'category' => normalize_category_slug((string) ($data['category'] ?? '')),
            'hero_asset' => trim((string) ($data['hero_asset'] ?? '')),
            'sections' => interessa_admin_normalize_sections(is_array($data['sections'] ?? null) ? $data['sections'] : []),
            'comparison' => interessa_admin_normalize_comparison(is_array($data['comparison'] ?? null) ? $data['comparison'] : []),
            'recommended_products' => array_values(array_filter(array_map(
                'interessa_admin_slugify',
                is_array($data['recommended_products'] ?? null) ? $data['recommended_products'] : []
            ))),
            'product_plan' => array_values(array_filter(array_map(
                static function (mixed $row): ?array {
                    if (!is_array($row)) {
                        return null;
                    }

                    $slug = interessa_admin_slugify((string) ($row['product_slug'] ?? $row['slug'] ?? ''));
                    if ($slug === '') {
                        return null;
                    }

                    $role = interessa_admin_slugify((string) ($row['role'] ?? ''));
                    if (!in_array($role, ['featured', 'value', 'alternative', 'vegan', 'clean', 'standard'], true)) {
                        $role = 'standard';
                    }

                    return [
                        'product_slug' => $slug,
                        'order' => max(1, (int) ($row['order'] ?? 1)),
                        'role' => $role,
                        'show_in_top' => !empty($row['show_in_top']),
                        'show_in_comparison' => !empty($row['show_in_comparison']),
                    ];
                },
                is_array($data['product_plan'] ?? null) ? $data['product_plan'] : []
            ))),
            'updated_at' => date('c'),
        ];
    }
}

if (!function_exists('interessa_admin_article_override')) {
    function interessa_admin_article_override(string $slug): array {
        $slug = function_exists('canonical_article_slug') ? canonical_article_slug($slug) : trim($slug);
        if ($slug === '') {
            return [];
        }

        return interessa_admin_read_json(interessa_admin_article_override_path($slug), []);
    }
}

if (!function_exists('interessa_admin_all_article_overrides')) {
    function interessa_admin_all_article_overrides(): array {
        $items = [];
        foreach (glob(INTERESSA_ADMIN_ARTICLES_DIR . '/*.json') ?: [] as $file) {
            $slug = basename($file, '.json');
            $items[$slug] = interessa_admin_read_json($file, []);
        }
        ksort($items);
        return $items;
    }
}

if (!function_exists('interessa_admin_save_article_override')) {
    function interessa_admin_save_article_override(string $slug, array $data): void {
        $slug = function_exists('canonical_article_slug') ? canonical_article_slug($slug) : trim($slug);
        if ($slug === '') {
            throw new RuntimeException('Chyba slug clanku.');
        }

        interessa_admin_write_json(
            interessa_admin_article_override_path($slug),
            interessa_admin_normalize_article_override($slug, $data)
        );
    }
}

if (!function_exists('interessa_admin_article_content')) {
    function interessa_admin_article_content(string $slug): array {
        $override = interessa_admin_article_override($slug);
        return interessa_admin_normalize_article_override($slug, $override);
    }
}

if (!function_exists('interessa_admin_article_has_structured_content')) {
    function interessa_admin_article_has_structured_content(string $slug): bool {
        $article = interessa_admin_article_content($slug);
        return trim((string) ($article['title'] ?? '')) !== ''
            || trim((string) ($article['intro'] ?? '')) !== ''
            || trim((string) ($article['meta_title'] ?? '')) !== ''
            || trim((string) ($article['meta_description'] ?? '')) !== ''
            || trim((string) ($article['category'] ?? '')) !== ''
            || trim((string) ($article['hero_asset'] ?? '')) !== ''
            || $article['sections'] !== []
            || ($article['comparison']['columns'] ?? []) !== []
            || $article['recommended_products'] !== [];
    }
}

if (!function_exists('interessa_admin_merge_article_meta')) {
    function interessa_admin_merge_article_meta(string $slug, array $meta): array {
        $override = interessa_admin_article_override($slug);
        if ($override === []) {
            return $meta;
        }

        if (trim((string) ($override['title'] ?? '')) !== '') {
            $meta['title'] = trim((string) $override['title']);
        }
        if (trim((string) ($override['meta_description'] ?? '')) !== '') {
            $meta['description'] = trim((string) $override['meta_description']);
        } elseif (trim((string) ($override['intro'] ?? '')) !== '') {
            $meta['description'] = trim((string) $override['intro']);
        }
        if (trim((string) ($override['meta_title'] ?? '')) !== '') {
            $meta['meta_title'] = trim((string) $override['meta_title']);
        }
        if (trim((string) ($override['meta_description'] ?? '')) !== '') {
            $meta['meta_description'] = trim((string) $override['meta_description']);
        }
        if (trim((string) ($override['category'] ?? '')) !== '') {
            $meta['category'] = normalize_category_slug((string) $override['category']);
        }

        return $meta;
    }
}


if (!function_exists('interessa_admin_products')) {
    function interessa_admin_products(): array {
        return interessa_admin_read_json(INTERESSA_ADMIN_PRODUCTS_FILE, []);
    }
}

if (!function_exists('interessa_admin_save_products')) {
    function interessa_admin_save_products(array $products): void {
        ksort($products);
        interessa_admin_write_json(INTERESSA_ADMIN_PRODUCTS_FILE, $products);
    }
}

if (!function_exists('interessa_admin_product_record')) {
    function interessa_admin_product_record(string $slug): ?array {
        $slug = interessa_admin_slugify($slug);
        if ($slug === '') {
            return null;
        }

        $products = interessa_admin_products();
        $record = $products[$slug] ?? null;
        return is_array($record) ? $record : null;
    }
}

if (!function_exists('interessa_admin_normalize_product_record')) {
    function interessa_admin_normalize_product_record(string $slug, array $product): array {
        $slug = interessa_admin_slugify($slug);
        $merchantSlug = interessa_admin_slugify($product['merchant_slug'] ?? '');
        $targetAsset = $slug !== '' && function_exists('interessa_product_image_target_asset')
            ? interessa_product_image_target_asset($slug, $merchantSlug)
            : '';

        return [
            'slug' => $slug,
            'name' => interessa_admin_normalize_text($product['name'] ?? ''),
            'brand' => interessa_admin_normalize_text($product['brand'] ?? ''),
            'merchant' => interessa_admin_normalize_text($product['merchant'] ?? ''),
            'merchant_slug' => $merchantSlug,
            'category' => normalize_category_slug((string) ($product['category'] ?? '')),
            'affiliate_code' => interessa_admin_slugify($product['affiliate_code'] ?? ''),
            'fallback_url' => trim((string) ($product['fallback_url'] ?? '')),
            'summary' => interessa_admin_normalize_text($product['summary'] ?? ''),
            'rating' => max(0.0, min(5.0, (float) ($product['rating'] ?? 0))),
            'pros' => interessa_admin_lines_to_array($product['pros'] ?? []),
            'cons' => interessa_admin_lines_to_array($product['cons'] ?? []),
            'image' => array_filter([
                'asset' => trim((string) ($product['image_asset'] ?? $targetAsset)),
                'remote_src' => trim((string) ($product['image_remote_src'] ?? '')),
            ], static fn(mixed $value): bool => trim((string) $value) !== ''),
        ];
    }
}

if (!function_exists('interessa_admin_save_product_record')) {
    function interessa_admin_save_product_record(string $slug, array $product): void {
        $slug = interessa_admin_slugify($slug);
        if ($slug === '') {
            throw new RuntimeException('Chyba slug produktu.');
        }

        $products = interessa_admin_products();
        $products[$slug] = interessa_admin_normalize_product_record($slug, $product);
        interessa_admin_save_products($products);
    }
}

if (!function_exists('interessa_admin_merge_product_catalog')) {
    function interessa_admin_merge_product_catalog(array $catalog): array {
        foreach (interessa_admin_products() as $slug => $product) {
            $normalizedSlug = interessa_admin_slugify($slug);
            if ($normalizedSlug === '') {
                continue;
            }
            $catalog[$normalizedSlug] = array_replace($catalog[$normalizedSlug] ?? [], $product);
        }

        return $catalog;
    }
}

if (!function_exists('interessa_admin_affiliate_links')) {
    function interessa_admin_affiliate_links(): array {
        return interessa_admin_read_json(INTERESSA_ADMIN_AFFILIATE_LINKS_FILE, []);
    }
}

if (!function_exists('interessa_admin_save_affiliate_links')) {
    function interessa_admin_save_affiliate_links(array $links): void {
        ksort($links);
        interessa_admin_write_json(INTERESSA_ADMIN_AFFILIATE_LINKS_FILE, $links);
    }
}

if (!function_exists('interessa_admin_product_candidates')) {
    function interessa_admin_product_candidates(): array {
        return interessa_admin_read_json(INTERESSA_ADMIN_PRODUCT_CANDIDATES_FILE, []);
    }
}

if (!function_exists('interessa_admin_save_product_candidates')) {
    function interessa_admin_save_product_candidates(array $rows): void {
        ksort($rows);
        interessa_admin_write_json(INTERESSA_ADMIN_PRODUCT_CANDIDATES_FILE, $rows);
    }
}

if (!function_exists('interessa_admin_normalize_candidate_role')) {
    function interessa_admin_normalize_candidate_role(mixed $value): string {
        $role = interessa_admin_slugify((string) $value);
        if (!in_array($role, ['featured', 'value', 'alternative', 'vegan', 'clean', 'standard'], true)) {
            $role = 'standard';
        }
        return $role;
    }
}

if (!function_exists('interessa_admin_normalize_product_candidate_record')) {
    function interessa_admin_normalize_product_candidate_record(string $id, array $row): array {
        $id = interessa_admin_slugify($id);
        $merchantSlug = interessa_admin_slugify((string) ($row['merchant_slug'] ?? ''));
        $productSlug = interessa_admin_slugify((string) ($row['product_slug'] ?? $row['slug'] ?? $id));
        $sourceType = interessa_admin_slugify((string) ($row['source_type'] ?? 'manual'));
        if ($sourceType === '') {
            $sourceType = 'manual';
        }
        $clickStatus = interessa_admin_slugify((string) ($row['click_status'] ?? 'missing'));
        if (!in_array($clickStatus, ['missing', 'direct', 'dognet'], true)) {
            $clickStatus = 'missing';
        }

        $createdAt = trim((string) ($row['created_at'] ?? ''));
        if ($createdAt === '') {
            $createdAt = date('c');
        }

        return [
            'id' => $id,
            'product_slug' => $productSlug !== '' ? $productSlug : $id,
            'name' => interessa_admin_normalize_text($row['name'] ?? ''),
            'merchant' => interessa_admin_normalize_text($row['merchant'] ?? ''),
            'merchant_slug' => $merchantSlug,
            'category' => normalize_category_slug((string) ($row['category'] ?? '')),
            'product_type' => interessa_admin_slugify((string) ($row['product_type'] ?? '')),
            'price' => trim((string) ($row['price'] ?? '')),
            'url' => trim((string) ($row['url'] ?? $row['fallback_url'] ?? '')),
            'image_remote_src' => trim((string) ($row['image_remote_src'] ?? '')),
            'source_type' => $sourceType,
            'source_name' => interessa_admin_normalize_text($row['source_name'] ?? ''),
            'source_file' => interessa_admin_normalize_text($row['source_file'] ?? ''),
            'click_code' => interessa_admin_slugify((string) ($row['click_code'] ?? '')),
            'click_url' => trim((string) ($row['click_url'] ?? '')),
            'click_status' => $clickStatus,
            'article_slug' => canonical_article_slug(trim((string) ($row['article_slug'] ?? ''))),
            'order' => max(1, (int) ($row['order'] ?? 1)),
            'role' => interessa_admin_normalize_candidate_role($row['role'] ?? ''),
            'show_in_top' => !empty($row['show_in_top']),
            'show_in_comparison' => !empty($row['show_in_comparison']),
            'approved' => !empty($row['approved']),
            'notes' => interessa_admin_normalize_text($row['notes'] ?? ''),
            'created_at' => $createdAt,
            'updated_at' => date('c'),
        ];
    }
}

if (!function_exists('interessa_admin_product_candidate_record')) {
    function interessa_admin_product_candidate_record(string $id): ?array {
        $id = interessa_admin_slugify($id);
        if ($id === '') {
            return null;
        }
        $rows = interessa_admin_product_candidates();
        $record = $rows[$id] ?? null;
        return is_array($record) ? $record : null;
    }
}

if (!function_exists('interessa_admin_save_product_candidate_record')) {
    function interessa_admin_save_product_candidate_record(string $id, array $row): array {
        $id = interessa_admin_slugify($id);
        if ($id === '') {
            throw new RuntimeException('Chyba kod kandidata produktu.');
        }
        $rows = interessa_admin_product_candidates();
        $existing = is_array($rows[$id] ?? null) ? $rows[$id] : [];
        $rows[$id] = interessa_admin_normalize_product_candidate_record($id, array_replace($existing, $row));
        interessa_admin_save_product_candidates($rows);
        return $rows[$id];
    }
}

if (!function_exists('interessa_admin_candidate_id_from_row')) {
    function interessa_admin_candidate_id_from_row(array $row, array $existing): string {
        $merchantSlug = interessa_admin_slugify((string) ($row['merchant_slug'] ?? ''));
        $name = interessa_admin_normalize_text($row['name'] ?? '');
        $base = interessa_admin_slugify((string) ($row['slug'] ?? ''));
        if ($base === '') {
            $base = interessa_admin_slugify(($merchantSlug !== '' ? $merchantSlug . '-' : '') . $name);
        }
        if ($base === '') {
            $base = 'produkt-' . substr(md5(json_encode($row)), 0, 8);
        }
        $id = $base;
        $index = 2;
        while (isset($existing[$id])) {
            $sameUrl = trim((string) ($existing[$id]['url'] ?? '')) !== '' && trim((string) ($existing[$id]['url'] ?? '')) === trim((string) ($row['url'] ?? $row['fallback_url'] ?? ''));
            $sameName = trim((string) ($existing[$id]['name'] ?? '')) !== '' && trim((string) ($existing[$id]['name'] ?? '')) === $name;
            if ($sameUrl || $sameName) {
                return $id;
            }
            $id = $base . '-' . $index;
            $index++;
        }
        return $id;
    }
}

if (!function_exists('interessa_admin_import_product_candidates')) {
    function interessa_admin_import_product_candidates(array $rows, string $sourceType, string $sourceName = '', string $sourceFile = ''): array {
        $existing = interessa_admin_product_candidates();
        $imported = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = interessa_admin_candidate_id_from_row($row, $existing);
            $existing[$id] = interessa_admin_normalize_product_candidate_record($id, array_replace(
                is_array($existing[$id] ?? null) ? $existing[$id] : [],
                [
                    'product_slug' => (string) ($row['slug'] ?? $id),
                    'name' => $row['name'] ?? '',
                    'merchant' => $row['merchant'] ?? '',
                    'merchant_slug' => $row['merchant_slug'] ?? '',
                    'category' => $row['category'] ?? '',
                    'product_type' => $row['product_type'] ?? '',
                    'price' => $row['price'] ?? '',
                    'url' => $row['url'] ?? $row['fallback_url'] ?? '',
                    'image_remote_src' => $row['image_remote_src'] ?? '',
                    'source_type' => $sourceType,
                    'source_name' => $sourceName,
                    'source_file' => $sourceFile,
                ]
            ));
            $imported[] = $id;
        }

        if ($imported !== []) {
            interessa_admin_save_product_candidates($existing);
        }

        return $imported;
    }
}

if (!function_exists('interessa_admin_candidate_click_code')) {
    function interessa_admin_candidate_click_code(array $candidate): string {
        $code = interessa_admin_slugify((string) ($candidate['click_code'] ?? ''));
        if ($code !== '') {
            return $code;
        }
        $productSlug = interessa_admin_slugify((string) ($candidate['product_slug'] ?? ''));
        $merchantSlug = interessa_admin_slugify((string) ($candidate['merchant_slug'] ?? ''));
        if ($productSlug !== '' && $merchantSlug !== '') {
            return $productSlug . '-' . $merchantSlug;
        }
        if ($productSlug !== '') {
            return $productSlug . '-link';
        }
        return interessa_admin_slugify((string) ($candidate['id'] ?? '')) . '-link';
    }
}

if (!function_exists('interessa_admin_bind_product_to_article')) {
    function interessa_admin_bind_product_to_article(string $articleSlug, string $productSlug, array $settings): void {
        $articleSlug = canonical_article_slug(trim($articleSlug));
        $productSlug = interessa_admin_slugify($productSlug);
        if ($articleSlug === '' || $productSlug === '') {
            throw new RuntimeException('Chyba clanok alebo produkt pre prepojenie.');
        }

        $override = interessa_admin_article_content($articleSlug);
        $plan = is_array($override['product_plan'] ?? null) ? array_values($override['product_plan']) : [];
        $filtered = [];
        foreach ($plan as $row) {
            if (!is_array($row) || interessa_admin_slugify((string) ($row['product_slug'] ?? '')) === $productSlug) {
                continue;
            }
            $filtered[] = $row;
        }

        $filtered[] = [
            'product_slug' => $productSlug,
            'order' => max(1, (int) ($settings['order'] ?? 1)),
            'role' => interessa_admin_normalize_candidate_role($settings['role'] ?? ''),
            'show_in_top' => !empty($settings['show_in_top']),
            'show_in_comparison' => !empty($settings['show_in_comparison']),
        ];

        usort($filtered, static function (array $a, array $b): int {
            return ((int) ($a['order'] ?? 99)) <=> ((int) ($b['order'] ?? 99));
        });

        $override['product_plan'] = $filtered;
        $override['recommended_products'] = array_values(array_map(
            static fn(array $row): string => (string) ($row['product_slug'] ?? ''),
            $filtered
        ));
        interessa_admin_save_article_override($articleSlug, $override);
    }
}

if (!function_exists('interessa_admin_prepare_candidate_click')) {
    function interessa_admin_prepare_candidate_click(string $id): array {
        $candidate = interessa_admin_product_candidate_record($id);
        if (!is_array($candidate)) {
            throw new RuntimeException('Kandidat produktu sa nenasiel.');
        }

        $inputUrl = trim((string) ($candidate['url'] ?? ''));
        if ($inputUrl === '') {
            throw new RuntimeException('Kandidat zatial nema link produktu.');
        }
        if (!preg_match('~^https?://~i', $inputUrl)) {
            throw new RuntimeException('Link produktu musi zacinat na http:// alebo https:// .');
        }

        $linkType = aff_detect_link_type_from_url($inputUrl, 'affiliate');
        $finalUrl = $linkType === 'affiliate' ? aff_extract_final_url($inputUrl) : $inputUrl;
        $finalUrl = trim($finalUrl);
        if ($finalUrl === '') {
            throw new RuntimeException('Z linku sa nepodarilo zistit cielovu stranku produktu.');
        }

        $merchantMeta = interessa_admin_guess_merchant_from_url($finalUrl);
        if (trim((string) ($candidate['merchant_slug'] ?? '')) === '' && trim((string) ($merchantMeta['merchant_slug'] ?? '')) !== '') {
            $candidate['merchant_slug'] = (string) $merchantMeta['merchant_slug'];
        }
        if (trim((string) ($candidate['merchant'] ?? '')) === '' && trim((string) ($merchantMeta['merchant'] ?? '')) !== '') {
            $candidate['merchant'] = (string) $merchantMeta['merchant'];
        }

        $code = interessa_admin_candidate_click_code($candidate);
        interessa_admin_save_affiliate_record($code, [
            'url' => $inputUrl,
            'merchant' => (string) ($candidate['merchant'] ?? ''),
            'merchant_slug' => (string) ($candidate['merchant_slug'] ?? ''),
            'product_slug' => (string) ($candidate['product_slug'] ?? ''),
            'link_type' => $linkType,
            'source' => 'candidate-product',
        ]);

        if (function_exists('dognet_helper_ensure_row')) {
            dognet_helper_ensure_row([
                'code' => $code,
                'deeplink_url' => $linkType === 'affiliate' ? $inputUrl : '',
                'product_url' => $finalUrl,
                'merchant_slug' => (string) ($candidate['merchant_slug'] ?? ''),
                'product_slug' => (string) ($candidate['product_slug'] ?? ''),
                'merchant' => (string) ($candidate['merchant'] ?? ''),
                'link_type' => 'affiliate',
                'product_name' => (string) ($candidate['name'] ?? ''),
                'notes' => 'Pripravil admin kandidat produktu',
            ]);
        }

        $candidate['url'] = $finalUrl;
        $candidate['click_code'] = $code;
        $candidate['click_url'] = $inputUrl;
        $candidate['click_status'] = $linkType === 'affiliate' ? 'dognet' : 'direct';
        $candidate = interessa_admin_save_product_candidate_record($id, $candidate);

        return [
            'id' => $id,
            'code' => $code,
            'final_url' => $finalUrl,
            'click_status' => (string) ($candidate['click_status'] ?? 'missing'),
        ];
    }
}

if (!function_exists('interessa_admin_save_candidate_assignment')) {
    function interessa_admin_save_candidate_assignment(string $id, array $assignment): array {
        $candidate = interessa_admin_product_candidate_record($id);
        if (!is_array($candidate)) {
            throw new RuntimeException('Kandidat produktu sa nenasiel.');
        }

        $candidate['article_slug'] = canonical_article_slug(trim((string) ($assignment['article_slug'] ?? '')));
        $candidate['order'] = max(1, (int) ($assignment['order'] ?? 1));
        $candidate['role'] = interessa_admin_normalize_candidate_role($assignment['role'] ?? '');
        $candidate['show_in_top'] = !empty($assignment['show_in_top']);
        $candidate['show_in_comparison'] = !empty($assignment['show_in_comparison']);

        return interessa_admin_save_product_candidate_record($id, $candidate);
    }
}

if (!function_exists('interessa_admin_approve_candidate_for_web')) {
    function interessa_admin_approve_candidate_for_web(string $id): array {
        $candidate = interessa_admin_product_candidate_record($id);
        if (!is_array($candidate)) {
            throw new RuntimeException('Kandidat produktu sa nenasiel.');
        }

        $productSlug = interessa_admin_slugify((string) ($candidate['product_slug'] ?? ''));
        if ($productSlug === '') {
            throw new RuntimeException('Kandidat nema kod produktu.');
        }

        if (trim((string) ($candidate['url'] ?? '')) === '') {
            throw new RuntimeException('Najprv dopln link produktu.');
        }

        $clickCode = trim((string) ($candidate['click_code'] ?? ''));
        if ($clickCode === '') {
            throw new RuntimeException('Najprv priprav klik do obchodu.');
        }

        $productPayload = [
            'name' => (string) ($candidate['name'] ?? ''),
            'brand' => (string) ($candidate['merchant'] ?? ''),
            'merchant' => (string) ($candidate['merchant'] ?? ''),
            'merchant_slug' => (string) ($candidate['merchant_slug'] ?? ''),
            'category' => (string) ($candidate['category'] ?? ''),
            'affiliate_code' => $clickCode,
            'fallback_url' => (string) ($candidate['url'] ?? ''),
            'image_remote_src' => (string) ($candidate['image_remote_src'] ?? ''),
            'summary' => '',
        ];
        interessa_admin_save_product_record($productSlug, $productPayload);

        if (trim((string) ($candidate['article_slug'] ?? '')) !== '') {
            interessa_admin_bind_product_to_article((string) $candidate['article_slug'], $productSlug, [
                'order' => (int) ($candidate['order'] ?? 1),
                'role' => (string) ($candidate['role'] ?? 'standard'),
                'show_in_top' => !empty($candidate['show_in_top']),
                'show_in_comparison' => !empty($candidate['show_in_comparison']),
            ]);
        }

        $mirrored = false;
        $remoteSrc = trim((string) ($candidate['image_remote_src'] ?? ''));
        if ($remoteSrc !== '' && interessa_admin_detect_remote_image_extension($remoteSrc) === 'webp') {
            try {
                $asset = interessa_admin_mirror_remote_product_image($productSlug, (string) ($candidate['merchant_slug'] ?? ''), $remoteSrc);
                $savedProduct = array_replace(interessa_product($productSlug) ?? [], interessa_admin_product_record($productSlug) ?? []);
                $savedProduct['image_asset'] = $asset;
                interessa_admin_save_product_record($productSlug, $savedProduct);
                $mirrored = true;
            } catch (Throwable) {
            }
        }

        $candidate['approved'] = true;
        $candidate = interessa_admin_save_product_candidate_record($id, $candidate);

        return [
            'id' => $id,
            'product_slug' => $productSlug,
            'article_slug' => (string) ($candidate['article_slug'] ?? ''),
            'mirrored' => $mirrored,
        ];
    }
}

if (!function_exists('interessa_admin_normalize_affiliate_record')) {
    function interessa_admin_normalize_affiliate_record(string $code, array $record): array {
        $code = interessa_admin_slugify($code);
        return [
            'code' => $code,
            'url' => trim((string) ($record['url'] ?? '')),
            'merchant' => interessa_admin_normalize_text($record['merchant'] ?? ''),
            'merchant_slug' => interessa_admin_slugify($record['merchant_slug'] ?? ''),
            'product_slug' => interessa_admin_slugify($record['product_slug'] ?? ''),
            'link_type' => trim((string) ($record['link_type'] ?? 'affiliate')) ?: 'affiliate',
            'source' => trim((string) ($record['source'] ?? 'admin-panel')) ?: 'admin-panel',
        ];
    }
}

if (!function_exists('interessa_admin_save_affiliate_record')) {
    function interessa_admin_save_affiliate_record(string $code, array $record): void {
        $code = interessa_admin_slugify($code);
        if ($code === '') {
            throw new RuntimeException('Chyba kod affiliate odkazu.');
        }

        $links = interessa_admin_affiliate_links();
        $links[$code] = interessa_admin_normalize_affiliate_record($code, $record);
        interessa_admin_save_affiliate_links($links);
    }
}

if (!function_exists('interessa_admin_merge_affiliate_registry')) {
    function interessa_admin_merge_affiliate_registry(array $registry): array {
        foreach (interessa_admin_affiliate_links() as $code => $row) {
            $normalizedCode = interessa_admin_slugify($code);
            if ($normalizedCode === '') {
                continue;
            }
            $registry[$normalizedCode] = array_replace($registry[$normalizedCode] ?? [], $row);
        }

        return $registry;
    }
}

if (!function_exists('interessa_admin_article_hero_asset')) {
    function interessa_admin_article_hero_asset(string $slug, string $ext = 'webp'): string {
        $slug = function_exists('canonical_article_slug') ? canonical_article_slug($slug) : trim($slug);
        $ext = strtolower(trim($ext)) ?: 'webp';
        return 'img/articles/heroes/' . $slug . '.' . $ext;
    }
}

if (!function_exists('interessa_admin_article_hero_path')) {
    function interessa_admin_article_hero_path(string $slug, string $ext = 'webp'): string {
        return dirname(__DIR__) . '/assets/' . interessa_admin_article_hero_asset($slug, $ext);
    }
}

if (!function_exists('interessa_admin_uploaded_image_extension')) {
    function interessa_admin_uploaded_image_extension(array $file, string $default = 'webp'): string {
        $default = strtolower(trim($default)) ?: 'webp';
        $tmp = (string) ($file['tmp_name'] ?? '');
        $name = strtolower((string) ($file['name'] ?? ''));
        $allowed = [
            'image/webp' => 'webp',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
        ];

        $mime = '';
        if ($tmp !== '' && is_file($tmp)) {
            $header = @file_get_contents($tmp, false, null, 0, 16);
            if (is_string($header) && $header !== '') {
                $bytes = array_values(unpack('C*', $header) ?: []);
                if (
                    count($bytes) >= 8
                    && $bytes[0] === 0x89
                    && $bytes[1] === 0x50
                    && $bytes[2] === 0x4E
                    && $bytes[3] === 0x47
                    && $bytes[4] === 0x0D
                    && $bytes[5] === 0x0A
                    && $bytes[6] === 0x1A
                    && $bytes[7] === 0x0A
                ) {
                    return 'png';
                }

                if (
                    count($bytes) >= 3
                    && $bytes[0] === 0xFF
                    && $bytes[1] === 0xD8
                    && $bytes[2] === 0xFF
                ) {
                    return 'jpg';
                }

                if (
                    count($bytes) >= 12
                    && $bytes[0] === 0x52
                    && $bytes[1] === 0x49
                    && $bytes[2] === 0x46
                    && $bytes[3] === 0x46
                    && $bytes[8] === 0x57
                    && $bytes[9] === 0x45
                    && $bytes[10] === 0x42
                    && $bytes[11] === 0x50
                ) {
                    return 'webp';
                }
            }

            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo !== false) {
                    $detected = finfo_file($finfo, $tmp);
                    if (is_string($detected)) {
                        $mime = strtolower(trim($detected));
                    }
                    finfo_close($finfo);
                }
            }

            if ($mime === '' && function_exists('getimagesize')) {
                $size = @getimagesize($tmp);
                if (is_array($size) && is_string($size['mime'] ?? null)) {
                    $mime = strtolower(trim((string) $size['mime']));
                }
            }
        }

        if ($mime !== '' && isset($allowed[$mime])) {
            return $allowed[$mime];
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($ext, ['webp', 'png', 'jpg', 'jpeg'], true)) {
            return $ext === 'jpeg' ? 'jpg' : $ext;
        }

        return $default;
    }
}

if (!function_exists('interessa_admin_store_uploaded_article_hero')) {
    function interessa_admin_store_uploaded_article_hero(string $slug, array $file): string {
        $slug = function_exists('canonical_article_slug') ? canonical_article_slug($slug) : trim($slug);
        if ($slug === '') {
            throw new RuntimeException('Chyba slug clanku.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Hero obrazok nebol korektne nahraty.');
        }

        $ext = interessa_admin_uploaded_image_extension($file, 'webp');
        if ($ext !== 'webp') {
            throw new RuntimeException('Admin ocakava finalny WebP. PNG/JPG sa ma automaticky previest na WebP este pred uploadom. Obnov stranku a skus to znova.');
        }
        $target = interessa_admin_article_hero_path($slug, $ext);
        interessa_admin_ensure_dir(dirname($target));
        if (!move_uploaded_file($tmp, $target)) {
            throw new RuntimeException('Nepodarilo sa ulozit hero obrazok.');
        }

        return interessa_admin_article_hero_asset($slug, $ext);
    }
}

if (!function_exists('interessa_admin_category_image_asset')) {
    function interessa_admin_category_image_asset(string $slug, string $variant = 'hero', string $ext = 'webp'): string {
        $slug = normalize_category_slug($slug);
        $variant = interessa_admin_slugify($variant);
        $ext = strtolower(trim($ext)) ?: 'webp';

        if ($slug === '') {
            throw new RuntimeException('Chyba slug temy.');
        }
        if ($variant === '') {
            $variant = 'hero';
        }

        return 'img/categories/' . $slug . '/' . $variant . '.' . $ext;
    }
}

if (!function_exists('interessa_admin_category_image_path')) {
    function interessa_admin_category_image_path(string $slug, string $variant = 'hero', string $ext = 'webp'): string {
        return dirname(__DIR__) . '/assets/' . interessa_admin_category_image_asset($slug, $variant, $ext);
    }
}

if (!function_exists('interessa_admin_store_uploaded_category_image')) {
    function interessa_admin_store_uploaded_category_image(string $slug, string $variant, array $file): string {
        $slug = normalize_category_slug($slug);
        $variant = interessa_admin_slugify($variant);
        if ($slug === '') {
            throw new RuntimeException('Chyba slug temy.');
        }
        if ($variant === '') {
            $variant = 'hero';
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Obrazok temy nebol korektne nahraty.');
        }

        $ext = interessa_admin_uploaded_image_extension($file, 'webp');
        if ($ext !== 'webp') {
            throw new RuntimeException('Admin ocakava finalny WebP. PNG/JPG sa ma automaticky previest na WebP este pred uploadom. Obnov stranku a skus to znova.');
        }

        $target = interessa_admin_category_image_path($slug, $variant, $ext);
        interessa_admin_ensure_dir(dirname($target));
        if (!move_uploaded_file($tmp, $target)) {
            throw new RuntimeException('Nepodarilo sa ulozit obrazok temy.');
        }

        return interessa_admin_category_image_asset($slug, $variant, $ext);
    }
}

if (!function_exists('interessa_admin_store_uploaded_product_image')) {
    function interessa_admin_store_uploaded_product_image(string $productSlug, string $merchantSlug, array $file): string {
        $productSlug = trim($productSlug);
        $merchantSlug = trim($merchantSlug);
        if ($productSlug === '') {
            throw new RuntimeException('Chyba slug produktu.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Produktovy obrazok nebol korektne nahraty.');
        }

        $ext = interessa_admin_uploaded_image_extension($file, 'webp');
        if ($ext !== 'webp') {
            throw new RuntimeException('Admin ocakava finalny WebP. PNG/JPG sa ma automaticky previest na WebP este pred uploadom. Obnov stranku a skus to znova.');
        }
        $target = function_exists('interessa_admin_product_image_target_path_for_ext')
            ? interessa_admin_product_image_target_path_for_ext($productSlug, $merchantSlug, $ext)
            : dirname(__DIR__) . '/assets/img/products/' . ($merchantSlug !== '' ? $merchantSlug . '/' : '') . $productSlug . '/main.' . $ext;
        interessa_admin_ensure_dir(dirname($target));
        if (!move_uploaded_file($tmp, $target)) {
            throw new RuntimeException('Nepodarilo sa ulozit produktovy obrazok.');
        }

        return function_exists('interessa_admin_product_image_target_asset_for_ext')
            ? interessa_admin_product_image_target_asset_for_ext($productSlug, $merchantSlug, $ext)
            : 'img/products/' . ($merchantSlug !== '' ? $merchantSlug . '/' : '') . $productSlug . '/main.' . $ext;
    }
}

if (!function_exists('interessa_admin_detect_remote_image_extension')) {
    function interessa_admin_detect_remote_image_extension(string $url, string $contentType = ''): string {
        $contentType = strtolower(trim($contentType));
        if (str_contains($contentType, 'webp')) {
            return 'webp';
        }
        if (str_contains($contentType, 'png')) {
            return 'png';
        }
        if (str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg')) {
            return 'jpg';
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['webp', 'png', 'jpg', 'jpeg'], true)) {
            return $ext === 'jpeg' ? 'jpg' : $ext;
        }

        return 'png';
    }
}

if (!function_exists('interessa_admin_product_image_target_asset_for_ext')) {
    function interessa_admin_product_image_target_asset_for_ext(string $productSlug, string $merchantSlug, string $ext): string {
        $productSlug = trim($productSlug);
        $merchantSlug = trim($merchantSlug);
        $ext = strtolower(trim($ext)) ?: 'webp';
        return 'img/products/' . ($merchantSlug !== '' ? $merchantSlug . '/' : '') . $productSlug . '/main.' . $ext;
    }
}

if (!function_exists('interessa_admin_product_image_target_path_for_ext')) {
    function interessa_admin_product_image_target_path_for_ext(string $productSlug, string $merchantSlug, string $ext): string {
        return dirname(__DIR__) . '/assets/' . interessa_admin_product_image_target_asset_for_ext($productSlug, $merchantSlug, $ext);
    }
}

if (!function_exists('interessa_admin_fetch_remote_image_bytes_via_curl_exe')) {
    function interessa_admin_windows_curl_executable(): string {
        $systemRoot = rtrim((string) getenv('SystemRoot'), '\\/');
        $candidates = array_filter([
            $systemRoot !== '' ? $systemRoot . '\\System32\\curl.exe' : '',
            $systemRoot !== '' ? $systemRoot . '\\Sysnative\\curl.exe' : '',
            'curl.exe',
        ], static fn(string $value): bool => trim($value) !== '');

        foreach ($candidates as $candidate) {
            if (str_ends_with(strtolower($candidate), 'curl.exe') && is_file($candidate)) {
                return $candidate;
            }
        }

        return 'curl.exe';
    }
}

if (!function_exists('interessa_admin_detect_remote_image_extension_from_body')) {
    function interessa_admin_detect_remote_image_extension_from_body(string $body): string {
        if ($body === '') {
            return '';
        }
        if (substr($body, 0, 4) === 'RIFF' && substr($body, 8, 4) === 'WEBP') {
            return 'webp';
        }
        if (substr($body, 0, 8) === "\x89PNG\x0D\x0A\x1A\x0A") {
            return 'png';
        }
        if (substr($body, 0, 3) === "\xFF\xD8\xFF") {
            return 'jpg';
        }
        if (substr($body, 0, 6) === 'GIF87a' || substr($body, 0, 6) === 'GIF89a') {
            return 'gif';
        }

        return '';
    }
}

if (!function_exists('interessa_admin_fetch_remote_image_bytes_via_curl_exe')) {
    function interessa_admin_fetch_remote_image_bytes_via_curl_exe(string $url): array {
        if (!function_exists('exec')) {
            throw new RuntimeException('Systemove stiahnutie nie je dostupne.');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'interessa-packshot-');
        if ($tempFile === false) {
            throw new RuntimeException('Nepodarilo sa pripravit docasny subor pre packshot.');
        }

        $contentType = '';
        $curlExecutable = interessa_admin_windows_curl_executable();
        $command = escapeshellarg($curlExecutable) . ' -L --fail --silent --show-error '
            . '--output ' . escapeshellarg($tempFile) . ' '
            . '--write-out ' . escapeshellarg('%{content_type}') . ' '
            . escapeshellarg($url) . ' 2>&1';

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        $contentType = trim((string) implode("\n", $output));

        if ($exitCode !== 0) {
            @unlink($tempFile);
            $message = trim($contentType);
            throw new RuntimeException('Nepodarilo sa stiahnut obrazok z e-shopu: ' . ($message !== '' ? $message : 'systemove stiahnutie zlyhalo.'));
        }

        $body = @file_get_contents($tempFile);
        @unlink($tempFile);

        if (!is_string($body) || $body === '') {
            throw new RuntimeException('Stiahnuty remote packshot je prazdny.');
        }

        return [
            'body' => $body,
            'content_type' => $contentType,
        ];
    }
}

if (!function_exists('interessa_admin_fetch_remote_image_bytes')) {
    function interessa_admin_fetch_remote_image_bytes(string $url): array {
        $url = trim($url);
        if ($url === '' || !preg_match('~^https?://~i', $url)) {
            throw new RuntimeException('Remote URL pre packshot nie je validna.');
        }

        $contentType = '';
        $body = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_USERAGENT => 'InteresaAdmin/1.0',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $body = curl_exec($ch);
            if ($body === false) {
                $error = (string) curl_error($ch);
                curl_close($ch);
                throw new RuntimeException('Nepodarilo sa stiahnut remote packshot: ' . $error);
            }
            $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($status >= 400) {
                throw new RuntimeException('Remote packshot vratil HTTP ' . $status . '.');
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 20,
                    'follow_location' => 1,
                    'user_agent' => 'InteresaAdmin/1.0',
                ],
            ]);
            $body = @file_get_contents($url, false, $context);
            if ($body !== false) {
                $headers = $http_response_header ?? [];
                foreach ($headers as $header) {
                    if (stripos((string) $header, 'Content-Type:') === 0) {
                        $contentType = trim((string) substr((string) $header, strlen('Content-Type:')));
                        break;
                    }
                }
            }
        }

        if ((!is_string($body) || $body === '') && strtoupper(substr(PHP_OS_FAMILY, 0, 7)) === 'WINDOWS') {
            $fallback = interessa_admin_fetch_remote_image_bytes_via_curl_exe($url);
            if (trim((string) ($fallback['content_type'] ?? '')) === '' && is_string($fallback['body'] ?? null)) {
                $detectedExt = interessa_admin_detect_remote_image_extension_from_body((string) $fallback['body']);
                if ($detectedExt !== '') {
                    $fallback['content_type'] = $detectedExt === 'jpg' ? 'image/jpeg' : ('image/' . $detectedExt);
                }
            }
            return $fallback;
        }

        if (!is_string($body) || $body === '') {
            throw new RuntimeException('Remote packshot je prazdny.');
        }

        return [
            'body' => $body,
            'content_type' => $contentType,
        ];
    }
}

if (!function_exists('interessa_admin_mirror_remote_product_image')) {
    function interessa_admin_mirror_remote_product_image(string $productSlug, string $merchantSlug, string $remoteUrl): string {
        $productSlug = trim($productSlug);
        $merchantSlug = trim($merchantSlug);
        if ($productSlug === '') {
            throw new RuntimeException('Chyba slug produktu.');
        }

        $download = interessa_admin_fetch_remote_image_bytes($remoteUrl);
        $ext = interessa_admin_detect_remote_image_extension($remoteUrl, (string) ($download['content_type'] ?? ''));
        if ($ext !== 'webp') {
            throw new RuntimeException('Remote packshot z e-shopu nie je WebP. Pouzi admin tlacidlo Ulozit packshot z e-shopu, ktore ho prevedie do WebP.');
        }
        $target = interessa_admin_product_image_target_path_for_ext($productSlug, $merchantSlug, $ext);
        interessa_admin_ensure_dir(dirname($target));

        if (@file_put_contents($target, (string) ($download['body'] ?? '')) === false) {
            throw new RuntimeException('Nepodarilo sa ulozit zrkadleny packshot.');
        }

        return interessa_admin_product_image_target_asset_for_ext($productSlug, $merchantSlug, $ext);
    }
}

if (!function_exists('interessa_admin_prepare_remote_product_image_download')) {
    function interessa_admin_prepare_remote_product_image_download(string $slug): array {
        $slug = interessa_admin_slugify($slug);
        if ($slug === '') {
            throw new RuntimeException('Chyba slug produktu.');
        }

        $product = interessa_product($slug);
        if (!is_array($product)) {
            throw new RuntimeException('Vybrany produkt sa nenasiel v katalogu.');
        }

        $normalized = interessa_normalize_product($product);
        $remoteUrl = trim((string) ($normalized['image_remote_src'] ?? ''));
        $enriched = false;

        if ($remoteUrl === '' && trim((string) ($normalized['fallback_url'] ?? '')) !== '') {
            $result = interessa_admin_enrich_product_record_from_source($slug);
            $enriched = !empty($result['saved']);
            $product = interessa_product($slug);
            $normalized = is_array($product) ? interessa_normalize_product($product) : $normalized;
            $remoteUrl = trim((string) ($normalized['image_remote_src'] ?? ''));
        }

        if ($remoteUrl === '') {
            throw new RuntimeException('Produkt nema dostupny remote packshot z e-shopu.');
        }

        $download = interessa_admin_fetch_remote_image_bytes($remoteUrl);
        $contentType = trim((string) ($download['content_type'] ?? 'application/octet-stream'));
        $extension = interessa_admin_detect_remote_image_extension($remoteUrl, $contentType);
        $path = (string) parse_url($remoteUrl, PHP_URL_PATH);
        $baseName = basename($path);
        if ($baseName === '' || $baseName === '/' || !preg_match('~\.[a-z0-9]+$~i', $baseName)) {
            $baseName = $slug . '.' . $extension;
        }

        return [
            'slug' => $slug,
            'remote_url' => $remoteUrl,
            'file_name' => $baseName,
            'content_type' => $contentType,
            'extension' => $extension,
            'body' => (string) ($download['body'] ?? ''),
            'enriched' => $enriched,
        ];
    }
}

if (!function_exists('interessa_admin_fetch_remote_html_via_curl_exe')) {
    function interessa_admin_fetch_remote_html_via_curl_exe(string $url): string {
        if (!function_exists('exec')) {
            throw new RuntimeException('Systemove stiahnutie nie je dostupne.');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'interessa-html-');
        if ($tempFile === false) {
            throw new RuntimeException('Nepodarilo sa pripravit docasny subor pre HTML.');
        }

        $curlExecutable = interessa_admin_windows_curl_executable();
        $command = escapeshellarg($curlExecutable) . ' -L --fail --silent --show-error '
            . '--output ' . escapeshellarg($tempFile) . ' '
            . escapeshellarg($url) . ' 2>&1';

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        if ($exitCode !== 0) {
            @unlink($tempFile);
            $message = trim((string) implode("\n", $output));
            throw new RuntimeException('Nepodarilo sa stiahnut produktovu stranku: ' . ($message !== '' ? $message : 'systemove stiahnutie zlyhalo.'));
        }

        $body = @file_get_contents($tempFile);
        @unlink($tempFile);
        if (!is_string($body) || trim($body) === '') {
            throw new RuntimeException('Stiahnuta produktova stranka je prazdna.');
        }

        return $body;
    }
}

if (!function_exists('interessa_admin_fetch_remote_html')) {
    function interessa_admin_fetch_remote_html(string $url): string {
        $url = trim($url);
        if ($url === '' || !preg_match('~^https?://~i', $url)) {
            throw new RuntimeException('URL produktu nie je validna.');
        }

        $body = false;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_USERAGENT => 'InteresaAdmin/1.0',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ],
            ]);
            $body = curl_exec($ch);
            if ($body === false) {
                $error = (string) curl_error($ch);
                curl_close($ch);
                throw new RuntimeException('Nepodarilo sa stiahnut produktovu stranku: ' . $error);
            }
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($status >= 400) {
                throw new RuntimeException('Produktova stranka vratila HTTP ' . $status . '.');
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 20,
                    'follow_location' => 1,
                    'user_agent' => 'InteresaAdmin/1.0',
                    'header' => "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n",
                ],
            ]);
            $body = @file_get_contents($url, false, $context);
        }

        if ((!is_string($body) || trim($body) === '') && strtoupper(substr(PHP_OS_FAMILY, 0, 7)) === 'WINDOWS') {
            return interessa_admin_fetch_remote_html_via_curl_exe($url);
        }

        if (!is_string($body) || trim($body) === '') {
            throw new RuntimeException('Produktova stranka je prazdna.');
        }

        return $body;
    }
}

if (!function_exists('interessa_admin_looks_like_product_url')) {
    function interessa_admin_looks_like_product_url(string $url): bool {
        $url = trim($url);
        if ($url === '' || !preg_match('~^https?://~i', $url)) {
            return false;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH));
        if ($path === '' || $path === '/') {
            return false;
        }

        return true;
    }
}

if (!function_exists('interessa_admin_url_join')) {
    function interessa_admin_url_join(string $baseUrl, string $candidate): string {
        $candidate = trim($candidate);
        if ($candidate === '') {
            return '';
        }
        if (preg_match('~^https?://~i', $candidate)) {
            return $candidate;
        }
        if (str_starts_with($candidate, '//')) {
            $scheme = (string) parse_url($baseUrl, PHP_URL_SCHEME);
            return ($scheme !== '' ? $scheme : 'https') . ':' . $candidate;
        }

        $parts = parse_url($baseUrl);
        $scheme = (string) ($parts['scheme'] ?? 'https');
        $host = (string) ($parts['host'] ?? '');
        if ($host === '') {
            return $candidate;
        }

        if (str_starts_with($candidate, '/')) {
            return $scheme . '://' . $host . $candidate;
        }

        $path = (string) ($parts['path'] ?? '/');
        $dir = rtrim(str_replace('\\', '/', dirname($path)), '/');
        if ($dir === '' || $dir === '.') {
            $dir = '';
        }

        return $scheme . '://' . $host . ($dir !== '' ? $dir . '/' : '/') . ltrim($candidate, '/');
    }
}

if (!function_exists('interessa_admin_html_extract_meta')) {
    function interessa_admin_html_extract_meta(string $html, array $keys): string {
        foreach ($keys as $key) {
            $patterns = [
                '~<meta[^>]+(?:property|name)\s*=\s*["\']' . preg_quote($key, '~') . '["\'][^>]+content\s*=\s*["\']([^"\']+)["\']~i',
                '~<meta[^>]+content\s*=\s*["\']([^"\']+)["\'][^>]+(?:property|name)\s*=\s*["\']' . preg_quote($key, '~') . '["\']~i',
            ];
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $html, $match) === 1) {
                    return trim(html_entity_decode((string) ($match[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                }
            }
        }

        return '';
    }
}

if (!function_exists('interessa_admin_html_extract_title')) {
    function interessa_admin_html_extract_title(string $html): string {
        if (preg_match('~<title[^>]*>(.*?)</title>~is', $html, $match) !== 1) {
            return '';
        }

        $title = html_entity_decode(strip_tags((string) ($match[1] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = preg_replace('~\s+~u', ' ', $title) ?? $title;
        return trim($title);
    }
}

if (!function_exists('interessa_admin_json_ld_field_value')) {
    function interessa_admin_json_ld_field_value(mixed $node, string $field): string {
        if (!is_array($node)) {
            return '';
        }

        if (array_key_exists($field, $node)) {
            $value = $node[$field];
            if (is_string($value)) {
                return trim($value);
            }
            if (is_array($value)) {
                if ($field === 'image') {
                    if (isset($value['url']) && is_string($value['url'])) {
                        return trim($value['url']);
                    }
                    foreach ($value as $item) {
                        $resolved = is_array($item)
                            ? interessa_admin_json_ld_field_value($item, 'url')
                            : trim((string) $item);
                        if ($resolved !== '') {
                            return $resolved;
                        }
                    }
                }
                if ($field === 'brand') {
                    if (isset($value['name']) && is_string($value['name'])) {
                        return trim($value['name']);
                    }
                    foreach ($value as $item) {
                        $resolved = is_array($item)
                            ? interessa_admin_json_ld_field_value($item, 'name')
                            : trim((string) $item);
                        if ($resolved !== '') {
                            return $resolved;
                        }
                    }
                }
            }
        }

        foreach ($node as $child) {
            if (!is_array($child)) {
                continue;
            }
            $resolved = interessa_admin_json_ld_field_value($child, $field);
            if ($resolved !== '') {
                return $resolved;
            }
        }

        return '';
    }
}

if (!function_exists('interessa_admin_extract_json_ld_data')) {
    function interessa_admin_extract_json_ld_data(string $html): array {
        $payload = [
            'name' => '',
            'description' => '',
            'image' => '',
            'brand' => '',
        ];

        if (preg_match_all('~<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>~is', $html, $matches) < 1) {
            return $payload;
        }

        foreach (($matches[1] ?? []) as $scriptBody) {
            $decoded = json_decode(html_entity_decode((string) $scriptBody, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            if (!is_array($decoded)) {
                continue;
            }

            foreach (['name', 'description', 'image', 'brand'] as $field) {
                if ($payload[$field] !== '') {
                    continue;
                }
                $payload[$field] = interessa_admin_json_ld_field_value($decoded, $field);
            }
        }

        return $payload;
    }
}

if (!function_exists('interessa_admin_extract_product_page_data')) {
    function interessa_admin_extract_product_page_data(string $url): array {
        $html = interessa_admin_fetch_remote_html($url);
        $jsonLd = interessa_admin_extract_json_ld_data($html);

        $name = $jsonLd['name'] !== '' ? $jsonLd['name'] : interessa_admin_html_extract_meta($html, ['og:title']);
        if ($name === '') {
            $name = interessa_admin_html_extract_title($html);
        }

        $summary = $jsonLd['description'] !== '' ? $jsonLd['description'] : interessa_admin_html_extract_meta($html, ['description', 'og:description', 'twitter:description']);
        $image = $jsonLd['image'] !== '' ? $jsonLd['image'] : interessa_admin_html_extract_meta($html, ['og:image', 'twitter:image']);
        $brand = $jsonLd['brand'];

        $clean = static function (string $value): string {
            $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $value = strip_tags($value);
            $value = preg_replace('~\s+~u', ' ', $value) ?? $value;
            return trim($value);
        };

        return [
            'name' => $clean($name),
            'summary' => $clean($summary),
            'brand' => $clean($brand),
            'image_remote_src' => interessa_admin_url_join($url, $image),
            'source_url' => $url,
        ];
    }
}

if (!function_exists('interessa_admin_guess_merchant_from_url')) {
    function interessa_admin_guess_merchant_from_url(string $url): array {
        $host = strtolower(trim((string) parse_url($url, PHP_URL_HOST)));
        $host = preg_replace('~^www\.~', '', $host) ?? $host;

        if ($host === '') {
            return ['merchant' => '', 'merchant_slug' => ''];
        }

        $map = [
            'gymbeam' => ['merchant' => 'GymBeam', 'merchant_slug' => 'gymbeam'],
            'aktin' => ['merchant' => 'Aktin', 'merchant_slug' => 'aktin'],
            'vilgain' => ['merchant' => 'Aktin', 'merchant_slug' => 'aktin'],
            'myprotein' => ['merchant' => 'Myprotein', 'merchant_slug' => 'myprotein'],
            'protein' => ['merchant' => 'Protein.sk', 'merchant_slug' => 'protein-sk'],
            'ironaesthetics' => ['merchant' => 'IronAesthetics', 'merchant_slug' => 'ironaesthetics'],
            'symprove' => ['merchant' => 'Symprove', 'merchant_slug' => 'symprove'],
            'imunoklub' => ['merchant' => 'Imunoklub', 'merchant_slug' => 'imunoklub'],
            'kloubus' => ['merchant' => 'Kloubus', 'merchant_slug' => 'kloubus'],
        ];

        foreach ($map as $needle => $merchantMeta) {
            if (str_contains($host, $needle)) {
                return $merchantMeta;
            }
        }

        $parts = explode('.', $host);
        $base = count($parts) >= 2 ? $parts[count($parts) - 2] : $host;
        $slug = interessa_admin_slugify($base);

        return [
            'merchant' => $slug !== '' ? ucfirst($slug) : '',
            'merchant_slug' => $slug,
        ];
    }
}

if (!function_exists('interessa_admin_prepare_product_from_input_link')) {
    function interessa_admin_prepare_product_from_input_link(string $slug, string $inputUrl): array {
        $slug = interessa_admin_slugify($slug);
        if ($slug === '') {
            throw new RuntimeException('Chyba kod produktu.');
        }

        $product = interessa_product($slug);
        if (!is_array($product)) {
            throw new RuntimeException('Vybrany produkt sa nenasiel v katalogu.');
        }

        $inputUrl = trim($inputUrl);
        if ($inputUrl === '') {
            throw new RuntimeException('Vloz link na produkt alebo Dognet link.');
        }
        if (!preg_match('~^https?://~i', $inputUrl)) {
            throw new RuntimeException('Link musi zacinat na http:// alebo https:// .');
        }

        $normalized = interessa_normalize_product($product);
        $override = interessa_admin_product_record($slug) ?? [];
        $payload = array_replace($normalized, $override);

        $linkType = aff_detect_link_type_from_url($inputUrl, 'affiliate');
        $finalUrl = $linkType === 'affiliate' ? aff_extract_final_url($inputUrl) : $inputUrl;
        $finalUrl = trim($finalUrl);
        if ($finalUrl === '') {
            throw new RuntimeException('Z linku sa nepodarilo zistit cielovu stranku produktu.');
        }
        if (!interessa_admin_looks_like_product_url($finalUrl)) {
            throw new RuntimeException('Tento link zatial nevyzera ako konkretna stranka produktu. Vloz link priamo na jeden produkt.');
        }

        $merchantMeta = interessa_admin_guess_merchant_from_url($finalUrl);
        if (trim((string) ($payload['merchant'] ?? '')) === '' && trim((string) ($merchantMeta['merchant'] ?? '')) !== '') {
            $payload['merchant'] = (string) $merchantMeta['merchant'];
        }
        if (trim((string) ($payload['merchant_slug'] ?? '')) === '' && trim((string) ($merchantMeta['merchant_slug'] ?? '')) !== '') {
            $payload['merchant_slug'] = (string) $merchantMeta['merchant_slug'];
        }

        $affiliateCode = trim((string) ($payload['affiliate_code'] ?? ''));
        if ($affiliateCode === '') {
            $merchantPart = interessa_admin_slugify((string) ($payload['merchant_slug'] ?? ''));
            $affiliateCode = $merchantPart !== '' ? ($slug . '-' . $merchantPart) : ($slug . '-link');
            $payload['affiliate_code'] = $affiliateCode;
        }

        $payload['fallback_url'] = $finalUrl;

        interessa_admin_save_affiliate_record($affiliateCode, [
            'url' => $inputUrl,
            'merchant' => (string) ($payload['merchant'] ?? ''),
            'merchant_slug' => (string) ($payload['merchant_slug'] ?? ''),
            'product_slug' => $slug,
            'link_type' => $linkType,
            'source' => 'admin-quick-link',
        ]);

        interessa_admin_save_product_record($slug, $payload);
        $enrichment = interessa_admin_enrich_product_record_from_source($slug);
        $autoImageSaved = false;
        $autoImageError = '';

        $preparedProduct = interessa_product($slug);
        if (is_array($preparedProduct)) {
            $preparedNormalized = interessa_normalize_product($preparedProduct);
            $remoteSrc = trim((string) ($preparedNormalized['image_remote_src'] ?? ''));
            $merchantSlug = trim((string) ($preparedNormalized['merchant_slug'] ?? ''));

            if (empty($preparedNormalized['has_local_image']) && $remoteSrc !== '' && interessa_admin_detect_remote_image_extension($remoteSrc) === 'webp') {
                try {
                    $asset = interessa_admin_mirror_remote_product_image($slug, $merchantSlug, $remoteSrc);
                    $preparedPayload = array_replace($preparedNormalized, interessa_admin_product_record($slug) ?? []);
                    $preparedPayload['image_asset'] = $asset;
                    interessa_admin_save_product_record($slug, $preparedPayload);
                    $autoImageSaved = true;
                } catch (Throwable $e) {
                    $autoImageError = trim((string) $e->getMessage());
                }
            }
        }

        return [
            'slug' => $slug,
            'affiliate_code' => $affiliateCode,
            'final_url' => $finalUrl,
            'link_type' => $linkType,
            'enrichment' => $enrichment,
            'auto_image_saved' => $autoImageSaved,
            'auto_image_error' => $autoImageError,
            'click_ready' => aff_resolve($affiliateCode) !== null,
        ];
    }
}

if (!function_exists('interessa_admin_enrich_product_record_from_source')) {
    function interessa_admin_enrich_product_record_from_source(string $slug): array {
        $slug = interessa_admin_slugify($slug);
        if ($slug === '') {
            throw new RuntimeException('Chyba slug produktu.');
        }

        $product = interessa_product($slug);
        if (!is_array($product)) {
            throw new RuntimeException('Vybrany produkt sa nenasiel v katalogu.');
        }

        $normalized = interessa_normalize_product($product);
        $override = interessa_admin_product_record($slug) ?? [];
        $fallbackUrl = trim((string) ($override['fallback_url'] ?? $normalized['fallback_url'] ?? ''));
        if ($fallbackUrl === '') {
            $fallbackUrl = aff_product_url_for_code((string) ($normalized['affiliate_code'] ?? ''));
        }
        if ($fallbackUrl === '') {
            throw new RuntimeException('Produkt nema referencnu URL produktu na e-shope. Najprv vloz priamu URL konkretneho produktu.');
        }
        if (!interessa_admin_looks_like_product_url($fallbackUrl)) {
            throw new RuntimeException('Toto zatial nie je priama URL produktu. Do pola URL produktu vloz konkretnu stranku produktu z e-shopu, nie len hlavnu stranku obchodu.');
        }

        $detected = interessa_admin_extract_product_page_data($fallbackUrl);
        $payload = array_replace($normalized, $override);
        $updated = [];

        if (trim((string) ($payload['name'] ?? '')) === '' && trim((string) ($detected['name'] ?? '')) !== '') {
            $payload['name'] = (string) $detected['name'];
            $updated[] = 'name';
        }
        if (trim((string) ($payload['brand'] ?? '')) === '' && trim((string) ($detected['brand'] ?? '')) !== '') {
            $payload['brand'] = (string) $detected['brand'];
            $updated[] = 'brand';
        }
        if (trim((string) ($payload['summary'] ?? '')) === '' && trim((string) ($detected['summary'] ?? '')) !== '') {
            $payload['summary'] = (string) $detected['summary'];
            $updated[] = 'summary';
        }
        if (trim((string) ($payload['fallback_url'] ?? '')) === '' && $fallbackUrl !== '') {
            $payload['fallback_url'] = $fallbackUrl;
            $updated[] = 'fallback_url';
        }
        if (trim((string) ($payload['image_remote_src'] ?? '')) === '' && trim((string) ($detected['image_remote_src'] ?? '')) !== '') {
            $payload['image_remote_src'] = (string) $detected['image_remote_src'];
            $updated[] = 'image_remote_src';
        }

        if ($updated !== []) {
            interessa_admin_save_product_record($slug, $payload);
        }

        return [
            'slug' => $slug,
            'updated_fields' => $updated,
            'detected' => $detected,
            'saved' => $updated !== [],
        ];
    }
}

if (!function_exists('interessa_admin_autofill_product_image_gaps')) {
    function interessa_admin_autofill_product_image_gaps(array $rows): array {
        $seen = [];
        $result = [
            'processed' => 0,
            'enriched' => 0,
            'mirrored' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $slug = interessa_admin_slugify((string) ($row['product_slug'] ?? ''));
            if ($slug === '' || isset($seen[$slug])) {
                continue;
            }
            $seen[$slug] = true;
            $result['processed']++;

            try {
                $enrichment = interessa_admin_enrich_product_record_from_source($slug);
                if (!empty($enrichment['saved'])) {
                    $result['enriched']++;
                }

                $product = interessa_product($slug);
                if (!is_array($product)) {
                    continue;
                }

                $normalized = interessa_normalize_product($product);
                if (!empty($normalized['has_local_image'])) {
                    continue;
                }

                $remoteSrc = trim((string) ($normalized['image_remote_src'] ?? ''));
                if ($remoteSrc === '') {
                    continue;
                }

                if (interessa_admin_detect_remote_image_extension($remoteSrc) !== 'webp') {
                    continue;
                }

                $merchantSlug = trim((string) ($normalized['merchant_slug'] ?? ''));
                $asset = interessa_admin_mirror_remote_product_image($slug, $merchantSlug, $remoteSrc);
                $payload = array_replace($normalized, interessa_admin_product_record($slug) ?? []);
                $payload['image_asset'] = $asset;
                interessa_admin_save_product_record($slug, $payload);
                $result['mirrored']++;
            } catch (Throwable $e) {
                $result['failed']++;
                $result['errors'][$slug] = trim($e->getMessage());
            }
        }

        return $result;
    }
}

if (!function_exists('interessa_admin_export_bundle')) {
    function interessa_admin_export_bundle(): array {
        return [
            'exported_at' => date('c'),
            'articles' => interessa_admin_all_article_overrides(),
            'products' => interessa_admin_products(),
            'affiliate_links' => interessa_admin_affiliate_links(),
        ];
    }
}

if (!function_exists('interessa_admin_import_bundle')) {
    function interessa_admin_import_bundle(array $bundle): array {
        $imported = [
            'articles' => 0,
            'products' => 0,
            'affiliate_links' => 0,
        ];

        foreach (($bundle['articles'] ?? []) as $slug => $article) {
            if (!is_array($article)) {
                continue;
            }
            interessa_admin_save_article_override((string) $slug, $article);
            $imported['articles']++;
        }

        $products = [];
        foreach (($bundle['products'] ?? []) as $slug => $product) {
            if (!is_array($product)) {
                continue;
            }
            $normalizedSlug = interessa_admin_slugify((string) $slug);
            if ($normalizedSlug === '') {
                continue;
            }
            $products[$normalizedSlug] = interessa_admin_normalize_product_record($normalizedSlug, $product);
        }
        if ($products !== []) {
            interessa_admin_save_products(array_replace(interessa_admin_products(), $products));
            $imported['products'] = count($products);
        }

        $links = [];
        foreach (($bundle['affiliate_links'] ?? []) as $code => $record) {
            if (!is_array($record)) {
                continue;
            }
            $normalizedCode = interessa_admin_slugify((string) $code);
            if ($normalizedCode === '') {
                continue;
            }
            $links[$normalizedCode] = interessa_admin_normalize_affiliate_record($normalizedCode, $record);
        }
        if ($links !== []) {
            interessa_admin_save_affiliate_links(array_replace(interessa_admin_affiliate_links(), $links));
            $imported['affiliate_links'] = count($links);
        }

        return $imported;
    }
}

if (!function_exists('interessa_admin_delete_article_override')) {
    function interessa_admin_delete_article_override(string $slug): void {
        $path = interessa_admin_article_override_path($slug);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

if (!function_exists('interessa_admin_delete_product_record')) {
    function interessa_admin_delete_product_record(string $slug): void {
        $slug = interessa_admin_slugify($slug);
        if ($slug === '') {
            return;
        }

        $products = interessa_admin_products();
        unset($products[$slug]);
        if ($products === []) {
            if (is_file(INTERESSA_ADMIN_PRODUCTS_FILE)) {
                @unlink(INTERESSA_ADMIN_PRODUCTS_FILE);
            }
            return;
        }

        interessa_admin_save_products($products);
    }
}

if (!function_exists('interessa_admin_delete_affiliate_record')) {
    function interessa_admin_delete_affiliate_record(string $code): void {
        $code = interessa_admin_slugify($code);
        if ($code === '') {
            return;
        }

        $links = interessa_admin_affiliate_links();
        unset($links[$code]);
        if ($links === []) {
            if (is_file(INTERESSA_ADMIN_AFFILIATE_LINKS_FILE)) {
                @unlink(INTERESSA_ADMIN_AFFILIATE_LINKS_FILE);
            }
            return;
        }

        interessa_admin_save_affiliate_links($links);
    }
}
