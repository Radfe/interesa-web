<?php
if (!function_exists('interessa_media_clean_text')) {
    function interessa_media_clean_text(string $text): string {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (function_exists('interessa_fix_mojibake')) {
            $text = interessa_fix_mojibake($text);
        }

        return trim($text);
    }
}

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

if (!function_exists('interessa_product_image_target_asset')) {
    function interessa_product_image_target_asset(string $slug, string $merchantSlug = ''): string {
        $slug = trim($slug);
        $merchantSlug = trim($merchantSlug);

        if ($slug === '') {
            return 'img/placeholders/product-1x1.svg';
        }

        if ($merchantSlug !== '') {
            return 'img/products/' . $merchantSlug . '/' . $slug . '/main.webp';
        }

        return 'img/products/' . $slug . '/main.webp';
    }
}

if (!function_exists('interessa_product_image_target_path')) {
    function interessa_product_image_target_path(string $slug, string $merchantSlug = ''): string {
        return dirname(__DIR__) . '/assets/' . interessa_product_image_target_asset($slug, $merchantSlug);
    }
}

if (!function_exists('interessa_product_image_candidate_bases')) {
    function interessa_product_image_candidate_bases(string $slug, string $merchantSlug = ''): array {
        $slug = trim($slug);
        $merchantSlug = trim($merchantSlug);
        if ($slug === '') {
            return [];
        }

        $bases = [];
        $targetAsset = interessa_product_image_target_asset($slug, $merchantSlug);
        $canonicalBase = preg_replace('~\.(webp|jpe?g|png|svg)$~i', '', $targetAsset) ?: $targetAsset;
        $bases[] = ltrim($targetAsset, '/');
        $bases[] = ltrim($canonicalBase, '/');

        if ($merchantSlug !== '') {
            $bases[] = 'img/products/' . $merchantSlug . '/' . $slug . '/main';
            $bases[] = 'img/products/' . $merchantSlug . '/' . $slug;
        }

        $bases[] = 'img/products/' . $slug . '/main';
        $bases[] = 'img/products/' . $slug;

        return array_values(array_unique(array_filter(array_map(static function (string $base): string {
            return trim($base, '/');
        }, $bases))));
    }
}

if (!function_exists('interessa_product_image_local_asset')) {
    function interessa_product_image_local_asset(string $slug, string $merchantSlug = ''): ?string {
        $variants = interessa_collect_asset_candidates(interessa_product_image_candidate_bases($slug, $merchantSlug));
        if ($variants === []) {
            return null;
        }

        $primary = end($variants);
        if ($primary === false) {
            return null;
        }

        return trim((string) ($primary['asset'] ?? '')) ?: null;
    }
}

if (!function_exists('interessa_product_image_local_path')) {
    function interessa_product_image_local_path(string $slug, string $merchantSlug = ''): ?string {
        $asset = interessa_product_image_local_asset($slug, $merchantSlug);
        if ($asset === null) {
            return null;
        }

        return interessa_asset_file_path($asset);
    }
}

if (!function_exists('interessa_product_has_local_image')) {
    function interessa_product_has_local_image(string $slug, string $merchantSlug = ''): bool {
        return interessa_product_image_local_path($slug, $merchantSlug) !== null;
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
                    if (!preg_match('~^' . preg_quote($baseName, '~') . '-(\d+)\.' . preg_quote($ext, '~') . '$~', $fileName, $match)) {
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

        $sourceType = trim((string) ($options['source_type'] ?? 'local')) ?: 'local';
        if ($variants === []) {
            $fallbackAsset = interessa_image_placeholder_asset($kind);
            $fallbackSize = interessa_image_size_from_asset($fallbackAsset);
            $variants = [[
                'asset' => $fallbackAsset,
                'width' => $fallbackSize['width'],
                'height' => $fallbackSize['height'],
            ]];
            $sourceType = 'placeholder';
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
            'alt' => interessa_media_clean_text((string) ($options['alt'] ?? '')),
            'width' => $width > 0 ? $width : null,
            'height' => $height > 0 ? $height : null,
            'asset' => (string) ($primary['asset'] ?? ''),
            'source_type' => $sourceType,
            'srcset' => $srcset !== [] ? implode(', ', $srcset) : null,
            'sizes' => trim((string) ($options['sizes'] ?? '')) ?: null,
            'loading' => trim((string) ($options['loading'] ?? 'lazy')) ?: 'lazy',
            'decoding' => trim((string) ($options['decoding'] ?? 'async')) ?: 'async',
            'fetchpriority' => trim((string) ($options['fetchpriority'] ?? '')) ?: null,
            'kind' => trim((string) ($options['kind'] ?? $kind)) ?: $kind,
            'entity' => trim((string) ($options['entity'] ?? '')) ?: null,
            'variant_name' => trim((string) ($options['variant_name'] ?? '')) ?: null,
            'crop_mode' => trim((string) ($options['crop_mode'] ?? 'cover')) ?: 'cover',
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
            'alt' => interessa_media_clean_text((string) ($config['alt'] ?? $fallbackAlt)),
            'width' => $width > 0 ? $width : null,
            'height' => $height > 0 ? $height : null,
            'asset' => null,
            'source_type' => 'remote',
            'srcset' => null,
            'sizes' => trim((string) ($config['sizes'] ?? $sizes)) ?: null,
            'loading' => trim((string) ($config['loading'] ?? $loading)) ?: $loading,
            'decoding' => trim((string) ($config['decoding'] ?? 'async')) ?: 'async',
            'fetchpriority' => trim((string) ($config['fetchpriority'] ?? '')) ?: null,
            'kind' => trim((string) ($config['kind'] ?? 'article')) ?: 'article',
            'entity' => trim((string) ($config['entity'] ?? '')) ?: null,
            'variant_name' => trim((string) ($config['variant_name'] ?? '')) ?: null,
            'crop_mode' => trim((string) ($config['crop_mode'] ?? 'cover')) ?: 'cover',
        ];
    }
}

if (!function_exists('interessa_article_image_meta')) {
    function interessa_article_image_meta(string $slug, string $variant = 'thumb', bool $allowFallback = true): ?array {
        $canonicalSlug = function_exists('canonical_article_slug') ? canonical_article_slug($slug) : $slug;
        $registry = interessa_media_registry()['articles'][$canonicalSlug] ?? [];
        $entry = is_array($registry[$variant] ?? null) ? $registry[$variant] : [];
        $meta = article_meta($canonicalSlug);
        $override = function_exists('interessa_admin_article_override') ? interessa_admin_article_override($canonicalSlug) : [];
        $alt = interessa_media_clean_text((string) ($entry['alt'] ?? ''));
        if ($alt === '') {
            $alt = interessa_media_clean_text((string) ($override['title'] ?? ''));
        }
        if ($alt === '') {
            $alt = $meta['title'] !== '' ? $meta['title'] : humanize_slug($canonicalSlug);
        }

        $preferredSlug = function_exists('interessa_article_preferred_slug')
            ? interessa_article_preferred_slug($slug)
            : $slug;
        $preferredMeta = article_meta($preferredSlug);
        $preferredTitle = interessa_media_clean_text((string) ($preferredMeta['title'] ?? ''));
        if ($preferredTitle !== '' && ($preferredSlug !== $canonicalSlug || str_contains($preferredTitle, '2026'))) {
            $alt = $preferredTitle;
        }

        $variants = [];
        $overrideAsset = trim((string) ($override['hero_asset'] ?? ''));
        if ($variant === 'hero' && $overrideAsset !== '') {
            $variants = interessa_collect_asset_candidates([ltrim($overrideAsset, '/')]);
        }

        if ($variants === [] && isset($entry['asset'])) {
            $asset = trim((string) $entry['asset']);
            if ($asset !== '') {
                $variants = interessa_collect_asset_candidates([ltrim($asset, '/')]);
            }
        }

        if ($variants === []) {
            $variants = interessa_collect_asset_candidates([
                'img/articles/heroes/' . $canonicalSlug,
                'img/articles/' . $canonicalSlug . '/hero',
                'img/articles/' . $canonicalSlug . '/' . $variant,
                'img/articles/' . $canonicalSlug,
                'img/articles/' . $canonicalSlug . '-hero',
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
            'kind' => 'article',
            'entity' => $canonicalSlug,
            'variant_name' => $variant,
            'crop_mode' => $variant === 'hero' ? 'cover' : 'cover',
        ], 'article', $allowFallback);
    }
}

if (!function_exists('interessa_article_image_state')) {
    function interessa_article_image_state(string $slug, string $variant = 'hero'): array {
        $canonicalSlug = function_exists('canonical_article_slug') ? canonical_article_slug($slug) : $slug;
        $articleMeta = interessa_article_image_meta($canonicalSlug, $variant, false);
        if (is_array($articleMeta)) {
            return [
                'status' => 'article',
                'label' => 'vlastny obrazok clanku',
                'meta' => $articleMeta,
            ];
        }

        $meta = article_meta($canonicalSlug);
        $categorySlug = trim((string) ($meta['category'] ?? ''));
        if ($categorySlug !== '') {
            $fallbackMeta = interessa_category_image_meta($categorySlug, 'hero', true);
            if (is_array($fallbackMeta) && (string) ($fallbackMeta['source_type'] ?? '') === 'category') {
                return [
                    'status' => 'theme-fallback',
                    'label' => 'len fallback temy',
                    'meta' => $fallbackMeta,
                ];
            }
        }

        return [
            'status' => 'missing',
            'label' => 'naozaj chyba',
            'meta' => null,
        ];
    }
}

if (!function_exists('interessa_category_image_meta')) {
    function interessa_category_image_meta(string $slug, string $variant = 'hero', bool $allowFallback = true): ?array {
        $slug = normalize_category_slug($slug);
        $registry = interessa_media_registry()['categories'][$slug] ?? [];
        $entry = is_array($registry[$variant] ?? null) ? $registry[$variant] : [];
        $meta = category_meta($slug);
        $title = $meta['title'] ?? humanize_slug($slug);
        $alt = interessa_media_clean_text((string) ($entry['alt'] ?? ''));
        if ($alt === '') {
            $alt = $title;
        }

        $variants = [];
        $canonicalVariants = interessa_collect_asset_candidates([
            'img/categories/' . $slug . '/' . $variant,
            'img/categories/' . $slug,
        ]);
        if ($canonicalVariants !== []) {
            $variants = $canonicalVariants;
        } elseif (isset($entry['asset'])) {
            $asset = trim((string) $entry['asset']);
            if ($asset !== '') {
                $variants = interessa_collect_asset_candidates([ltrim($asset, '/')]);
            }
        }

        if ($variants === []) {
            $variants = interessa_collect_asset_candidates([
                'img/icons/' . $slug,
            ]);
        }

        return interessa_build_image_meta($variants, [
            'alt' => $alt,
            'sizes' => $entry['sizes'] ?? null,
            'kind' => 'category',
            'entity' => $slug,
            'variant_name' => $variant,
            'crop_mode' => 'cover',
        ], 'category', $allowFallback);
    }
}

if (!function_exists('interessa_category_local_asset')) {
    function interessa_category_local_asset(string $slug, string $variant = 'hero'): ?string {
        $slug = normalize_category_slug($slug);
        $variant = trim($variant);
        if ($slug === '') {
            return null;
        }
        if ($variant === '') {
            $variant = 'hero';
        }

        $variants = interessa_collect_asset_candidates([
            'img/categories/' . $slug . '/' . $variant,
            'img/categories/' . $slug,
        ]);
        if ($variants === []) {
            return null;
        }

        $primary = end($variants);
        if ($primary === false) {
            return null;
        }

        return trim((string) ($primary['asset'] ?? '')) ?: null;
    }
}

if (!function_exists('interessa_product_image_meta')) {
    function interessa_product_image_meta(string $slug, array $config = [], bool $allowFallback = true): ?array {
        $slug = trim($slug);
        $alt = interessa_media_clean_text((string) ($config['alt'] ?? humanize_slug($slug)));
        $merchantSlug = trim((string) ($config['merchant_slug'] ?? ''));
        $sizes = (string) ($config['sizes'] ?? '(min-width: 1100px) 280px, 50vw');
        $targetAsset = trim((string) ($config['mirror_asset'] ?? interessa_product_image_target_asset($slug, $merchantSlug)));
        $variants = [];

        if (isset($config['asset'])) {
            $asset = trim((string) $config['asset']);
            if ($asset !== '') {
                $variants = interessa_collect_asset_candidates([ltrim($asset, '/')]);
            }
        }

        if ($variants === []) {
            $variants = interessa_collect_asset_candidates(interessa_product_image_candidate_bases($slug, $merchantSlug));
        }

        if ($variants === []) {
            $remote = interessa_remote_image_meta($config, $alt, $sizes);
            if ($remote !== null) {
                $remote['target_asset'] = $targetAsset;
                return $remote;
            }
        }

        $image = interessa_build_image_meta($variants, [
            'alt' => $alt,
            'sizes' => $sizes,
            'loading' => (string) ($config['loading'] ?? 'lazy'),
            'kind' => 'product',
            'entity' => $slug,
            'variant_name' => trim((string) ($config['variant_name'] ?? 'main')) ?: 'main',
            'crop_mode' => 'contain',
        ], 'product', $allowFallback);

        if ($image !== null) {
            $image['target_asset'] = $targetAsset;
        }

        return $image;
    }
}

if (!function_exists('interessa_brand_image_meta')) {
    function interessa_brand_image_meta(string $name = 'logo-full', bool $allowFallback = true): ?array {
        $name = trim($name);
        if ($name === '') {
            $name = 'logo-full';
        }

        if ($name === 'logo-full') {
            $variants = interessa_collect_asset_candidates([
                'img/brand/' . $name,
            ]);
            if ($variants === []) {
                $variants = interessa_collect_asset_candidates([
                    'img/brand/logo-full-web',
                ]);
            }
        } else {
            $variants = interessa_collect_asset_candidates([
                'img/brand/' . $name,
            ]);
        }

        return interessa_build_image_meta($variants, [
            'alt' => interessa_media_clean_text('Interesa'),
            'loading' => 'eager',
            'fetchpriority' => 'high',
            'source_type' => 'local',
            'kind' => 'brand',
            'entity' => $name,
            'variant_name' => $name,
            'crop_mode' => 'contain',
        ], 'brand', $allowFallback);
    }
}

if (!function_exists('interessa_render_image')) {
    function interessa_render_image(?array $image, array $attrs = []): string {
        if (!is_array($image) || trim((string) ($image['src'] ?? '')) === '') {
            return '';
        }

        $attrs = array_merge([
            'src' => $image['src'],
            'alt' => $image['alt'] ?? '',
            'loading' => $image['loading'] ?? 'lazy',
            'decoding' => $image['decoding'] ?? 'async',
        ], $attrs);

        foreach (['width', 'height', 'srcset', 'sizes', 'fetchpriority'] as $key) {
            if (!isset($attrs[$key]) && !empty($image[$key])) {
                $attrs[$key] = $image[$key];
            }
        }

        $existingClass = trim((string) ($attrs['class'] ?? ''));
        $autoClasses = [];
        $kind = trim((string) ($image['kind'] ?? ''));
        $variantName = trim((string) ($image['variant_name'] ?? ''));
        $sourceType = trim((string) ($image['source_type'] ?? ''));
        $cropMode = trim((string) ($image['crop_mode'] ?? ''));
        if ($kind !== '') {
            $autoClasses[] = 'media-image';
            $autoClasses[] = 'media-image--' . preg_replace('~[^a-z0-9\-]+~i', '-', strtolower($kind));
        }
        if ($variantName !== '') {
            $autoClasses[] = 'media-image--' . preg_replace('~[^a-z0-9\-]+~i', '-', strtolower($variantName));
        }
        if ($sourceType !== '') {
            $autoClasses[] = 'is-source-' . preg_replace('~[^a-z0-9\-]+~i', '-', strtolower($sourceType));
        }
        if ($cropMode !== '') {
            $autoClasses[] = 'is-crop-' . preg_replace('~[^a-z0-9\-]+~i', '-', strtolower($cropMode));
        }
        $attrs['class'] = trim($existingClass . ' ' . implode(' ', array_filter($autoClasses)));
        if (!isset($attrs['data-image-kind']) && $kind !== '') {
            $attrs['data-image-kind'] = $kind;
        }
        if (!isset($attrs['data-image-variant']) && $variantName !== '') {
            $attrs['data-image-variant'] = $variantName;
        }
        if (!isset($attrs['data-image-source']) && $sourceType !== '') {
            $attrs['data-image-source'] = $sourceType;
        }
        if (!isset($attrs['data-image-entity']) && !empty($image['entity'])) {
            $attrs['data-image-entity'] = (string) $image['entity'];
        }

        $htmlAttrs = [];
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false || $value === '') {
                continue;
            }
            if ($value === true) {
                $htmlAttrs[] = $key;
                continue;
            }

            $htmlAttrs[] = $key . '="' . esc((string) $value) . '"';
        }

        return '<img ' . implode(' ', $htmlAttrs) . '>';
    }
}
