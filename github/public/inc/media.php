<?php
declare(strict_types=1);

if (!function_exists('interessa_media_registry')) {
    function interessa_media_registry(): array {
        static $registry = null;
        if (is_array($registry)) {
            return $registry;
        }

        $base = dirname(__DIR__) . '/content/media';
        $registry = [
            'articles' => is_file($base . '/articles.php') ? (include $base . '/articles.php') : [],
            'categories' => is_file($base . '/categories.php') ? (include $base . '/categories.php') : [],
        ];

        return $registry;
    }
}

if (!function_exists('interessa_image_placeholder_asset')) {
    function interessa_image_placeholder_asset(string $kind = 'article'): string {
        $kind = strtolower(trim($kind));
        return match ($kind) {
            'brand' => 'img/brand/og-default.svg',
            'category' => 'img/placeholders/category-16x9.svg',
            'product' => 'img/placeholders/product-1x1.svg',
            default => 'img/placeholders/article-16x9.svg',
        };
    }
}

if (!function_exists('interessa_asset_public_url')) {
    function interessa_asset_public_url(string $assetPath): string {
        $assetPath = ltrim($assetPath, '/');
        if (str_starts_with($assetPath, 'assets/')) {
            $assetPath = substr($assetPath, 7);
        }

        return asset($assetPath);
    }
}

if (!function_exists('interessa_asset_file_path')) {
    function interessa_asset_file_path(string $assetPath): ?string {
        $assetPath = ltrim($assetPath, '/');
        if (str_starts_with($assetPath, 'assets/')) {
            $assetPath = substr($assetPath, 7);
        }

        $fullPath = dirname(__DIR__) . '/assets/' . $assetPath;
        return is_file($fullPath) ? $fullPath : null;
    }
}

if (!function_exists('interessa_image_size_from_asset')) {
    function interessa_image_size_from_asset(string $assetPath): array {
        $file = interessa_asset_file_path($assetPath);
        if ($file === null) {
            return ['width' => null, 'height' => null];
        }

        $size = @getimagesize($file);
        if (!is_array($size)) {
            return ['width' => null, 'height' => null];
        }

        return [
            'width' => isset($size[0]) ? (int) $size[0] : null,
            'height' => isset($size[1]) ? (int) $size[1] : null,
        ];
    }
}

if (!function_exists('interessa_collect_asset_candidates')) {
    function interessa_collect_asset_candidates(array $bases): array {
        $variants = [];
        $exts = ['webp', 'jpg', 'jpeg', 'png', 'svg'];
        $root = dirname(__DIR__) . '/assets/';

        foreach ($bases as $base) {
            $base = trim((string) $base, '/');
            if ($base === '') {
                continue;
            }

            if (preg_match('~\.(webp|jpe?g|png|svg)$~i', $base)) {
                $exact = $root . $base;
                if (is_file($exact)) {
                    $size = interessa_image_size_from_asset($base);
                    $variants[] = [
                        'asset' => $base,
                        'width' => $size['width'],
                        'height' => $size['height'],
                    ];
                }
                continue;
            }

            foreach ($exts as $ext) {
                $exact = $root . $base . '.' . $ext;
                if (is_file($exact)) {
                    $size = interessa_image_size_from_asset($base . '.' . $ext);
                    $variants[] = [
                        'asset' => $base . '.' . $ext,
                        'width' => $size['width'],
                        'height' => $size['height'],
                    ];
                }

                $baseName = basename($base);
                foreach (glob($root . $base . '-*.' . $ext) ?: [] as $file) {
                    $fileName = basename($file);
                    if (!preg_match('~^' . preg_quote($baseName, '~') . '-(\\d+)\\.' . preg_quote($ext, '~') . '$~', $fileName, $match)) {
                        continue;
                    }

                    $relative = str_replace('\\', '/', substr($file, strlen($root)));
                    $size = interessa_image_size_from_asset($relative);
                    $variants[] = [
                        'asset' => $relative,
                        'width' => (int) $match[1],
                        'height' => $size['height'],
                    ];
                }
        }
            }

        usort($variants, static function (array $left, array $right): int {
            return (int) ($left['width'] ?? 0) <=> (int) ($right['width'] ?? 0);
        });

        $unique = [];
        foreach ($variants as $variant) {
            $unique[$variant['asset']] = $variant;
        }

        return array_values($unique);
    }
}

if (!function_exists('interessa_build_image_meta')) {
    function interessa_build_image_meta(array $variants, array $options, string $kind, bool $allowFallback = true): ?array {
        if ($variants === [] && !$allowFallback) {
            return null;
        }

        if ($variants === []) {
            $fallbackAsset = interessa_image_placeholder_asset($kind);
            $fallbackSize = interessa_image_size_from_asset($fallbackAsset);
            $variants = [[
                'asset' => $fallbackAsset,
                'width' => $fallbackSize['width'],
                'height' => $fallbackSize['height'],
            ]];
        }

        $primary = end($variants);
        if ($primary === false) {
            return null;
        }

        $srcset = [];
        foreach ($variants as $variant) {
            $width = (int) ($variant['width'] ?? 0);
            if ($width <= 0) {
                continue;
            }

            $srcset[] = interessa_asset_public_url((string) $variant['asset']) . ' ' . $width . 'w';
        }

        $width = (int) ($options['width'] ?? ($primary['width'] ?? 0));
        $height = (int) ($options['height'] ?? ($primary['height'] ?? 0));

        return [
            'src' => interessa_asset_public_url((string) $primary['asset']),
            'alt' => trim((string) ($options['alt'] ?? '')),
            'width' => $width > 0 ? $width : null,
            'height' => $height > 0 ? $height : null,
            'srcset' => $srcset !== [] ? implode(', ', $srcset) : null,
            'sizes' => trim((string) ($options['sizes'] ?? '')) ?: null,
            'loading' => trim((string) ($options['loading'] ?? 'lazy')) ?: 'lazy',
            'decoding' => trim((string) ($options['decoding'] ?? 'async')) ?: 'async',
            'fetchpriority' => trim((string) ($options['fetchpriority'] ?? '')) ?: null,
        ];
    }
}

if (!function_exists('interessa_remote_image_meta')) {
    function interessa_remote_image_meta(array $config, string $fallbackAlt, string $sizes, string $loading = 'lazy'): ?array {
        $src = trim((string) ($config['remote_src'] ?? $config['src'] ?? ''));
        if ($src === '' || !preg_match('~^https?://~i', $src)) {
            return null;
        }

        $width = (int) ($config['width'] ?? 0);
        $height = (int) ($config['height'] ?? 0);

        return [
            'src' => $src,
            'alt' => trim((string) ($config['alt'] ?? $fallbackAlt)),
            'width' => $width > 0 ? $width : null,
            'height' => $height > 0 ? $height : null,
            'srcset' => null,
            'sizes' => trim((string) ($config['sizes'] ?? $sizes)) ?: null,
            'loading' => trim((string) ($config['loading'] ?? $loading)) ?: $loading,
            'decoding' => trim((string) ($config['decoding'] ?? 'async')) ?: 'async',
            'fetchpriority' => trim((string) ($config['fetchpriority'] ?? '')) ?: null,
        ];
    }
}

if (!function_exists('interessa_article_image_meta')) {
    function interessa_article_image_meta(string $slug, string $variant = 'thumb', bool $allowFallback = true): ?array {
        $registry = interessa_media_registry()['articles'][$slug] ?? [];
        $entry = is_array($registry[$variant] ?? null) ? $registry[$variant] : [];
        $meta = article_meta($slug);
        $alt = trim((string) ($entry['alt'] ?? ''));
        if ($alt === '') {
            $alt = $meta['title'] !== '' ? $meta['title'] : humanize_slug($slug);
        }

        $variants = [];
        if (isset($entry['asset'])) {
            $asset = trim((string) $entry['asset']);
            if ($asset !== '') {
                $variants = interessa_collect_asset_candidates([ltrim($asset, '/')]);
            }
        }

        if ($variants === []) {
            $variants = interessa_collect_asset_candidates([
                'img/articles/heroes/' . $slug,
                'img/articles/' . $slug . '/hero',
                'img/articles/' . $slug . '/' . $variant,
                'img/articles/' . $slug,
                'img/articles/' . $slug . '-hero',
            ]);
        }

        if ($variants === [] && !empty($meta['category'])) {
            return interessa_category_image_meta((string) $meta['category'], 'hero', $allowFallback);
        }

        return interessa_build_image_meta($variants, [
            'alt' => $alt,
            'width' => $entry['width'] ?? 1200,
            'height' => $entry['height'] ?? 800,
            'sizes' => $entry['sizes'] ?? null,
            'loading' => $variant === 'hero' ? 'eager' : 'lazy',
            'fetchpriority' => $variant === 'hero' ? 'high' : null,
        ], 'article', $allowFallback);
    }
}

if (!function_exists('interessa_category_image_meta')) {
    function interessa_category_image_meta(string $slug, string $variant = 'hero', bool $allowFallback = true): ?array {
        $slug = normalize_category_slug($slug);
        $registry = interessa_media_registry()['categories'][$slug] ?? [];
        $entry = is_array($registry[$variant] ?? null) ? $registry[$variant] : [];
        $meta = category_meta($slug);
        $title = $meta['title'] ?? humanize_slug($slug);
        $alt = trim((string) ($entry['alt'] ?? ''));
        if ($alt === '') {
            $alt = $title;
        }

        $variants = [];
        if (isset($entry['asset'])) {
            $asset = trim((string) $entry['asset']);
            if ($asset !== '') {
                $variants = interessa_collect_asset_candidates([ltrim($asset, '/')]);
            }
        }

        if ($variants === []) {
            $variants = interessa_collect_asset_candidates([
                'img/categories/' . $slug . '/' . $variant,
                'img/categories/' . $slug,
                'img/icons/' . $slug,
            ]);
        }

        return interessa_build_image_meta($variants, [
            'alt' => $alt,
            'sizes' => $entry['sizes'] ?? null,
        ], 'category', $allowFallback);
    }
}

if (!function_exists('interessa_product_image_meta')) {
    function interessa_product_image_meta(string $slug, array $config = [], bool $allowFallback = true): ?array {
        $slug = trim($slug);
        $alt = trim((string) ($config['alt'] ?? humanize_slug($slug)));
        $sizes = (string) ($config['sizes'] ?? '(min-width: 1100px) 280px, 50vw');
        $variants = [];

        if (isset($config['asset'])) {
            $asset = trim((string) $config['asset']);
            if ($asset !== '') {
                $variants = interessa_collect_asset_candidates([ltrim($asset, '/')]);
            }
        }

        if ($variants === []) {
            $variants = interessa_collect_asset_candidates([
                'img/products/' . $slug . '/main',
                'img/products/' . $slug,
            ]);
        }

        if ($variants === []) {
            $remote = interessa_remote_image_meta($config, $alt, $sizes);
            if ($remote !== null) {
                return $remote;
            }
        }

        return interessa_build_image_meta($variants, [
            'alt' => $alt,
            'sizes' => $sizes,
            'width' => $config['width'] ?? null,
            'height' => $config['height'] ?? null,
        ], 'product', $allowFallback);
    }
}

if (!function_exists('interessa_brand_image_meta')) {
    function interessa_brand_image_meta(string $name): ?array {
        $variants = interessa_collect_asset_candidates(['img/brand/' . trim($name, '/')]);
        return interessa_build_image_meta($variants, [
            'alt' => 'Interesa',
            'loading' => 'eager',
            'decoding' => 'sync',
        ], 'brand', false);
    }
}

if (!function_exists('interessa_render_image')) {
    function interessa_render_image(?array $image, array $attrs = []): string {
        if ($image === null || trim((string) ($image['src'] ?? '')) === '') {
            return '';
        }

        $attributes = [
            'src' => (string) $image['src'],
            'alt' => (string) ($attrs['alt'] ?? $image['alt'] ?? ''),
            'loading' => (string) ($attrs['loading'] ?? $image['loading'] ?? 'lazy'),
            'decoding' => (string) ($attrs['decoding'] ?? $image['decoding'] ?? 'async'),
        ];

        if (!empty($image['width']) && empty($attrs['width'])) {
            $attributes['width'] = (string) $image['width'];
        }
        if (!empty($image['height']) && empty($attrs['height'])) {
            $attributes['height'] = (string) $image['height'];
        }
        if (!empty($image['srcset'])) {
            $attributes['srcset'] = (string) $image['srcset'];
        }
        if (!empty($image['sizes'])) {
            $attributes['sizes'] = (string) $image['sizes'];
        }
        if (!empty($image['fetchpriority'])) {
            $attributes['fetchpriority'] = (string) $image['fetchpriority'];
        }

        foreach ($attrs as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $attributes[(string) $key] = (string) $value;
        }

        $html = '<img';
        foreach ($attributes as $key => $value) {
            $html .= ' ' . esc((string) $key) . '="' . esc((string) $value) . '"';
        }
        $html .= ' />';

        return $html;
    }
}
