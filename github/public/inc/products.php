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

        $file = dirname(__DIR__) . '/content/products/catalog.php';
        $catalog = is_file($file) ? include $file : [];
        if (!is_array($catalog)) {
            $catalog = [];
        }

        ksort($catalog);
        return $catalog;
    }
}

if (!function_exists('interessa_product_by_affiliate_code')) {
    function interessa_product_by_affiliate_code(string $code): ?array {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        foreach (interessa_product_catalog() as $product) {
            if (trim((string) ($product['affiliate_code'] ?? '')) === $code) {
                return $product;
            }
        }

        return null;
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
        $affiliateCode = trim((string) ($product['affiliate_code'] ?? ''));
        $fallbackUrl = trim((string) ($product['fallback_url'] ?? ''));
        $imageConfig = is_array($product['image'] ?? null) ? $product['image'] : [];
        $image = $slug !== '' ? interessa_product_image_meta($slug, $imageConfig, true) : null;

        return [
            'slug' => $slug,
            'name' => trim((string) ($product['name'] ?? humanize_slug($slug))),
            'merchant' => trim((string) ($product['merchant'] ?? '')),
            'merchant_slug' => trim((string) ($product['merchant_slug'] ?? '')),
            'category' => normalize_category_slug((string) ($product['category'] ?? '')),
            'affiliate_code' => $affiliateCode,
            'fallback_url' => $fallbackUrl,
            'summary' => trim((string) ($product['summary'] ?? '')),
            'pros' => is_array($product['pros'] ?? null) ? array_values($product['pros']) : [],
            'cons' => is_array($product['cons'] ?? null) ? array_values($product['cons']) : [],
            'image' => $image,
            'image_source' => trim((string) ($product['image_source'] ?? '')),
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
            $record = aff_record($code);
            $linkedSlug = trim((string) ($record['product_slug'] ?? ''));
            if ($linkedSlug !== '') {
                $product = interessa_product($linkedSlug);
            }
        }

        if ($product === null) {
            if (empty($row['img']) && !empty($row['slug'])) {
                $image = interessa_product_image_meta((string) $row['slug'], [], true);
                if ($image !== null) {
                    $row['img'] = $image['src'];
                    $row['_image'] = $image;
                }
            }
            return $row;
        }

        $normalized = interessa_normalize_product($product);
        $merged = array_replace($normalized, $row);
        $merged['code'] = trim((string) ($merged['code'] ?? $normalized['affiliate_code'] ?? ''));
        $merged['url'] = trim((string) ($merged['url'] ?? $normalized['fallback_url'] ?? ''));
        $merged['subtitle'] = trim((string) ($merged['subtitle'] ?? $normalized['summary'] ?? ''));
        $merged['merchant'] = trim((string) ($merged['merchant'] ?? $normalized['merchant'] ?? ''));
        $merged['slug'] = trim((string) ($merged['slug'] ?? $normalized['slug'] ?? ''));
        $merged['pros'] = is_array($merged['pros'] ?? null) ? array_values($merged['pros']) : $normalized['pros'];
        $merged['cons'] = is_array($merged['cons'] ?? null) ? array_values($merged['cons']) : $normalized['cons'];
        $merged['best_for'] = trim((string) ($merged['best_for'] ?? ''));
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

        if ($code !== '' && aff_resolve($code) !== null) {
            return [
                'href' => '/go/' . rawurlencode($code),
                'rel' => 'nofollow sponsored',
                'label' => 'Do obchodu',
                'note' => 'Affiliate odkaz',
            ];
        }

        if ($fallback !== '') {
            return [
                'href' => $fallback,
                'rel' => 'nofollow',
                'label' => 'Pozriet produkt',
                'note' => 'Priamy odkaz',
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