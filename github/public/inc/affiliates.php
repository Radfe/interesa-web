<?php
declare(strict_types=1);

const AFF_DIRS = [
    __DIR__ . '/../content/affiliates',
    __DIR__ . '/..',
];
const AFF_FILES = ['affiliate_simple_edit.csv', 'affiliate_links.csv'];
const AFF_REGISTRY_FILE = __DIR__ . '/../content/affiliates/links.php';

function aff_detect_delim(string $headerLine): string {
    if (str_contains($headerLine, ';')) { return ';'; }
    if (str_contains($headerLine, "\t")) { return "\t"; }
    return ',';
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

                $registry[$code] = [
                    'code' => $code,
                    'url' => $url,
                    'merchant' => trim((string) ($row['merchant'] ?? '')),
                    'product_slug' => trim((string) ($row['product_slug'] ?? '')),
                    'source' => trim((string) ($row['source'] ?? 'php-registry')),
                ];
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
        $headers = fgetcsv($handle, 0, $delimiter) ?: [];

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

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
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
                'product_slug' => '',
                'source' => basename($path),
            ];
            $current['url'] = $link;
            $current['source'] = basename($path);
            $registry[$code] = $current;
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