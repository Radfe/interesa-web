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

if (!function_exists('interessa_article_product_category_map')) {
    function interessa_article_product_category_map(): array {
        return [
            'proteiny' => ['proteiny'],
            'sila' => ['kreatin', 'pre-workout', 'proteiny'],
            'vyziva' => ['proteiny', 'kreatin', 'mineraly', 'probiotika-travenie', 'klby-koza'],
            'mineraly' => ['mineraly'],
            'imunita' => ['probiotika-travenie', 'mineraly'],
            'klby-koza' => ['klby-koza', 'mineraly'],
            'kreatin' => ['kreatin'],
            'pre-workout' => ['pre-workout'],
            'probiotika-travenie' => ['probiotika-travenie'],
        ];
    }
}

if (!function_exists('interessa_article_product_categories')) {
    function interessa_article_product_categories(string $articleId): array {
        $articleId = function_exists('canonical_article_slug') ? canonical_article_slug(trim($articleId)) : trim($articleId);
        if ($articleId === '' || !function_exists('article_meta')) {
            return [];
        }

        $meta = article_meta($articleId);
        $articleCategory = normalize_category_slug((string) ($meta['category'] ?? ''));
        if ($articleCategory === '') {
            return [];
        }

        $map = interessa_article_product_category_map();
        $categories = $map[$articleCategory] ?? [$articleCategory];
        array_unshift($categories, $articleCategory);

        $normalized = [];
        foreach ($categories as $category) {
            $category = normalize_category_slug((string) $category);
            if ($category !== '' && !in_array($category, $normalized, true)) {
                $normalized[] = $category;
            }
        }

        return $normalized;
    }
}

if (!function_exists('interessa_article_product_context')) {
    function interessa_article_product_context(string $articleId): array {
        $articleId = function_exists('canonical_article_slug') ? canonical_article_slug(trim($articleId)) : trim($articleId);
        if ($articleId === '' || !function_exists('article_meta')) {
            return [];
        }

        $meta = article_meta($articleId);
        $title = trim((string) ($meta['title'] ?? ''));
        $description = trim((string) ($meta['description'] ?? ''));
        $category = normalize_category_slug((string) ($meta['category'] ?? ''));
        $haystack = mb_strtolower(trim(implode(' ', array_filter([
            $articleId,
            $title,
            $description,
            str_replace('-', ' ', $articleId),
            str_replace('-', ' ', $category),
        ]))), 'UTF-8');

        return [
            'slug' => $articleId,
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'haystack' => $haystack,
        ];
    }
}

if (!function_exists('interessa_article_product_theme_rules')) {
    function interessa_article_product_theme_rules(): array {
        return [
            'pre-workout' => [
                'triggers' => ['pre-workout', 'predtrening', 'pred trening', 'nakopavac', 'nakopávač'],
                'allow_categories' => ['pre-workout'],
                'prefer_keywords' => ['pre-workout', 'predtrening', 'pred trening', 'pump', 'stim', 'citrulin', 'citrulline', 'beta alanine', 'beta-alanine', 'kofein', 'caffeine', 'nitric oxide', 'no booster', 'aakg', 'arginine'],
                'exclude_keywords' => ['probiotik', 'magnez', 'horcik', 'whey', 'protein', 'kolagen', 'klby', 'imunita', 'vitamin', 'miner', 'kreatin', 'creatine'],
                'strict' => true,
            ],
            'kreatin' => [
                'triggers' => ['kreatin', 'creatine', 'creapure'],
                'allow_categories' => ['kreatin'],
                'prefer_keywords' => ['kreatin', 'creatine', 'creapure', 'monohydrate', 'monohydrat'],
                'exclude_keywords' => ['probiotik', 'magnez', 'horcik', 'whey', 'protein', 'pre-workout', 'predtrening'],
                'strict' => true,
            ],
            'proteiny' => [
                'triggers' => ['protein', 'proteiny', 'whey', 'gainer', 'vegansky protein', 'vegánsky proteín'],
                'allow_categories' => ['proteiny'],
                'prefer_keywords' => ['protein', 'proteiny', 'whey', 'isolate', 'isolat', 'hydro', 'casein', 'kasein', 'vegan', 'vegansky', 'gainer'],
                'exclude_keywords' => ['probiotik', 'magnez', 'horcik', 'pre-workout', 'predtrening', 'kreatin', 'creatine'],
                'strict' => true,
            ],
            'probiotika-travenie' => [
                'triggers' => ['probiotik', 'traven', 'digestion', 'gut', 'creva'],
                'allow_categories' => ['probiotika-travenie'],
                'prefer_keywords' => ['probiotik', 'digest', 'gut', 'lactobac', 'bifido', 'traven', 'enzym'],
                'exclude_keywords' => ['whey', 'protein', 'pre-workout', 'predtrening', 'kreatin', 'creatine'],
                'strict' => true,
            ],
            'klby-koza' => [
                'triggers' => ['klby', 'kolagen', 'collagen', 'koza', 'kĺby'],
                'allow_categories' => ['klby-koza'],
                'prefer_keywords' => ['klby', 'kolagen', 'collagen', 'joint', 'glucosamine', 'msm', 'hyaluron'],
                'exclude_keywords' => ['whey', 'protein', 'pre-workout', 'predtrening', 'kreatin', 'creatine'],
                'strict' => true,
            ],
            'mineraly' => [
                'triggers' => ['magnez', 'horcik', 'magnesium', 'mineraly', 'minerály', 'zinc', 'zinok', 'electroly'],
                'allow_categories' => ['mineraly'],
                'prefer_keywords' => ['magnez', 'horcik', 'magnesium', 'zinc', 'zinok', 'electroly', 'miner'],
                'exclude_keywords' => ['whey', 'protein', 'pre-workout', 'predtrening', 'kreatin', 'creatine'],
                'strict' => true,
            ],
        ];
    }
}

if (!function_exists('interessa_article_product_rule')) {
    function interessa_article_product_rule(string $articleId): array {
        $context = interessa_article_product_context($articleId);
        $categories = interessa_article_product_categories($articleId);
        if ($context === []) {
            return [
                'context' => [],
                'categories' => $categories,
                'strict' => false,
            ];
        }

        $haystack = (string) ($context['haystack'] ?? '');
        $matchedRule = null;
        foreach (interessa_article_product_theme_rules() as $ruleKey => $rule) {
            $tokens = array_values(array_unique(array_filter(array_merge(
                [(string) $ruleKey],
                is_array($rule['triggers'] ?? null) ? $rule['triggers'] : []
            ))));
            foreach ($tokens as $token) {
                $token = mb_strtolower(trim((string) $token), 'UTF-8');
                if ($token !== '' && str_contains($haystack, $token)) {
                    $matchedRule = $rule + ['key' => $ruleKey];
                    break 2;
                }
            }
        }

        if ($matchedRule === null) {
            $category = (string) ($context['category'] ?? '');
            $rules = interessa_article_product_theme_rules();
            if ($category !== '' && isset($rules[$category])) {
                $matchedRule = $rules[$category] + ['key' => $category];
            }
        }

        if ($matchedRule === null) {
            $matchedRule = [
                'key' => '',
                'allow_categories' => $categories,
                'prefer_keywords' => [],
                'exclude_keywords' => [],
                'strict' => false,
            ];
        }

        $matchedRule['allow_categories'] = array_values(array_unique(array_filter(array_map(
            static fn($value): string => normalize_category_slug((string) $value),
            is_array($matchedRule['allow_categories'] ?? null) ? $matchedRule['allow_categories'] : $categories
        ))));
        $matchedRule['prefer_keywords'] = array_values(array_unique(array_filter(array_map(
            static fn($value): string => mb_strtolower(trim((string) $value), 'UTF-8'),
            is_array($matchedRule['prefer_keywords'] ?? null) ? $matchedRule['prefer_keywords'] : []
        ))));
        $matchedRule['exclude_keywords'] = array_values(array_unique(array_filter(array_map(
            static fn($value): string => mb_strtolower(trim((string) $value), 'UTF-8'),
            is_array($matchedRule['exclude_keywords'] ?? null) ? $matchedRule['exclude_keywords'] : []
        ))));
        $matchedRule['context'] = $context;
        $matchedRule['categories'] = $categories;

        return $matchedRule;
    }
}

if (!function_exists('interessa_supported_affiliate_merchants')) {
    function interessa_supported_affiliate_merchants(): array {
        return [
            'symprove',
            'protein',
            'gymbeam',
            'ironaesthetics',
            'imunoklub',
        ];
    }
}

if (!function_exists('interessa_is_supported_affiliate_merchant')) {
    function interessa_is_supported_affiliate_merchant(string $merchantSlug): bool {
        $merchantSlug = interessa_guess_slug_from_text($merchantSlug);
        return $merchantSlug !== '' && in_array($merchantSlug, interessa_supported_affiliate_merchants(), true);
    }
}

if (!function_exists('interessa_product_article_match_score')) {
    function interessa_product_article_match_score(array $normalizedProduct, array $rule): ?array {
        $slug = trim((string) ($normalizedProduct['slug'] ?? ''));
        $category = normalize_category_slug((string) ($normalizedProduct['category'] ?? ''));
        $merchantSlug = trim((string) ($normalizedProduct['merchant_slug'] ?? ''));
        if ($slug === '' || $category === '' || !interessa_is_supported_affiliate_merchant($merchantSlug)) {
            return null;
        }

        $allowCategories = is_array($rule['allow_categories'] ?? null) ? $rule['allow_categories'] : [];
        if ($allowCategories !== [] && !in_array($category, $allowCategories, true)) {
            return null;
        }

        $haystack = mb_strtolower(trim(implode(' ', array_filter([
            $slug,
            str_replace('-', ' ', $slug),
            (string) ($normalizedProduct['name'] ?? ''),
            (string) ($normalizedProduct['summary'] ?? ''),
            $category,
            trim((string) ($normalizedProduct['merchant'] ?? '')),
        ]))), 'UTF-8');

        $excludeHits = 0;
        foreach ((array) ($rule['exclude_keywords'] ?? []) as $keyword) {
            if ($keyword !== '' && str_contains($haystack, (string) $keyword)) {
                $excludeHits++;
            }
        }
        if ($excludeHits > 0) {
            return null;
        }

        $preferHits = 0;
        foreach ((array) ($rule['prefer_keywords'] ?? []) as $keyword) {
            if ($keyword !== '' && str_contains($haystack, (string) $keyword)) {
                $preferHits++;
            }
        }

        $categoryPriority = array_search($category, (array) ($rule['categories'] ?? []), true);
        if ($categoryPriority === false) {
            $categoryPriority = 999;
        }

        $strict = (bool) ($rule['strict'] ?? false);
        if ($strict && $preferHits === 0 && $allowCategories !== [$category]) {
            return null;
        }

        $visualScore = interessa_product_visual_score($normalizedProduct);
        $qualityScore = $visualScore;
        if (trim((string) ($normalizedProduct['summary'] ?? '')) !== '') {
            $qualityScore += 5;
        }
        if (trim((string) ($normalizedProduct['fallback_url'] ?? '')) !== '' || trim((string) ($normalizedProduct['affiliate_code'] ?? '')) !== '') {
            $qualityScore += 10;
        }

        $score = 0;
        $score += $strict ? 300 : 100;
        $score += max(0, 60 - ((int) $categoryPriority * 15));
        $score += min(4, $preferHits) * 35;
        $score += min(80, (int) floor($qualityScore / 2));

        return [
            'score' => $score,
            'category_priority' => (int) $categoryPriority,
            'prefer_hits' => $preferHits,
            'quality_score' => $qualityScore,
        ];
    }
}

if (!function_exists('interessa_get_products_for_article')) {
    function interessa_get_products_for_article(string $articleId, int $limit = 5): array {
        $articleId = function_exists('canonical_article_slug') ? canonical_article_slug(trim($articleId)) : trim($articleId);
        $limit = max(1, $limit);
        if ($articleId === '') {
            return [];
        }

        $articleRule = interessa_article_product_rule($articleId);
        $articleCategories = is_array($articleRule['categories'] ?? null) ? $articleRule['categories'] : [];
        if ($articleCategories === [] && !(bool) ($articleRule['strict'] ?? false)) {
            return [];
        }

        $matches = [];
        foreach (interessa_product_catalog() as $productSlug => $product) {
            $normalized = interessa_normalize_product(is_array($product) ? $product : []);
            $normalizedSlug = trim((string) ($normalized['slug'] ?? $productSlug));
            $productCategory = normalize_category_slug((string) ($normalized['category'] ?? ''));
            $merchantSlug = trim((string) ($normalized['merchant_slug'] ?? ''));
            if ($normalizedSlug === '' || $productCategory === '' || !interessa_is_supported_affiliate_merchant($merchantSlug)) {
                continue;
            }

            $matchMeta = interessa_product_article_match_score($normalized, $articleRule);
            if ($matchMeta === null) {
                continue;
            }

            $target = interessa_affiliate_target($normalized);
            $categoryMeta = function_exists('category_meta') ? category_meta($productCategory) : null;
            $affiliateLink = trim((string) ($target['href'] ?? ''));
            $imageMeta = is_array($normalized['image'] ?? null) ? $normalized['image'] : [];
            $imageSourceType = trim((string) ($imageMeta['source_type'] ?? 'placeholder'));
            $hasReadyClick = $affiliateLink !== '' && $affiliateLink !== '#';
            $hasReadyImage = $imageSourceType !== '' && $imageSourceType !== 'placeholder';
            $readinessScore = 0;
            if ($hasReadyClick) {
                $readinessScore += 100;
            }
            if ($hasReadyImage) {
                $readinessScore += 60;
            }

            $matches[] = [
                'product_slug' => $normalizedSlug,
                'name' => trim((string) ($normalized['name'] ?? $normalizedSlug)),
                'image' => $normalized['image'],
                'affiliate_link' => $affiliateLink !== '' ? $affiliateLink : '#',
                'affiliate_label' => trim((string) ($target['label'] ?? 'Do obchodu')) ?: 'Do obchodu',
                'merchant' => trim((string) ($normalized['merchant'] ?? '')),
                'merchant_slug' => trim((string) ($normalized['merchant_slug'] ?? '')),
                'category' => $productCategory,
                'category_label' => trim((string) ($categoryMeta['title'] ?? humanize_slug($productCategory))),
                'summary' => trim((string) ($normalized['summary'] ?? '')),
                '_match_score' => (int) ($matchMeta['score'] ?? 0),
                '_category_priority' => (int) ($matchMeta['category_priority'] ?? 999),
                '_prefer_hits' => (int) ($matchMeta['prefer_hits'] ?? 0),
                '_readiness_score' => $readinessScore,
                '_has_ready_click' => $hasReadyClick ? 1 : 0,
                '_has_ready_image' => $hasReadyImage ? 1 : 0,
                '_quality_score' => (int) ($matchMeta['quality_score'] ?? 0),
            ];
        }

        usort($matches, static function (array $left, array $right): int {
            $matchCompare = ((int) ($right['_match_score'] ?? 0)) <=> ((int) ($left['_match_score'] ?? 0));
            if ($matchCompare !== 0) {
                return $matchCompare;
            }

            $priorityCompare = ((int) ($left['_category_priority'] ?? 999)) <=> ((int) ($right['_category_priority'] ?? 999));
            if ($priorityCompare !== 0) {
                return $priorityCompare;
            }

            $preferCompare = ((int) ($right['_prefer_hits'] ?? 0)) <=> ((int) ($left['_prefer_hits'] ?? 0));
            if ($preferCompare !== 0) {
                return $preferCompare;
            }

            $readinessCompare = ((int) ($right['_readiness_score'] ?? 0)) <=> ((int) ($left['_readiness_score'] ?? 0));
            if ($readinessCompare !== 0) {
                return $readinessCompare;
            }

            $qualityCompare = ((int) ($right['_quality_score'] ?? 0)) <=> ((int) ($left['_quality_score'] ?? 0));
            if ($qualityCompare !== 0) {
                return $qualityCompare;
            }

            return strcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        });

        $matches = array_slice($matches, 0, $limit);
        foreach ($matches as &$row) {
            unset($row['_match_score'], $row['_category_priority'], $row['_prefer_hits'], $row['_readiness_score'], $row['_has_ready_click'], $row['_has_ready_image'], $row['_quality_score']);
        }
        unset($row);

        return array_values($matches);
    }
}

if (!function_exists('getProductsForArticle')) {
    function getProductsForArticle(string $articleId, int $limit = 5): array {
        return interessa_get_products_for_article($articleId, $limit);
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
        $resolvedTarget = function_exists('aff_resolve_click_target')
            ? aff_resolve_click_target(array_replace($row, [
                'prefer_registry' => true,
            ]))
            : [];
        if (trim((string) ($resolvedTarget['href'] ?? '')) !== '') {
            return [
                'href' => trim((string) ($resolvedTarget['href'] ?? '')),
                'rel' => trim((string) ($resolvedTarget['rel'] ?? 'nofollow')),
                'label' => trim((string) ($resolvedTarget['label'] ?? 'Pozriet produkt')),
                'note' => trim((string) ($resolvedTarget['note'] ?? '')),
            ];
        }

        $fallback = trim((string) ($row['fallback_url'] ?? $row['url'] ?? ''));
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

if (!function_exists('interessa_is_valid_product_image_url')) {
    function interessa_is_valid_product_image_url(string $url): bool {
        $url = trim($url);
        if ($url === '' || !preg_match('~^https?://~i', $url)) {
            return false;
        }

        $path = strtolower(trim((string) parse_url($url, PHP_URL_PATH)));
        $query = strtolower(trim((string) parse_url($url, PHP_URL_QUERY)));
        $haystack = $path . ($query !== '' ? '?' . $query : '');
        $baseName = strtolower(basename($path));

        $blockedNeedles = [
            'placeholder',
            'spacer',
            'default-image',
            'default_image',
            'defaultimage',
            'no-image',
            'no_image',
            'noimage',
            'image-not-available',
            'not-available',
            'coming-soon',
            'coming_soon',
            'blank.',
            'loader.',
        ];

        foreach ($blockedNeedles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return false;
            }
        }

        if ($baseName !== '' && preg_match('~(?:^|[-_\.])(logo|icon|favicon|sprite)(?:[-_\.]|$)~i', $baseName) === 1) {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension !== '' && !in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'], true)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('interessa_build_product_debug_log')) {
    function interessa_build_product_debug_log(string $event, array $context = []): void {
        $payload = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $payload[$key] = $value;
            }
        }

        $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            $encoded = '{}';
        }

        error_log('[interessa_build_product] ' . $event . ' ' . $encoded);
    }
}

if (!function_exists('interessa_build_product_from_url_fetch_html')) {
    function interessa_build_product_from_url_fetch_html(string $url): string {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (function_exists('interessa_admin_fetch_remote_html')) {
            return (string) interessa_admin_fetch_remote_html($url);
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch !== false) {
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_CONNECTTIMEOUT => 8,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_MAXREDIRS => 5,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; InteresaProductBot/1.0)',
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]);
                $body = curl_exec($ch);
                curl_close($ch);
                if (is_string($body)) {
                    return $body;
                }
            }
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 15,
                'follow_location' => 1,
                'user_agent' => 'Mozilla/5.0 (compatible; InteresaProductBot/1.0)',
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        return is_string($body) ? $body : '';
    }
}

if (!function_exists('interessa_build_product_from_url_join')) {
    function interessa_build_product_from_url_join(string $baseUrl, string $candidate): string {
        $candidate = trim($candidate);
        if ($candidate === '') {
            return '';
        }

        if (function_exists('interessa_admin_url_join')) {
            return (string) interessa_admin_url_join($baseUrl, $candidate);
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

if (!function_exists('interessa_build_product_from_url_meta')) {
    function interessa_build_product_from_url_meta(string $html, array $keys): string {
        if (function_exists('interessa_admin_html_extract_meta')) {
            return (string) interessa_admin_html_extract_meta($html, $keys);
        }

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

if (!function_exists('interessa_build_product_from_url_title')) {
    function interessa_build_product_from_url_title(string $html): string {
        if (function_exists('interessa_admin_html_extract_title')) {
            return (string) interessa_admin_html_extract_title($html);
        }

        if (preg_match('~<title[^>]*>(.*?)</title>~is', $html, $match) !== 1) {
            return '';
        }

        $title = html_entity_decode(strip_tags((string) ($match[1] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = preg_replace('~\s+~u', ' ', $title) ?? $title;
        return trim($title);
    }
}

if (!function_exists('interessa_clean_product_name')) {
    function interessa_clean_product_name(string $name, array $context = []): string {
        $name = html_entity_decode(trim($name), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (function_exists('interessa_media_clean_text')) {
            $name = interessa_media_clean_text($name);
        }
        $name = preg_replace('~\s+~u', ' ', $name) ?? $name;
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $merchant = trim((string) ($context['merchant'] ?? ''));
        $merchantSlug = trim((string) ($context['merchant_slug'] ?? ''));
        $merchantVariants = [];
        if ($merchant !== '') {
            $merchantVariants[] = mb_strtolower($merchant, 'UTF-8');
        }
        if ($merchantSlug !== '') {
            $merchantVariants[] = mb_strtolower(str_replace(['-', '_'], ' ', $merchantSlug), 'UTF-8');
        }

        $separators = [' | ', ' - ', ' – ', ' — '];
        foreach ($separators as $separator) {
            $parts = array_values(array_filter(array_map(static fn($part): string => trim((string) $part), explode($separator, $name))));
            if (count($parts) < 2) {
                continue;
            }

            $lastPart = (string) end($parts);
            $lastPartLower = mb_strtolower($lastPart, 'UTF-8');
            $matchesMerchant = false;
            foreach ($merchantVariants as $variant) {
                if ($variant !== '' && ($lastPartLower === $variant || str_contains($lastPartLower, $variant))) {
                    $matchesMerchant = true;
                    break;
                }
            }

            if ($matchesMerchant) {
                array_pop($parts);
                $candidate = trim(implode($separator, $parts));
                if ($candidate !== '') {
                    $name = $candidate;
                }
                break;
            }
        }

        $seoPatterns = [
            '~\s*[\|\-–—]\s*k[uú]pi[tť].*$~iu',
            '~\s*[\|\-–—]\s*najlep[sš]ia cena.*$~iu',
            '~\s*[\|\-–—]\s*skladom.*$~iu',
            '~\s*[\|\-–—]\s*doprava zdarma.*$~iu',
            '~\s*\(\s*skladom\s*\)\s*$~iu',
        ];
        foreach ($seoPatterns as $pattern) {
            $candidate = preg_replace($pattern, '', $name);
            if (is_string($candidate) && trim($candidate) !== '') {
                $name = trim($candidate);
            }
        }

        $name = preg_replace('~\s+~u', ' ', $name) ?? $name;
        return trim($name, " \t\n\r\0\x0B-–—|");
    }
}

if (!function_exists('interessa_build_product_from_url_merchant')) {
    function interessa_build_product_from_url_merchant(string $url): array {
        if (function_exists('aff_resolve_merchant_meta')) {
            $resolved = aff_resolve_merchant_meta('', '', $url);
            if (is_array($resolved)) {
                return [
                    'merchant' => trim((string) ($resolved['name'] ?? '')),
                    'merchant_slug' => trim((string) ($resolved['merchant_slug'] ?? '')),
                ];
            }
        }

        if (function_exists('interessa_admin_guess_merchant_from_url')) {
            $merchant = interessa_admin_guess_merchant_from_url($url);
            if (is_array($merchant)) {
                return [
                    'merchant' => trim((string) ($merchant['merchant'] ?? '')),
                    'merchant_slug' => trim((string) ($merchant['merchant_slug'] ?? '')),
                ];
            }
        }

        $host = strtolower(trim((string) parse_url($url, PHP_URL_HOST)));
        $host = preg_replace('~^www\.~', '', $host) ?? $host;
        $label = $host !== '' ? preg_replace('~\.[a-z0-9-]+$~i', '', $host) : '';
        $label = trim((string) str_replace(['-', '_'], ' ', (string) $label));

        return [
            'merchant' => $label !== '' ? ucwords($label) : '',
            'merchant_slug' => $host !== '' ? interessa_guess_slug_from_text($label !== '' ? $label : $host) : '',
        ];
    }
}

if (!function_exists('interessa_build_product_from_url_affiliate_target')) {
    function interessa_build_product_from_url_affiliate_target(string $url, array $merchant, string $slug): array {
        if (!function_exists('aff_resolve_click_target')) {
            return [
                'affiliate_url' => '',
                'code' => '',
                'status' => 'missing',
                'source' => 'resolver-missing',
            ];
        }

        return aff_resolve_click_target([
            'product_slug' => trim($slug),
            'merchant' => trim((string) ($merchant['merchant'] ?? '')),
            'merchant_slug' => trim((string) ($merchant['merchant_slug'] ?? '')),
            'fallback_url' => trim($url),
            'product_url' => trim($url),
            'prefer_registry' => true,
        ]);
    }
}

if (!function_exists('interessa_build_product_from_url_slug')) {
    function interessa_build_product_from_url_slug(string $url, string $title = ''): string {
        $title = trim($title);
        if ($title !== '') {
            $slug = interessa_guess_slug_from_text($title);
            if ($slug !== '') {
                return $slug;
            }
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($path !== '') {
            $segments = array_values(array_filter(array_map(static fn($segment): string => trim((string) $segment), explode('/', $path))));
            $candidate = (string) end($segments);
            if ($candidate !== '') {
                $candidate = preg_replace('~\.[a-z0-9]+$~i', '', $candidate) ?? $candidate;
                $slug = interessa_guess_slug_from_text($candidate);
                if ($slug !== '') {
                    return $slug;
                }
            }
        }

        return '';
    }
}

if (!function_exists('interessa_build_product_image_target_asset_for_ext')) {
    function interessa_build_product_image_target_asset_for_ext(string $slug, string $merchantSlug, string $ext): string {
        $slug = trim($slug);
        $merchantSlug = trim($merchantSlug);
        $ext = strtolower(trim($ext));
        if ($slug === '') {
            return '';
        }

        $baseAsset = interessa_product_image_target_asset($slug, $merchantSlug);
        $baseWithoutExt = preg_replace('~\.[a-z0-9]+$~i', '', $baseAsset) ?: $baseAsset;
        return $baseWithoutExt . '.' . ($ext !== '' ? $ext : 'webp');
    }
}

if (!function_exists('interessa_build_product_image_target_path_for_ext')) {
    function interessa_build_product_image_target_path_for_ext(string $slug, string $merchantSlug, string $ext): string {
        $asset = interessa_build_product_image_target_asset_for_ext($slug, $merchantSlug, $ext);
        return $asset !== '' ? dirname(__DIR__) . '/assets/' . ltrim($asset, '/') : '';
    }
}

if (!function_exists('interessa_build_product_image_download')) {
    function interessa_build_product_image_download(string $url): array {
        $url = trim($url);
        if ($url === '' || !preg_match('~^https?://~i', $url)) {
            return ['success' => false, 'body' => '', 'content_type' => '', 'error' => 'invalid-url'];
        }

        if (function_exists('interessa_admin_fetch_remote_image_bytes')) {
            try {
                $download = interessa_admin_fetch_remote_image_bytes($url);
                return [
                    'success' => trim((string) ($download['body'] ?? '')) !== '',
                    'body' => (string) ($download['body'] ?? ''),
                    'content_type' => trim((string) ($download['content_type'] ?? '')),
                    'error' => '',
                ];
            } catch (Throwable $e) {
                return ['success' => false, 'body' => '', 'content_type' => '', 'error' => trim($e->getMessage())];
            }
        }

        $contentType = '';
        $body = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch !== false) {
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_USERAGENT => 'Mozilla/5.0',
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                ]);
                $body = curl_exec($ch);
                if ($body !== false) {
                    $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                }
                curl_close($ch);
            }
        }

        if (!is_string($body) || $body === '') {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 15,
                    'follow_location' => 1,
                    'user_agent' => 'Mozilla/5.0',
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);
            $body = @file_get_contents($url, false, $context);
            if (is_string($body)) {
                $headers = $http_response_header ?? [];
                foreach ($headers as $header) {
                    if (stripos((string) $header, 'Content-Type:') === 0) {
                        $contentType = trim((string) substr((string) $header, strlen('Content-Type:')));
                        break;
                    }
                }
            }
        }

        return [
            'success' => is_string($body) && $body !== '',
            'body' => is_string($body) ? $body : '',
            'content_type' => $contentType,
            'error' => is_string($body) && $body !== '' ? '' : 'download-empty',
        ];
    }
}

if (!function_exists('interessa_build_product_image_extension')) {
    function interessa_build_product_image_extension(string $remoteUrl, string $contentType = '', string $body = ''): string {
        if (function_exists('interessa_admin_detect_remote_image_extension')) {
            $detected = trim((string) interessa_admin_detect_remote_image_extension($remoteUrl, $contentType));
            if ($detected !== '') {
                return $detected;
            }
        }

        $path = strtolower(trim((string) parse_url($remoteUrl, PHP_URL_PATH)));
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'], true)) {
            return $ext === 'jpeg' ? 'jpg' : $ext;
        }

        $contentType = strtolower(trim($contentType));
        $typeMap = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/avif' => 'avif',
        ];
        if (isset($typeMap[$contentType])) {
            return $typeMap[$contentType];
        }

        if ($body !== '') {
            if (substr($body, 0, 4) === "\x89PNG") {
                return 'png';
            }
            if (substr($body, 0, 3) === "\xFF\xD8\xFF") {
                return 'jpg';
            }
            if (substr($body, 0, 4) === 'RIFF' && substr($body, 8, 4) === 'WEBP') {
                return 'webp';
            }
            if (substr($body, 0, 6) === 'GIF87a' || substr($body, 0, 6) === 'GIF89a') {
                return 'gif';
            }
        }

        return '';
    }
}

if (!function_exists('interessa_build_product_image_local_copy')) {
    function interessa_build_product_image_local_copy(string $slug, string $merchantSlug, string $remoteUrl): array {
        $slug = trim($slug);
        $merchantSlug = trim($merchantSlug);
        $remoteUrl = trim($remoteUrl);
        if ($slug === '' || !interessa_is_valid_product_image_url($remoteUrl)) {
            interessa_build_product_debug_log('image-local-copy-skip', [
                'remote_src' => $remoteUrl,
                'download_success' => false,
                'local_path' => '',
                'reason' => 'invalid-input',
            ]);
            return [];
        }

        $download = interessa_build_product_image_download($remoteUrl);
        interessa_build_product_debug_log('image-download', [
            'remote_src' => $remoteUrl,
            'download_success' => !empty($download['success']),
            'local_path' => '',
            'error' => (string) ($download['error'] ?? ''),
        ]);
        $body = (string) ($download['body'] ?? '');
        if ($body === '') {
            return [];
        }

        $ext = interessa_build_product_image_extension($remoteUrl, (string) ($download['content_type'] ?? ''), $body);
        if ($ext === '' || !in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'], true)) {
            return [];
        }
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $targetAsset = interessa_build_product_image_target_asset_for_ext($slug, $merchantSlug, $ext);
        $targetPath = interessa_build_product_image_target_path_for_ext($slug, $merchantSlug, $ext);
        if ($targetAsset === '' || $targetPath === '') {
            return [];
        }

        $dir = dirname($targetPath);
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            interessa_build_product_debug_log('image-local-copy-failed', [
                'remote_src' => $remoteUrl,
                'download_success' => !empty($download['success']),
                'local_path' => $targetPath,
                'reason' => 'mkdir-failed',
            ]);
            return [];
        }

        if (@file_put_contents($targetPath, $body) === false) {
            interessa_build_product_debug_log('image-local-copy-failed', [
                'remote_src' => $remoteUrl,
                'download_success' => !empty($download['success']),
                'local_path' => $targetPath,
                'reason' => 'write-failed',
            ]);
            return [];
        }

        interessa_build_product_debug_log('image-local-copy-saved', [
            'remote_src' => $remoteUrl,
            'download_success' => !empty($download['success']),
            'local_path' => $targetPath,
        ]);

        return [
            'asset' => $targetAsset,
            'local_path' => $targetPath,
            'local_url' => interessa_asset_public_url($targetAsset),
        ];
    }
}

if (!function_exists('interessa_build_product_from_url')) {
    function interessa_build_product_from_url(string $url): array {
        $url = trim($url);
        $validUrl = function_exists('interessa_admin_looks_like_product_url')
            ? (bool) interessa_admin_looks_like_product_url($url)
            : ($url !== '' && preg_match('~^https?://~i', $url) === 1 && trim((string) parse_url($url, PHP_URL_PATH)) !== '' && trim((string) parse_url($url, PHP_URL_PATH)) !== '/');

        if (!$validUrl) {
            return [];
        }

        $merchant = interessa_build_product_from_url_merchant($url);
        $html = interessa_build_product_from_url_fetch_html($url);
        $title = '';
        $imageUrl = '';
        $slug = '';

        if ($html !== '') {
            $title = interessa_build_product_from_url_meta($html, ['og:title', 'twitter:title']);
            if ($title === '') {
                $title = interessa_build_product_from_url_title($html);
            }
            $title = interessa_clean_product_name($title, $merchant);

            $imageCandidate = interessa_build_product_from_url_meta($html, ['og:image', 'twitter:image']);
            if ($imageCandidate !== '') {
                $resolvedImage = interessa_build_product_from_url_join($url, $imageCandidate);
                if (interessa_is_valid_product_image_url($resolvedImage)) {
                    $imageUrl = $resolvedImage;
                }
            }
        }
        $slug = interessa_build_product_from_url_slug($url, $title);

        $imageRemoteSrc = $imageUrl;
        $imageLocal = [];
        if ($slug !== '' && $imageRemoteSrc !== '') {
            $imageLocal = interessa_build_product_image_local_copy($slug, (string) ($merchant['merchant_slug'] ?? ''), $imageRemoteSrc);
        }

        $image = [];
        $imageSource = 'remote';
        $imagePublicUrl = $imageRemoteSrc;
        if ($slug !== '') {
            $image = interessa_product_image_meta($slug, [
                'merchant_slug' => (string) ($merchant['merchant_slug'] ?? ''),
                'remote_src' => $imageRemoteSrc,
                'alt' => $title !== '' ? $title : humanize_slug($slug),
            ], false) ?? [];
        }

        $localAsset = trim((string) ($image['asset'] ?? ($imageLocal['asset'] ?? '')));
        $localPath = $localAsset !== '' ? (interessa_asset_file_path($localAsset) ?? trim((string) ($imageLocal['local_path'] ?? ''))) : trim((string) ($imageLocal['local_path'] ?? ''));
        $localUrl = $localAsset !== '' ? interessa_asset_public_url($localAsset) : trim((string) ($imageLocal['local_url'] ?? ''));
        if ($localUrl !== '' && $localPath !== '') {
            $imageSource = 'local';
            $imagePublicUrl = $localUrl;
        }

        if ($imagePublicUrl === '' && $imageRemoteSrc !== '') {
            $imagePublicUrl = $imageRemoteSrc;
            $imageSource = 'remote';
        }

        $image['remote_src'] = $imageRemoteSrc;
        $image['local_path'] = $localPath;
        $image['local_url'] = $localUrl;
        $image['source'] = $imageSource;
        $image['source_type'] = $imageSource;
        $image['download_success'] = !empty($imageLocal) || $imageRemoteSrc !== '';
        if ($imageSource === 'local') {
            $image['src'] = $localUrl;
        } elseif ($imageRemoteSrc !== '') {
            $image['src'] = $imageRemoteSrc;
        }

        interessa_build_product_debug_log('image-result', [
            'remote_src' => $imageRemoteSrc,
            'download_success' => !empty($imageLocal),
            'local_path' => $localPath,
        ]);

        $affiliateTarget = interessa_build_product_from_url_affiliate_target($url, $merchant, $slug);
        $affiliateUrl = trim((string) ($affiliateTarget['affiliate_url'] ?? ''));
        $affiliateCode = trim((string) ($affiliateTarget['code'] ?? ''));
        if ($affiliateUrl === '' && function_exists('aff_extract_final_url')) {
            $finalUrl = trim((string) aff_extract_final_url($url));
            if ($finalUrl !== '' && $finalUrl !== $url) {
                $affiliateUrl = $url;
            }
        }

        return [
            'slug' => $slug,
            'name' => $title,
            'title' => $title,
            'merchant' => $merchant['merchant'],
            'merchant_slug' => $merchant['merchant_slug'],
            'fallback_url' => $url,
            'affiliate_url' => $affiliateUrl,
            'affiliate_code' => $affiliateCode,
            'affiliate_status' => trim((string) ($affiliateTarget['status'] ?? ($affiliateUrl !== '' ? 'affiliate_ready' : 'missing'))),
            'image' => $image,
            'image_url' => $imagePublicUrl,
            'image_mode' => $imageSource,
            'html_fetched' => $html !== '',
            'is_valid_product_url' => true,
        ];
    }
}
