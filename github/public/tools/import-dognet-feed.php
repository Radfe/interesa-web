<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This tool is intended for CLI usage only.\n");
    exit(1);
}

$source = $argv[1] ?? '';
$merchant = $argv[2] ?? 'merchant';
if (!is_string($source) || trim($source) === '' || !is_file($source)) {
    fwrite(STDERR, "Usage: php public/tools/import-dognet-feed.php <feed-file> <merchant-slug>\n");
    exit(1);
}

function dognet_detect_delimiter(string $line): string {
    if (str_contains($line, ';')) {
        return ';';
    }
    if (str_contains($line, "\t")) {
        return "\t";
    }
    return ',';
}

$handle = fopen($source, 'r');
if ($handle === false) {
    fwrite(STDERR, "Unable to open feed file.\n");
    exit(1);
}

$firstLine = fgets($handle);
if ($firstLine === false) {
    fclose($handle);
    fwrite(STDERR, "Feed file is empty.\n");
    exit(1);
}

$delimiter = dognet_detect_delimiter($firstLine);
rewind($handle);
$headers = fgetcsv($handle, 0, $delimiter, '"', '\\') ?: [];
$headers = array_map(static fn($value) => strtolower(trim((string) $value)), $headers);
$rows = [];

while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
    if (!is_array($row)) {
        continue;
    }

    $record = array_combine($headers, array_pad($row, count($headers), '')) ?: [];
    $name = trim((string) ($record['name'] ?? $record['product'] ?? ''));
    if ($name === '') {
        continue;
    }

    $slug = strtolower(trim(preg_replace('~[^a-z0-9]+~i', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name), '-'));
    $rows[] = [
        'name' => $name,
        'slug' => $merchant . '-' . $slug,
        'merchant_slug' => $merchant,
        'image_url' => trim((string) ($record['image'] ?? $record['image_url'] ?? '')),
        'affiliate_url' => trim((string) ($record['deeplink'] ?? $record['url'] ?? '')),
        'merchant_product_id' => trim((string) ($record['id'] ?? $record['product_id'] ?? '')),
    ];
}

fclose($handle);
echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;