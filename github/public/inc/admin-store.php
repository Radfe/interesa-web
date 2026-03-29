<?php

declare(strict_types=1);

require_once __DIR__ . '/affiliates.php';

const INTERESSA_ADMIN_STORAGE_DIR = __DIR__ . '/../storage/admin';
const INTERESSA_ADMIN_ARTICLES_DIR = INTERESSA_ADMIN_STORAGE_DIR . '/articles';
const INTERESSA_ADMIN_PRODUCTS_FILE = INTERESSA_ADMIN_STORAGE_DIR . '/products.json';
const INTERESSA_ADMIN_AFFILIATE_LINKS_FILE = INTERESSA_ADMIN_STORAGE_DIR . '/affiliate-links.json';
const INTERESSA_ADMIN_ARTICLE_PRODUCTS_FILE = INTERESSA_ADMIN_STORAGE_DIR . '/article-products.json';
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

        if ($baseName === 'logo-full') {
            interessa_admin_delete_asset_variants(interessa_admin_brand_dir() . '/logo-full-web');
            $generated = null;
            if ($ext === 'svg') {
                $generated = interessa_admin_generate_brand_logo_web_svg_derivative($target, 'logo-full-web');
            }
            if ($generated === null) {
                interessa_admin_generate_brand_logo_web_derivative($target, $ext, 'logo-full-web');
            }
        }

        return 'img/brand/' . $baseName . '.' . $ext;
    }
}

if (!function_exists('interessa_admin_extract_canva_svg_bounds')) {
    function interessa_admin_extract_canva_svg_bounds(string $svg): ?array {
        $patterns = [
            '~<clipPath[^>]*>\s*<path[^>]+d="M\s*([0-9.\-]+)\s+([0-9.\-]+)\s+L\s*([0-9.\-]+)\s+[0-9.\-]+\s+L\s*[0-9.\-]+\s+([0-9.\-]+)\s+L\s*([0-9.\-]+)\s+([0-9.\-]+)\s+Z~i',
            '~<clipPath[^>]*>\s*<rect[^>]+x="([0-9.\-]+)"[^>]+y="([0-9.\-]+)"[^>]+width="([0-9.\-]+)"[^>]+height="([0-9.\-]+)"~i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $svg, $match) !== 1) {
                continue;
            }

            if (count($match) >= 7) {
                $left = (float) $match[1];
                $top = (float) $match[2];
                $right = (float) $match[3];
                $bottom = (float) $match[4];
                $leftCheck = (float) $match[5];
                $bottomCheck = (float) $match[6];
                if (abs($left - $leftCheck) > 1 || abs($bottom - $bottomCheck) > 1) {
                    continue;
                }
                return [
                    'x' => $left,
                    'y' => $top,
                    'width' => max(1.0, $right - $left),
                    'height' => max(1.0, $bottom - $top),
                ];
            }

            if (count($match) >= 5) {
                return [
                    'x' => (float) $match[1],
                    'y' => (float) $match[2],
                    'width' => max(1.0, (float) $match[3]),
                    'height' => max(1.0, (float) $match[4]),
                ];
            }
        }

        return null;
    }
}

if (!function_exists('interessa_admin_generate_brand_logo_web_svg_derivative')) {
    function interessa_admin_generate_brand_logo_web_svg_derivative(string $sourcePath, string $derivativeBaseName = 'logo-full-web'): ?string {
        if (!is_file($sourcePath)) {
            return null;
        }

        $svg = @file_get_contents($sourcePath);
        if (!is_string($svg) || $svg === '') {
            return null;
        }

        $bounds = interessa_admin_extract_canva_svg_bounds($svg);
        if ($bounds === null) {
            $target = interessa_admin_brand_dir() . '/' . interessa_admin_slugify($derivativeBaseName) . '.svg';
            $written = @copy($sourcePath, $target);
            return $written ? 'img/brand/' . interessa_admin_slugify($derivativeBaseName) . '.svg' : null;
        }

        if (($bounds['width'] ?? 0.0) <= ($bounds['height'] ?? 0.0)) {
            $target = interessa_admin_brand_dir() . '/' . interessa_admin_slugify($derivativeBaseName) . '.svg';
            $written = @copy($sourcePath, $target);
            return $written ? 'img/brand/' . interessa_admin_slugify($derivativeBaseName) . '.svg' : null;
        }

        $x = rtrim(rtrim(number_format($bounds['x'], 6, '.', ''), '0'), '.');
        $y = rtrim(rtrim(number_format($bounds['y'], 6, '.', ''), '0'), '.');
        $width = rtrim(rtrim(number_format($bounds['width'], 6, '.', ''), '0'), '.');
        $height = rtrim(rtrim(number_format($bounds['height'], 6, '.', ''), '0'), '.');
        $viewBox = $x . ' ' . $y . ' ' . $width . ' ' . $height;

        $svg = preg_replace('~\sviewBox="[^"]+"~i', ' viewBox="' . $viewBox . '"', $svg, 1) ?? $svg;
        $svg = preg_replace('~\swidth="[^"]+"~i', ' width="' . $width . '"', $svg, 1) ?? $svg;
        $svg = preg_replace('~\sheight="[^"]+"~i', ' height="' . $height . '"', $svg, 1) ?? $svg;

        $target = interessa_admin_brand_dir() . '/' . interessa_admin_slugify($derivativeBaseName) . '.svg';
        $written = @file_put_contents($target, $svg);
        if (!is_int($written) || $written <= 0) {
            return null;
        }

        return 'img/brand/' . interessa_admin_slugify($derivativeBaseName) . '.svg';
    }
}

if (!function_exists('interessa_admin_extract_embedded_svg_raster_bytes')) {
    function interessa_admin_extract_embedded_svg_raster_bytes(string $svg): ?string {
        if ($svg === '') {
            return null;
        }

        if (preg_match('~data:image/(?:png|jpe?g|webp);base64,([^"\']+)~is', $svg, $match) !== 1) {
            return null;
        }

        $payload = preg_replace('~\s+~', '', (string) ($match[1] ?? '')) ?? '';
        if ($payload === '') {
            return null;
        }

        $decoded = base64_decode($payload, true);
        return is_string($decoded) && $decoded !== '' ? $decoded : null;
    }
}

if (!function_exists('interessa_admin_load_brand_image_resource')) {
    function interessa_admin_load_brand_image_resource(string $sourcePath, string $ext) {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $ext = strtolower(trim($ext));
        $bytes = @file_get_contents($sourcePath);
        if (!is_string($bytes) || $bytes === '') {
            return null;
        }

        if ($ext === 'svg') {
            $bytes = interessa_admin_extract_embedded_svg_raster_bytes($bytes) ?? '';
            if ($bytes === '') {
                return null;
            }
        }

        $image = @imagecreatefromstring($bytes);
        return $image !== false ? $image : null;
    }
}

if (!function_exists('interessa_admin_brand_pixel_is_visible')) {
    function interessa_admin_brand_pixel_is_visible(int $rgba): bool {
        $alpha = ($rgba >> 24) & 0x7F;
        if ($alpha >= 120) {
            return false;
        }

        $red = ($rgba >> 16) & 0xFF;
        $green = ($rgba >> 8) & 0xFF;
        $blue = $rgba & 0xFF;

        return !($red >= 247 && $green >= 247 && $blue >= 247);
    }
}

if (!function_exists('interessa_admin_brand_visible_bounds')) {
    function interessa_admin_brand_visible_bounds($image): ?array {
        $width = imagesx($image);
        $height = imagesy($image);
        $left = $width;
        $top = $height;
        $right = -1;
        $bottom = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if (!interessa_admin_brand_pixel_is_visible((int) imagecolorat($image, $x, $y))) {
                    continue;
                }

                if ($x < $left) {
                    $left = $x;
                }
                if ($y < $top) {
                    $top = $y;
                }
                if ($x > $right) {
                    $right = $x;
                }
                if ($y > $bottom) {
                    $bottom = $y;
                }
            }
        }

        if ($right < 0 || $bottom < 0) {
            return null;
        }

        return [
            'left' => $left,
            'top' => $top,
            'right' => $right,
            'bottom' => $bottom,
        ];
    }
}

if (!function_exists('interessa_admin_generate_brand_logo_web_derivative')) {
    function interessa_admin_generate_brand_logo_web_derivative(string $sourcePath, string $ext, string $derivativeBaseName = 'logo-full-web'): ?string {
        if (!is_file($sourcePath) || !function_exists('imagepng') || !function_exists('imagecreatetruecolor')) {
            return null;
        }

        $image = interessa_admin_load_brand_image_resource($sourcePath, $ext);
        if ($image === null) {
            return null;
        }

        $bounds = interessa_admin_brand_visible_bounds($image);
        if ($bounds === null) {
            imagedestroy($image);
            return null;
        }

        $contentWidth = max(1, $bounds['right'] - $bounds['left'] + 1);
        $contentHeight = max(1, $bounds['bottom'] - $bounds['top'] + 1);
        $padX = max(18, (int) round($contentWidth * 0.04));
        $padY = max(12, (int) round($contentHeight * 0.08));

        $cropLeft = max(0, $bounds['left'] - $padX);
        $cropTop = max(0, $bounds['top'] - $padY);
        $cropRight = min(imagesx($image) - 1, $bounds['right'] + $padX);
        $cropBottom = min(imagesy($image) - 1, $bounds['bottom'] + $padY);
        $cropWidth = max(1, $cropRight - $cropLeft + 1);
        $cropHeight = max(1, $cropBottom - $cropTop + 1);

        $cropped = imagecreatetruecolor($cropWidth, $cropHeight);
        if ($cropped === false) {
            imagedestroy($image);
            return null;
        }

        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        $transparent = imagecolorallocatealpha($cropped, 255, 255, 255, 127);
        imagefilledrectangle($cropped, 0, 0, $cropWidth, $cropHeight, $transparent);
        imagecopy($cropped, $image, 0, 0, $cropLeft, $cropTop, $cropWidth, $cropHeight);

        $target = interessa_admin_brand_dir() . '/' . interessa_admin_slugify($derivativeBaseName) . '.png';
        imagepng($cropped, $target, 9);

        imagedestroy($cropped);
        imagedestroy($image);

        return 'img/brand/' . interessa_admin_slugify($derivativeBaseName) . '.png';
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
        $written = @file_put_contents($tmpPath, $json . PHP_EOL);
        if ($written === false) {
            @unlink($tmpPath);
            throw new RuntimeException('Nepodarilo sa zapisat docasny admin subor.');
        }
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

if (!function_exists('interessa_admin_article_backup_dir')) {
    function interessa_admin_article_backup_dir(string $slug): string {
        $slug = interessa_admin_slugify($slug);
        return INTERESSA_ADMIN_ARTICLES_DIR . '/_backups/' . $slug;
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

if (!function_exists('interessa_admin_normalize_related_links')) {
    function interessa_admin_normalize_related_links(string $articleSlug, array $items): array {
        $articleSlug = function_exists('canonical_article_slug') ? canonical_article_slug($articleSlug) : trim($articleSlug);
        $knownSlugs = [];

        if (function_exists('article_registry')) {
            foreach (article_registry() as $knownSlug => $_row) {
                $normalized = function_exists('canonical_article_slug') ? canonical_article_slug((string) $knownSlug) : trim((string) $knownSlug);
                if ($normalized !== '') {
                    $knownSlugs[$normalized] = true;
                }
            }
        }

        foreach (glob(INTERESSA_ADMIN_ARTICLES_DIR . '/*.json') ?: [] as $file) {
            $normalized = function_exists('canonical_article_slug')
                ? canonical_article_slug((string) basename($file, '.json'))
                : trim((string) basename($file, '.json'));
            if ($normalized !== '') {
                $knownSlugs[$normalized] = true;
            }
        }

        $normalizedItems = [];
        $seen = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $slug = interessa_admin_slugify((string) ($item['slug'] ?? ''));
            $slug = function_exists('canonical_article_slug') ? canonical_article_slug($slug) : $slug;
            if ($slug === '' || $slug === $articleSlug || isset($seen[$slug]) || !isset($knownSlugs[$slug])) {
                continue;
            }

            $seen[$slug] = true;
            $normalizedItems[] = [
                'slug' => $slug,
                'label' => interessa_admin_normalize_text($item['label'] ?? ''),
                'description' => interessa_admin_normalize_text($item['description'] ?? ''),
            ];

            if (count($normalizedItems) >= 4) {
                break;
            }
        }

        return $normalizedItems;
    }
}

if (!function_exists('interessa_admin_normalize_product_recommendation')) {
    function interessa_admin_normalize_product_recommendation(string $articleSlug, array $data): array {
        $articleSlug = function_exists('canonical_article_slug') ? canonical_article_slug($articleSlug) : trim($articleSlug);
        $intentKey = interessa_admin_slugify((string) ($data['intent_key'] ?? ''));
        $generatedAt = trim((string) ($data['generated_at'] ?? ''));
        $appliedAt = trim((string) ($data['applied_at'] ?? ''));
        $normalizedItems = [];
        $seenSlots = [];
        $seenSlugs = [];

        foreach ((array) ($data['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $slot = max(1, min(3, (int) ($item['slot'] ?? 0)));
            $productSlug = interessa_admin_slugify((string) ($item['product_slug'] ?? $item['slug'] ?? ''));
            if ($productSlug === '' || isset($seenSlots[$slot]) || isset($seenSlugs[$productSlug])) {
                continue;
            }

            $seenSlots[$slot] = true;
            $seenSlugs[$productSlug] = true;
            $normalizedItems[] = [
                'slot' => $slot,
                'product_slug' => $productSlug,
                'role' => interessa_admin_normalize_candidate_role($item['role'] ?? 'standard'),
                'branch' => interessa_admin_slugify((string) ($item['branch'] ?? '')),
                'branch_label' => interessa_admin_normalize_text($item['branch_label'] ?? ''),
                'reasoning' => interessa_admin_normalize_text($item['reasoning'] ?? ''),
                'score' => max(0, (int) ($item['score'] ?? 0)),
                'merchant' => interessa_admin_normalize_text($item['merchant'] ?? ''),
                'merchant_slug' => interessa_admin_slugify((string) ($item['merchant_slug'] ?? '')),
                'category' => normalize_category_slug((string) ($item['category'] ?? '')),
                'affiliate_ready' => !empty($item['affiliate_ready']),
                'product_level_ready' => !empty($item['product_level_ready']),
            ];
        }

        usort($normalizedItems, static function (array $left, array $right): int {
            return max(1, (int) ($left['slot'] ?? 1)) <=> max(1, (int) ($right['slot'] ?? 1));
        });

        return [
            'article_slug' => $articleSlug,
            'intent_key' => $intentKey,
            'intent_label' => interessa_admin_normalize_text($data['intent_label'] ?? ''),
            'summary' => interessa_admin_normalize_text($data['summary'] ?? ''),
            'generated_at' => $generatedAt,
            'applied_at' => $appliedAt,
            'items' => $normalizedItems,
        ];
    }
}

if (!function_exists('interessa_admin_normalize_article_override')) {
    function interessa_admin_normalize_article_override(string $slug, array $data): array {
        $canonicalSlug = function_exists('canonical_article_slug') ? canonical_article_slug($slug) : $slug;
        $updatedAt = trim((string) ($data['updated_at'] ?? ''));
        $updatedBy = interessa_admin_normalize_text($data['updated_by'] ?? '');

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
            'related_links' => interessa_admin_normalize_related_links($canonicalSlug, is_array($data['related_links'] ?? null) ? $data['related_links'] : []),
            'product_recommendation' => interessa_admin_normalize_product_recommendation($canonicalSlug, is_array($data['product_recommendation'] ?? null) ? $data['product_recommendation'] : []),
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
            'updated_at' => $updatedAt,
            'updated_by' => $updatedBy !== '' ? $updatedBy : 'admin',
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
        $result = interessa_admin_safe_save_article_override($slug, $data);
        if (!($result['success'] ?? false)) {
            throw new RuntimeException((string) ($result['error'] ?? 'Clanok sa nepodarilo ulozit.'));
        }
    }
}

if (!function_exists('interessa_admin_write_article_override_raw')) {
    function interessa_admin_write_article_override_raw(string $slug, array $data): void {
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

if (!function_exists('interessa_admin_validate_article_save_payload')) {
    function interessa_admin_validate_article_save_payload(string $slug, array $data): array {
        $slug = function_exists('canonical_article_slug') ? canonical_article_slug($slug) : trim($slug);
        if ($slug === '') {
            return [
                'success' => false,
                'error' => 'Chyba slug clanku.',
            ];
        }

        $title = interessa_admin_normalize_text($data['title'] ?? '');
        if ($title === '') {
            return [
                'success' => false,
                'error' => 'Vypln nazov clanku.',
            ];
        }

        foreach (['intro', 'meta_title', 'meta_description', 'category', 'hero_asset'] as $field) {
            if (array_key_exists($field, $data) && is_array($data[$field])) {
                return [
                    'success' => false,
                    'error' => 'Pole ' . $field . ' ma neplatny format.',
                ];
            }
        }

        foreach (['sections', 'comparison', 'related_links', 'product_recommendation', 'recommended_products', 'product_plan'] as $field) {
            if (array_key_exists($field, $data) && !is_array($data[$field])) {
                return [
                    'success' => false,
                    'error' => 'Pole ' . $field . ' musi byt pole.',
                ];
            }
        }

        return [
            'success' => true,
            'error' => '',
        ];
    }
}

if (!function_exists('interessa_admin_create_article_backup')) {
    function interessa_admin_create_article_backup(string $slug, string $path): ?string {
        if (!is_file($path)) {
            return null;
        }

        $backupDir = interessa_admin_article_backup_dir($slug);
        interessa_admin_ensure_dir($backupDir);
        $backupPath = $backupDir . '/' . date('Ymd-His') . '.json';
        if (!@copy($path, $backupPath)) {
            throw new RuntimeException('Nepodarilo sa vytvorit zalohu clanku pred ulozenim.');
        }

        return $backupPath;
    }
}

if (!function_exists('interessa_admin_restore_article_state')) {
    function interessa_admin_restore_article_state(string $articlePath, bool $articleExisted, string $originalArticleJson, array $originalArticleProducts): void {
        if ($articleExisted) {
            $decoded = json_decode($originalArticleJson, true);
            if (!is_array($decoded)) {
                throw new RuntimeException('Nepodarilo sa obnovit povodny clanok po zlyhani ulozenia.');
            }
            interessa_admin_write_json($articlePath, $decoded);
        } elseif (is_file($articlePath)) {
            @unlink($articlePath);
        }

        interessa_admin_save_article_products($originalArticleProducts);
    }
}

if (!function_exists('interessa_admin_safe_save_article_override')) {
    function interessa_admin_safe_save_article_override(string $slug, array $data): array {
        $slug = function_exists('canonical_article_slug') ? canonical_article_slug($slug) : trim($slug);
        $validation = interessa_admin_validate_article_save_payload($slug, $data);
        if (!($validation['success'] ?? false)) {
            return [
                'success' => false,
                'error' => (string) ($validation['error'] ?? 'Clanok sa nepodarilo ulozit.'),
            ];
        }

        $updatedAt = date('c');
        $data['updated_at'] = $updatedAt;
        $data['updated_by'] = trim((string) ($data['updated_by'] ?? '')) !== ''
            ? trim((string) $data['updated_by'])
            : 'admin';
        $normalized = interessa_admin_normalize_article_override($slug, $data);

        if (trim((string) ($normalized['slug'] ?? '')) === '') {
            return [
                'success' => false,
                'error' => 'Chyba slug clanku.',
            ];
        }

        if (trim((string) ($normalized['title'] ?? '')) === '') {
            return [
                'success' => false,
                'error' => 'Vypln nazov clanku.',
            ];
        }

        $articlePath = interessa_admin_article_override_path($slug);
        $articleExisted = is_file($articlePath);
        $originalArticleJson = $articleExisted ? (string) @file_get_contents($articlePath) : '';
        if ($articleExisted && $originalArticleJson === '') {
            return [
                'success' => false,
                'error' => 'Nepodarilo sa precitat povodny clanok pred ulozenim.',
            ];
        }

        $originalArticleProducts = interessa_admin_article_products();

        try {
            interessa_admin_create_article_backup($slug, $articlePath);
            interessa_admin_write_article_override_raw($slug, $normalized);
            interessa_admin_save_article_product_plan($slug, is_array($normalized['product_plan'] ?? null) ? $normalized['product_plan'] : []);
        } catch (Throwable $e) {
            try {
                interessa_admin_restore_article_state($articlePath, $articleExisted, $originalArticleJson, $originalArticleProducts);
            } catch (Throwable $restoreError) {
                return [
                    'success' => false,
                    'error' => trim($e->getMessage()) . ' Obnova povodneho stavu zlyhala: ' . trim($restoreError->getMessage()),
                ];
            }

            return [
                'success' => false,
                'error' => trim($e->getMessage()) !== '' ? trim($e->getMessage()) : 'Clanok sa nepodarilo ulozit.',
            ];
        }

        return [
            'success' => true,
            'slug' => $slug,
            'updated_at' => $updatedAt,
        ];
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
        $legacyArticlePath = dirname(__DIR__) . '/content/articles/' . canonical_article_slug($slug) . '.html';
        $hasStructuredBody = $article['sections'] !== []
            || ($article['comparison']['columns'] ?? []) !== []
            || ($article['comparison']['rows'] ?? []) !== [];

        if (is_file($legacyArticlePath)) {
            return $hasStructuredBody;
        }

        return trim((string) ($article['title'] ?? '')) !== ''
            || trim((string) ($article['intro'] ?? '')) !== ''
            || trim((string) ($article['meta_title'] ?? '')) !== ''
            || trim((string) ($article['meta_description'] ?? '')) !== ''
            || trim((string) ($article['category'] ?? '')) !== ''
            || trim((string) ($article['hero_asset'] ?? '')) !== ''
            || $hasStructuredBody
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
            'price' => max(0.0, round((float) ($product['price'] ?? 0), 2)),
            'url' => trim((string) ($product['url'] ?? $product['fallback_url'] ?? '')),
            'affiliate_code' => interessa_admin_slugify($product['affiliate_code'] ?? ''),
            'fallback_url' => trim((string) ($product['fallback_url'] ?? '')),
            'summary' => interessa_admin_normalize_text($product['summary'] ?? ''),
            'rating' => max(0.0, min(5.0, (float) ($product['rating'] ?? 0))),
            'pros' => interessa_admin_lines_to_array($product['pros'] ?? []),
            'cons' => interessa_admin_lines_to_array($product['cons'] ?? []),
            'merchant_product_id' => trim((string) ($product['merchant_product_id'] ?? '')),
            'feed_source' => trim((string) ($product['feed_source'] ?? '')),
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

if (!function_exists('interessa_admin_article_product_key')) {
    function interessa_admin_article_product_key(string $articleSlug, string $productSlug): string {
        $articleSlug = canonical_article_slug(trim($articleSlug));
        $productSlug = interessa_admin_slugify($productSlug);
        return $articleSlug !== '' && $productSlug !== ''
            ? $articleSlug . '::' . $productSlug
            : '';
    }
}

if (!function_exists('interessa_admin_article_products')) {
    function interessa_admin_article_products(): array {
        return interessa_admin_read_json(INTERESSA_ADMIN_ARTICLE_PRODUCTS_FILE, []);
    }
}

if (!function_exists('interessa_admin_save_article_products')) {
    function interessa_admin_save_article_products(array $rows): void {
        ksort($rows);
        interessa_admin_write_json(INTERESSA_ADMIN_ARTICLE_PRODUCTS_FILE, $rows);
    }
}

if (!function_exists('interessa_admin_normalize_article_product_record')) {
    function interessa_admin_normalize_article_product_record(array $row): array {
        $articleSlug = canonical_article_slug(trim((string) ($row['article_slug'] ?? '')));
        $productSlug = interessa_admin_slugify((string) ($row['product_slug'] ?? $row['slug'] ?? ''));
        $createdAt = trim((string) ($row['created_at'] ?? ''));
        if ($createdAt === '') {
            $createdAt = date('c');
        }

        return [
            'article_slug' => $articleSlug,
            'product_slug' => $productSlug,
            'order' => max(1, (int) ($row['order'] ?? 1)),
            'role' => interessa_admin_normalize_candidate_role($row['role'] ?? ''),
            'show_in_top' => !empty($row['show_in_top']),
            'show_in_comparison' => !empty($row['show_in_comparison']),
            'enabled' => array_key_exists('enabled', $row) ? !empty($row['enabled']) : true,
            'created_at' => $createdAt,
            'updated_at' => date('c'),
        ];
    }
}

if (!function_exists('interessa_admin_save_article_product_record')) {
    function interessa_admin_save_article_product_record(string $articleSlug, string $productSlug, array $row): array {
        $articleSlug = canonical_article_slug(trim($articleSlug));
        $productSlug = interessa_admin_slugify($productSlug);
        $key = interessa_admin_article_product_key($articleSlug, $productSlug);
        if ($key === '') {
            throw new RuntimeException('Chyba clanok alebo produkt pre article_product vazbu.');
        }

        $rows = interessa_admin_article_products();
        $existing = is_array($rows[$key] ?? null) ? $rows[$key] : [];
        $payload = array_replace($existing, $row, [
            'article_slug' => $articleSlug,
            'product_slug' => $productSlug,
        ]);
        $rows[$key] = interessa_admin_normalize_article_product_record($payload);
        interessa_admin_save_article_products($rows);
        return $rows[$key];
    }
}

if (!function_exists('interessa_admin_article_product_records_for_article')) {
    function interessa_admin_article_product_records_for_article(string $articleSlug): array {
        $articleSlug = canonical_article_slug(trim($articleSlug));
        if ($articleSlug === '') {
            return [];
        }

        $matches = [];
        foreach (interessa_admin_article_products() as $key => $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized = interessa_admin_normalize_article_product_record($row);
            if (($normalized['article_slug'] ?? '') !== $articleSlug) {
                continue;
            }

            $matches[$key] = $normalized;
        }

        uasort($matches, static function (array $left, array $right): int {
            $orderCompare = ((int) ($left['order'] ?? 999)) <=> ((int) ($right['order'] ?? 999));
            if ($orderCompare !== 0) {
                return $orderCompare;
            }

            return strcmp((string) ($left['product_slug'] ?? ''), (string) ($right['product_slug'] ?? ''));
        });

        return array_values($matches);
    }
}

if (!function_exists('interessa_admin_article_product_role_label')) {
    function interessa_admin_article_product_role_label(string $role): string {
        $role = interessa_admin_normalize_candidate_role($role);
        return match ($role) {
            'featured' => 'Hlavny tip',
            'value' => 'Vyhodna volba',
            'alternative' => 'Ina moznost',
            'vegan' => 'Veganska moznost',
            'clean' => 'Cista moznost',
            default => 'Bez oznacenia',
        };
    }
}

if (!function_exists('interessa_admin_sync_article_product_override')) {
    function interessa_admin_sync_article_product_override(string $articleSlug): void {
        $articleSlug = canonical_article_slug(trim($articleSlug));
        if ($articleSlug === '') {
            throw new RuntimeException('Chyba slug clanku pre article_product mirror.');
        }

        $rows = interessa_admin_article_products();
        $links = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized = interessa_admin_normalize_article_product_record($row);
            if ((string) ($normalized['article_slug'] ?? '') !== $articleSlug) {
                continue;
            }
            if (empty($normalized['enabled'])) {
                continue;
            }

            $links[] = $normalized;
        }

        usort($links, static function (array $a, array $b): int {
            $orderA = max(1, (int) ($a['order'] ?? 1));
            $orderB = max(1, (int) ($b['order'] ?? 1));
            if ($orderA === $orderB) {
                return strcmp(
                    interessa_admin_slugify((string) ($a['product_slug'] ?? '')),
                    interessa_admin_slugify((string) ($b['product_slug'] ?? ''))
                );
            }
            return $orderA <=> $orderB;
        });

        $productPlan = [];
        $recommendedProducts = [];

        foreach ($links as $row) {
            $productSlug = interessa_admin_slugify((string) ($row['product_slug'] ?? ''));
            if ($productSlug === '') {
                continue;
            }

            $planRow = [
                'product_slug' => $productSlug,
                'order' => max(1, (int) ($row['order'] ?? 1)),
                'role' => interessa_admin_normalize_candidate_role($row['role'] ?? ''),
                'show_in_top' => !empty($row['show_in_top']),
                'show_in_comparison' => !empty($row['show_in_comparison']),
            ];
            $productPlan[] = $planRow;
            $recommendedProducts[] = $productSlug;
        }

        $override = interessa_admin_article_override($articleSlug);
        if ($override === []) {
            $override = ['slug' => $articleSlug];
        }
        $override['product_plan'] = $productPlan;
        $override['recommended_products'] = array_values($recommendedProducts);
        interessa_admin_write_article_override_raw($articleSlug, $override);

        $slotMap = [1 => '-', 2 => '-', 3 => '-'];
        foreach ($productPlan as $planRow) {
            $slot = max(1, min(3, (int) ($planRow['order'] ?? 1)));
            $slotMap[$slot] = (string) ($planRow['product_slug'] ?? '-');
        }
        error_log('SYNC UPDATED: SLOT 1=' . $slotMap[1] . ', SLOT 2=' . $slotMap[2] . ', SLOT 3=' . $slotMap[3]);
    }
}

if (!function_exists('interessa_admin_save_article_product_plan')) {
    function interessa_admin_save_article_product_plan(string $articleSlug, array $productPlan): void {
        $articleSlug = canonical_article_slug(trim($articleSlug));
        if ($articleSlug === '') {
            throw new RuntimeException('Chyba slug clanku pre product plan save.');
        }

        $rows = interessa_admin_article_products();
        foreach ($rows as $key => $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized = interessa_admin_normalize_article_product_record($row);
            if ((string) ($normalized['article_slug'] ?? '') !== $articleSlug) {
                continue;
            }

            $normalized['enabled'] = false;
            $rows[$key] = $normalized;
        }

        foreach ($productPlan as $planRow) {
            if (!is_array($planRow)) {
                continue;
            }

            $productSlug = interessa_admin_slugify((string) ($planRow['product_slug'] ?? ''));
            if ($productSlug === '') {
                continue;
            }

            $key = interessa_admin_article_product_key($articleSlug, $productSlug);
            $existing = is_array($rows[$key] ?? null) ? $rows[$key] : [];
            $rows[$key] = interessa_admin_normalize_article_product_record(array_replace($existing, [
                'article_slug' => $articleSlug,
                'product_slug' => $productSlug,
                'order' => max(1, (int) ($planRow['order'] ?? 1)),
                'role' => interessa_admin_normalize_candidate_role((string) ($planRow['role'] ?? 'standard')),
                'show_in_top' => !empty($planRow['show_in_top']),
                'show_in_comparison' => !empty($planRow['show_in_comparison']),
                'enabled' => true,
            ]));
        }

        interessa_admin_save_article_products($rows);
        interessa_admin_sync_article_product_override($articleSlug);
    }
}

if (!function_exists('interessa_admin_article_product_state')) {
    function interessa_admin_article_product_state(string $articleSlug, ?array $fallbackOverride = null): array {
        $articleSlug = canonical_article_slug(trim($articleSlug));
        $fallbackOverride = is_array($fallbackOverride) ? $fallbackOverride : interessa_admin_article_content($articleSlug);

        $links = $articleSlug !== '' ? interessa_admin_article_product_records_for_article($articleSlug) : [];
        $productPlan = [];
        $recommendedProducts = [];

        foreach ($links as $row) {
            if (!is_array($row) || empty($row['enabled'])) {
                continue;
            }

            $productSlug = interessa_admin_slugify((string) ($row['product_slug'] ?? ''));
            if ($productSlug === '') {
                continue;
            }

            $planRow = [
                'product_slug' => $productSlug,
                'order' => max(1, (int) ($row['order'] ?? 1)),
                'role' => interessa_admin_normalize_candidate_role($row['role'] ?? ''),
                'show_in_top' => !empty($row['show_in_top']),
                'show_in_comparison' => !empty($row['show_in_comparison']),
            ];
            $productPlan[] = $planRow;

            if (!empty($planRow['show_in_top'])) {
                $recommendedProducts[] = $productSlug;
            }
        }

        if ($productPlan !== []) {
            return [
                'product_plan' => $productPlan,
                'recommended_products' => array_values(array_unique($recommendedProducts)),
                'source' => 'article_products',
            ];
        }

        return [
            'product_plan' => is_array($fallbackOverride['product_plan'] ?? null) ? array_values($fallbackOverride['product_plan']) : [],
            'recommended_products' => is_array($fallbackOverride['recommended_products'] ?? null)
                ? array_values(array_unique(array_map('strval', $fallbackOverride['recommended_products'])))
                : [],
            'source' => 'article_override',
        ];
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
        if (in_array($clickStatus, ['dognet', 'direct'], true)) {
            $clickStatus = 'ready';
        }
        if (!in_array($clickStatus, ['missing', 'ready'], true)) {
            $clickStatus = 'missing';
        }
        $productStatus = interessa_admin_slugify((string) ($row['product_status'] ?? ''));
        if (!in_array($productStatus, ['created', 'updated'], true)) {
            $productStatus = '';
        }
        $affiliateStatus = interessa_admin_slugify((string) ($row['affiliate_status'] ?? 'missing'));
        if (!in_array($affiliateStatus, ['created', 'updated', 'missing'], true)) {
            $affiliateStatus = 'missing';
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
            'target_article_slug' => canonical_article_slug(trim((string) ($row['target_article_slug'] ?? ''))),
            'import_filter_text' => interessa_admin_normalize_text($row['import_filter_text'] ?? ''),
            'batch_id' => interessa_admin_slugify((string) ($row['batch_id'] ?? '')),
            'click_code' => interessa_admin_slugify((string) ($row['click_code'] ?? '')),
            'click_url' => trim((string) ($row['click_url'] ?? '')),
            'click_status' => $clickStatus,
            'article_slug' => canonical_article_slug(trim((string) ($row['article_slug'] ?? ''))),
            'order' => max(1, (int) ($row['order'] ?? 1)),
            'role' => interessa_admin_normalize_candidate_role($row['role'] ?? ''),
            'show_in_top' => !empty($row['show_in_top']),
            'show_in_comparison' => !empty($row['show_in_comparison']),
            'approved' => !empty($row['approved']),
            'product_status' => $productStatus,
            'affiliate_status' => $affiliateStatus,
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
    function interessa_admin_import_product_candidates(array $rows, string $sourceType, string $sourceName = '', string $sourceFile = '', array $meta = []): array {
        $existing = interessa_admin_product_candidates();
        $imported = [];
        $batchId = interessa_admin_slugify('import-' . date('Ymd-His'));
        $targetArticleSlug = canonical_article_slug(trim((string) ($meta['target_article_slug'] ?? '')));
        $importFilterText = trim((string) ($meta['import_filter_text'] ?? ''));

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
                    'target_article_slug' => $targetArticleSlug,
                    'import_filter_text' => $importFilterText,
                    'batch_id' => $batchId,
                ]
            ));
            $imported[] = $id;
        }

        if ($imported !== []) {
            interessa_admin_save_product_candidates($existing);
        }

        return [
            'ids' => $imported,
            'batch_id' => $batchId,
        ];
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

        interessa_admin_save_article_product_record($articleSlug, $productSlug, [
            'order' => max(1, (int) ($settings['order'] ?? 1)),
            'role' => interessa_admin_normalize_candidate_role($settings['role'] ?? ''),
            'show_in_top' => !empty($settings['show_in_top']),
            'show_in_comparison' => !empty($settings['show_in_comparison']),
            'enabled' => true,
        ]);
        interessa_admin_sync_article_product_override($articleSlug);
    }
}

if (!function_exists('interessa_admin_assign_product_to_article_slot')) {
    function interessa_admin_assign_product_to_article_slot(string $articleSlug, string $productSlug, int $slot): void {
        $articleSlug = canonical_article_slug(trim($articleSlug));
        $productSlug = interessa_admin_slugify($productSlug);
        $slot = max(1, min(3, $slot));
        if ($articleSlug === '' || $productSlug === '') {
            throw new RuntimeException('Chyba clanok alebo produkt pre slot priradenie.');
        }

        $rows = interessa_admin_article_products();
        $comparisonAllowed = false;
        if (function_exists('interessa_article_comparison_table_whitelist')) {
            $comparisonAllowed = in_array($articleSlug, interessa_article_comparison_table_whitelist(), true);
        }
        $targetKey = interessa_admin_article_product_key($articleSlug, $productSlug);
        $existing = is_array($rows[$targetKey] ?? null) ? $rows[$targetKey] : [];

        foreach ($rows as $rowKey => $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized = interessa_admin_normalize_article_product_record($row);
            if ((string) ($normalized['article_slug'] ?? '') !== $articleSlug) {
                continue;
            }

            $rowProductSlug = interessa_admin_slugify((string) ($normalized['product_slug'] ?? ''));
            $rowOrder = max(1, (int) ($normalized['order'] ?? 1));
            if ($rowOrder === $slot) {
                $normalized['enabled'] = false;
                $rows[$rowKey] = $normalized;
                continue;
            }

            if ($rowProductSlug === $productSlug) {
                $normalized['enabled'] = false;
                $rows[$rowKey] = $normalized;
            }
        }

        $rows[$targetKey] = interessa_admin_normalize_article_product_record(array_replace($existing, [
            'article_slug' => $articleSlug,
            'product_slug' => $productSlug,
            'order' => $slot,
            'role' => $slot === 1 ? 'featured' : 'standard',
            'show_in_top' => true,
            'show_in_comparison' => $comparisonAllowed,
            'enabled' => true,
        ]));

        interessa_admin_save_article_products($rows);
        interessa_admin_sync_article_product_override($articleSlug);
    }
}

if (!function_exists('interessa_admin_direct_save_article_slot_product')) {
    function interessa_admin_direct_save_article_slot_product(string $articleSlug, string $productSlug, int $slot, int $featuredSlot = 0): array {
        $articleSlug = canonical_article_slug(trim($articleSlug));
        $productSlug = interessa_admin_slugify($productSlug);
        $slot = max(1, min(3, $slot));
        $featuredSlot = max(1, min(3, $featuredSlot > 0 ? $featuredSlot : $slot));
        if ($articleSlug === '' || $productSlug === '') {
            throw new RuntimeException('SLOT SAVE FAILED: chyba clanok alebo produkt pre slot.');
        }

        $originalRows = interessa_admin_article_products();
        $articlePath = interessa_admin_article_override_path($articleSlug);
        $articleExisted = is_file($articlePath);
        $originalArticleJson = $articleExisted ? (string) @file_get_contents($articlePath) : '';
        if ($articleExisted && $originalArticleJson === '') {
            throw new RuntimeException('SLOT SAVE FAILED: nepodarilo sa precitat povodny clanok pred zapisom.');
        }

        $rows = $originalRows;
        $comparisonAllowed = false;
        if (function_exists('interessa_article_comparison_table_whitelist')) {
            $comparisonAllowed = in_array($articleSlug, interessa_article_comparison_table_whitelist(), true);
        }

        $targetKey = interessa_admin_article_product_key($articleSlug, $productSlug);
        $existing = is_array($rows[$targetKey] ?? null) ? $rows[$targetKey] : [];

        foreach ($rows as $rowKey => $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized = interessa_admin_normalize_article_product_record($row);
            if ((string) ($normalized['article_slug'] ?? '') !== $articleSlug) {
                continue;
            }

            $rowProductSlug = interessa_admin_slugify((string) ($normalized['product_slug'] ?? ''));
            $rowOrder = max(1, (int) ($normalized['order'] ?? 1));

            if ($rowOrder === $slot || $rowProductSlug === $productSlug) {
                $normalized['enabled'] = false;
            }

            $rows[$rowKey] = $normalized;
        }

        $rows[$targetKey] = interessa_admin_normalize_article_product_record(array_replace($existing, [
            'article_slug' => $articleSlug,
            'product_slug' => $productSlug,
            'order' => $slot,
            'role' => $slot === $featuredSlot ? 'featured' : 'standard',
            'show_in_top' => true,
            'show_in_comparison' => $comparisonAllowed,
            'enabled' => true,
        ]));

        foreach ($rows as $rowKey => $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized = interessa_admin_normalize_article_product_record($row);
            if ((string) ($normalized['article_slug'] ?? '') !== $articleSlug || empty($normalized['enabled'])) {
                $rows[$rowKey] = $normalized;
                continue;
            }

            $rowOrder = max(1, (int) ($normalized['order'] ?? 1));
            $normalized['role'] = $rowOrder === $featuredSlot ? 'featured' : 'standard';
            $normalized['show_in_top'] = true;
            $rows[$rowKey] = interessa_admin_normalize_article_product_record($normalized);
        }

        try {
            interessa_admin_save_article_products($rows);
            interessa_admin_sync_article_product_override($articleSlug);

            $persistedRows = interessa_admin_article_product_records_for_article($articleSlug);
            $enabledRows = array_values(array_filter($persistedRows, static function ($row): bool {
                return is_array($row) && !empty($row['enabled']);
            }));
            usort($enabledRows, static function (array $a, array $b): int {
                return max(1, (int) ($a['order'] ?? 1)) <=> max(1, (int) ($b['order'] ?? 1));
            });

            $targetEnabledRows = array_values(array_filter($enabledRows, static function (array $row) use ($slot): bool {
                return max(1, (int) ($row['order'] ?? 1)) === $slot;
            }));
            if (count($targetEnabledRows) !== 1) {
                throw new RuntimeException('SLOT SAVE FAILED: expected one enabled product in slot ' . $slot . '.');
            }

            $enabledTargetSlug = interessa_admin_slugify((string) ($targetEnabledRows[0]['product_slug'] ?? ''));
            if ($enabledTargetSlug !== $productSlug) {
                throw new RuntimeException('SLOT SAVE FAILED: expected ' . $productSlug . ' in slot ' . $slot . ', but persisted article-products state does not match.');
            }

            $override = interessa_admin_article_override($articleSlug);
            $overridePlan = array_values(array_filter((array) ($override['product_plan'] ?? []), static fn($row): bool => is_array($row)));
            usort($overridePlan, static function (array $a, array $b): int {
                return max(1, (int) ($a['order'] ?? 1)) <=> max(1, (int) ($b['order'] ?? 1));
            });
            $overrideTargetSlug = '';
            foreach ($overridePlan as $planRow) {
                if (max(1, (int) ($planRow['order'] ?? 1)) === $slot) {
                    $overrideTargetSlug = interessa_admin_slugify((string) ($planRow['product_slug'] ?? ''));
                    break;
                }
            }
            if ($overrideTargetSlug !== $productSlug) {
                throw new RuntimeException('SLOT SAVE FAILED: expected ' . $productSlug . ' in slot ' . $slot . ', but article JSON product_plan does not match.');
            }

            $expectedRecommended = array_values(array_map(
                static fn(array $row): string => interessa_admin_slugify((string) ($row['product_slug'] ?? '')),
                $enabledRows
            ));
            $persistedRecommended = array_values(array_map(
                'interessa_admin_slugify',
                is_array($override['recommended_products'] ?? null) ? $override['recommended_products'] : []
            ));
            if ($persistedRecommended !== $expectedRecommended) {
                throw new RuntimeException('SLOT SAVE FAILED: recommended_products does not match enabled slot state.');
            }

            return [
                'success' => true,
                'article_slug' => $articleSlug,
                'product_slug' => $productSlug,
                'target_slot' => $slot,
                'featured_slot' => $featuredSlot,
                'product_plan' => $overridePlan,
                'recommended_products' => $persistedRecommended,
            ];
        } catch (Throwable $e) {
            interessa_admin_restore_article_state($articlePath, $articleExisted, $originalArticleJson, $originalRows);
            throw $e;
        }
    }
}

if (!function_exists('interessa_admin_recommendation_keyword_hits')) {
    function interessa_admin_recommendation_keyword_hits(string $text, array $keywords): array {
        $text = interessa_product_lower(trim($text));
        if ($text === '') {
            return [];
        }

        $hits = [];
        foreach ($keywords as $keyword) {
            $keyword = interessa_product_lower(trim((string) $keyword));
            if ($keyword !== '' && str_contains($text, $keyword)) {
                $hits[] = $keyword;
            }
        }

        return array_values(array_unique($hits));
    }
}

if (!function_exists('interessa_admin_recommendation_product_text')) {
    function interessa_admin_recommendation_product_text(array $product): string {
        $normalized = interessa_normalize_product($product);
        return interessa_product_lower(trim(implode(' ', array_filter([
            (string) ($normalized['slug'] ?? ''),
            str_replace('-', ' ', (string) ($normalized['slug'] ?? '')),
            (string) ($normalized['name'] ?? ''),
            (string) ($normalized['summary'] ?? ''),
            (string) ($normalized['category'] ?? ''),
        ]))));
    }
}

if (!function_exists('interessa_admin_recommendation_family_key')) {
    function interessa_admin_recommendation_family_key(array $product): string {
        $normalized = interessa_normalize_product($product);
        $base = interessa_product_lower(trim((string) ($normalized['name'] ?? $normalized['slug'] ?? '')));
        if ($base === '') {
            return interessa_admin_slugify((string) ($normalized['slug'] ?? ''));
        }

        $base = preg_replace('~\([^)]*\)~u', ' ', $base) ?? $base;
        $base = preg_replace('~\b\d+\s?(g|kg|mg|ml|caps|kapsul|kapsuly|tbl|tabs|tabliet|servings?|porci[ea]|pcs|kusov?)\b~iu', ' ', $base) ?? $base;
        $base = preg_replace('~\b(chocolate|cokolada|vanilla|strawberry|jahoda|banana|banan|berry|fruit punch|watermelon|tropical|orange|lemon|mango|apple|cola|grape|peach|lime|neutral|natural|original|unflavoured|unflavored|bez prichute)\b~iu', ' ', $base) ?? $base;
        $base = preg_replace('~[^a-z0-9]+~', '-', $base) ?? $base;
        $base = trim($base, '-');

        return $base !== '' ? $base : interessa_admin_slugify((string) ($normalized['slug'] ?? ''));
    }
}

if (!function_exists('interessa_admin_article_recommendation_context')) {
    function interessa_admin_article_recommendation_context(string $articleSlug): array {
        $articleSlug = canonical_article_slug(trim($articleSlug));
        if ($articleSlug === '') {
            return [];
        }

        $override = interessa_admin_article_content($articleSlug);
        $meta = function_exists('article_meta') ? article_meta($articleSlug) : [];
        $title = trim((string) (($override['title'] ?? '') ?: ($meta['title'] ?? '')));
        $intro = trim((string) (($override['intro'] ?? '') ?: ($meta['description'] ?? '')));
        $category = normalize_category_slug((string) (($override['category'] ?? '') ?: ($meta['category'] ?? '')));
        $sections = is_array($override['sections'] ?? null) ? $override['sections'] : [];
        $relatedLinks = is_array($override['related_links'] ?? null) ? $override['related_links'] : [];
        $coreTextParts = [$articleSlug, str_replace('-', ' ', $articleSlug), $title, $intro, $category];
        $textParts = $coreTextParts;

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }
            $heading = (string) ($section['heading'] ?? '');
            $body = (string) ($section['body'] ?? '');
            $coreTextParts[] = $heading;
            $coreTextParts[] = $body;
            $textParts[] = $heading;
            $textParts[] = $body;
        }
        foreach ($relatedLinks as $item) {
            if (!is_array($item)) {
                continue;
            }
            $textParts[] = (string) ($item['slug'] ?? '');
            $textParts[] = (string) ($item['label'] ?? '');
            $textParts[] = (string) ($item['description'] ?? '');
        }

        return [
            'slug' => $articleSlug,
            'title' => $title,
            'intro' => $intro,
            'category' => $category,
            'sections' => $sections,
            'related_links' => $relatedLinks,
            'core_haystack' => interessa_product_lower(trim(implode(' ', array_filter($coreTextParts)))),
            'haystack' => interessa_product_lower(trim(implode(' ', array_filter($textParts)))),
            'rule' => function_exists('interessa_article_product_rule')
                ? interessa_article_product_rule($articleSlug)
                : ['allow_categories' => [], 'prefer_keywords' => [], 'exclude_keywords' => [], 'categories' => [], 'strict' => false],
        ];
    }
}

if (!function_exists('interessa_admin_article_recommendation_profiles')) {
    function interessa_admin_article_recommendation_profiles(): array {
        return [
            'proteiny-general' => [
                'intent_label' => 'Proteiny',
                'summary' => 'System hlada 3 rozdielne vetvy: univerzalny whey, value alternativu a cistejsiu specializovanu volbu.',
                'allow_categories' => ['proteiny'],
                'prefer_keywords' => ['protein', 'proteiny', 'whey', 'isolate', 'isolat', 'hydro', 'casein', 'kasein'],
                'exclude_keywords' => ['pre-workout', 'predtrening', 'kreatin', 'creatine', 'probiotik', 'magnesium', 'horcik', 'zinc', 'zinok'],
                'slots' => [
                    1 => [
                        'role' => 'featured',
                        'branch' => 'overall',
                        'branch_label' => 'Najlepsi protein celkovo',
                        'reasoning' => 'Najvyvazenejsi default choice pre vacsinu ludi, ktori chcu univerzalny whey protein.',
                        'preferred_slugs' => ['gymbeam-just-whey'],
                        'preferred_keywords' => ['just whey', 'whey', 'blend'],
                        'avoid_keywords' => ['isolate', 'isolat', 'vegan', 'gainer', 'breakfast', 'bar'],
                        'preferred_categories' => ['proteiny'],
                    ],
                    2 => [
                        'role' => 'value',
                        'branch' => 'value',
                        'branch_label' => 'Najlepsi pomer cena vykon',
                        'reasoning' => 'Rozumna value alternativa pre kazdodenne pouzitie bez zbytocneho preplacania.',
                        'preferred_slugs' => ['gymbeam-true-whey', 'proteinsk-100-whey-protein-nutrend'],
                        'preferred_keywords' => ['true whey', '100 whey', '100% whey', 'whey premium', 'premium whey'],
                        'avoid_keywords' => ['isolate', 'isolat', 'vegan', 'gainer', 'breakfast', 'bar'],
                        'preferred_categories' => ['proteiny'],
                    ],
                    3 => [
                        'role' => 'alternative',
                        'branch' => 'specific',
                        'branch_label' => 'Ina vetva rozhodnutia',
                        'reasoning' => 'Specificka alternativa pre cistejsi profil alebo uzsi use-case.',
                        'preferred_slugs' => ['gymbeam-gymbeam-pure-isowhey-bez-pr-ichute'],
                        'preferred_keywords' => ['isolate', 'isolat', 'isowhey', 'hydro', 'vegan'],
                        'avoid_keywords' => ['breakfast', 'bar'],
                        'preferred_categories' => ['proteiny'],
                        'requires_prefer_hit' => true,
                    ],
                ],
            ],
            'proteiny-chudnutie' => [
                'intent_label' => 'Protein na chudnutie',
                'summary' => 'System hlada isolate ako hlavnu volbu, value whey ako kompromis a univerzalny whey ako sirsi default.',
                'allow_categories' => ['proteiny'],
                'prefer_keywords' => ['protein', 'whey', 'isolate', 'isolat', 'dieta', 'chudnut', 'redukcia'],
                'exclude_keywords' => ['pre-workout', 'predtrening', 'kreatin', 'creatine', 'probiotik', 'magnesium', 'horcik'],
                'slots' => [
                    1 => [
                        'role' => 'featured',
                        'branch' => 'lean',
                        'branch_label' => 'Najlepsi na chudnutie',
                        'reasoning' => 'Cistejsi profil, ktory lepsie zapada do dietneho alebo redukcneho kontextu.',
                        'preferred_slugs' => ['gymbeam-gymbeam-pure-isowhey-bez-pr-ichute'],
                        'preferred_keywords' => ['isolate', 'isolat', 'isowhey', 'lean', 'diet'],
                        'preferred_categories' => ['proteiny'],
                        'requires_prefer_hit' => true,
                    ],
                    2 => [
                        'role' => 'value',
                        'branch' => 'value',
                        'branch_label' => 'Najlepsi pomer cena vykon',
                        'reasoning' => 'Rozumna value alternativa, ked nechces zbytocne preplacat isolate.',
                        'preferred_slugs' => ['gymbeam-true-whey'],
                        'preferred_keywords' => ['true whey', '100 whey', 'whey premium', 'whey'],
                        'avoid_keywords' => ['gainer', 'breakfast', 'bar'],
                        'preferred_categories' => ['proteiny'],
                    ],
                    3 => [
                        'role' => 'alternative',
                        'branch' => 'general',
                        'branch_label' => 'Univerzalna alternativa',
                        'reasoning' => 'Sirsia univerzalna volba pre ludi, ktori nechcu byt viazani len na dietny profil.',
                        'preferred_slugs' => ['gymbeam-just-whey'],
                        'preferred_keywords' => ['just whey', 'whey', 'blend'],
                        'preferred_categories' => ['proteiny'],
                    ],
                ],
            ],
            'kreatin-general' => [
                'intent_label' => 'Kreatin',
                'summary' => 'System hlada monohydrat ako hlavnu volbu, value alternativu z ineho schvaleneho merchanta a specificku odlisnu formu.',
                'allow_categories' => ['kreatin'],
                'prefer_keywords' => ['kreatin', 'creatine', 'creapure', 'monohydrate', 'monohydrat'],
                'exclude_keywords' => ['pre-workout', 'predtrening', 'whey', 'isolate', 'isolat', 'gainer', 'probiotik', 'magnesium', 'horcik'],
                'slots' => [
                    1 => [
                        'role' => 'featured',
                        'branch' => 'overall',
                        'branch_label' => 'Najlepsi kreatin celkovo',
                        'reasoning' => 'Najbezpecnejsia hlavna volba pre vacsinu ludi, ktori chcu klasicky monohydrat.',
                        'preferred_slugs' => ['gymbeam-gymbeam-kreat-in-100-creapure-r'],
                        'preferred_keywords' => ['creapure', 'monohydrate', 'monohydrat', 'creatine'],
                        'avoid_keywords' => ['hcl', 'caps'],
                        'preferred_categories' => ['kreatin'],
                    ],
                    2 => [
                        'role' => 'value',
                        'branch' => 'value',
                        'branch_label' => 'Najlepsi pomer cena vykon',
                        'reasoning' => 'Value alternativa s rovnakou zakladnou logikou, ale s lepsim rozpoctovym uhlom.',
                        'preferred_slugs' => ['proteinsk-kreatin-monohydrat-value'],
                        'preferred_keywords' => ['monohydrate', 'monohydrat', 'creatine', 'kreatin'],
                        'preferred_merchants' => ['protein-sk'],
                        'avoid_keywords' => ['hcl'],
                        'preferred_categories' => ['kreatin'],
                        'allow_family_overlap' => true,
                    ],
                    3 => [
                        'role' => 'alternative',
                        'branch' => 'specific',
                        'branch_label' => 'Alternativa pre iny use-case',
                        'reasoning' => 'Odlisna forma pre citatela, ktory nechce ostat len pri klasickom monohydrate.',
                        'preferred_slugs' => ['gymbeam-gymbeam-creatine-hcl-bez-pr-ichute'],
                        'preferred_keywords' => ['hcl', 'micronized', 'caps'],
                        'preferred_categories' => ['kreatin'],
                        'requires_prefer_hit' => true,
                    ],
                ],
            ],
            'pre-workout-general' => [
                'intent_label' => 'Pre-workout',
                'summary' => 'System hlada vyvazeny default, silnejsi stimulant a pump-focused alternativu bez fake-choice duplicity.',
                'allow_categories' => ['pre-workout'],
                'prefer_keywords' => ['pre-workout', 'predtrening', 'stim', 'pump', 'citrulline', 'citrulin', 'beta alanine', 'kofein', 'caffeine'],
                'exclude_keywords' => ['kreatin', 'creatine', 'whey', 'protein', 'probiotik', 'magnesium', 'horcik', 'zinc', 'zinok'],
                'slots' => [
                    1 => [
                        'role' => 'featured',
                        'branch' => 'overall',
                        'branch_label' => 'Najlepsi vyvazeny default',
                        'reasoning' => 'Najbezpecnejsia hlavna volba pre vacsinu ludi, ktori chcu hotovy pre-workout bez extremu.',
                        'preferred_slugs' => ['gymbeam-cellucor-c4-original-frozen-bombsicle'],
                        'preferred_keywords' => ['pre-workout', 'original', 'c4'],
                        'avoid_keywords' => ['citrulline', 'citrulin', 'malate', 'aakg', 'arginine', 'caffeine power'],
                        'preferred_categories' => ['pre-workout'],
                    ],
                    2 => [
                        'role' => 'value',
                        'branch' => 'stim',
                        'branch_label' => 'Silnejsia alebo tvrdsia alternativa',
                        'reasoning' => 'Silnejsia stim vetva pre ludi, ktori chcu tvrdsi nakop na intenzivnejsi trening.',
                        'preferred_slugs' => ['gymbeam-activlab-black-wolf-multifruit'],
                        'preferred_keywords' => ['black wolf', 'hardcore', 'hard', 'stim', 'caffeine'],
                        'avoid_keywords' => ['citrulline', 'citrulin', 'pump', 'aakg', 'arginine'],
                        'preferred_categories' => ['pre-workout'],
                    ],
                    3 => [
                        'role' => 'alternative',
                        'branch' => 'pump',
                        'branch_label' => 'Ina vetva rozhodnutia',
                        'reasoning' => 'Jasne odlisny use-case pre pump-focused alebo menej stimulantovy pristup.',
                        'preferred_slugs' => ['gymbeam-gymbeam-citrulline-malate-bez-pr-ichute'],
                        'preferred_keywords' => ['stim free', 'stim-free', 'pump', 'citrulline', 'citrulin', 'malate', 'arginine', 'aakg'],
                        'preferred_categories' => ['pre-workout'],
                        'requires_prefer_hit' => true,
                    ],
                ],
            ],
            'pre-workout-caffeine-free' => [
                'intent_label' => 'Pre-workout bez kofeinu',
                'summary' => 'System hlada pump alebo non-stim default, silnejsiu pump alternativu a az tretiu vetvu s miernejsim hotovym pre-workoutom.',
                'allow_categories' => ['pre-workout'],
                'prefer_keywords' => ['pre-workout', 'predtrening', 'pump', 'stim free', 'stim-free', 'bez kofeinu', 'citrulline', 'citrulin', 'arginine'],
                'exclude_keywords' => ['kreatin', 'creatine', 'whey', 'protein', 'probiotik', 'magnesium', 'horcik'],
                'slots' => [
                    1 => [
                        'role' => 'featured',
                        'branch' => 'pump',
                        'branch_label' => 'Najlepsia non-stim volba',
                        'reasoning' => 'Hlavny pump-focused default pre clanok, ktory nechce stavat vyber na kofeine.',
                        'preferred_slugs' => ['gymbeam-gymbeam-citrulline-malate-bez-pr-ichute'],
                        'preferred_keywords' => ['stim free', 'stim-free', 'pump', 'citrulline', 'citrulin', 'malate', 'arginine', 'aakg'],
                        'preferred_categories' => ['pre-workout'],
                        'requires_prefer_hit' => true,
                    ],
                    2 => [
                        'role' => 'value',
                        'branch' => 'pump-alt',
                        'branch_label' => 'Druha pump alternativa',
                        'reasoning' => 'Druha pump vetva pre cloveka, ktory chce iny typ no-stim supportu.',
                        'preferred_keywords' => ['pump', 'arginine', 'aakg', 'citrulline', 'citrulin'],
                        'preferred_categories' => ['pre-workout'],
                        'requires_prefer_hit' => true,
                    ],
                    3 => [
                        'role' => 'alternative',
                        'branch' => 'balanced',
                        'branch_label' => 'Miernejsi hotovy pre-workout',
                        'reasoning' => 'Sirsi default pre citatela, ktory napriek teme nechce cisto single-ingredient vetvu.',
                        'preferred_slugs' => ['gymbeam-cellucor-c4-original-frozen-bombsicle'],
                        'preferred_keywords' => ['pre-workout', 'original', 'c4'],
                        'preferred_categories' => ['pre-workout'],
                    ],
                ],
            ],
            'probiotika-general' => [
                'intent_label' => 'Probiotika',
                'summary' => 'System hlada everyday probiotikum, sirsi complex a specificku strainovu alebo digestion alternativu.',
                'allow_categories' => ['probiotika-travenie'],
                'prefer_keywords' => ['probiotik', 'digest', 'gut', 'lactobac', 'bifido', 'traven', 'enzym', 'mikrobiom', 'antibiotik', 'naduv'],
                'exclude_keywords' => ['pre-workout', 'predtrening', 'kreatin', 'creatine', 'whey', 'protein', 'magnesium', 'horcik'],
                'slots' => [
                    1 => [
                        'role' => 'featured',
                        'branch' => 'everyday',
                        'branch_label' => 'Najlepsia everyday volba',
                        'reasoning' => 'Jednoducha kazdodenna volba pre vacsinu ludi, ktori chcu probiotic basis bez chaosu.',
                        'preferred_slugs' => ['gymbeam-probioten'],
                        'preferred_keywords' => ['probio', 'probiotic', 'everyday'],
                        'avoid_keywords' => ['lactobacillus', 'rhamnosus'],
                        'preferred_categories' => ['probiotika-travenie'],
                    ],
                    2 => [
                        'role' => 'value',
                        'branch' => 'complex',
                        'branch_label' => 'Sirsia alebo value alternativa',
                        'reasoning' => 'Sirsia probiotic vetva pre cloveka, ktory chce complex alebo sirsi blend.',
                        'preferred_slugs' => ['gymbeam-gymbeam-probio-complex'],
                        'preferred_keywords' => ['complex', 'multi', 'blend', 'probio'],
                        'preferred_categories' => ['probiotika-travenie'],
                    ],
                    3 => [
                        'role' => 'alternative',
                        'branch' => 'strain',
                        'branch_label' => 'Specificka strainova alternativa',
                        'reasoning' => 'Odlisna vetva pre citatela, ktory chce cielenejsi strain alebo uzsi use-case.',
                        'preferred_slugs' => ['gymbeam-gymbeam-lactobacillus-rhamnosus'],
                        'preferred_keywords' => ['lactobacillus', 'rhamnosus', 'strain'],
                        'preferred_categories' => ['probiotika-travenie'],
                        'requires_prefer_hit' => true,
                    ],
                ],
            ],
            'mineraly-horcik' => [
                'intent_label' => 'Horcik',
                'summary' => 'System hlada daily-use magnesium, value citrate a recovery vetvu bez weak convenience logiky.',
                'allow_categories' => ['mineraly'],
                'prefer_keywords' => ['magnesium', 'magnez', 'horcik', 'citrate', 'citrat', 'chelate', 'bisglycinat', 'zmb'],
                'exclude_keywords' => ['zinc', 'zinok', 'pre-workout', 'predtrening', 'kreatin', 'creatine', 'whey', 'protein', 'probiotik'],
                'slots' => [
                    1 => [
                        'role' => 'featured',
                        'branch' => 'daily',
                        'branch_label' => 'Najlepsi horcik na bezne pouzitie',
                        'reasoning' => 'Hlavna daily-use volba pre cloveka, ktory chce kvalitny magnesium na pravidelny rezim.',
                        'preferred_slugs' => ['gymbeam-gymbeam-magnesium-chelate-powder-bez-pr-ichute'],
                        'preferred_keywords' => ['magnesium chelate', 'chelate', 'bisglycinat', 'bisglycinate', 'magnesium'],
                        'avoid_keywords' => ['zinc', 'zinok', 'shot', 'zmb'],
                        'preferred_categories' => ['mineraly'],
                    ],
                    2 => [
                        'role' => 'value',
                        'branch' => 'value',
                        'branch_label' => 'Najlepsia value volba',
                        'reasoning' => 'Rozumna budget alebo jednoduchsia alternativa pre kazdodenne doplnenie magnezia.',
                        'preferred_slugs' => ['gymbeam-magnesium-citrate-caps'],
                        'preferred_keywords' => ['citrate', 'citrat', 'caps', 'kaps'],
                        'avoid_keywords' => ['zinc', 'zinok', 'zmb'],
                        'preferred_categories' => ['mineraly'],
                    ],
                    3 => [
                        'role' => 'alternative',
                        'branch' => 'recovery',
                        'branch_label' => 'Specificky recovery use-case',
                        'reasoning' => 'Odlisna vetva pre recovery, vecerny rezim alebo sleep-oriented use-case.',
                        'preferred_slugs' => ['gymbeam-gymbeam-zmb6-chelate'],
                        'preferred_keywords' => ['zmb', 'sleep', 'recovery', 'chelate'],
                        'preferred_categories' => ['mineraly'],
                        'requires_prefer_hit' => true,
                    ],
                ],
            ],
            'mineraly-zinok' => [
                'intent_label' => 'Zinok',
                'summary' => 'System hlada chelat ako hlavnu volbu, basic zinc ako value a jasne odlisnu kombinovanu vetvu.',
                'allow_categories' => ['mineraly'],
                'prefer_keywords' => ['zinok', 'zinc', 'zinek', 'chelat', 'bisglycinat', 'picolinat', 'citrate'],
                'exclude_keywords' => ['magnesium', 'magnez', 'horcik', 'pre-workout', 'predtrening', 'kreatin', 'creatine', 'whey', 'protein'],
                'slots' => [
                    1 => [
                        'role' => 'featured',
                        'branch' => 'daily',
                        'branch_label' => 'Najlepsia daily-use forma zinku',
                        'reasoning' => 'Chelatovana alebo lepsie obhajitelna forma zinku ako hlavna volba pre clanok o zinku.',
                        'preferred_slugs' => ['gymbeam-gymbeam-zinok-chel-at-bisglycin-at', 'gymbeam-gymbeam-zinok-pikolin-at'],
                        'preferred_keywords' => ['zinc chelate', 'zinok chelat', 'bisglycinat', 'bisglycinate', 'picolinat'],
                        'preferred_categories' => ['mineraly'],
                        'requires_prefer_hit' => true,
                    ],
                    2 => [
                        'role' => 'value',
                        'branch' => 'value',
                        'branch_label' => 'Najlepsia value volba',
                        'reasoning' => 'Jednoduchy basic zinok ako budget alebo value alternativa.',
                        'preferred_slugs' => ['gymbeam-gymbeam-zinok-180-tab', 'gymbeam-gymbeam-zinok-90-tab-bez-pr-ichute'],
                        'preferred_keywords' => ['zinok', 'zinc', 'tab'],
                        'avoid_keywords' => ['vitamin c', 'medi', 'magnesium'],
                        'preferred_categories' => ['mineraly'],
                    ],
                    3 => [
                        'role' => 'alternative',
                        'branch' => 'combo',
                        'branch_label' => 'Specificka kombinovana alternativa',
                        'reasoning' => 'Ina vetva rozhodnutia pre cloveka, ktory chce zinok v kombinacii alebo s inym use-case akcentom.',
                        'preferred_slugs' => ['gymbeam-gymbeam-chel-at-zinku-medi', 'gymbeam-gymbeam-vitam-in-c-zinok-120-tab'],
                        'preferred_keywords' => ['vitamin c', 'zinok', 'zinku', 'medi', 'c + zinok'],
                        'preferred_categories' => ['mineraly'],
                        'requires_prefer_hit' => true,
                    ],
                ],
            ],
            'vitamin-d-general' => [
                'intent_label' => 'Vitamin D',
                'summary' => 'System hlada D3+K2 ako hlavny default, cisty D3 variant ako jednoduchsi value krok a tretiu odlisnu vetvu s dalsim wellness benefitom.',
                'allow_categories' => [],
                'disable_rule_category_fallback' => true,
                'prefer_keywords' => ['vitamin d', 'vitamin d3', 'd3', 'k2', 'k1', 'imunita'],
                'exclude_keywords' => ['pre-workout', 'predtrening', 'kreatin', 'creatine', 'whey', 'protein', 'kolagen', 'collagen'],
                'slots' => [
                    1 => [
                        'role' => 'featured',
                        'branch' => 'overall',
                        'branch_label' => 'Najlepsi vitamin D default',
                        'reasoning' => 'Najpraktickejsia hlavna volba pre cloveka, ktory chce vitamin D riesit jednym silnym defaultom.',
                        'preferred_slugs' => ['gymbeam-gymbeam-vitamin-d3-k1-k2-forte', 'gymbeam-now-foods-vitamin-d3-k2-120-kaps'],
                        'preferred_keywords' => ['vitamin d3', 'd3', 'k2', 'forte'],
                    ],
                    2 => [
                        'role' => 'value',
                        'branch' => 'value',
                        'branch_label' => 'Jednoduchsia D3 volba',
                        'reasoning' => 'Cisty D3 variant pre cloveka, ktory nechce komplikovanejsi stack a hlada jednoduchsi daily-use format.',
                        'preferred_slugs' => ['gymbeam-gymbeam-vitam-in-d3-2000-iu', 'gymbeam-gymbeam-vitam-in-d3-1000-iu'],
                        'preferred_keywords' => ['vitamin d3', 'd3', '2000 iu', '1000 iu'],
                    ],
                    3 => [
                        'role' => 'alternative',
                        'branch' => 'wellness',
                        'branch_label' => 'Ina wellness vetva',
                        'reasoning' => 'Doplna vitamin D temu o odlisny use-case s dalsim benefitom mimo cisteho D3 defaultu.',
                        'preferred_slugs' => ['gymbeam-gymbeam-omega-3-vitamin-d3', 'gymbeam-now-foods-vitamin-d3-k2-120-kaps'],
                        'preferred_keywords' => ['omega', 'vitamin d3', 'd3', 'k2'],
                        'requires_prefer_hit' => true,
                    ],
                ],
            ],
            'kolagen-general' => [
                'intent_label' => 'Kolagen a klby',
                'summary' => 'System hlada joint-focused hlavnu volbu, value kolagenovu alternativu a tretiu odlisnu kolagenovu vetvu.',
                'allow_categories' => ['klby-koza'],
                'prefer_keywords' => ['kolagen', 'collagen', 'klby', 'joint', 'hydrolyz', 'marine', 'type ii'],
                'exclude_keywords' => ['pre-workout', 'predtrening', 'kreatin', 'creatine', 'whey', 'isolate', 'isolat', 'probiotik', 'magnesium', 'horcik'],
                'slots' => [
                    1 => [
                        'role' => 'featured',
                        'branch' => 'joint',
                        'branch_label' => 'Najlepsi kolagen na klby',
                        'reasoning' => 'Hlavna volba pre clanok, ktory riesi klby, pohyb a cielenejsi joint use-case.',
                        'preferred_slugs' => ['gymbeam-gymbeam-collagen-type-ii-joint-complex'],
                        'preferred_keywords' => ['joint', 'type ii', 'klby', 'kolagen'],
                        'requires_prefer_hit' => true,
                    ],
                    2 => [
                        'role' => 'value',
                        'branch' => 'value',
                        'branch_label' => 'Najlepsia value kolagen volba',
                        'reasoning' => 'Jednoduchsia alebo rozpoctovo lepsia kolagenova volba pre cloveka, ktory nechce ist hned do specializovanej joint vetvy.',
                        'preferred_slugs' => ['proteinsk-kolagen-protein-nutrition'],
                        'preferred_keywords' => ['kolagen', 'collagen', 'hydrolyz'],
                        'preferred_merchants' => ['protein-sk'],
                    ],
                    3 => [
                        'role' => 'alternative',
                        'branch' => 'specific',
                        'branch_label' => 'Ina kolagenova vetva',
                        'reasoning' => 'Tretia vetva pre citatela, ktory chce iny typ kolagenu alebo iny format pouzitia.',
                        'preferred_slugs' => ['gymbeam-gymbeam-marine-collg-zelen-e-jablko', 'gymbeam-runcollg-collagen'],
                        'preferred_keywords' => ['marine', 'hydrolyz', 'collagen', 'kolagen', 'runcollg'],
                        'requires_prefer_hit' => true,
                    ],
                ],
            ],
            'omega3-general' => [
                'intent_label' => 'Omega-3',
                'summary' => 'System hlada klasicky fish oil default, silnejsiu forte value volbu a tretiu odlisnu vetvu pre iny use-case.',
                'allow_categories' => [],
                'disable_rule_category_fallback' => true,
                'prefer_keywords' => ['omega 3', 'omega-3', 'fish oil', 'rybi olej', 'dha', 'epa', 'omega'],
                'exclude_keywords' => ['pre-workout', 'predtrening', 'kreatin', 'creatine', 'whey', 'protein', 'kolagen', 'probiotik'],
                'slots' => [
                    1 => [
                        'role' => 'featured',
                        'branch' => 'overall',
                        'branch_label' => 'Najlepsia omega-3 default volba',
                        'reasoning' => 'Najpraktickejsi hlavny fish oil default pre vacsinu ludi, ktori chcu doplnit omega-3 bez komplikacii.',
                        'preferred_slugs' => ['gymbeam-gymbeam-omega-3'],
                        'preferred_keywords' => ['omega 3', 'omega-3'],
                    ],
                    2 => [
                        'role' => 'value',
                        'branch' => 'forte',
                        'branch_label' => 'Silnejsia alebo value forte alternativa',
                        'reasoning' => 'Forte alebo silnejsie nastavena alternativa pre cloveka, ktory chce vyssiu koncentraciu alebo silnejsi dojem z produktu.',
                        'preferred_slugs' => ['gymbeam-gymbeam-omega-3-forte'],
                        'preferred_keywords' => ['omega 3 forte', 'forte', 'omega-3'],
                        'requires_prefer_hit' => true,
                    ],
                    3 => [
                        'role' => 'alternative',
                        'branch' => 'specific',
                        'branch_label' => 'Ina vetva rozhodnutia',
                        'reasoning' => 'Odlisna omega-3 vetva pre citatela, ktory chce vegan variant alebo sirsi wellness angle.',
                        'preferred_slugs' => ['gymbeam-gymbeam-vegan-omega-3', 'gymbeam-gymbeam-omega-3-vitamin-d3'],
                        'preferred_keywords' => ['vegan omega 3', 'omega-3', 'vitamin d3'],
                        'requires_prefer_hit' => true,
                    ],
                ],
            ],
            'multivitamin-general' => [
                'intent_label' => 'Multivitamin',
                'summary' => 'System hlada silnejsi all-in-one default, jednoduchsi daily-use format a tretiu alternativnu vetvu.',
                'allow_categories' => [],
                'disable_rule_category_fallback' => true,
                'prefer_keywords' => ['multivitamin', 'multivitam', 'vitamins', 'daily vitamin'],
                'exclude_keywords' => ['pre-workout', 'predtrening', 'kreatin', 'creatine', 'whey', 'protein isolate', 'kolagen', 'probiotik'],
                'slots' => [
                    1 => [
                        'role' => 'featured',
                        'branch' => 'overall',
                        'branch_label' => 'Najlepsi multivitamin default',
                        'reasoning' => 'Hlavna volba pre citatela, ktory chce co najjasnejsi all-in-one multivitamin default.',
                        'preferred_slugs' => ['gymbeam-now-men-s-active-sports-multivitamin'],
                        'preferred_keywords' => ['multivitamin', 'active sports'],
                    ],
                    2 => [
                        'role' => 'value',
                        'branch' => 'simple',
                        'branch_label' => 'Jednoduchsia everyday volba',
                        'reasoning' => 'Lahsie uchopitelna alebo jednoduchsi format orientovany na bezny daily-use.',
                        'preferred_slugs' => ['gymbeam-gymbeam-yummies-multivitamin-pomaranc-citr-on-ceresna'],
                        'preferred_keywords' => ['multivitamin', 'yummies'],
                        'requires_prefer_hit' => true,
                    ],
                    3 => [
                        'role' => 'alternative',
                        'branch' => 'specific',
                        'branch_label' => 'Ina multivitaminova alternativa',
                        'reasoning' => 'Tretia vetva pre cloveka, ktory chce iny styl multivitaminoveho produktu.',
                        'preferred_slugs' => ['gymbeam-the-protein-works-multivitamin-ultra-60-tab'],
                        'preferred_keywords' => ['multivitamin', 'ultra', 'vitamins'],
                        'requires_prefer_hit' => true,
                    ],
                ],
            ],
            'vitamin-c-general' => [
                'intent_label' => 'Vitamin C',
                'summary' => 'System hlada jednoduchy vitamin C default, silnejsiu alebo value alternativu a tretiu kombinovanu vetvu.',
                'allow_categories' => [],
                'disable_rule_category_fallback' => true,
                'prefer_keywords' => ['vitamin c', 'askorb', 'imunita', 'zinc'],
                'exclude_keywords' => ['pre-workout', 'predtrening', 'kreatin', 'creatine', 'whey', 'protein', 'kolagen'],
                'slots' => [
                    1 => [
                        'role' => 'featured',
                        'branch' => 'overall',
                        'branch_label' => 'Najlepsi vitamin C default',
                        'reasoning' => 'Najjednoduchsia hlavna volba pre cloveka, ktory chce riesit vitamin C priamo a bez zbytocneho komba.',
                        'preferred_slugs' => ['gymbeam-gymbeam-vitamin-c-500-mg'],
                        'preferred_keywords' => ['vitamin c'],
                    ],
                    2 => [
                        'role' => 'value',
                        'branch' => 'higher-dose',
                        'branch_label' => 'Silnejsia alebo value alternativa',
                        'reasoning' => 'Vyssia davka alebo value alternativa pre cloveka, ktory chce iny format vitaminu C.',
                        'preferred_slugs' => ['gymbeam-now-foods-vitamin-c-1000-mg'],
                        'preferred_keywords' => ['vitamin c 1000', '1000 mg', 'vitamin c'],
                        'requires_prefer_hit' => true,
                    ],
                    3 => [
                        'role' => 'alternative',
                        'branch' => 'combo',
                        'branch_label' => 'Kombinovana alternativa',
                        'reasoning' => 'Ina vetva rozhodnutia pre cloveka, ktory chce vitamin C uz v kombinacii so zinkom.',
                        'preferred_slugs' => ['gymbeam-gymbeam-vitam-in-c-zinok-120-tab'],
                        'preferred_keywords' => ['vitamin c', 'zinok', 'zinc'],
                        'requires_prefer_hit' => true,
                    ],
                ],
            ],
            'vitamin-b12-general' => [
                'intent_label' => 'Vitamin B12',
                'summary' => 'System hlada aktivnu alebo silnu B12 formu, jednoduchu tabletkovu value volbu a tretiu odlisnu formu pouzitia.',
                'allow_categories' => [],
                'disable_rule_category_fallback' => true,
                'prefer_keywords' => ['vitamin b12', 'b12', 'kobalamin', 'metylkobalamin'],
                'exclude_keywords' => ['pre-workout', 'predtrening', 'kreatin', 'creatine', 'whey', 'protein', 'kolagen'],
                'slots' => [
                    1 => [
                        'role' => 'featured',
                        'branch' => 'active',
                        'branch_label' => 'Najlepsia aktivna forma',
                        'reasoning' => 'Hlavna volba pre cloveka, ktory chce lepsie obhajitelnu aktivnu alebo pokrocilejsiu B12 formu.',
                        'preferred_slugs' => ['gymbeam-gymbeam-akt-ivny-vitam-in-b12-metylkobalam-in', 'gymbeam-gymbeam-vitam-in-b12-koenz-ymov-a-forma'],
                        'preferred_keywords' => ['active', 'metylkobalamin', 'koenzym', 'vitamin b12'],
                        'requires_prefer_hit' => true,
                    ],
                    2 => [
                        'role' => 'value',
                        'branch' => 'value',
                        'branch_label' => 'Jednoduchsia tabletkova volba',
                        'reasoning' => 'Jednoduchsi tabletkovy default pre cloveka, ktory chce vitamin B12 vyriesit bez komplikacii.',
                        'preferred_slugs' => ['gymbeam-gymbeam-vitam-in-b12-kobalam-in-90-tab-bez-pr-ichute'],
                        'preferred_keywords' => ['vitamin b12', 'kobalamin', 'tab'],
                    ],
                    3 => [
                        'role' => 'alternative',
                        'branch' => 'format',
                        'branch_label' => 'Ina forma pouzitia',
                        'reasoning' => 'Tretia vetva pre cloveka, ktory chce iny sposob uzivania, napríklad sprej.',
                        'preferred_slugs' => ['gymbeam-gymbeam-vitam-in-b12-v-spreji-dragon-fruit'],
                        'preferred_keywords' => ['vitamin b12', 'sprej', 'spray'],
                        'requires_prefer_hit' => true,
                    ],
                ],
            ],
            'vyziva-general' => [
                'intent_label' => 'Doplnky vyzivy',
                'summary' => 'System hlada vykonovy zaklad, recovery/value zaklad a sirsi mikronutrientovy next step.',
                'allow_categories' => ['kreatin', 'mineraly', 'proteiny', 'probiotika-travenie', 'klby-koza', 'imunita'],
                'prefer_keywords' => ['doplnky', 'vyziva', 'kreatin', 'magnesium', 'horcik', 'vitamin d', 'protein', 'probiotik'],
                'exclude_keywords' => ['predtrening extreme', 'pre-workout hardcore'],
                'slots' => [
                    1 => [
                        'role' => 'featured',
                        'branch' => 'foundation-performance',
                        'branch_label' => 'Najpraktickejsi zaklad',
                        'reasoning' => 'Najsilnejsi vykonovy zaklad, ktory dava zmysel pre siroku skupinu citatelov.',
                        'preferred_slugs' => ['gymbeam-gymbeam-kreat-in-100-creapure-r'],
                        'preferred_keywords' => ['kreatin', 'creatine', 'creapure', 'vykon', 'sila'],
                        'preferred_categories' => ['kreatin'],
                    ],
                    2 => [
                        'role' => 'value',
                        'branch' => 'foundation-recovery',
                        'branch_label' => 'Value alebo recovery zaklad',
                        'reasoning' => 'Rozumna kazdodenna alternativa pre regeneraciu, napatie alebo hekticky rezim.',
                        'preferred_slugs' => ['gymbeam-gymbeam-magnesium-chelate-powder-bez-pr-ichute'],
                        'preferred_keywords' => ['magnesium', 'horcik', 'recovery', 'regeneracia'],
                        'preferred_categories' => ['mineraly'],
                    ],
                    3 => [
                        'role' => 'alternative',
                        'branch' => 'micronutrient',
                        'branch_label' => 'Sirsi micronutrientovy next step',
                        'reasoning' => 'Specificka tretia vetva pre cloveka, ktory chce doplnit zaklad o rozumny mikronutrientovy produkt.',
                        'preferred_slugs' => ['gymbeam-gymbeam-vitamin-d3-k1-k2-forte'],
                        'preferred_keywords' => ['vitamin d', 'vitamin d3', 'k1', 'k2', 'imunita'],
                        'preferred_categories' => ['mineraly', 'imunita'],
                        'requires_prefer_hit' => true,
                    ],
                ],
            ],
            'imunita-general' => [
                'intent_label' => 'Imunita',
                'summary' => 'System hlada hlavnu imunitnu volbu, value podporu a odlisnu gut alebo wellness vetvu.',
                'allow_categories' => ['imunita', 'mineraly', 'probiotika-travenie'],
                'prefer_keywords' => ['imunita', 'immune', 'vitamin d', 'zinc', 'zinok', 'probiotic', 'gut', 'beta glukan', 'betaglukan', 'imunoglukan'],
                'exclude_keywords' => ['pre-workout', 'predtrening', 'kreatin', 'creatine', 'whey', 'protein'],
                'slots' => [
                    1 => [
                        'role' => 'featured',
                        'branch' => 'immune-core',
                        'branch_label' => 'Najlepsi imunitny zaklad',
                        'reasoning' => 'Hlavna volba pre cloveka, ktory hlada jasny imunitny default produkt.',
                        'preferred_slugs' => ['imunoklub-imunoglukan-p4h-60-kapsul'],
                        'preferred_keywords' => ['imuno', 'glukan', 'immune', 'vitamin d', 'd3', 'beta glukan'],
                        'preferred_merchants' => ['imunoklub'],
                        'preferred_categories' => ['imunita', 'mineraly'],
                    ],
                    2 => [
                        'role' => 'value',
                        'branch' => 'value',
                        'branch_label' => 'Najlepsia value podpora',
                        'reasoning' => 'Jednoduchsia value alternativa pre bezny imunitny support.',
                        'preferred_slugs' => ['gymbeam-gymbeam-vitam-in-c-zinok-120-tab'],
                        'preferred_keywords' => ['vitamin c', 'zinok', 'zinc'],
                        'preferred_categories' => ['mineraly'],
                    ],
                    3 => [
                        'role' => 'alternative',
                        'branch' => 'gut',
                        'branch_label' => 'Ina wellness vetva',
                        'reasoning' => 'Doplna imunitnu temu o gut alebo digestion angle ako odlisny next step.',
                        'preferred_slugs' => ['symprove-daily-essential-4-tyzdne', 'gymbeam-probioten'],
                        'preferred_keywords' => ['probiotic', 'probio', 'gut', 'digestion'],
                        'preferred_categories' => ['probiotika-travenie'],
                        'requires_prefer_hit' => true,
                    ],
                ],
            ],
        ];
    }
}

if (!function_exists('interessa_admin_article_recommendation_profile')) {
    function interessa_admin_article_recommendation_profile(string $articleSlug): array {
        $context = interessa_admin_article_recommendation_context($articleSlug);
        $haystack = (string) (($context['core_haystack'] ?? '') ?: ($context['haystack'] ?? ''));
        $profiles = interessa_admin_article_recommendation_profiles();

        $hasTokens = static function (array $tokens) use ($haystack): bool {
            foreach ($tokens as $token) {
                $token = interessa_product_lower(trim((string) $token));
                if ($token !== '' && str_contains($haystack, $token)) {
                    return true;
                }
            }
            return false;
        };

        $profileKey = 'vyziva-general';
          if (in_array($articleSlug, ['najlepsie-proteiny-2026', 'najlepsie-proteiny-2025'], true)) {
              $profileKey = 'proteiny-general';
          } elseif ($articleSlug === 'protein-na-chudnutie') {
              $profileKey = 'proteiny-chudnutie';
          } elseif ($articleSlug === 'kreatin-porovnanie') {
              $profileKey = 'kreatin-general';
          } elseif (str_contains($articleSlug, 'vitamin-d') || str_contains($articleSlug, 'vitamin-d3')) {
              $profileKey = 'vitamin-d-general';
          } elseif (str_contains($articleSlug, 'vitamin-c')) {
              $profileKey = 'vitamin-c-general';
          } elseif (str_contains($articleSlug, 'vitamin-b12')) {
              $profileKey = 'vitamin-b12-general';
          } elseif (str_contains($articleSlug, 'multivitamin')) {
              $profileKey = 'multivitamin-general';
          } elseif (str_contains($articleSlug, 'omega-3') || str_contains($articleSlug, 'omega3')) {
              $profileKey = 'omega3-general';
          } elseif (str_contains($articleSlug, 'pre-workout')) {
              $profileKey = $hasTokens(['bez kofeinu', 'bezkofein', 'stim free', 'stim-free', 'without caffeine', 'caffeine free'])
                  ? 'pre-workout-caffeine-free'
                  : 'pre-workout-general';
          } elseif (str_contains($articleSlug, 'probiot')) {
              $profileKey = 'probiotika-general';
          } elseif (str_contains($articleSlug, 'kolagen') || str_contains($articleSlug, 'collagen') || str_contains($articleSlug, 'klby')) {
              $profileKey = 'kolagen-general';
          } elseif (str_contains($articleSlug, 'imunit') || str_contains($articleSlug, 'beta-glukan')) {
              $profileKey = 'imunita-general';
          } elseif (str_contains($articleSlug, 'horcik') || str_contains($articleSlug, 'magnez')) {
              $profileKey = 'mineraly-horcik';
          } elseif (str_contains($articleSlug, 'zinok') || str_contains($articleSlug, 'zinc') || str_contains($articleSlug, 'zinek')) {
              $profileKey = 'mineraly-zinok';
          } elseif ($articleSlug === 'doplnky-vyzivy' || normalize_category_slug((string) ($context['category'] ?? '')) === 'vyziva') {
              $profileKey = 'vyziva-general';
          } elseif ($hasTokens(['pre-workout', 'predtrening', 'pred trening', 'nakopavac'])) {
              $profileKey = $hasTokens(['bez kofeinu', 'bezkofein', 'stim free', 'stim-free', 'without caffeine', 'caffeine free'])
                  ? 'pre-workout-caffeine-free'
                  : 'pre-workout-general';
          } elseif ($hasTokens(['kreatin', 'creatine', 'creapure'])) {
              $profileKey = 'kreatin-general';
          } elseif ($hasTokens(['vitamin d3', 'vitamin d', ' d3 ', 'k2'])) {
              $profileKey = 'vitamin-d-general';
          } elseif ($hasTokens(['vitamin c', 'askorb'])) {
              $profileKey = 'vitamin-c-general';
          } elseif ($hasTokens(['vitamin b12', ' b12 ', 'kobalamin', 'metylkobalamin'])) {
              $profileKey = 'vitamin-b12-general';
          } elseif ($hasTokens(['multivitamin', 'multivitam'])) {
              $profileKey = 'multivitamin-general';
          } elseif ($hasTokens(['omega-3', 'omega 3', 'fish oil', 'epa', 'dha', 'rybi olej'])) {
              $profileKey = 'omega3-general';
          } elseif ($hasTokens(['protein', 'proteiny', 'whey', 'isolate', 'isolat'])) {
              $profileKey = $hasTokens(['chudnut', 'chudnutie', 'redukcia', 'dieta', 'diet'])
                  ? 'proteiny-chudnutie'
                  : 'proteiny-general';
          } elseif ($hasTokens(['probiotik', 'digestion', 'gut', 'lactobac', 'rhamnosus'])) {
              $profileKey = 'probiotika-general';
          } elseif ($hasTokens(['kolagen', 'collagen', 'klby', 'joint'])) {
              $profileKey = 'kolagen-general';
          } elseif ($hasTokens(['imunita', 'immune', 'beta glukan', 'betaglukan', 'imunoglukan'])) {
              $profileKey = 'imunita-general';
          } elseif ($hasTokens(['zinok', 'zinc', 'zinek'])) {
              $profileKey = 'mineraly-zinok';
          } elseif ($hasTokens(['horcik', 'magnez', 'magnesium', 'zmb'])) {
              $profileKey = 'mineraly-horcik';
          } elseif ($hasTokens(['imunita', 'immune', 'vitamin d', 'd3', 'imunoglukan'])) {
              $profileKey = 'imunita-general';
        }

        $profile = $profiles[$profileKey] ?? $profiles['vyziva-general'];
        $rule = is_array($context['rule'] ?? null) ? $context['rule'] : [];
        $profileAllowCategories = array_values(array_unique(array_filter(array_map(
            static fn($value): string => normalize_category_slug((string) $value),
            is_array($profile['allow_categories'] ?? null) ? $profile['allow_categories'] : []
        ))));
          if ($profileAllowCategories === [] && empty($profile['disable_rule_category_fallback'])) {
              $profileAllowCategories = array_values(array_unique(array_filter(array_map(
                  static fn($value): string => normalize_category_slug((string) $value),
                  is_array($rule['categories'] ?? null) ? $rule['categories'] : []
              ))));
          }
        $profilePreferKeywords = array_values(array_unique(array_filter(array_map(
            static fn($value): string => interessa_product_lower(trim((string) $value)),
            is_array($profile['prefer_keywords'] ?? null) ? $profile['prefer_keywords'] : []
        ))));
        $profileExcludeKeywords = array_values(array_unique(array_filter(array_map(
            static fn($value): string => interessa_product_lower(trim((string) $value)),
            is_array($profile['exclude_keywords'] ?? null) ? $profile['exclude_keywords'] : []
        ))));

        $profile['key'] = $profileKey;
        $profile['context'] = $context;
        $profile['article_rule'] = [
            'allow_categories' => $profileAllowCategories,
            'prefer_keywords' => $profilePreferKeywords,
            'exclude_keywords' => $profileExcludeKeywords,
            'categories' => $profileAllowCategories,
            'strict' => true,
        ];

        return $profile;
    }
}

if (!function_exists('interessa_admin_recommendation_target_state')) {
    function interessa_admin_recommendation_target_state(array $product): array {
        $normalized = interessa_normalize_product($product);
        $target = function_exists('interessa_affiliate_target') ? interessa_affiliate_target($normalized) : [];
        $directUrl = trim((string) ($target['direct_url'] ?? $normalized['url'] ?? $normalized['fallback_url'] ?? ''));
        if ($directUrl === '') {
            $directUrl = trim((string) ($normalized['url'] ?? $normalized['fallback_url'] ?? ''));
        }

        $merchantSlug = trim((string) ($normalized['merchant_slug'] ?? ''));
        $affiliateCode = trim((string) ($target['code'] ?? $normalized['affiliate_code'] ?? ''));
        $productLevelReady = $directUrl !== '' && interessa_admin_looks_like_product_url($directUrl);
        $affiliateReady = $productLevelReady && ($affiliateCode !== '' || trim((string) ($target['status'] ?? '')) === 'affiliate_ready');
        $hasImage = !empty($normalized['has_local_image']) || trim((string) ($normalized['image_remote_src'] ?? '')) !== '';

        return [
            'merchant_slug' => $merchantSlug,
            'direct_url' => $directUrl,
            'affiliate_code' => $affiliateCode,
            'product_level_ready' => $productLevelReady,
            'affiliate_ready' => $affiliateReady,
            'has_image' => $hasImage,
            'supported_merchant' => function_exists('interessa_is_supported_affiliate_merchant')
                ? interessa_is_supported_affiliate_merchant($merchantSlug)
                : false,
        ];
    }
}

if (!function_exists('interessa_admin_recommendation_slot_candidate')) {
    function interessa_admin_recommendation_slot_candidate(array $product, array $profile, array $slotProfile): ?array {
        $normalized = interessa_normalize_product($product);
        $slug = interessa_admin_slugify((string) ($normalized['slug'] ?? ''));
        $category = normalize_category_slug((string) ($normalized['category'] ?? ''));
        if ($slug === '' || $category === '') {
            return null;
        }

        $baseRule = is_array($profile['article_rule'] ?? null) ? $profile['article_rule'] : [];
        if (!empty($slotProfile['preferred_categories'])) {
            $baseRule['allow_categories'] = array_values(array_unique(array_filter(array_map(
                static fn($value): string => normalize_category_slug((string) $value),
                (array) ($slotProfile['preferred_categories'] ?? [])
            ))));
            $baseRule['categories'] = $baseRule['allow_categories'];
        }

        $text = interessa_admin_recommendation_product_text($normalized);
        $preferredSlugs = array_values(array_filter(array_map('interessa_admin_slugify', (array) ($slotProfile['preferred_slugs'] ?? []))));
        $preferHits = interessa_admin_recommendation_keyword_hits($text, (array) ($slotProfile['preferred_keywords'] ?? []));
        $avoidHits = interessa_admin_recommendation_keyword_hits($text, (array) ($slotProfile['avoid_keywords'] ?? []));
        $baseMeta = function_exists('interessa_product_article_match_score')
            ? interessa_product_article_match_score($normalized, $baseRule)
            : null;
        if (!is_array($baseMeta) && !in_array($slug, $preferredSlugs, true) && $preferHits === []) {
            return null;
        }

        $targetState = interessa_admin_recommendation_target_state($normalized);
        if (empty($targetState['supported_merchant']) || empty($targetState['product_level_ready'])) {
            return null;
        }
        if (!empty($slotProfile['requires_prefer_hit']) && $preferHits === [] && !in_array($slug, (array) ($slotProfile['preferred_slugs'] ?? []), true)) {
            return null;
        }
        if ($avoidHits !== []) {
            return null;
        }

        $score = (int) ($baseMeta['score'] ?? 0);
        if (in_array($slug, $preferredSlugs, true)) {
            $score += 1400;
        }
        $score += count($preferHits) * 160;
        $score += !empty($targetState['affiliate_ready']) ? 220 : 40;
        $score += !empty($targetState['has_image']) ? 50 : 0;
        $preferredMerchants = array_values(array_filter(array_map('interessa_admin_slugify', (array) ($slotProfile['preferred_merchants'] ?? []))));
        if ($preferredMerchants !== [] && in_array((string) ($targetState['merchant_slug'] ?? ''), $preferredMerchants, true)) {
            $score += 140;
        }

        return [
            'slot' => max(1, min(3, (int) ($slotProfile['slot'] ?? 1))),
            'product_slug' => $slug,
            'role' => interessa_admin_normalize_candidate_role($slotProfile['role'] ?? 'standard'),
            'branch' => interessa_admin_slugify((string) ($slotProfile['branch'] ?? '')),
            'branch_label' => interessa_admin_normalize_text($slotProfile['branch_label'] ?? ''),
            'reasoning' => interessa_admin_normalize_text($slotProfile['reasoning'] ?? ''),
            'score' => $score,
            'merchant' => trim((string) ($normalized['merchant'] ?? '')),
            'merchant_slug' => trim((string) ($targetState['merchant_slug'] ?? '')),
            'category' => $category,
            'affiliate_ready' => !empty($targetState['affiliate_ready']),
            'product_level_ready' => !empty($targetState['product_level_ready']),
            'family_key' => interessa_admin_recommendation_family_key($normalized),
            'name' => trim((string) ($normalized['name'] ?? $slug)),
        ];
    }
}

if (!function_exists('interessa_admin_build_article_product_recommendation')) {
    function interessa_admin_build_article_product_recommendation(string $articleSlug): array {
        $articleSlug = canonical_article_slug(trim($articleSlug));
        if ($articleSlug === '') {
            throw new RuntimeException('Nepodarilo sa vyhodnotit clanok pre navrh produktov.');
        }

        $profile = interessa_admin_article_recommendation_profile($articleSlug);
        $catalog = function_exists('interessa_product_catalog') ? interessa_product_catalog() : [];
        if (!is_array($catalog) || $catalog === []) {
            throw new RuntimeException('Katalog produktov nie je dostupny.');
        }

        $allowedCategories = array_values(array_unique(array_filter(array_map(
            static fn($value): string => normalize_category_slug((string) $value),
            (array) (($profile['article_rule']['allow_categories'] ?? []))
        ))));
        $globalPreferKeywords = array_values(array_unique(array_filter(array_map(
            static fn($value): string => interessa_product_lower(trim((string) $value)),
            (array) (($profile['article_rule']['prefer_keywords'] ?? []))
        ))));
        $globalExcludeKeywords = array_values(array_unique(array_filter(array_map(
            static fn($value): string => interessa_product_lower(trim((string) $value)),
            (array) (($profile['article_rule']['exclude_keywords'] ?? []))
        ))));
        $preferredSlugs = [];
        foreach ((array) ($profile['slots'] ?? []) as $slotProfile) {
            foreach ((array) ($slotProfile['preferred_slugs'] ?? []) as $preferredSlug) {
                $normalizedPreferredSlug = interessa_admin_slugify((string) $preferredSlug);
                if ($normalizedPreferredSlug !== '') {
                    $preferredSlugs[$normalizedPreferredSlug] = true;
                }
            }
        }

        $candidatePool = [];
        foreach ($catalog as $catalogSlug => $catalogRow) {
            if (!is_array($catalogRow)) {
                continue;
            }

            $rawSlug = interessa_admin_slugify((string) ($catalogRow['slug'] ?? $catalogSlug));
            $rawCategory = normalize_category_slug((string) ($catalogRow['category'] ?? ''));
            $rawMerchantSlug = interessa_admin_slugify((string) ($catalogRow['merchant_slug'] ?? ''));
            if ($rawSlug === '' || $rawCategory === '' || !function_exists('interessa_is_supported_affiliate_merchant') || !interessa_is_supported_affiliate_merchant($rawMerchantSlug)) {
                continue;
            }
            if ($allowedCategories !== [] && !in_array($rawCategory, $allowedCategories, true) && !isset($preferredSlugs[$rawSlug])) {
                continue;
            }

            $rawText = interessa_product_lower(trim(implode(' ', array_filter([
                $rawSlug,
                str_replace('-', ' ', $rawSlug),
                (string) ($catalogRow['name'] ?? ''),
                (string) ($catalogRow['summary'] ?? ''),
                $rawCategory,
            ]))));
            if ($globalExcludeKeywords !== [] && interessa_admin_recommendation_keyword_hits($rawText, $globalExcludeKeywords) !== []) {
                continue;
            }
            if ($globalPreferKeywords !== [] && interessa_admin_recommendation_keyword_hits($rawText, $globalPreferKeywords) === [] && !isset($preferredSlugs[$rawSlug])) {
                continue;
            }

            $candidatePool[$rawSlug] = $catalogRow;
        }
        if ($candidatePool === []) {
            throw new RuntimeException('System nenasiel relevantny pool produktov pre clanok.');
        }

        $selected = [];
        $selectedSlugs = [];
        $selectedFamilies = [];
        $allCandidates = [];
        foreach (range(1, 3) as $slot) {
            $slotProfile = is_array($profile['slots'][$slot] ?? null) ? $profile['slots'][$slot] : [];
            $slotProfile['slot'] = $slot;
            $slotCandidates = [];

            foreach ($candidatePool as $product) {
                if (!is_array($product)) {
                    continue;
                }

                $candidate = interessa_admin_recommendation_slot_candidate($product, $profile, $slotProfile);
                if (!is_array($candidate)) {
                    continue;
                }
                if (isset($selectedSlugs[$candidate['product_slug']])) {
                    continue;
                }

                $familyKey = trim((string) ($candidate['family_key'] ?? $candidate['product_slug']));
                $allowFamilyOverlap = !empty($slotProfile['allow_family_overlap']);
                if (!$allowFamilyOverlap && $familyKey !== '' && isset($selectedFamilies[$familyKey])) {
                    continue;
                }

                $slotCandidates[] = $candidate;
                $allCandidates[] = $candidate;
            }

            usort($slotCandidates, static function (array $left, array $right): int {
                $compare = ((int) ($right['score'] ?? 0)) <=> ((int) ($left['score'] ?? 0));
                if ($compare !== 0) {
                    return $compare;
                }

                return strcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
            });

            $top = $slotCandidates[0] ?? null;
            if (is_array($top)) {
                $selected[] = $top;
                $selectedSlugs[$top['product_slug']] = true;
                $familyKey = trim((string) ($top['family_key'] ?? $top['product_slug']));
                if ($familyKey !== '') {
                    $selectedFamilies[$familyKey] = true;
                }
            }
        }

        if (count($selected) < 3) {
            usort($allCandidates, static function (array $left, array $right): int {
                $compare = ((int) ($right['score'] ?? 0)) <=> ((int) ($left['score'] ?? 0));
                if ($compare !== 0) {
                    return $compare;
                }

                return max(1, (int) ($left['slot'] ?? 1)) <=> max(1, (int) ($right['slot'] ?? 1));
            });
            foreach ($allCandidates as $candidate) {
                $slot = max(1, min(3, (int) ($candidate['slot'] ?? 1)));
                $familyKey = trim((string) ($candidate['family_key'] ?? $candidate['product_slug']));
                $slotProfile = is_array($profile['slots'][$slot] ?? null) ? $profile['slots'][$slot] : [];
                $allowFamilyOverlap = !empty($slotProfile['allow_family_overlap']);
                if (isset($selectedSlugs[$candidate['product_slug']]) || (!$allowFamilyOverlap && $familyKey !== '' && isset($selectedFamilies[$familyKey]))) {
                    continue;
                }

                $alreadyFilled = false;
                foreach ($selected as $selectedItem) {
                    if (max(1, min(3, (int) ($selectedItem['slot'] ?? 1))) === $slot) {
                        $alreadyFilled = true;
                        break;
                    }
                }
                if ($alreadyFilled) {
                    continue;
                }

                $selected[] = $candidate;
                $selectedSlugs[$candidate['product_slug']] = true;
                if ($familyKey !== '') {
                    $selectedFamilies[$familyKey] = true;
                }
                if (count($selected) >= 3) {
                    break;
                }
            }
        }

        usort($selected, static function (array $left, array $right): int {
            return max(1, (int) ($left['slot'] ?? 1)) <=> max(1, (int) ($right['slot'] ?? 1));
        });

        if (count($selected) !== 3) {
            throw new RuntimeException('System nenasiel 3 dostatocne relevantne a odlisne produkty pre clanok.');
        }

        return interessa_admin_normalize_product_recommendation($articleSlug, [
            'article_slug' => $articleSlug,
            'intent_key' => (string) ($profile['key'] ?? ''),
            'intent_label' => (string) ($profile['intent_label'] ?? ''),
            'summary' => (string) ($profile['summary'] ?? ''),
            'generated_at' => date('c'),
            'items' => array_map(static function (array $item): array {
                return [
                    'slot' => max(1, min(3, (int) ($item['slot'] ?? 1))),
                    'product_slug' => (string) ($item['product_slug'] ?? ''),
                    'role' => (string) ($item['role'] ?? 'standard'),
                    'branch' => (string) ($item['branch'] ?? ''),
                    'branch_label' => (string) ($item['branch_label'] ?? ''),
                    'reasoning' => (string) ($item['reasoning'] ?? ''),
                    'score' => (int) ($item['score'] ?? 0),
                    'merchant' => (string) ($item['merchant'] ?? ''),
                    'merchant_slug' => (string) ($item['merchant_slug'] ?? ''),
                    'category' => (string) ($item['category'] ?? ''),
                    'affiliate_ready' => !empty($item['affiliate_ready']),
                    'product_level_ready' => !empty($item['product_level_ready']),
                ];
            }, $selected),
        ]);
    }
}

if (!function_exists('interessa_admin_store_article_product_recommendation')) {
    function interessa_admin_store_article_product_recommendation(string $articleSlug): array {
        $articleSlug = canonical_article_slug(trim($articleSlug));
        if ($articleSlug === '') {
            throw new RuntimeException('Chyba clanok pre navrh produktov.');
        }

        $recommendation = interessa_admin_build_article_product_recommendation($articleSlug);
        $override = interessa_admin_article_override($articleSlug);
        if ($override === []) {
            $override = ['slug' => $articleSlug];
        }
        $override['product_recommendation'] = $recommendation;
        interessa_admin_write_article_override_raw($articleSlug, $override);

        $persisted = interessa_admin_article_override($articleSlug);
        $persistedRecommendation = interessa_admin_normalize_product_recommendation($articleSlug, is_array($persisted['product_recommendation'] ?? null) ? $persisted['product_recommendation'] : []);
        if (count((array) ($persistedRecommendation['items'] ?? [])) !== 3) {
            throw new RuntimeException('Navrh produktov sa neulozil korektne.');
        }

        return $persistedRecommendation;
    }
}

if (!function_exists('interessa_admin_load_or_build_article_product_recommendation')) {
    function interessa_admin_load_or_build_article_product_recommendation(string $articleSlug, bool $forceRegenerate = false): array {
        $articleSlug = canonical_article_slug(trim($articleSlug));
        if ($articleSlug === '') {
            throw new RuntimeException('Chyba clanok pre navrh produktov.');
        }

        $override = interessa_admin_article_override($articleSlug);
        $stored = interessa_admin_normalize_product_recommendation($articleSlug, is_array($override['product_recommendation'] ?? null) ? $override['product_recommendation'] : []);
        if (!$forceRegenerate && count((array) ($stored['items'] ?? [])) === 3) {
            return $stored;
        }

        return interessa_admin_store_article_product_recommendation($articleSlug);
    }
}

if (!function_exists('interessa_admin_apply_article_product_recommendation')) {
    function interessa_admin_apply_article_product_recommendation(string $articleSlug, bool $forceRegenerate = false): array {
        $articleSlug = canonical_article_slug(trim($articleSlug));
        if ($articleSlug === '') {
            throw new RuntimeException('Chyba clanok pre automaticke priradenie produktov.');
        }

        $recommendation = interessa_admin_load_or_build_article_product_recommendation($articleSlug, $forceRegenerate);
        $items = array_values(array_filter((array) ($recommendation['items'] ?? []), 'is_array'));
        if (count($items) !== 3) {
            throw new RuntimeException('System nema pripravene 3 navrhnute produkty pre clanok.');
        }

        $originalRows = interessa_admin_article_products();
        $articlePath = interessa_admin_article_override_path($articleSlug);
        $articleExisted = is_file($articlePath);
        $originalArticleJson = $articleExisted ? (string) @file_get_contents($articlePath) : '';
        if ($articleExisted && $originalArticleJson === '') {
            throw new RuntimeException('Nepodarilo sa precitat povodny clanok pred automatickym priradenim.');
        }

        $comparisonAllowed = false;
        if (function_exists('interessa_article_comparison_table_whitelist')) {
            $comparisonAllowed = in_array($articleSlug, interessa_article_comparison_table_whitelist(), true);
        }

        try {
            usort($items, static function (array $left, array $right): int {
                return max(1, (int) ($left['slot'] ?? 1)) <=> max(1, (int) ($right['slot'] ?? 1));
            });

            foreach ($items as $item) {
                $slot = max(1, min(3, (int) ($item['slot'] ?? 1)));
                $productSlug = interessa_admin_slugify((string) ($item['product_slug'] ?? ''));
                if ($productSlug === '') {
                    throw new RuntimeException('Navrhnuty produkt pre Slot ' . $slot . ' nema validny slug.');
                }
                interessa_admin_direct_save_article_slot_product($articleSlug, $productSlug, $slot, 1);
            }

            $productPlan = [];
            foreach ($items as $item) {
                $slot = max(1, min(3, (int) ($item['slot'] ?? 1)));
                $productPlan[] = [
                    'product_slug' => interessa_admin_slugify((string) ($item['product_slug'] ?? '')),
                    'order' => $slot,
                    'role' => interessa_admin_normalize_candidate_role($item['role'] ?? ($slot === 1 ? 'featured' : 'standard')),
                    'show_in_top' => true,
                    'show_in_comparison' => $comparisonAllowed,
                ];
            }
            interessa_admin_save_article_product_plan($articleSlug, $productPlan);

            $override = interessa_admin_article_override($articleSlug);
            $recommendation['applied_at'] = date('c');
            $override['product_recommendation'] = $recommendation;
            interessa_admin_write_article_override_raw($articleSlug, $override);

            $persistedRows = interessa_admin_article_product_records_for_article($articleSlug);
            $enabledRows = array_values(array_filter($persistedRows, static function ($row): bool {
                return is_array($row) && !empty($row['enabled']);
            }));
            usort($enabledRows, static function (array $left, array $right): int {
                return max(1, (int) ($left['order'] ?? 1)) <=> max(1, (int) ($right['order'] ?? 1));
            });
            if (count($enabledRows) !== 3) {
                throw new RuntimeException('SLOT SAVE FAILED: article-products nema presne 3 aktivne sloty po automatickom priradeni.');
            }

            $expectedSlugs = [];
            foreach ($items as $item) {
                $expectedSlugs[max(1, min(3, (int) ($item['slot'] ?? 1)))] = interessa_admin_slugify((string) ($item['product_slug'] ?? ''));
            }
            ksort($expectedSlugs);

            foreach ($enabledRows as $row) {
                $slot = max(1, min(3, (int) ($row['order'] ?? 1)));
                $rowSlug = interessa_admin_slugify((string) ($row['product_slug'] ?? ''));
                if (($expectedSlugs[$slot] ?? '') !== $rowSlug) {
                    throw new RuntimeException('SLOT SAVE FAILED: aktivny produkt v Slot ' . $slot . ' nesedi s navrhom.');
                }
            }

            $state = interessa_admin_article_product_state($articleSlug, interessa_admin_article_override($articleSlug));
            $statePlan = array_values(array_filter((array) ($state['product_plan'] ?? []), 'is_array'));
            usort($statePlan, static function (array $left, array $right): int {
                return max(1, (int) ($left['order'] ?? 1)) <=> max(1, (int) ($right['order'] ?? 1));
            });
            if (count($statePlan) !== 3) {
                throw new RuntimeException('SLOT SAVE FAILED: product_plan nema presne 3 polozky po automatickom priradeni.');
            }
            foreach ($statePlan as $planRow) {
                $slot = max(1, min(3, (int) ($planRow['order'] ?? 1)));
                $rowSlug = interessa_admin_slugify((string) ($planRow['product_slug'] ?? ''));
                if (($expectedSlugs[$slot] ?? '') !== $rowSlug) {
                    throw new RuntimeException('SLOT SAVE FAILED: product_plan pre Slot ' . $slot . ' nesedi s navrhom.');
                }
            }

            $recommended = array_values(array_map(
                'interessa_admin_slugify',
                is_array($state['recommended_products'] ?? null) ? $state['recommended_products'] : []
            ));
            $expectedRecommended = array_values($expectedSlugs);
            if ($recommended !== $expectedRecommended) {
                throw new RuntimeException('SLOT SAVE FAILED: recommended_products po automatickom priradeni nesedi s aktivnymi slotmi.');
            }

              return [
                  'success' => true,
                  'article_slug' => $articleSlug,
                  'intent_key' => (string) ($recommendation['intent_key'] ?? ''),
                  'intent_label' => (string) ($recommendation['intent_label'] ?? ''),
                  'summary' => (string) ($recommendation['summary'] ?? ''),
                  'applied_at' => (string) ($recommendation['applied_at'] ?? ''),
                  'items' => $items,
                  'product_plan' => $statePlan,
                  'recommended_products' => $recommended,
              ];
        } catch (Throwable $e) {
            interessa_admin_restore_article_state($articlePath, $articleExisted, $originalArticleJson, $originalRows);
            throw $e;
        }
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
        $candidate['click_status'] = 'ready';
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
        $candidate['order'] = max(1, (int) ($assignment['order'] ?? 10));
        $candidate['role'] = interessa_admin_normalize_candidate_role($assignment['role'] ?? '');
        $candidate['show_in_top'] = !empty($assignment['show_in_top']);
        $candidate['show_in_comparison'] = !empty($assignment['show_in_comparison']);

        return interessa_admin_save_product_candidate_record($id, $candidate);
    }
}

if (!function_exists('interessa_admin_product_bridge_payload_from_candidate')) {
    function interessa_admin_product_bridge_payload_from_candidate(array $candidate, array $existingProduct = []): array {
        $productSlug = interessa_admin_slugify((string) ($candidate['product_slug'] ?? ''));
        $current = array_replace(
            is_array($existingProduct) ? $existingProduct : [],
            interessa_admin_product_record($productSlug) ?? []
        );

        $resolvedCurrent = interessa_normalize_product($current);
        $currentImage = is_array($current['image'] ?? null) ? $current['image'] : [];
        $candidateName = interessa_admin_normalize_text($candidate['name'] ?? '');
        $candidateMerchant = interessa_admin_normalize_text($candidate['merchant'] ?? '');
        $candidateMerchantSlug = interessa_admin_slugify((string) ($candidate['merchant_slug'] ?? ''));
        $candidateCategory = normalize_category_slug((string) ($candidate['category'] ?? ''));
        $candidateFallbackUrl = trim((string) ($candidate['url'] ?? ''));
        $candidateImageRemoteSrc = trim((string) ($candidate['image_remote_src'] ?? ''));
        $candidateAffiliateCode = interessa_admin_slugify((string) ($candidate['click_code'] ?? ''));

        $merged = [
            'slug' => $productSlug,
            'name' => trim((string) ($current['name'] ?? '')),
            'brand' => trim((string) ($current['brand'] ?? '')),
            'merchant' => trim((string) ($current['merchant'] ?? '')),
            'merchant_slug' => trim((string) ($current['merchant_slug'] ?? '')),
            'category' => trim((string) ($current['category'] ?? '')),
            'affiliate_code' => trim((string) ($current['affiliate_code'] ?? '')),
            'fallback_url' => trim((string) ($current['fallback_url'] ?? '')),
            'summary' => trim((string) ($current['summary'] ?? '')),
            'rating' => $current['rating'] ?? 0,
            'pros' => $current['pros'] ?? [],
            'cons' => $current['cons'] ?? [],
            'image_asset' => trim((string) ($current['image_asset'] ?? ($currentImage['asset'] ?? ''))),
            'image_remote_src' => trim((string) ($current['image_remote_src'] ?? ($currentImage['remote_src'] ?? ''))),
        ];

        if ($merged['name'] === '' && $candidateName !== '') {
            $merged['name'] = $candidateName;
        }
        if ($merged['brand'] === '' && $candidateMerchant !== '') {
            $merged['brand'] = $candidateMerchant;
        }
        if ($merged['merchant'] === '' && $candidateMerchant !== '') {
            $merged['merchant'] = $candidateMerchant;
        }
        if ($merged['merchant_slug'] === '' && $candidateMerchantSlug !== '') {
            $merged['merchant_slug'] = $candidateMerchantSlug;
        }
        if ($merged['category'] === '' && $candidateCategory !== '') {
            $merged['category'] = $candidateCategory;
        }
        if ($merged['affiliate_code'] === '' && $candidateAffiliateCode !== '') {
            $merged['affiliate_code'] = $candidateAffiliateCode;
        }
        if ($merged['fallback_url'] === '' && $candidateFallbackUrl !== '') {
            $merged['fallback_url'] = $candidateFallbackUrl;
        }
        if ($merged['image_remote_src'] === '' && $candidateImageRemoteSrc !== '') {
            $merged['image_remote_src'] = $candidateImageRemoteSrc;
        }
        if ($merged['image_asset'] === '' && trim((string) ($resolvedCurrent['image_local_asset'] ?? '')) !== '') {
            $merged['image_asset'] = trim((string) ($resolvedCurrent['image_local_asset'] ?? ''));
        }

        return $merged;
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

        $clickCode = trim((string) ($candidate['click_code'] ?? ''));
        $clickReady = $clickCode !== '' && trim((string) ($candidate['click_url'] ?? '')) !== '';

        $existingAffiliateRecord = array_replace(
            aff_record($clickCode) ?? [],
            interessa_admin_affiliate_links()[$clickCode] ?? []
        );
        $affiliateWasExisting = $existingAffiliateRecord !== [];
        $affiliateStatus = 'missing';
        if ($clickReady) {
            $affiliatePayload = interessa_admin_affiliate_bridge_payload_from_candidate($candidate, $existingAffiliateRecord);
            interessa_admin_save_affiliate_record($clickCode, $affiliatePayload);
            $affiliateStatus = $affiliateWasExisting ? 'updated' : 'created';
        }

        $existingProduct = array_replace(
            interessa_product($productSlug) ?? [],
            interessa_admin_product_record($productSlug) ?? []
        );
        $productWasExisting = $existingProduct !== [];
        $productPayload = interessa_admin_product_bridge_payload_from_candidate($candidate, $existingProduct);
        interessa_admin_save_product_record($productSlug, $productPayload);

        $articleSlug = trim((string) ($candidate['article_slug'] ?? ''));
        $articleReady = $articleSlug !== '';
        if ($articleReady) {
            interessa_admin_bind_product_to_article($articleSlug, $productSlug, [
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
        $candidate['click_status'] = $clickReady ? 'ready' : 'missing';
        $candidate['product_status'] = $productWasExisting ? 'updated' : 'created';
        $candidate['affiliate_status'] = $affiliateStatus;
        $candidate = interessa_admin_save_product_candidate_record($id, $candidate);

        return [
            'id' => $id,
            'product_slug' => $productSlug,
            'article_slug' => (string) ($candidate['article_slug'] ?? ''),
            'article_ready' => $articleReady,
            'mirrored' => $mirrored,
        ];
    }
}

if (!function_exists('interessa_admin_affiliate_bridge_payload_from_candidate')) {
    function interessa_admin_affiliate_bridge_payload_from_candidate(array $candidate, array $existingRecord = []): array {
        $clickCode = interessa_admin_slugify((string) ($candidate['click_code'] ?? ''));
        $current = array_replace(
            is_array($existingRecord) ? $existingRecord : [],
            interessa_admin_affiliate_links()[$clickCode] ?? []
        );

        $candidateClickUrl = trim((string) ($candidate['click_url'] ?? ''));
        $candidateMerchant = interessa_admin_normalize_text($candidate['merchant'] ?? '');
        $candidateMerchantSlug = interessa_admin_slugify((string) ($candidate['merchant_slug'] ?? ''));
        $candidateProductSlug = interessa_admin_slugify((string) ($candidate['product_slug'] ?? ''));
        $candidateClickStatus = interessa_admin_slugify((string) ($candidate['click_status'] ?? ''));
        $candidateLinkType = $candidateClickStatus === 'direct' ? 'product' : 'affiliate';

        $merged = [
            'code' => $clickCode,
            'url' => trim((string) ($current['url'] ?? '')),
            'merchant' => trim((string) ($current['merchant'] ?? '')),
            'merchant_slug' => trim((string) ($current['merchant_slug'] ?? '')),
            'product_slug' => trim((string) ($current['product_slug'] ?? '')),
            'link_type' => trim((string) ($current['link_type'] ?? '')),
            'source' => trim((string) ($current['source'] ?? '')),
        ];

        if ($merged['url'] === '' && $candidateClickUrl !== '') {
            $merged['url'] = $candidateClickUrl;
        }
        if ($merged['merchant'] === '' && $candidateMerchant !== '') {
            $merged['merchant'] = $candidateMerchant;
        }
        if ($merged['merchant_slug'] === '' && $candidateMerchantSlug !== '') {
            $merged['merchant_slug'] = $candidateMerchantSlug;
        }
        if ($merged['product_slug'] === '' && $candidateProductSlug !== '') {
            $merged['product_slug'] = $candidateProductSlug;
        }
        if ($merged['link_type'] === '' && $candidateLinkType !== '') {
            $merged['link_type'] = $candidateLinkType;
        }
        if ($merged['source'] === '') {
            $merged['source'] = 'candidate-approved';
        }

        return $merged;
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

if (!function_exists('interessa_admin_detect_local_image_extension')) {
    function interessa_admin_detect_local_image_extension(string $path, string $originalName = '', string $default = 'webp'): string {
        $default = strtolower(trim($default)) ?: 'webp';
        $originalName = strtolower(trim($originalName));

        if ($originalName !== '') {
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (in_array($ext, ['webp', 'png', 'jpg', 'jpeg'], true)) {
                return $ext === 'jpeg' ? 'jpg' : $ext;
            }
        }

        if (!is_file($path)) {
            return $default;
        }

        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = strtolower((string) @finfo_file($finfo, $path));
                @finfo_close($finfo);
            }
        }

        $map = [
            'image/webp' => 'webp',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
        ];
        if ($mime !== '' && isset($map[$mime])) {
            return $map[$mime];
        }

        $prefix = @file_get_contents($path, false, null, 0, 16);
        if (is_string($prefix) && $prefix !== '') {
            if (substr($prefix, 0, 4) === 'RIFF' && substr($prefix, 8, 4) === 'WEBP') {
                return 'webp';
            }
            if (substr($prefix, 0, 8) === "\x89PNG\x0D\x0A\x1A\x0A") {
                return 'png';
            }
            if (substr($prefix, 0, 3) === "\xFF\xD8\xFF") {
                return 'jpg';
            }
        }

        return $default;
    }
}

if (!function_exists('interessa_admin_convert_local_image_to_webp')) {
    function interessa_admin_convert_local_image_to_webp(string $sourcePath, string $sourceExt, string $targetPath): void {
        $sourceExt = strtolower(trim($sourceExt));

        if ($sourceExt === 'webp') {
            if (!@copy($sourcePath, $targetPath)) {
                throw new RuntimeException('Nepodarilo sa ulozit WebP subor.');
            }
            return;
        }

        if (class_exists('Imagick')) {
            try {
                $image = new Imagick($sourcePath);
                $image->setImageFormat('webp');
                $image->setImageCompressionQuality(90);
                if (!$image->writeImage($targetPath)) {
                    throw new RuntimeException('Imagick nedokazal zapisat WebP.');
                }
                $image->clear();
                $image->destroy();
                return;
            } catch (Throwable $e) {
                if (is_file($targetPath)) {
                    @unlink($targetPath);
                }
            }
        }

        $createFn = match ($sourceExt) {
            'jpg', 'jpeg' => 'imagecreatefromjpeg',
            'png' => 'imagecreatefrompng',
            default => '',
        };

        if ($createFn !== '' && function_exists($createFn) && function_exists('imagewebp')) {
            $image = @$createFn($sourcePath);
            if (!$image) {
                throw new RuntimeException('Nepodarilo sa spracovat nahraty obrazok.');
            }

            if ($sourceExt === 'png') {
                @imagepalettetotruecolor($image);
                @imagealphablending($image, true);
                @imagesavealpha($image, true);
            }

            $written = @imagewebp($image, $targetPath, 90);
            @imagedestroy($image);
            if (!$written) {
                throw new RuntimeException('Nepodarilo sa ulozit WebP verziu obrazka.');
            }
            return;
        }

        throw new RuntimeException('Server nedokaze previest PNG/JPG na WebP. Nahraj priamo WebP alebo dopln GD/Imagick.');
    }
}

if (!function_exists('interessa_admin_store_article_hero_from_local_path')) {
    function interessa_admin_store_article_hero_from_local_path(string $slug, string $sourcePath, string $originalName = '', bool $isUploaded = false): string {
        $slug = function_exists('canonical_article_slug') ? canonical_article_slug($slug) : trim($slug);
        if ($slug === '') {
            throw new RuntimeException('Chyba slug clanku.');
        }
        if (!is_file($sourcePath)) {
            throw new RuntimeException('Hero obrazok sa nepodarilo nacitat.');
        }

        $sourceExt = interessa_admin_detect_local_image_extension($sourcePath, $originalName, 'webp');
        if (!in_array($sourceExt, ['webp', 'png', 'jpg'], true)) {
            throw new RuntimeException('Hero obrazok musi byt vo formate JPG, PNG alebo WebP.');
        }

        $target = interessa_admin_article_hero_path($slug, 'webp');
        $targetBase = preg_replace('~\.webp$~i', '', $target) ?: $target;
        $targetTemp = $target . '.tmp';
        interessa_admin_ensure_dir(dirname($target));

        if ($sourceExt === 'webp') {
            $stored = $isUploaded && is_uploaded_file($sourcePath)
                ? @move_uploaded_file($sourcePath, $targetTemp)
                : @copy($sourcePath, $targetTemp);
            if (!$stored) {
                throw new RuntimeException('Nepodarilo sa ulozit hero obrazok.');
            }
        } else {
            interessa_admin_convert_local_image_to_webp($sourcePath, $sourceExt, $targetTemp);
        }

        interessa_admin_delete_asset_variants($targetBase);
        if (!@rename($targetTemp, $target)) {
            @unlink($targetTemp);
            throw new RuntimeException('Nepodarilo sa finalne ulozit hero obrazok.');
        }

        return interessa_admin_article_hero_asset($slug, 'webp');
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

        return interessa_admin_store_article_hero_from_local_path(
            $slug,
            $tmp,
            (string) ($file['name'] ?? ''),
            true
        );
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

if (!function_exists('interessa_admin_is_localhost_url')) {
    function interessa_admin_is_localhost_url(string $url): bool {
        $host = strtolower(trim((string) parse_url($url, PHP_URL_HOST)));
        if ($host === '') {
            return false;
        }

        return in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
    }
}

if (!function_exists('interessa_admin_is_placeholder_image_url')) {
    function interessa_admin_is_placeholder_image_url(string $url): bool {
        $path = strtolower(trim((string) parse_url($url, PHP_URL_PATH)));
        if ($path === '') {
            return false;
        }

        if (str_contains($path, '/assets/img/og-default.')) {
            return true;
        }

        $baseName = basename($path);
        return in_array($baseName, ['og-default.jpg', 'og-default.jpeg', 'og-default.png', 'og-default.webp'], true);
    }
}

if (!function_exists('interessa_admin_valid_remote_shop_image_url')) {
    function interessa_admin_valid_remote_shop_image_url(string $url): string {
        $url = trim($url);
        if ($url === '' || !preg_match('~^https?://~i', $url)) {
            return '';
        }

        if (interessa_admin_is_localhost_url($url)) {
            return '';
        }

        if (interessa_admin_is_placeholder_image_url($url)) {
            return '';
        }

        return $url;
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
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_USERAGENT => 'Mozilla/5.0',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
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
        $remoteUrl = interessa_admin_valid_remote_shop_image_url($remoteUrl);
        if ($productSlug === '') {
            throw new RuntimeException('Chyba slug produktu.');
        }
        if ($remoteUrl === '') {
            throw new RuntimeException('Produkt nema pouzitelny remote obrazok z e-shopu.');
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
        $remoteUrl = interessa_admin_valid_remote_shop_image_url((string) ($normalized['image_remote_src'] ?? ''));
        $enriched = false;

        if ($remoteUrl === '' && trim((string) ($normalized['fallback_url'] ?? '')) !== '') {
            $result = interessa_admin_enrich_product_record_from_source($slug);
            $enriched = !empty($result['saved']);
            $product = interessa_product($slug);
            $normalized = is_array($product) ? interessa_normalize_product($product) : $normalized;
            $remoteUrl = interessa_admin_valid_remote_shop_image_url((string) ($normalized['image_remote_src'] ?? ''));
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
            . '--connect-timeout 10 --max-time 20 '
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

        $host = strtolower(trim((string) parse_url($url, PHP_URL_HOST)));
        if (in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            return false;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH));
        if ($path === '' || $path === '/') {
            return false;
        }

        $pathLower = strtolower($path);
        if ($pathLower === '/admin' || str_starts_with($pathLower, '/admin/') || $pathLower === '/dognet-helper' || str_starts_with($pathLower, '/dognet-helper/')) {
            return false;
        }

        $segments = array_values(array_filter(array_map(
            static fn($segment): string => strtolower(trim((string) $segment)),
            explode('/', trim($path, '/'))
        )));
        if ($segments === []) {
            return false;
        }

        $genericSegments = [
            'shop',
            'eshop',
            'produkty',
            'produkt',
            'products',
            'product',
            'kategoria',
            'kategorie',
            'category',
            'categories',
            'catalog',
            'katalog',
            'brand',
            'brands',
            'znacky',
            'vyhladavanie',
            'search',
            'sale',
            'akcie',
            'novinky',
        ];

        if (count($segments) === 1 && in_array($segments[0], $genericSegments, true)) {
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
            'image_remote_src' => interessa_admin_valid_remote_shop_image_url(interessa_admin_url_join($url, $image)),
            'source_url' => $url,
        ];
    }
}

if (!function_exists('interessa_admin_guess_merchant_from_url')) {
    function interessa_admin_guess_merchant_from_url(string $url): array {
        if (function_exists('aff_resolve_merchant_meta')) {
            $resolved = aff_resolve_merchant_meta('', '', $url);
            if (is_array($resolved)) {
                return [
                    'merchant' => trim((string) ($resolved['name'] ?? '')),
                    'merchant_slug' => trim((string) ($resolved['merchant_slug'] ?? '')),
                ];
            }
        }

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
        }
        $payload['affiliate_code'] = $affiliateCode;

        $payload['fallback_url'] = $finalUrl;

        $affiliateTarget = function_exists('aff_resolve_click_target')
            ? aff_resolve_click_target([
                'affiliate_code' => $affiliateCode,
                'product_slug' => $slug,
                'merchant' => (string) ($payload['merchant'] ?? ''),
                'merchant_slug' => (string) ($payload['merchant_slug'] ?? ''),
                'fallback_url' => $finalUrl,
                'product_url' => $finalUrl,
                'prefer_registry' => true,
            ])
            : [];

        $resolvedAffiliateUrl = trim((string) ($affiliateTarget['affiliate_url'] ?? ''));
        $resolvedAffiliateCode = trim((string) ($affiliateTarget['code'] ?? ''));
        if ($resolvedAffiliateCode !== '') {
            $affiliateCode = $resolvedAffiliateCode;
            $payload['affiliate_code'] = $resolvedAffiliateCode;
        }

        $affiliateRecordUrl = $resolvedAffiliateUrl !== '' ? $resolvedAffiliateUrl : $inputUrl;
        $affiliateRecordType = $resolvedAffiliateUrl !== '' ? 'affiliate' : $linkType;
        $affiliateRecordSource = $resolvedAffiliateUrl !== '' ? 'admin-quick-link-auto' : 'admin-quick-link';

        interessa_admin_save_affiliate_record($affiliateCode, [
            'url' => $affiliateRecordUrl,
            'merchant' => (string) ($payload['merchant'] ?? ''),
            'merchant_slug' => (string) ($payload['merchant_slug'] ?? ''),
            'product_slug' => $slug,
            'link_type' => $affiliateRecordType,
            'source' => $affiliateRecordSource,
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
