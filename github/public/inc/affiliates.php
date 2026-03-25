<?php
declare(strict_types=1);

const AFF_DIRS = [
    __DIR__ . '/../content/affiliates',
    __DIR__ . '/..',
];
const AFF_FILES = ['affiliate_simple_edit.csv', 'affiliate_links.csv'];
const AFF_REGISTRY_FILES = [
    __DIR__ . '/../content/affiliates/links.php',
    __DIR__ . '/../content/affiliates/links_overrides.php',
];
const AFF_MERCHANTS_FILE = __DIR__ . '/../content/affiliates/merchants.php';

function aff_detect_delim(string $headerLine): string {
    if (str_contains($headerLine, ';')) { return ';'; }
    if (str_contains($headerLine, "\t")) { return "\t"; }
    return ',';
}

function aff_slugify_merchant(string $value): string {
    $value = trim(strtolower($value));
    if ($value === '') {
        return '';
    }

    $value = str_replace([' ', '.', '/', '&'], '-', $value);
    $value = preg_replace('~[^a-z0-9-]+~', '', $value) ?? '';
    return trim($value, '-');
}

function aff_detect_link_type_from_url(string $url, string $default = 'affiliate'): string {
    $url = trim($url);
    if ($url === '') {
        return $default;
    }

    if (str_contains($url, 'dognet') || str_contains($url, 'utm_term=dognet')) {
        return 'affiliate';
    }

    return $default;
}

function aff_normalize_link_type(mixed $value, string $url = '', string $default = 'affiliate'): string {
    $type = trim(strtolower((string) $value));
    if ($type === 'affiliate' || $type === 'product') {
        return $type;
    }

    return aff_detect_link_type_from_url($url, $default);
}

function aff_load_merchants(): array {
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $data = is_file(AFF_MERCHANTS_FILE) ? include AFF_MERCHANTS_FILE : [];
    $cache = is_array($data) ? $data : [];
    return $cache;
}

function aff_normalize_host(string $host): string {
    $host = trim(strtolower($host));
    $host = preg_replace('~:\d+$~', '', $host) ?? $host;
    return preg_replace('~^www\.~', '', $host) ?? $host;
}

function aff_extract_host_from_url(string $url): string {
    $host = (string) parse_url(trim($url), PHP_URL_HOST);
    return aff_normalize_host($host);
}

function aff_normalize_compare_url(string $url): string {
    $url = trim($url);
    if ($url === '' || !preg_match('~^https?://~i', $url)) {
        return '';
    }

    $parts = parse_url($url);
    if (!is_array($parts)) {
        return rtrim($url, '/');
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
    $host = aff_normalize_host((string) ($parts['host'] ?? ''));
    $path = trim((string) ($parts['path'] ?? ''));
    $path = $path === '' ? '/' : '/' . ltrim($path, '/');
    $path = rtrim($path, '/');
    if ($path === '') {
        $path = '/';
    }

    $query = '';
    if (!empty($parts['query'])) {
        $queryParams = [];
        parse_str((string) $parts['query'], $queryParams);
        ksort($queryParams);
        $query = http_build_query($queryParams);
    }

    return $scheme . '://' . $host . $path . ($query !== '' ? '?' . $query : '');
}

function aff_urls_match(string $left, string $right): bool {
    $left = aff_normalize_compare_url($left);
    $right = aff_normalize_compare_url($right);
    return $left !== '' && $right !== '' && $left === $right;
}

function aff_merchant_lookup_map(): array {
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $cache = [];
    foreach (aff_load_merchants() as $merchantSlug => $merchantMeta) {
        if (!is_array($merchantMeta)) {
            continue;
        }

        $canonicalSlug = aff_slugify_merchant((string) $merchantSlug);
        if ($canonicalSlug === '') {
            continue;
        }

        $merchantMeta['merchant_slug'] = $canonicalSlug;
        $merchantMeta['name'] = trim((string) ($merchantMeta['name'] ?? $canonicalSlug));

        $keys = [$canonicalSlug];
        $nameKey = aff_slugify_merchant((string) ($merchantMeta['name'] ?? ''));
        if ($nameKey !== '') {
            $keys[] = $nameKey;
        }

        foreach ((array) ($merchantMeta['aliases'] ?? []) as $alias) {
            $aliasKey = aff_slugify_merchant((string) $alias);
            if ($aliasKey !== '') {
                $keys[] = $aliasKey;
            }
        }

        foreach ((array) ($merchantMeta['hosts'] ?? []) as $host) {
            $hostKey = aff_normalize_host((string) $host);
            if ($hostKey !== '') {
                $keys[] = 'host:' . $hostKey;
            }
        }

        foreach (array_values(array_unique($keys)) as $key) {
            $cache[$key] = $merchantMeta;
        }
    }

    return $cache;
}

function aff_resolve_merchant_meta(string $merchantSlug = '', string $merchant = '', string $url = ''): ?array {
    $lookup = aff_merchant_lookup_map();
    $candidates = [];

    $normalizedMerchantSlug = aff_slugify_merchant($merchantSlug);
    if ($normalizedMerchantSlug !== '') {
        $candidates[] = $normalizedMerchantSlug;
    }

    $normalizedMerchant = aff_slugify_merchant($merchant);
    if ($normalizedMerchant !== '') {
        $candidates[] = $normalizedMerchant;
    }

    $host = aff_extract_host_from_url($url);
    if ($host !== '') {
        $candidates[] = 'host:' . $host;
        $parts = explode('.', $host);
        if (count($parts) >= 2) {
            $base = $parts[count($parts) - 2];
            $baseKey = aff_slugify_merchant($base);
            if ($baseKey !== '') {
                $candidates[] = $baseKey;
            }
        }
    }

    foreach ($candidates as $candidateKey) {
        if (!isset($lookup[$candidateKey]) || !is_array($lookup[$candidateKey])) {
            continue;
        }

        $resolved = $lookup[$candidateKey];
        $resolved['merchant_slug'] = aff_slugify_merchant((string) ($resolved['merchant_slug'] ?? ''));
        $resolved['name'] = trim((string) ($resolved['name'] ?? $merchant ?: $merchantSlug));
        return $resolved;
    }

    return null;
}

function aff_merchant(string $slug): ?array {
    $slug = trim($slug);
    if ($slug === '') {
        return null;
    }

    $resolved = aff_resolve_merchant_meta($slug);
    if (is_array($resolved)) {
        return $resolved;
    }

    $merchants = aff_load_merchants();
    return $merchants[$slug] ?? null;
}

function aff_is_supported_affiliate_merchant(string $merchantSlug = '', string $merchant = '', string $url = ''): bool {
    $resolved = aff_resolve_merchant_meta($merchantSlug, $merchant, $url);
    if (!is_array($resolved)) {
        return false;
    }

    return !empty($resolved['affiliate_supported'])
        && trim((string) ($resolved['network'] ?? '')) === 'dognet'
        && trim((string) ($resolved['feed_url'] ?? '')) !== ''
        && (int) ($resolved['campaign_id'] ?? 0) > 0;
}

function aff_supported_affiliate_merchant_meta(string $merchantSlug = '', string $merchant = '', string $url = ''): ?array {
    $resolved = aff_resolve_merchant_meta($merchantSlug, $merchant, $url);
    if (!is_array($resolved) || !aff_is_supported_affiliate_merchant($merchantSlug, $merchant, $url)) {
        return null;
    }

    return $resolved;
}

function aff_supported_affiliate_merchants(): array {
    $supported = [];
    foreach (aff_load_merchants() as $merchantSlug => $merchantMeta) {
        if (!is_array($merchantMeta)) {
            continue;
        }

        $resolved = aff_supported_affiliate_merchant_meta((string) $merchantSlug);
        if (!is_array($resolved)) {
            continue;
        }

        $supported[trim((string) ($resolved['merchant_slug'] ?? $merchantSlug))] = $resolved;
    }

    ksort($supported);
    return $supported;
}

function aff_supported_affiliate_feed_url(string $merchantSlug = '', string $merchant = '', string $url = ''): string {
    $resolved = aff_supported_affiliate_merchant_meta($merchantSlug, $merchant, $url);
    return trim((string) ($resolved['feed_url'] ?? ''));
}

function aff_supported_affiliate_campaign_id(string $merchantSlug = '', string $merchant = '', string $url = ''): int {
    $resolved = aff_supported_affiliate_merchant_meta($merchantSlug, $merchant, $url);
    return (int) ($resolved['campaign_id'] ?? 0);
}

function aff_merge_merchant_meta(array $record): array {
    $resolvedMerchant = aff_resolve_merchant_meta(
        (string) ($record['merchant_slug'] ?? ''),
        (string) ($record['merchant'] ?? ''),
        (string) ($record['url'] ?? '')
    );
    $merchantSlug = trim((string) ($resolvedMerchant['merchant_slug'] ?? ''));
    if ($merchantSlug === '' && !empty($record['merchant'])) {
        $merchantSlug = aff_slugify_merchant((string) $record['merchant']);
    }

    $record['merchant_slug'] = $merchantSlug;
    $record['link_type'] = aff_normalize_link_type($record['link_type'] ?? '', (string) ($record['url'] ?? ''), 'affiliate');

    $merchantMeta = $resolvedMerchant ?? ($merchantSlug !== '' ? aff_merchant($merchantSlug) : null);
    if ($merchantMeta === null) {
        return $record;
    }

    $record['merchant'] = trim((string) ($merchantMeta['name'] ?? $record['merchant'] ?? ''));
    $record['merchant_meta'] = $merchantMeta;
    return $record;
}

function aff_load_registry(): array {
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $registry = [];
    foreach (AFF_REGISTRY_FILES as $registryFile) {
        if (!is_file($registryFile)) {
            continue;
        }

        $data = include $registryFile;
        if (!is_array($data)) {
            continue;
        }

        foreach ($data as $code => $row) {
            $code = trim((string) $code);
            if ($code === '') {
                continue;
            }

            $row = is_array($row) ? $row : [];
            $url = trim((string) ($row['url'] ?? ''));
            if ($url !== '' && !preg_match('~^https?://~i', $url)) {
                $url = '';
            }

            $existing = $registry[$code] ?? [];
            $record = aff_merge_merchant_meta([
                'code' => $code,
                'url' => $url,
                'merchant' => trim((string) ($row['merchant'] ?? ($existing['merchant'] ?? ''))),
                'merchant_slug' => trim((string) ($row['merchant_slug'] ?? ($existing['merchant_slug'] ?? ''))),
                'product_slug' => trim((string) ($row['product_slug'] ?? ($existing['product_slug'] ?? ''))),
                'link_type' => $row['link_type'] ?? ($existing['link_type'] ?? ''),
                'source' => trim((string) ($row['source'] ?? basename($registryFile))),
            ]);

            $registry[$code] = array_replace($existing, $record);
        }
    }

    foreach (AFF_FILES as $fileName) {
        $path = null;
        foreach (AFF_DIRS as $dir) {
            $candidate = $dir . '/' . $fileName;
            if (is_file($candidate) && is_readable($candidate)) {
                $path = $candidate;
                break;
            }
        }

        if ($path === null) {
            continue;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            continue;
        }

        $first = fgets($handle);
        if ($first === false) {
            fclose($handle);
            continue;
        }

        $delimiter = aff_detect_delim($first);
        rewind($handle);
        $headers = fgetcsv($handle, 0, $delimiter, '"', '\\') ?: [];

        $codeIndex = null;
        $linkIndex = null;
        $merchantIndex = null;
        $merchantSlugIndex = null;
        $productSlugIndex = null;
        $linkTypeIndex = null;

        foreach ($headers as $index => $header) {
            $header = strtolower(trim((string) $header, " \t\n\r\0\x0B\"'"));
            if ($header === 'code') { $codeIndex = $index; }
            if ($header === 'deeplink' || $header === 'deeplink_url' || $header === 'url') { $linkIndex = $index; }
            if ($header === 'merchant') { $merchantIndex = $index; }
            if ($header === 'merchant_slug') { $merchantSlugIndex = $index; }
            if ($header === 'product_slug') { $productSlugIndex = $index; }
            if ($header === 'link_type') { $linkTypeIndex = $index; }
        }

        if ($codeIndex === null || $linkIndex === null) {
            $codeIndex = 0;
            $linkIndex = 1;
        }

        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            if (!is_array($row)) {
                continue;
            }

            $code = trim((string) ($row[$codeIndex] ?? ''));
            $link = trim((string) ($row[$linkIndex] ?? ''));
            if ($code === '' || $link === '' || !preg_match('~^https?://~i', $link)) {
                continue;
            }
            if (str_contains($link, 'REPLACE_')) {
                continue;
            }

            $current = $registry[$code] ?? [
                'code' => $code,
                'merchant' => '',
                'merchant_slug' => '',
                'product_slug' => '',
                'link_type' => 'affiliate',
                'source' => basename($path),
            ];

            $merchant = $merchantIndex !== null ? trim((string) ($row[$merchantIndex] ?? '')) : trim((string) ($current['merchant'] ?? ''));
            $merchantSlug = $merchantSlugIndex !== null ? trim((string) ($row[$merchantSlugIndex] ?? '')) : trim((string) ($current['merchant_slug'] ?? ''));
            $productSlug = $productSlugIndex !== null ? trim((string) ($row[$productSlugIndex] ?? '')) : trim((string) ($current['product_slug'] ?? ''));
            $linkType = $linkTypeIndex !== null
                ? aff_normalize_link_type((string) ($row[$linkTypeIndex] ?? ''), $link, 'affiliate')
                : aff_detect_link_type_from_url($link, 'affiliate');

            $current['url'] = $link;
            $current['merchant'] = $merchant;
            $current['merchant_slug'] = $merchantSlug;
            $current['product_slug'] = $productSlug;
            $current['link_type'] = $linkType;
            $current['source'] = basename($path);
            $registry[$code] = aff_merge_merchant_meta($current);
        }

        fclose($handle);
    }

    $cache = $registry;
    return $cache;
}

function aff_registry(): array {
    return aff_load_registry();
}

function aff_record(string $code): ?array {
    $code = trim($code);
    if ($code === '') {
        return null;
    }

    $registry = aff_load_registry();
    return $registry[$code] ?? null;
}

function aff_link_type(?array $record): string {
    if (!is_array($record)) {
        return 'affiliate';
    }

    return aff_normalize_link_type($record['link_type'] ?? '', (string) ($record['url'] ?? ''), 'affiliate');
}

function aff_is_affiliate_record(?array $record): bool {
    if (!is_array($record)) {
        return false;
    }

    $url = trim((string) ($record['url'] ?? ''));
    if ($url === '') {
        return false;
    }

    return aff_link_type($record) === 'affiliate';
}

function aff_load_map(): array {
    $map = [];
    foreach (aff_load_registry() as $code => $record) {
        $url = trim((string) ($record['url'] ?? ''));
        if ($url === '' || !preg_match('~^https?://~i', $url)) {
            continue;
        }
        $map[$code] = $url;
    }
    return $map;
}

function aff_resolve(string $code): ?string {
    $record = aff_record($code);
    if ($record === null) {
        return null;
    }

    $url = trim((string) ($record['url'] ?? ''));
    return $url !== '' ? $url : null;
}

function aff_extract_final_url(string $url): string {
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    $parts = @parse_url($url);
    if (!is_array($parts)) {
        return $url;
    }

    $query = [];
    parse_str((string) ($parts['query'] ?? ''), $query);
    $candidate = trim((string) ($query['url'] ?? ''));
    if ($candidate === '') {
        return $url;
    }

    $candidate = rawurldecode($candidate);
    return preg_match('~^https?://~i', $candidate) ? $candidate : $url;
}

function aff_product_url_for_code(string $code): string {
    $record = aff_record($code);
    if (!is_array($record)) {
        return '';
    }

    $url = trim((string) ($record['url'] ?? ''));
    if ($url === '') {
        return '';
    }

    return aff_extract_final_url($url);
}

if (!function_exists('aff_is_valid_http_url')) {
    function aff_is_valid_http_url(mixed $value): bool {
        $url = trim((string) $value);
        return $url !== '' && preg_match('~^https?://~i', $url) === 1;
    }
}

if (!function_exists('aff_has_full_url_path')) {
    function aff_has_full_url_path(string $url): bool {
        $url = trim($url);
        if (!aff_is_valid_http_url($url)) {
            return false;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH));
        return $path !== '' && $path !== '/';
    }
}

if (!function_exists('aff_context_product_slug')) {
    function aff_context_product_slug(array $context): string {
        $productSlug = trim((string) ($context['product_slug'] ?? $context['slug'] ?? ''));
        return $productSlug;
    }
}

if (!function_exists('aff_context_merchant_slug')) {
function aff_context_merchant_slug(array $context): string {
    $resolvedMerchant = aff_resolve_merchant_meta(
        (string) ($context['merchant_slug'] ?? ''),
        (string) ($context['merchant'] ?? ''),
        aff_context_direct_url($context)
    );

    if (is_array($resolvedMerchant)) {
        return trim((string) ($resolvedMerchant['merchant_slug'] ?? ''));
    }

    $merchantSlug = trim((string) ($context['merchant_slug'] ?? ''));
    if ($merchantSlug !== '') {
        return aff_slugify_merchant($merchantSlug);
    }

    $merchant = trim((string) ($context['merchant'] ?? ''));
    return $merchant !== '' ? aff_slugify_merchant($merchant) : '';
}
}

if (!function_exists('aff_context_direct_url')) {
    function aff_context_direct_url(array $context): string {
        $fallbackUrl = trim((string) ($context['fallback_url'] ?? ''));
        if (aff_has_full_url_path($fallbackUrl)) {
            return $fallbackUrl;
        }

        return '';
    }
}

if (!function_exists('aff_context_explicit_click_url')) {
    function aff_context_explicit_click_url(array $context): array {
        $fields = ['click_url', 'affiliate_url'];
        foreach ($fields as $field) {
            $url = trim((string) ($context[$field] ?? ''));
            if (!aff_is_valid_http_url($url)) {
                continue;
            }

            return [
                'field' => $field,
                'url' => $url,
            ];
        }

        return ['field' => '', 'url' => ''];
    }
}

if (!function_exists('aff_find_best_record_for_context')) {
    function aff_find_best_record_for_context(array $context): ?array {
        $code = trim((string) ($context['code'] ?? $context['affiliate_code'] ?? ''));
        if ($code !== '') {
            $record = aff_record($code);
            if (is_array($record)) {
                $record['code'] = trim((string) ($record['code'] ?? $code));
                return $record;
            }
        }

        $productSlug = aff_context_product_slug($context);
        $directUrl = aff_context_direct_url($context);
        $merchantSlug = aff_context_merchant_slug($context);
        $merchant = trim((string) ($context['merchant'] ?? ''));
        if ($code === '' && !aff_is_supported_affiliate_merchant($merchantSlug, $merchant, $directUrl)) {
            return null;
        }

        $directHost = aff_extract_host_from_url($directUrl);
        $bestRecord = null;
        $bestScore = -1;
        $normalizeText = static function (string $value): string {
            $value = trim($value);
            if ($value === '') {
                return '';
            }

            return function_exists('mb_strtolower')
                ? mb_strtolower($value, 'UTF-8')
                : strtolower($value);
        };

        foreach (aff_registry() as $registryCode => $record) {
            if (!is_array($record)) {
                continue;
            }

            $recordProductSlug = trim((string) ($record['product_slug'] ?? ''));
            $recordMerchantSlug = trim((string) ($record['merchant_slug'] ?? ''));
            $recordMerchant = trim((string) ($record['merchant'] ?? ''));
            $recordUrl = trim((string) ($record['url'] ?? ''));
            if (!aff_is_valid_http_url($recordUrl)) {
                continue;
            }

            $recordDirectUrl = aff_extract_final_url($recordUrl);
            $recordMatchUrl = $recordDirectUrl !== '' ? $recordDirectUrl : $recordUrl;
            $recordHost = aff_extract_host_from_url($recordMatchUrl);
            $score = -1;

            if ($directUrl !== '' && aff_urls_match($recordMatchUrl, $directUrl)) {
                $score = 30;
            } elseif ($productSlug !== '' && $recordProductSlug !== '' && $recordProductSlug === $productSlug) {
                $score = 20;
            } else {
                continue;
            }

            if ($merchantSlug !== '') {
                if ($recordMerchantSlug !== '' && $recordMerchantSlug === $merchantSlug) {
                    $score += 5;
                } elseif ($recordMerchantSlug !== '') {
                    continue;
                } elseif ($recordHost !== '' && $directHost !== '' && $recordHost === $directHost) {
                    $score += 3;
                }
            }

            if ($merchant !== '') {
                if ($recordMerchant !== '' && $normalizeText($recordMerchant) === $normalizeText($merchant)) {
                    $score += 2;
                } elseif ($merchantSlug === '' && $recordMerchant !== '') {
                    continue;
                }
            }

            if ($score <= $bestScore) {
                continue;
            }

            $record['code'] = trim((string) ($record['code'] ?? $registryCode));
            $bestRecord = $record;
            $bestScore = $score;
        }

        return $bestRecord;
    }
}

if (!function_exists('aff_build_click_target')) {
    function aff_build_click_target(array $input): array {
        $href = trim((string) ($input['href'] ?? ''));
        $status = trim((string) ($input['status'] ?? ''));
        $isAffiliate = !empty($input['is_affiliate']);

        return [
            'href' => $href,
            'resolved_url' => trim((string) ($input['resolved_url'] ?? $href)),
            'direct_url' => trim((string) ($input['direct_url'] ?? '')),
            'affiliate_url' => trim((string) ($input['affiliate_url'] ?? '')),
            'code' => trim((string) ($input['code'] ?? '')),
            'status' => $status !== '' ? $status : ($isAffiliate ? 'affiliate_ready' : 'direct_fallback'),
            'is_affiliate' => $isAffiliate,
            'source' => trim((string) ($input['source'] ?? '')),
            'rel' => trim((string) ($input['rel'] ?? ($isAffiliate ? 'nofollow sponsored' : 'nofollow'))),
            'label' => trim((string) ($input['label'] ?? ($isAffiliate ? 'Do obchodu' : 'Pozriet produkt'))),
            'note' => trim((string) ($input['note'] ?? '')),
        ];
    }
}

if (!function_exists('interessa_dognet_dt_map')) {
    function interessa_dognet_dt_map(): array {
        return [
            'gymbeam' => 'lpejamq',
            'protein' => '3dDnWjP6',
            'imunoklub' => 'htqL0IFR',
            'ironaesthetics' => 'ilmqYrP6',
            'symprove' => 'tuohKPmo',
        ];
    }
}

if (!function_exists('interessa_affiliate_clean_url')) {
    function interessa_affiliate_clean_url(string $url): string {
        $url = trim($url);
        if ($url === '' || !aff_is_valid_http_url($url)) {
            return '';
        }

        $cleanUrl = trim(aff_extract_final_url($url));
        if ($cleanUrl === '' || !aff_is_valid_http_url($cleanUrl)) {
            $cleanUrl = $url;
        }

        $parts = parse_url($cleanUrl);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        $scheme = strtolower((string) $parts['scheme']);
        $host = (string) $parts['host'];
        $path = (string) ($parts['path'] ?? '');
        $queryParams = [];
        parse_str((string) ($parts['query'] ?? ''), $queryParams);
        unset($queryParams['utm_term']);
        $query = $queryParams !== [] ? http_build_query($queryParams) : '';

        return $scheme . '://' . $host . $path . ($query !== '' ? '?' . $query : '');
    }
}

if (!function_exists('interessa_affiliate_link')) {
    function interessa_affiliate_link(string $url, string $merchant): string {
        $merchant = trim($merchant);
        $cleanUrl = interessa_affiliate_clean_url($url);
        if ($cleanUrl === '') {
            return '';
        }

        $merchantMeta = aff_supported_affiliate_merchant_meta($merchant, $merchant, $cleanUrl);
        $merchantSlug = trim((string) ($merchantMeta['merchant_slug'] ?? ''));
        $dtMap = interessa_dognet_dt_map();
        $dtCode = trim((string) ($dtMap[$merchantSlug] ?? ''));
        if ($dtCode !== '') {
            return 'https://go.dognet.com/?dt=' . rawurlencode($dtCode) . '&url=' . rawurlencode($cleanUrl);
        }

        return $cleanUrl;
    }
}

if (!function_exists('aff_debug_click_target')) {
    function aff_debug_click_target(array $context, array $target): void {
        $status = trim((string) ($target['status'] ?? ''));
        $source = trim((string) ($target['source'] ?? ''));
        $fallbackUrl = trim((string) ($context['fallback_url'] ?? ''));
        $hasExplicitClick = trim((string) ($context['click_url'] ?? '')) !== '';
        $hasExplicitAffiliate = trim((string) ($context['affiliate_url'] ?? '')) !== '';

        $shouldLog = $status === 'direct_fallback'
            || $status === 'missing'
            || ($fallbackUrl !== '' && trim((string) ($target['href'] ?? '')) !== '' && trim((string) ($target['href'] ?? '')) !== $fallbackUrl && !$hasExplicitClick && !$hasExplicitAffiliate);
        if (!$shouldLog) {
            return;
        }

        $payload = [
            'product_slug' => trim((string) ($context['product_slug'] ?? $context['slug'] ?? '')),
            'affiliate_code' => trim((string) ($context['affiliate_code'] ?? $context['code'] ?? '')),
            'click_url' => trim((string) ($context['click_url'] ?? '')),
            'affiliate_url' => trim((string) ($context['affiliate_url'] ?? '')),
            'fallback_url' => $fallbackUrl,
            'fallback_has_full_path' => aff_has_full_url_path($fallbackUrl),
            'status' => $status,
            'source' => $source,
            'href' => trim((string) ($target['href'] ?? '')),
            'direct_url' => trim((string) ($target['direct_url'] ?? '')),
            'affiliate_target' => trim((string) ($target['affiliate_url'] ?? '')),
        ];

        error_log('[affiliate-resolve] ' . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}

if (!function_exists('aff_resolve_click_target')) {
    function aff_resolve_click_target(array $context): array {
        $contextCode = trim((string) ($context['code'] ?? $context['affiliate_code'] ?? ''));
        $directUrl = aff_context_direct_url($context);
        $clickUrl = trim((string) ($context['click_url'] ?? ''));
        if (aff_is_valid_http_url($clickUrl)) {
            $target = aff_build_click_target([
                'href' => $clickUrl,
                'resolved_url' => $clickUrl,
                'direct_url' => $directUrl,
                'affiliate_url' => $clickUrl,
                'code' => $contextCode,
                'status' => 'affiliate_ready',
                'is_affiliate' => true,
                'source' => 'explicit-click_url',
            ]);
            aff_debug_click_target($context, $target);
            return $target;
        }

        $affiliateUrl = trim((string) ($context['affiliate_url'] ?? ''));
        if (aff_is_valid_http_url($affiliateUrl)) {
            $target = aff_build_click_target([
                'href' => $affiliateUrl,
                'resolved_url' => $affiliateUrl,
                'direct_url' => $directUrl !== '' ? $directUrl : aff_extract_final_url($affiliateUrl),
                'affiliate_url' => $affiliateUrl,
                'code' => $contextCode,
                'status' => 'affiliate_ready',
                'is_affiliate' => true,
                'source' => 'explicit-affiliate_url',
            ]);
            aff_debug_click_target($context, $target);
            return $target;
        }

        $preferRegistry = !empty($context['prefer_registry']);
        $record = $preferRegistry ? aff_find_best_record_for_context($context) : null;
        if ($preferRegistry && $record !== null) {
            $recordCode = trim((string) ($record['code'] ?? $contextCode));
            $resolvedUrl = $recordCode !== '' ? (aff_resolve($recordCode) ?? '') : trim((string) ($record['url'] ?? ''));
            $resolvedUrl = trim((string) $resolvedUrl);
            $recordDirectUrl = aff_extract_final_url($resolvedUrl);
            $affiliateReady = $resolvedUrl !== '' && aff_link_type($record) === 'affiliate';

            if ($affiliateReady) {
                $target = aff_build_click_target([
                    'href' => $recordCode !== '' ? '/go/' . rawurlencode($recordCode) : $resolvedUrl,
                    'resolved_url' => $resolvedUrl,
                    'direct_url' => $directUrl !== '' ? $directUrl : $recordDirectUrl,
                    'affiliate_url' => $resolvedUrl,
                    'code' => $recordCode,
                    'status' => 'affiliate_ready',
                    'is_affiliate' => true,
                    'source' => $contextCode !== '' ? 'registry-code' : 'registry-product-merchant',
                ]);
                aff_debug_click_target($context, $target);
                return $target;
            }

            if ($recordDirectUrl !== '' && aff_has_full_url_path($recordDirectUrl)) {
                $target = aff_build_click_target([
                    'href' => $recordDirectUrl,
                    'resolved_url' => $recordDirectUrl,
                    'direct_url' => $directUrl !== '' ? $directUrl : $recordDirectUrl,
                    'affiliate_url' => '',
                    'code' => $recordCode,
                    'status' => 'direct_fallback',
                    'is_affiliate' => false,
                    'source' => 'registry-direct',
                ]);
                aff_debug_click_target($context, $target);
                return $target;
            }
        }

        if ($directUrl !== '') {
            $target = aff_build_click_target([
                'href' => $directUrl,
                'resolved_url' => $directUrl,
                'direct_url' => $directUrl,
                'affiliate_url' => '',
                'code' => $contextCode,
                'status' => 'direct_fallback',
                'is_affiliate' => false,
                'source' => 'fallback-url',
            ]);
            aff_debug_click_target($context, $target);
            return $target;
        }

        $target = aff_build_click_target([
            'href' => '',
            'resolved_url' => '',
            'direct_url' => '',
            'affiliate_url' => '',
            'code' => $contextCode,
            'status' => 'missing',
            'is_affiliate' => false,
            'source' => 'missing',
            'rel' => '',
            'label' => 'Coskoro',
        ]);
        aff_debug_click_target($context, $target);
        return $target;
    }
}

if (!function_exists('interessa_affiliate_target')) {
    function interessa_affiliate_target(array $row): array {
        if (function_exists('interessa_resolve_product_reference')) {
            $row = interessa_resolve_product_reference($row);
        }

        $target = aff_resolve_click_target($row);
        $merchant = trim((string) ($row['merchant_slug'] ?? $row['merchant'] ?? ''));
        $directUrl = trim((string) ($target['direct_url'] ?? ''));
        $targetCode = trim((string) ($target['code'] ?? ''));

        if (!empty($target['is_affiliate']) && $targetCode !== '') {
            $target['href'] = '/go/' . rawurlencode($targetCode);
            $target['rel'] = 'nofollow sponsored';
            $target['label'] = trim((string) ($target['label'] ?? 'Do obchodu')) ?: 'Do obchodu';
        } elseif ($directUrl !== '') {
            $wrappedHref = interessa_affiliate_link($directUrl, $merchant);
            if ($wrappedHref !== '') {
                $target['href'] = $wrappedHref;
                $target['rel'] = 'nofollow sponsored';
                $target['label'] = 'Do obchodu';
            }
        }

        return $target;
    }
}
