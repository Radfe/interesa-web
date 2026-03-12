<?php
declare(strict_types=1);

require_once __DIR__ . '/affiliates.php';
require_once __DIR__ . '/media.php';

if (!function_exists('interessa_product_catalog')) {
    function interessa_product_catalog(): array {
        static $catalog = null;
        if (is_array($catalog)) {
            return $catalog;
        }

        $baseDir = dirname(__DIR__) . '/content/products';
        $files = [
            $baseDir . '/catalog.php',
            $baseDir . '/catalog_overrides.php',
        ];

        $catalog = [];
        foreach ($files as $file) {
            $data = is_file($file) ? include $file : [];
            if (!is_array($data)) {
                continue;
            }

            $catalog = array_replace($catalog, $data);
        }

        ksort($catalog);
        return $catalog;
    }
}

if (!function_exists('interessa_product_visual_score')) {
    function interessa_product_visual_score(array $product): int {
        $imageConfig = is_array($product['image'] ?? null) ? $product['image'] : [];
        $slug = trim((string) ($product['slug'] ?? ''));
        $merchantSlug = trim((string) ($product['merchant_slug'] ?? ''));
        $score = 0;

        $asset = trim((string) ($imageConfig['asset'] ?? ''));
        if ($asset !== '' && interessa_asset_file_path($asset) !== null) {
            $score += 40;
        }

        $mirrorPath = $slug !== '' ? interessa_product_image_target_path($slug, $merchantSlug) : '';
        if ($mirrorPath !== '' && is_file($mirrorPath)) {
            $score += 40;
        }

        $remoteSrc = trim((string) ($imageConfig['remote_src'] ?? $imageConfig['src'] ?? ''));
        if ($remoteSrc !== '') {
            $score += 30;
        }

        if (trim((string) ($product['image_source'] ?? '')) === 'merchant-feed') {
            $score += 15;
        }

        if (trim((string) ($product['feed_source'] ?? '')) !== '') {
            $score += 5;
        }

        return $score;
    }
}

if (!function_exists('interessa_product_by_affiliate_code')) {
    function interessa_product_by_affiliate_code(string $code): ?array {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $matches = [];
        foreach (interessa_product_catalog() as $product) {
            if (trim((string) ($product['affiliate_code'] ?? '')) === $code) {
                $matches[] = $product;
            }
        }

        if ($matches === []) {
            return null;
        }

        usort($matches, static function (array $left, array $right): int {
            return interessa_product_visual_score($right) <=> interessa_product_visual_score($left);
        });

        return $matches[0] ?? null;
    }
}

if (!function_exists('interessa_product')) {
    function interessa_product(string $slug): ?array {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $catalog = interessa_product_catalog();
        return $catalog[$slug] ?? null;
    }
}

if (!function_exists('interessa_normalize_product')) {
    function interessa_normalize_product(array $product): array {
        $slug = trim((string) ($product['slug'] ?? ''));
        $merchantSlug = trim((string) ($product['merchant_slug'] ?? ''));
        $affiliateCode = trim((string) ($product['affiliate_code'] ?? ''));
        $fallbackUrl = trim((string) ($product['fallback_url'] ?? ''));
        $imageConfig = is_array($product['image'] ?? null) ? $product['image'] : [];
        $imageConfig['merchant_slug'] = $merchantSlug !== '' ? $merchantSlug : (string) ($imageConfig['merchant_slug'] ?? '');
        $image = $slug !== '' ? interessa_product_image_meta($slug, $imageConfig, true) : null;
        $imageRemoteSrc = trim((string) ($imageConfig['remote_src'] ?? $imageConfig['src'] ?? ''));
        $imageTargetAsset = $slug !== ''
            ? trim((string) (($image['target_asset'] ?? '') ?: interessa_product_image_target_asset($slug, $merchantSlug)))
            : '';
        $imageTargetPath = $imageTargetAsset !== '' ? interessa_product_image_target_path($slug, $merchantSlug) : '';

        return [
            'slug' => $slug,
            'name' => trim((string) ($product['name'] ?? humanize_slug($slug))),
            'merchant' => trim((string) ($product['merchant'] ?? '')),
            'merchant_slug' => $merchantSlug,
            'category' => normalize_category_slug((string) ($product['category'] ?? '')),
            'affiliate_code' => $affiliateCode,
            'fallback_url' => $fallbackUrl,
            'summary' => trim((string) ($product['summary'] ?? '')),
            'pros' => is_array($product['pros'] ?? null) ? array_values($product['pros']) : [],
            'cons' => is_array($product['cons'] ?? null) ? array_values($product['cons']) : [],
            'image' => $image,
            'image_source' => trim((string) ($product['image_source'] ?? '')),
            'image_mode' => trim((string) ($image['source_type'] ?? 'placeholder')),
            'image_remote_src' => $imageRemoteSrc,
            'image_target_asset' => $imageTargetAsset,
            'image_target_path' => $imageTargetPath,
            'feed_source' => trim((string) ($product['feed_source'] ?? '')),
            'merchant_product_id' => $product['merchant_product_id'] ?? null,
        ];
    }
}

if (!function_exists('interessa_resolve_product_reference')) {
    function interessa_resolve_product_reference(array $row): array {
        $product = null;
        $productSlug = trim((string) ($row['product_slug'] ?? ''));
        $code = trim((string) ($row['code'] ?? $row['affiliate_code'] ?? ''));

        if ($productSlug !== '') {
            $product = interessa_product($productSlug);
        }
        if ($product === null && $code !== '') {
            $product = interessa_product_by_affiliate_code($code);
        }
        if ($product === null && $code !== '') {
            $record = $code !== '' ? aff_record($code) : null;
            $linkedSlug = trim((string) ($record['product_slug'] ?? ''));
            if ($linkedSlug !== '') {
                $product = interessa_product($linkedSlug);
            }
        }

        if ($product === null) {
            if (empty($row['img']) && !empty($row['slug'])) {
                $image = interessa_product_image_meta(
                    (string) $row['slug'],
                    ['merchant_slug' => (string) ($row['merchant_slug'] ?? '')],
                    true
                );
                if ($image !== null) {
                    $row['img'] = $image['src'];
                    $row['_image'] = $image;
                    $row['image_mode'] = trim((string) ($image['source_type'] ?? 'placeholder'));
                    $row['image_target_asset'] = trim((string) ($image['target_asset'] ?? ''));
                }
            }
            return $row;
        }

        $normalized = interessa_normalize_product($product);
        $catalogName = trim((string) ($normalized['name'] ?? ''));
        $catalogSummary = trim((string) ($normalized['summary'] ?? ''));
        $merged = array_replace($normalized, $row);
        $merged['code'] = trim((string) ($merged['code'] ?? $normalized['affiliate_code'] ?? ''));
        $merged['url'] = trim((string) ($merged['url'] ?? $normalized['fallback_url'] ?? ''));
        $merged['subtitle'] = trim((string) ($merged['subtitle'] ?? $normalized['summary'] ?? ''));
        $merged['merchant'] = trim((string) ($merged['merchant'] ?? $normalized['merchant'] ?? ''));
        $merged['merchant_slug'] = trim((string) ($merged['merchant_slug'] ?? $normalized['merchant_slug'] ?? ''));
        $merged['slug'] = trim((string) ($merged['slug'] ?? $normalized['slug'] ?? ''));
        $merged['product_name'] = $catalogName !== '' ? $catalogName : trim((string) ($merged['name'] ?? ''));
        $merged['product_summary'] = $catalogSummary;
        $merged['pros'] = is_array($merged['pros'] ?? null) ? array_values($merged['pros']) : $normalized['pros'];
        $merged['cons'] = is_array($merged['cons'] ?? null) ? array_values($merged['cons']) : $normalized['cons'];
        $merged['best_for'] = trim((string) ($merged['best_for'] ?? ''));
        $merged['image_mode'] = trim((string) ($merged['image_mode'] ?? $normalized['image_mode'] ?? 'placeholder'));
        $merged['image_remote_src'] = trim((string) ($merged['image_remote_src'] ?? $normalized['image_remote_src'] ?? ''));
        $merged['image_target_asset'] = trim((string) ($merged['image_target_asset'] ?? $normalized['image_target_asset'] ?? ''));
        $merged['image_target_path'] = trim((string) ($merged['image_target_path'] ?? $normalized['image_target_path'] ?? ''));
        $merged['_image'] = $normalized['image'];
        if (empty($merged['img']) && is_array($normalized['image']) && !empty($normalized['image']['src'])) {
            $merged['img'] = $normalized['image']['src'];
        }

        return $merged;
    }
}

if (!function_exists('interessa_affiliate_target')) {
    function interessa_affiliate_target(array $row): array {
        $row = interessa_resolve_product_reference($row);
        $code = trim((string) ($row['code'] ?? $row['affiliate_code'] ?? ''));
        $fallback = trim((string) ($row['fallback_url'] ?? $row['url'] ?? ''));
        $record = $code !== '' ? aff_record($code) : null;

        if ($record !== null && aff_resolve($code) !== null) {
            $linkType = aff_link_type($record);
            return [
                'href' => '/go/' . rawurlencode($code),
                'rel' => $linkType === 'affiliate' ? 'nofollow sponsored' : 'nofollow',
                'label' => $linkType === 'affiliate' ? 'Do obchodu' : 'Pozriet produkt',
                'note' => '',
            ];
        }

        if ($fallback !== '') {
            return [
                'href' => $fallback,
                'rel' => 'nofollow',
                'label' => 'Pozriet produkt',
                'note' => '',
            ];
        }

        return [
            'href' => '',
            'rel' => '',
            'label' => 'Coskoro',
            'note' => '',
        ];
    }
}
