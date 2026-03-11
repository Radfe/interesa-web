<?php
declare(strict_types=1);

const AFF_DIRS = [
    __DIR__ . '/../content/affiliates',
    __DIR__ . '/..',
];
const AFF_FILES = ['affiliate_simple_edit.csv', 'affiliate_links.csv'];
const AFF_REGISTRY_FILE = __DIR__ . '/../content/affiliates/links.php';
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

    $merchantMeta = $merchantSlug !== '' ? aff_merchant($merchantSlug) : null;
    if ($merchantMeta === null) {
        $record['merchant_slug'] = $merchantSlug;
        return $record;
    }

    $record['merchant_slug'] = $merchantSlug;
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
    if (is_file(AFF_REGISTRY_FILE)) {
        $data = include AFF_REGISTRY_FILE;
        if (is_array($data)) {
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

                $registry[$code] = aff_merge_merchant_meta([
                    'code' => $code,
                    'url' => $url,
                    'merchant' => trim((string) ($row['merchant'] ?? '')),
                    'merchant_slug' => trim((string) ($row['merchant_slug'] ?? '')),
                    'product_slug' => trim((string) ($row['product_slug'] ?? '')),
                    'source' => trim((string) ($row['source'] ?? 'php-registry')),
                ]);
            }
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
        foreach ($headers as $index => $header) {
            $header = strtolower(trim((string) $header, " \t\n\r\0\x0B\"'"));
            if ($header === 'code') { $codeIndex = $index; }
            if ($header === 'deeplink' || $header === 'url') { $linkIndex = $index; }
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
                'source' => basename($path),
            ];
            $current['url'] = $link;
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