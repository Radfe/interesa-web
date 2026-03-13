<?php

declare(strict_types=1);

const INTERESSA_ADMIN_STORAGE_DIR = __DIR__ . '/../storage/admin';
const INTERESSA_ADMIN_ARTICLES_DIR = INTERESSA_ADMIN_STORAGE_DIR . '/articles';
const INTERESSA_ADMIN_PRODUCTS_FILE = INTERESSA_ADMIN_STORAGE_DIR . '/products.json';
const INTERESSA_ADMIN_AFFILIATE_LINKS_FILE = INTERESSA_ADMIN_STORAGE_DIR . '/affiliate-links.json';

if (!function_exists('interessa_admin_ensure_dir')) {
    function interessa_admin_ensure_dir(string $dir): void {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
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

        $target = interessa_admin_article_hero_path($slug, 'webp');
        interessa_admin_ensure_dir(dirname($target));
        if (!move_uploaded_file($tmp, $target)) {
            throw new RuntimeException('Nepodarilo sa ulozit hero obrazok.');
        }

        return interessa_admin_article_hero_asset($slug, 'webp');
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

        $target = function_exists('interessa_product_image_target_path')
            ? interessa_product_image_target_path($productSlug, $merchantSlug)
            : dirname(__DIR__) . '/assets/img/products/' . ($merchantSlug !== '' ? $merchantSlug . '/' : '') . $productSlug . '/main.webp';
        interessa_admin_ensure_dir(dirname($target));
        if (!move_uploaded_file($tmp, $target)) {
            throw new RuntimeException('Nepodarilo sa ulozit produktovy obrazok.');
        }

        return function_exists('interessa_product_image_target_asset')
            ? interessa_product_image_target_asset($productSlug, $merchantSlug)
            : 'img/products/' . ($merchantSlug !== '' ? $merchantSlug . '/' : '') . $productSlug . '/main.webp';
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
    function interessa_admin_fetch_remote_image_bytes_via_curl_exe(string $url): array {
        if (!function_exists('exec')) {
            throw new RuntimeException('Systemovy curl.exe nie je dostupny.');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'interessa-packshot-');
        if ($tempFile === false) {
            throw new RuntimeException('Nepodarilo sa pripravit docasny subor pre packshot.');
        }

        $contentType = '';
        $command = 'curl.exe -L --fail --silent --show-error '
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
            throw new RuntimeException('Nepodarilo sa stiahnut remote packshot: ' . ($message !== '' ? $message : 'curl.exe zlyhal.'));
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
            return interessa_admin_fetch_remote_image_bytes_via_curl_exe($url);
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
        $target = interessa_admin_product_image_target_path_for_ext($productSlug, $merchantSlug, $ext);
        interessa_admin_ensure_dir(dirname($target));

        if (@file_put_contents($target, (string) ($download['body'] ?? '')) === false) {
            throw new RuntimeException('Nepodarilo sa ulozit zrkadleny packshot.');
        }

        return interessa_admin_product_image_target_asset_for_ext($productSlug, $merchantSlug, $ext);
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
