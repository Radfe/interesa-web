<?php
declare(strict_types=1);

/**
 * Loader affiliate kódov z CSV (čiarka aj bodkočiarka).
 */
const AFF_DIR = __DIR__ . '/../content/affiliates';
const AFF_FILES = ['affiliate_simple_edit.csv', 'affiliate_links.csv']; // simple_edit má prioritu

function aff_detect_delim(string $headerLine): string {
    if (str_contains($headerLine, ';')) return ';';
    if (str_contains($headerLine, "\t")) return "\t";
    return ',';
}

function aff_load_map(): array {
    static $cache = null;
    if (is_array($cache)) return $cache;

    $map = [];
    foreach (AFF_FILES as $fname) {
        $path = AFF_DIR . '/' . $fname;
        if (!is_file($path) || !is_readable($path)) continue;

        $fh = fopen($path, 'r');
        if (!$fh) continue;

        $first = fgets($fh);
        if ($first === false) { fclose($fh); continue; }
        $delim = aff_detect_delim($first);
        rewind($fh);

        $headers = fgetcsv($fh, 0, $delim) ?: [];
        $idxCode = null; $idxLink = null;
        foreach ($headers as $i => $h) {
            $h = strtolower(trim($h, " \t\n\r\0\x0B\"'"));
            if ($h === 'code') $idxCode = $i;
            if ($h === 'deeplink') $idxLink = $i;
        }
        if ($idxCode === null || $idxLink === null) { $idxCode = 0; $idxLink = 1; }

        while (($row = fgetcsv($fh, 0, $delim)) !== false) {
            if (!is_array($row)) continue;
            $code = trim((string)($row[$idxCode] ?? ''));
            $link = trim((string)($row[$idxLink] ?? ''));
            if ($code === '' || $link === '') continue;
            if (!preg_match('~^https?://~i', $link)) continue;
            $map[$code] = $link;
        }
        fclose($fh);
    }
    return $cache = $map;
}

function aff_resolve(string $code): ?string {
    $map = aff_load_map();
    return $map[$code] ?? null;
}
