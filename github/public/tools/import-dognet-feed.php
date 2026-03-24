<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/affiliates.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This tool is intended for CLI usage only.\n");
    exit(1);
}

$source = $argv[1] ?? '';
$merchant = $argv[2] ?? 'merchant';
$limitArg = $argv[3] ?? '';
$limit = is_numeric($limitArg) ? max(0, (int) $limitArg) : 0;

if (!is_string($source) || trim($source) === '') {
    $source = 'auto';
}

if ($source === 'auto') {
    $source = function_exists('aff_supported_affiliate_feed_url')
        ? aff_supported_affiliate_feed_url($merchant)
        : '';
}

if (!is_string($source) || trim($source) === '' || (!is_file($source) && !preg_match('~^https?://~i', $source))) {
    fwrite(STDERR, "Usage: php public/tools/import-dognet-feed.php <feed-file|feed-url|auto> <merchant-slug> [limit]\n");
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

function dognet_slugify(string $value): string {
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if (!is_string($ascii) || $ascii === '') {
        $ascii = $value;
    }

    $slug = preg_replace('~[^a-z0-9]+~i', '-', $ascii) ?? '';
    return strtolower(trim($slug, '-'));
}

function dognet_merchant_identity(string $merchantSlug, string $url = '', string $merchant = ''): array {
    $resolved = function_exists('aff_supported_affiliate_merchant_meta')
        ? aff_supported_affiliate_merchant_meta($merchantSlug, $merchant, $url)
        : null;

    if (!is_array($resolved) && function_exists('aff_resolve_merchant_meta')) {
        $resolved = aff_resolve_merchant_meta($merchantSlug, $merchant, $url);
    }

    $resolvedSlug = trim((string) ($resolved['merchant_slug'] ?? $merchantSlug));
    $resolvedName = trim((string) ($resolved['name'] ?? $merchant));
    $feedUrl = trim((string) ($resolved['feed_url'] ?? ''));
    $campaignId = (int) ($resolved['campaign_id'] ?? 0);
    $affiliateSupported = !empty($resolved['affiliate_supported'])
        && trim((string) ($resolved['network'] ?? '')) === 'dognet'
        && $feedUrl !== ''
        && $campaignId > 0;

    if ($resolvedSlug === '') {
        $resolvedSlug = dognet_slugify($merchantSlug);
    }
    if ($resolvedName === '') {
        $resolvedName = $resolvedSlug !== '' ? ucfirst($resolvedSlug) : ucfirst(trim($merchantSlug));
    }

    return [
        'merchant_slug' => $resolvedSlug,
        'merchant' => $resolvedName,
        'campaign_id' => $campaignId,
        'feed_url' => $feedUrl,
        'affiliate_supported' => $affiliateSupported,
    ];
}

function dognet_download_source_if_needed(string $source): string {
    $source = trim($source);
    if ($source === '' || !preg_match('~^https?://~i', $source)) {
        return $source;
    }

    $tempFile = tempnam(sys_get_temp_dir(), 'dognet-tool-');
    if ($tempFile === false) {
        fwrite(STDERR, "Unable to prepare temp feed file.\n");
        exit(1);
    }

    $body = @file_get_contents($source);
    if (!is_string($body) || trim($body) === '') {
        @unlink($tempFile);
        fwrite(STDERR, "Unable to download feed URL.\n");
        exit(1);
    }

    file_put_contents($tempFile, $body);
    return $tempFile;
}

function dognet_cleanup_source(string $originalSource, string $workingSource): void {
    if ($workingSource !== $originalSource && is_file($workingSource)) {
        @unlink($workingSource);
    }
}

function dognet_with_canonical_source(array $row, array $merchantMeta): array {
    $row['campaign_id'] = (int) ($merchantMeta['campaign_id'] ?? 0);
    $row['feed_url'] = trim((string) ($merchantMeta['feed_url'] ?? ''));
    $row['affiliate_supported'] = !empty($merchantMeta['affiliate_supported']);
    return $row;
}

function dognet_is_xml_feed(string $path): bool {
    $head = file_get_contents($path, false, null, 0, 512);
    if (!is_string($head)) {
        return false;
    }

    return str_contains($head, '<?xml') || str_contains($head, '<SHOP');
}

function dognet_xml_value(SimpleXMLElement $item, string $tag): string {
    $result = $item->{$tag} ?? null;
    return trim((string) $result);
}

function dognet_import_xml(string $source, string $merchant, int $limit = 0): array {
    $reader = new XMLReader();
    if (!$reader->open($source, 'UTF-8')) {
        fwrite(STDERR, "Unable to open XML feed.\n");
        exit(1);
    }

    $rows = [];
    while ($reader->read()) {
        if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'SHOPITEM') {
            continue;
        }

        $xml = $reader->readOuterXML();
        if (!is_string($xml) || trim($xml) === '') {
            continue;
        }

        $item = simplexml_load_string($xml);
        if (!$item instanceof SimpleXMLElement) {
            continue;
        }

        $name = dognet_xml_value($item, 'PRODUCTNAME');
        if ($name === '') {
            $name = dognet_xml_value($item, 'PRODUCT');
        }
        if ($name === '') {
            continue;
        }

        $url = dognet_xml_value($item, 'URL');
        $merchantMeta = dognet_merchant_identity($merchant, $url, dognet_xml_value($item, 'MANUFACTURER'));
        $resolvedMerchantSlug = (string) ($merchantMeta['merchant_slug'] ?? $merchant);
        $resolvedMerchant = (string) ($merchantMeta['merchant'] ?? ucfirst($merchant));
        $productSlug = $resolvedMerchantSlug . '-' . dognet_slugify($name);
        $rows[] = [
            'name' => $name,
            'slug' => $productSlug,
            'merchant_slug' => $resolvedMerchantSlug,
            'merchant' => $resolvedMerchant,
            'image_url' => dognet_xml_value($item, 'IMGURL'),
            'affiliate_url' => $url,
            'raw_url' => $url,
            'merchant_product_id' => dognet_xml_value($item, 'ITEM_ID'),
            'ean' => dognet_xml_value($item, 'EAN'),
            'category_text' => dognet_xml_value($item, 'CATEGORYTEXT'),
            'feed_source' => 'heureka-xml',
            'affiliate_candidate' => str_contains($url, 'dognet'),
        ];
        $rows[array_key_last($rows)] = dognet_with_canonical_source($rows[array_key_last($rows)], $merchantMeta);

        if ($limit > 0 && count($rows) >= $limit) {
            break;
        }
    }

    $reader->close();
    return $rows;
}

function dognet_import_csv(string $source, string $merchant, int $limit = 0): array {
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

        $url = trim((string) ($record['deeplink'] ?? $record['url'] ?? ''));
        $merchantMeta = dognet_merchant_identity($merchant, $url);
        $resolvedMerchantSlug = (string) ($merchantMeta['merchant_slug'] ?? $merchant);
        $resolvedMerchant = (string) ($merchantMeta['merchant'] ?? ucfirst($merchant));
        $slug = dognet_slugify($name);
        $rows[] = [
            'name' => $name,
            'slug' => $resolvedMerchantSlug . '-' . $slug,
            'merchant_slug' => $resolvedMerchantSlug,
            'merchant' => $resolvedMerchant,
            'image_url' => trim((string) ($record['image'] ?? $record['image_url'] ?? '')),
            'affiliate_url' => $url,
            'raw_url' => trim((string) ($record['url'] ?? '')),
            'merchant_product_id' => trim((string) ($record['id'] ?? $record['product_id'] ?? '')),
            'ean' => trim((string) ($record['ean'] ?? '')),
            'category_text' => trim((string) ($record['category'] ?? $record['category_text'] ?? '')),
            'feed_source' => 'csv',
            'affiliate_candidate' => true,
        ];
        $rows[array_key_last($rows)] = dognet_with_canonical_source($rows[array_key_last($rows)], $merchantMeta);

        if ($limit > 0 && count($rows) >= $limit) {
            break;
        }
    }

    fclose($handle);
    return $rows;
}

$workingSource = dognet_download_source_if_needed($source);
try {
    $rows = dognet_is_xml_feed($workingSource)
        ? dognet_import_xml($workingSource, $merchant, $limit)
        : dognet_import_csv($workingSource, $merchant, $limit);
} finally {
    dognet_cleanup_source($source, $workingSource);
}

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
