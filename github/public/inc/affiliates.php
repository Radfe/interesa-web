<?php
declare(strict_types=1);

const AFF_DIRS = [
    __DIR__ . '/../content/affiliates',
    __DIR__ . '/..',
];
const AFF_FILES = ['affiliate_simple_edit.csv', 'affiliate_links.csv'];

function aff_detect_delim(string $headerLine): string {
    if (str_contains($headerLine, ';')) { return ';'; }
    if (str_contains($headerLine, "\t")) { return "\t"; }
    return ',';
}

function aff_load_map(): array {
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $map = [];
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

            $map[$code] = $link;
        }

        fclose($handle);
    }

    $cache = $map;
    return $cache;
}

function aff_resolve(string $code): ?string {
    $map = aff_load_map();
    return $map[$code] ?? null;
}