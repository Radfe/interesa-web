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

        $catalog = interessa_admin_merge_product_catalog($catalog);
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
        $hasLocalImage = $slug !== '' && interessa_product_has_local_image($slug, $merchantSlug);

        $asset = trim((string) ($imageConfig['asset'] ?? ''));
        if ($asset !== '' && interessa_asset_file_path($asset) !== null) {
            $score += 40;
        }

        if ($hasLocalImage) {
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

if (!function_exists('interessa_guess_slug_from_text')) {
    function interessa_guess_slug_from_text(string $value): string {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('~[^a-z0-9]+~', '-', $value);
        return trim((string) $value, '-');
    }
}

if (!function_exists('interessa_normalize_product')) {
    function interessa_normalize_product(array $product): array {
        $slug = trim((string) ($product['slug'] ?? ''));
        $merchantSlug = trim((string) ($product['merchant_slug'] ?? ''));
        $affiliateCode = trim((string) ($product['affiliate_code'] ?? ''));
        $fallbackUrl = trim((string) ($product['fallback_url'] ?? ''));
        if ($fallbackUrl === '' && $affiliateCode !== '') {
            $fallbackUrl = aff_product_url_for_code($affiliateCode);
        }
        $imageConfig = is_array($product['image'] ?? null) ? $product['image'] : [];
        $imageConfig['merchant_slug'] = $merchantSlug !== '' ? $merchantSlug : (string) ($imageConfig['merchant_slug'] ?? '');
        $image = $slug !== '' ? interessa_product_image_meta($slug, $imageConfig, true) : null;
        $imageRemoteSrc = trim((string) ($imageConfig['remote_src'] ?? $imageConfig['src'] ?? ''));
        $imageLocalAsset = $slug !== '' ? (interessa_product_image_local_asset($slug, $merchantSlug) ?? '') : '';
        $imageLocalPath = $slug !== '' ? (interessa_product_image_local_path($slug, $merchantSlug) ?? '') : '';
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
            'image_local_asset' => $imageLocalAsset,
            'image_local_path' => $imageLocalPath,
            'has_local_image' => $imageLocalAsset !== '',
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
            $row['code'] = $code;
            $row['_catalog_resolved'] = false;
            $row['product_name'] = trim((string) ($row['product_name'] ?? $row['name'] ?? ''));
            $row['product_summary'] = trim((string) ($row['product_summary'] ?? $row['subtitle'] ?? $row['summary'] ?? ''));
            $row['merchant'] = trim((string) ($row['merchant'] ?? ''));
            $row['merchant_slug'] = trim((string) ($row['merchant_slug'] ?? ''));
            if ($row['merchant_slug'] === '' && $row['merchant'] !== '') {
                $row['merchant_slug'] = interessa_guess_slug_from_text($row['merchant']);
            }
            $row['slug'] = trim((string) ($row['slug'] ?? ''));
            if ($row['slug'] === '' && $row['product_name'] !== '') {
                $row['slug'] = interessa_guess_slug_from_text($row['product_name']);
            }

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
                    $row['image_local_asset'] = trim((string) ($image['asset'] ?? ''));
                    $row['has_local_image'] = ($row['image_mode'] ?? '') === 'local';
                    $row['image_target_asset'] = trim((string) ($image['target_asset'] ?? ''));
                }
            }
            if (!isset($row['image_mode']) || trim((string) $row['image_mode']) === '') {
                $row['image_mode'] = 'placeholder';
            }
            return $row;
        }

        $normalized = interessa_normalize_product($product);
        $catalogName = trim((string) ($normalized['name'] ?? ''));
        $catalogSummary = trim((string) ($normalized['summary'] ?? ''));
        $merged = array_replace($normalized, $row);
        $merged['_catalog_resolved'] = true;
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

if (!function_exists('interessa_product_catalog_resolved')) {
    function interessa_product_catalog_resolved(array $row): bool {
        return (bool) ($row['_catalog_resolved'] ?? false);
    }
}

if (!function_exists('interessa_product_packshot_merchant_note')) {
    function interessa_product_packshot_merchant_note(string $merchantSlug): string {
        $merchantSlug = interessa_guess_slug_from_text($merchantSlug);
        if ($merchantSlug === 'aktin') {
            return 'Vizual drz minimalisticky, s cistym bielym pozadim a prirodzenym ecommerce stylom podobnym produktom z Aktinu.';
        }
        if ($merchantSlug === 'myprotein') {
            return 'Vizual nech je cisty, studiovy a mierne kontrastnejsi, podobny klasickym ecommerce packshotom Myprotein.';
        }
        if ($merchantSlug === 'proteinsk' || $merchantSlug === 'protein-sk') {
            return 'Vizual nech zostane jednoduchy a funkcny, ako bezny packshot doplnku vyzivy v lokalnom eshope.';
        }
        if ($merchantSlug === 'gymbeam') {
            return 'Ak mas referencny GymBeam packshot, drz sa jeho realistickeho ecommerce stylu bez dalsich grafickych prvkov.';
        }

        return 'Pouzi cisty packshot na svetlom neutralnom pozadi a drz produkt v strede bez dekoracii.';
    }
}

if (!function_exists('interessa_product_packshot_brief_from_reference')) {
    function interessa_product_packshot_brief_from_reference(array $reference): array {
        $slug = trim((string) ($reference['slug'] ?? $reference['product_slug'] ?? ''));
        $name = trim((string) ($reference['name'] ?? $reference['product_name'] ?? humanize_slug($slug !== '' ? $slug : 'produkt')));
        $merchant = trim((string) ($reference['merchant'] ?? ''));
        $merchantSlug = trim((string) ($reference['merchant_slug'] ?? ''));
        if ($merchantSlug === '' && $merchant !== '') {
            $merchantSlug = interessa_guess_slug_from_text($merchant);
        }

        $targetAsset = trim((string) ($reference['image_target_asset'] ?? $reference['target_asset'] ?? ''));
        if ($targetAsset === '' && $slug !== '') {
            $targetAsset = interessa_product_image_target_asset($slug, $merchantSlug);
        }

        $fileName = $targetAsset !== '' ? basename($targetAsset) : 'product-packshot.webp';
        $altText = trim($name . ($merchant !== '' ? ' - ' . $merchant : ''));
        $referenceUrl = trim((string) ($reference['fallback_url'] ?? $reference['url'] ?? ''));
        $merchantNote = interessa_product_packshot_merchant_note($merchantSlug !== '' ? $merchantSlug : $merchant);

        $prompt = 'Realisticky ecommerce packshot produktu ' . $name;
        if ($merchant !== '') {
            $prompt .= ' od ' . $merchant;
        }
        $prompt .= ', cisty obal doplnku vyzivy na svetlom neutralnom pozadi, jemny tien, produkt v strede, bez textovych overlayov, bez dekoracii, moderny health and fitness look, stvorcovy format. ' . $merchantNote;

        return [
            'name' => $name,
            'merchant' => $merchant,
            'asset_path' => $targetAsset,
            'file_name' => $fileName,
            'alt_text' => $altText,
            'dimensions' => '1200x1200',
            'prompt' => $prompt,
            'reference_url' => $referenceUrl,
            'merchant_note' => $merchantNote,
        ];
    }
}

if (!function_exists('interessa_product_packshot_brief')) {
    function interessa_product_packshot_brief(array $product): array {
        $normalized = interessa_normalize_product($product);
        return interessa_product_packshot_brief_from_reference($normalized);
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
