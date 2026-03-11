<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This tool is intended for CLI usage only.\n");
    exit(1);
}

$source = $argv[1] ?? '';
$merchantFilter = $argv[2] ?? '';
if (!is_string($source) || trim($source) === '' || !is_file($source)) {
    fwrite(STDERR, "Usage: php public/tools/import-dognet-links.php <csv-file> [merchant-slug]\n");
    exit(1);
}

function dognet_links_detect_delimiter(string $line): string {
    if (str_contains($line, ';')) {
        return ';';
    }
    if (str_contains($line, "\t")) {
        return "\t";
    }
    return ',';
}

function dognet_links_detect_type(string $url, string $explicit = ''): string {
    $explicit = trim(strtolower($explicit));
    if ($explicit === 'affiliate' || $explicit === 'product') {
        return $explicit;
    }

    if (str_contains($url, 'dognet') || str_contains($url, 'utm_term=dognet')) {
        return 'affiliate';
    }

    return 'affiliate';
}

$handle = fopen($source, 'r');
if ($handle === false) {
    fwrite(STDERR, "Unable to open CSV file.\n");
    exit(1);
}

$firstLine = fgets($handle);
if ($firstLine === false) {
    fclose($handle);
    fwrite(STDERR, "CSV file is empty.\n");
    exit(1);
}

$delimiter = dognet_links_detect_delimiter($firstLine);
rewind($handle);
$headers = fgetcsv($handle, 0, $delimiter, '"', '\\') ?: [];
$headers = array_map(static fn($value) => strtolower(trim((string) $value)), $headers);
$rows = [];

while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
    if (!is_array($row)) {
        continue;
    }

    $record = array_combine($headers, array_pad($row, count($headers), '')) ?: [];
    $code = trim((string) ($record['code'] ?? ''));
    $url = trim((string) ($record['deeplink_url'] ?? $record['deeplink'] ?? $record['url'] ?? ''));
    $merchantSlug = trim((string) ($record['merchant_slug'] ?? ''));
    if ($merchantFilter !== '' && $merchantFilter !== $merchantSlug) {
        continue;
    }
    if ($code === '' || $url === '' || !preg_match('~^https?://~i', $url)) {
        continue;
    }

    $rows[$code] = [
        'url' => $url,
        'merchant_slug' => $merchantSlug,
        'merchant' => trim((string) ($record['merchant'] ?? '')),
        'product_slug' => trim((string) ($record['product_slug'] ?? '')),
        'link_type' => dognet_links_detect_type($url, (string) ($record['link_type'] ?? '')),
        'source' => basename($source),
    ];
}

fclose($handle);
ksort($rows);
echo '<?php' . PHP_EOL;
echo 'declare(strict_types=1);' . PHP_EOL . PHP_EOL;
echo 'return ' . var_export($rows, true) . ';' . PHP_EOL;
