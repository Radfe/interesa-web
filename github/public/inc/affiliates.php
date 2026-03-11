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

function aff_merchant(string $slug): ?array {
    $slug = trim($slug);
    if ($slug === '') {
        return null;
    }

    $merchants = aff_load_merchants();
    return $merchants[$slug] ?? null;
}

function aff_merge_merchant_meta(array $record): array {
    $merchantSlug = trim((string) ($record['merchant_slug'] ?? ''));
    if ($merchantSlug === '' && !empty($record['merchant'])) {
        $merchantSlug = aff_slugify_merchant((string) $record['merchant']);
    }

    $record['merchant_slug'] = $merchantSlug;
    $record['link_type'] = aff_normalize_link_type($record['link_type'] ?? '', (string) ($record['url'] ?? ''), 'affiliate');

    $merchantMeta = $merchantSlug !== '' ? aff_merchant($merchantSlug) : null;
    if ($merchantMeta === null) {
        return $record;
    }

    $record['merchant'] = trim((string) ($record['merchant'] ?? $merchantMeta['name'] ?? ''));
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
