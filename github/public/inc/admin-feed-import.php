<?php

declare(strict_types=1);

require_once __DIR__ . '/affiliates.php';

if (!function_exists('interessa_admin_feed_detect_delimiter')) {
    function interessa_admin_feed_detect_delimiter(string $line): string {
        if (str_contains($line, ';')) {
            return ';';
        }
        if (str_contains($line, "\t")) {
            return "\t";
        }
        return ',';
    }
}

if (!function_exists('interessa_admin_feed_slugify')) {
    function interessa_admin_feed_slugify(string $value): string {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (!is_string($ascii) || $ascii === '') {
            $ascii = $value;
        }

        $slug = preg_replace('~[^a-z0-9]+~i', '-', $ascii) ?? '';
        return strtolower(trim($slug, '-'));
    }
}

if (!function_exists('interessa_admin_feed_is_xml')) {
    function interessa_admin_feed_is_xml(string $path): bool {
        $head = file_get_contents($path, false, null, 0, 512);
        if (!is_string($head)) {
            return false;
        }

        return str_contains($head, '<?xml') || str_contains($head, '<SHOP');
    }
}

if (!function_exists('interessa_admin_feed_is_json')) {
    function interessa_admin_feed_is_json(string $path): bool {
        $head = file_get_contents($path, false, null, 0, 256);
        if (!is_string($head)) {
            return false;
        }

        $head = ltrim($head);
        return $head !== '' && ($head[0] === '{' || $head[0] === '[');
    }
}

if (!function_exists('interessa_admin_candidate_import_limit')) {
    function interessa_admin_candidate_import_limit(): int {
        // Prvy batch kandidatov ma byt maly a stabilny, nie cely feed naraz.
        return 40;
    }
}

if (!function_exists('interessa_admin_feed_xml_value')) {
    function interessa_admin_feed_xml_value(SimpleXMLElement $item, string $tag): string {
        return trim((string) ($item->{$tag} ?? ''));
    }
}

if (!function_exists('interessa_admin_feed_filter_terms')) {
    function interessa_admin_feed_filter_terms(string $filter): array {
        $filter = trim($filter);
        if ($filter === '') {
            return [];
        }

        $parts = preg_split('~[\s,;|]+~u', $filter) ?: [];
        $terms = [];
        foreach ($parts as $part) {
            $part = strtolower(trim((string) $part));
            if ($part !== '') {
                $terms[] = $part;
            }
        }

        return array_values(array_unique($terms));
    }
}

if (!function_exists('interessa_admin_feed_row_matches_filter')) {
    function interessa_admin_feed_row_matches_filter(array $row, array $terms): bool {
        if ($terms === []) {
            return true;
        }

        $haystack = strtolower(trim(implode(' ', array_filter([
            (string) ($row['name'] ?? ''),
            (string) ($row['category'] ?? ''),
            (string) ($row['product_type'] ?? ''),
            (string) ($row['summary'] ?? ''),
            (string) ($row['url'] ?? ''),
        ]))));

        foreach ($terms as $term) {
            if ($term !== '' && str_contains($haystack, $term)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('interessa_admin_feed_merchant_identity')) {
    function interessa_admin_feed_merchant_identity(string $merchantSlug, string $url = '', string $merchant = ''): array {
        $resolved = function_exists('aff_resolve_merchant_meta')
            ? aff_resolve_merchant_meta($merchantSlug, $merchant, $url)
            : null;

        $resolvedSlug = trim((string) ($resolved['merchant_slug'] ?? $merchantSlug));
        $resolvedName = trim((string) ($resolved['name'] ?? $merchant));

        if ($resolvedSlug === '') {
            $resolvedSlug = interessa_admin_feed_slugify($merchantSlug);
        }
        if ($resolvedName === '') {
            $resolvedName = $resolvedSlug !== '' ? ucfirst($resolvedSlug) : ucfirst(trim($merchantSlug));
        }

        return [
            'merchant_slug' => $resolvedSlug,
            'merchant' => $resolvedName,
        ];
    }
}

if (!function_exists('interessa_admin_parse_feed_file')) {
    function interessa_admin_parse_feed_file(string $path, string $merchantSlug, int $limit = 0, string $filter = ''): array {
        if (!is_file($path)) {
            throw new RuntimeException('Feed subor neexistuje.');
        }

        if (interessa_admin_feed_is_json($path)) {
            return interessa_admin_parse_json_feed($path, $merchantSlug, $limit, $filter);
        }

        return interessa_admin_feed_is_xml($path)
            ? interessa_admin_parse_xml_feed($path, $merchantSlug, $limit, $filter)
            : interessa_admin_parse_csv_feed($path, $merchantSlug, $limit, $filter);
    }
}

if (!function_exists('interessa_admin_resolve_curl_executable')) {
    function interessa_admin_resolve_curl_executable(): string {
        $systemRoot = rtrim((string) getenv('SystemRoot'), '\\/');
        foreach (array_filter([
            $systemRoot !== '' ? $systemRoot . '\\System32\\curl.exe' : '',
            $systemRoot !== '' ? $systemRoot . '\\Sysnative\\curl.exe' : '',
            'curl.exe',
        ]) as $candidate) {
            if ($candidate === 'curl.exe' || is_file((string) $candidate)) {
                return (string) $candidate;
            }
        }

        return 'curl.exe';
    }
}

if (!function_exists('interessa_admin_download_remote_feed_to_temp_file_via_curl_exe')) {
    function interessa_admin_download_remote_feed_to_temp_file_via_curl_exe(string $url, string $tempFile): void {
        if (!function_exists('exec')) {
            throw new RuntimeException('Systemove stiahnutie feedu nie je dostupne.');
        }

        $curlExecutable = interessa_admin_resolve_curl_executable();
        $command = escapeshellarg($curlExecutable) . ' -L --fail --silent --show-error '
            . '--output ' . escapeshellarg($tempFile) . ' '
            . escapeshellarg($url) . ' 2>&1';

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        if ($exitCode !== 0) {
            $message = trim((string) implode("\n", $output));
            throw new RuntimeException('Nepodarilo sa stiahnut feed: ' . ($message !== '' ? $message : 'systemove stiahnutie zlyhalo.'));
        }
    }
}

if (!function_exists('interessa_admin_download_remote_feed_to_temp_file')) {
    function interessa_admin_download_remote_feed_to_temp_file(string $url): string {
        $url = trim($url);
        if ($url === '' || !preg_match('~^https?://~i', $url)) {
            throw new RuntimeException('URL feedu nie je validna.');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'interessa-feed-');
        if ($tempFile === false) {
            throw new RuntimeException('Nepodarilo sa pripravit docasny subor pre feed.');
        }

        $downloaded = false;
        if (function_exists('curl_init')) {
            $fileHandle = fopen($tempFile, 'wb');
            if ($fileHandle === false) {
                @unlink($tempFile);
                throw new RuntimeException('Nepodarilo sa otvorit docasny subor pre feed.');
            }

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_FILE => $fileHandle,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_USERAGENT => 'InteresaAdmin/1.0',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/xml,text/xml,text/csv,application/json,text/plain,*/*',
                ],
            ]);
            $result = curl_exec($ch);
            if ($result === false) {
                $error = (string) curl_error($ch);
                fclose($fileHandle);
                curl_close($ch);
                @unlink($tempFile);
                throw new RuntimeException('Nepodarilo sa stiahnut feed: ' . $error);
            }
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            fclose($fileHandle);
            curl_close($ch);
            if ($status >= 400) {
                @unlink($tempFile);
                throw new RuntimeException('Feed vratil HTTP ' . $status . '.');
            }
            $downloaded = true;
        } else {
            if (strtoupper(substr(PHP_OS_FAMILY, 0, 7)) === 'WINDOWS') {
                interessa_admin_download_remote_feed_to_temp_file_via_curl_exe($url, $tempFile);
                $downloaded = true;
            } else {
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'timeout' => 60,
                        'follow_location' => 1,
                        'user_agent' => 'InteresaAdmin/1.0',
                        'header' => "Accept: application/xml,text/xml,text/csv,application/json,text/plain,*/*\r\n",
                    ],
                ]);
                $body = @file_get_contents($url, false, $context);
                if (is_string($body) && trim($body) !== '') {
                    file_put_contents($tempFile, $body);
                    $downloaded = true;
                }
            }
        }

        if (!$downloaded) {
            @unlink($tempFile);
            throw new RuntimeException('Feed sa nepodarilo stiahnut.');
        }

        $size = @filesize($tempFile);
        if (!is_int($size) || $size <= 0) {
            @unlink($tempFile);
            throw new RuntimeException('Feed je prazdny.');
        }

        return $tempFile;
    }
}

if (!function_exists('interessa_admin_parse_feed_url')) {
    function interessa_admin_parse_feed_url(string $url, string $merchantSlug, int $limit = 0, string $filter = ''): array {
        $tempFile = interessa_admin_download_remote_feed_to_temp_file($url);
        try {
            return interessa_admin_parse_feed_file($tempFile, $merchantSlug, $limit, $filter);
        } finally {
            @unlink($tempFile);
        }
    }
}

if (!function_exists('interessa_admin_parse_xml_feed')) {
    function interessa_admin_parse_xml_feed(string $path, string $merchantSlug, int $limit = 0, string $filter = ''): array {
        $terms = interessa_admin_feed_filter_terms($filter);
        $reader = new XMLReader();
        if (!$reader->open($path, 'UTF-8')) {
            throw new RuntimeException('XML feed sa nepodarilo otvorit.');
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

            $name = interessa_admin_feed_xml_value($item, 'PRODUCTNAME');
            if ($name === '') {
                $name = interessa_admin_feed_xml_value($item, 'PRODUCT');
            }
            if ($name === '') {
                continue;
            }

            $url = interessa_admin_feed_xml_value($item, 'URL');
            $merchantMeta = interessa_admin_feed_merchant_identity($merchantSlug, $url);
            $resolvedMerchantSlug = (string) ($merchantMeta['merchant_slug'] ?? $merchantSlug);
            $resolvedMerchant = (string) ($merchantMeta['merchant'] ?? ucfirst($merchantSlug));
            $slug = $resolvedMerchantSlug . '-' . interessa_admin_feed_slugify($name);
            $row = [
                'slug' => $slug,
                'name' => $name,
                'merchant' => $resolvedMerchant,
                'merchant_slug' => $resolvedMerchantSlug,
                'category' => interessa_admin_feed_xml_value($item, 'CATEGORYTEXT'),
                'product_type' => interessa_admin_feed_xml_value($item, 'CATEGORYNAME'),
                'price' => interessa_admin_feed_xml_value($item, 'PRICE_VAT'),
                'url' => $url,
                'fallback_url' => $url,
                'image_remote_src' => interessa_admin_feed_xml_value($item, 'IMGURL'),
                'summary' => interessa_admin_feed_xml_value($item, 'CATEGORYTEXT'),
                'merchant_product_id' => interessa_admin_feed_xml_value($item, 'ITEM_ID'),
                'ean' => interessa_admin_feed_xml_value($item, 'EAN'),
                'feed_source' => 'xml',
            ];

            if (!interessa_admin_feed_row_matches_filter($row, $terms)) {
                continue;
            }

            $rows[] = $row;

            if ($limit > 0 && count($rows) >= $limit) {
                break;
            }
        }

        $reader->close();
        return $rows;
    }
}

if (!function_exists('interessa_admin_parse_csv_feed')) {
    function interessa_admin_parse_csv_feed(string $path, string $merchantSlug, int $limit = 0, string $filter = ''): array {
        $terms = interessa_admin_feed_filter_terms($filter);
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException('CSV feed sa nepodarilo otvorit.');
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return [];
        }

        $delimiter = interessa_admin_feed_detect_delimiter($firstLine);
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
            $merchantMeta = interessa_admin_feed_merchant_identity($merchantSlug, $url);
            $resolvedMerchantSlug = (string) ($merchantMeta['merchant_slug'] ?? $merchantSlug);
            $resolvedMerchant = (string) ($merchantMeta['merchant'] ?? ucfirst($merchantSlug));
            $slug = $resolvedMerchantSlug . '-' . interessa_admin_feed_slugify($name);
            $rowData = [
                'slug' => $slug,
                'name' => $name,
                'merchant' => $resolvedMerchant,
                'merchant_slug' => $resolvedMerchantSlug,
                'category' => trim((string) ($record['category'] ?? $record['category_text'] ?? '')),
                'product_type' => trim((string) ($record['product_type'] ?? $record['type'] ?? '')),
                'price' => trim((string) ($record['price'] ?? $record['price_vat'] ?? '')),
                'url' => $url,
                'fallback_url' => $url,
                'image_remote_src' => trim((string) ($record['image'] ?? $record['image_url'] ?? '')),
                'summary' => trim((string) ($record['category'] ?? $record['category_text'] ?? '')),
                'merchant_product_id' => trim((string) ($record['id'] ?? $record['product_id'] ?? '')),
                'ean' => trim((string) ($record['ean'] ?? '')),
                'feed_source' => 'csv',
            ];

            if (!interessa_admin_feed_row_matches_filter($rowData, $terms)) {
                continue;
            }

            $rows[] = $rowData;

            if ($limit > 0 && count($rows) >= $limit) {
                break;
            }
        }

        fclose($handle);
        return $rows;
    }
}

if (!function_exists('interessa_admin_parse_json_feed')) {
    function interessa_admin_parse_json_feed(string $path, string $merchantSlug, int $limit = 0, string $filter = ''): array {
        $terms = interessa_admin_feed_filter_terms($filter);
        $json = file_get_contents($path);
        $data = json_decode((string) $json, true);
        if (!is_array($data)) {
            throw new RuntimeException('JSON feed sa nepodarilo precitat.');
        }

        $items = $data;
        if (isset($data['products']) && is_array($data['products'])) {
            $items = $data['products'];
        } elseif (isset($data['items']) && is_array($data['items'])) {
            $items = $data['items'];
        }

        $rows = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['name'] ?? $item['title'] ?? $item['product'] ?? ''));
            if ($name === '') {
                continue;
            }

            $url = trim((string) ($item['url'] ?? $item['product_url'] ?? $item['link'] ?? ''));
            $merchantMeta = interessa_admin_feed_merchant_identity($merchantSlug, $url);
            $resolvedMerchantSlug = (string) ($merchantMeta['merchant_slug'] ?? $merchantSlug);
            $resolvedMerchant = (string) ($merchantMeta['merchant'] ?? ucfirst($merchantSlug));
            $slug = $resolvedMerchantSlug . '-' . interessa_admin_feed_slugify($name);
            $rowData = [
                'slug' => $slug,
                'name' => $name,
                'merchant' => $resolvedMerchant,
                'merchant_slug' => $resolvedMerchantSlug,
                'category' => trim((string) ($item['category'] ?? $item['category_text'] ?? '')),
                'product_type' => trim((string) ($item['product_type'] ?? $item['type'] ?? '')),
                'price' => trim((string) ($item['price'] ?? $item['price_vat'] ?? '')),
                'url' => $url,
                'fallback_url' => $url,
                'image_remote_src' => trim((string) ($item['image'] ?? $item['image_url'] ?? '')),
                'summary' => trim((string) ($item['summary'] ?? $item['description'] ?? '')),
                'merchant_product_id' => trim((string) ($item['id'] ?? $item['product_id'] ?? '')),
                'ean' => trim((string) ($item['ean'] ?? '')),
                'feed_source' => 'json',
            ];

            if (!interessa_admin_feed_row_matches_filter($rowData, $terms)) {
                continue;
            }

            $rows[] = $rowData;

            if ($limit > 0 && count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }
}

if (!function_exists('interessa_admin_import_feed_products')) {
    function interessa_admin_import_feed_products(array $rows): array {
        $products = interessa_admin_products();
        $imported = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $slug = interessa_admin_slugify((string) ($row['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $products[$slug] = interessa_admin_normalize_product_record($slug, [
                'name' => $row['name'] ?? '',
                'brand' => $row['merchant'] ?? '',
                'merchant' => $row['merchant'] ?? '',
                'merchant_slug' => $row['merchant_slug'] ?? '',
                'fallback_url' => $row['fallback_url'] ?? '',
                'summary' => $row['summary'] ?? '',
                'image_remote_src' => $row['image_remote_src'] ?? '',
            ]);
            $imported[] = $slug;
        }

        if ($imported !== []) {
            interessa_admin_save_products($products);
        }

        return $imported;
    }
}

if (!function_exists('interessa_admin_parse_affiliate_csv_file')) {
    function interessa_admin_parse_affiliate_csv_file(string $path): array {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException('Affiliate CSV sa nepodarilo otvorit.');
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return [];
        }

        $delimiter = interessa_admin_feed_detect_delimiter($firstLine);
        rewind($handle);
        $headers = fgetcsv($handle, 0, $delimiter, '"', '\\') ?: [];
        $headers = array_map(static fn($value) => strtolower(trim((string) $value)), $headers);
        $rows = [];

        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            if (!is_array($row)) {
                continue;
            }

            $record = array_combine($headers, array_pad($row, count($headers), '')) ?: [];
            $code = interessa_admin_slugify((string) ($record['code'] ?? ''));
            $url = trim((string) ($record['deeplink_url'] ?? $record['deeplink'] ?? $record['url'] ?? ''));
            if ($code === '' || $url === '') {
                continue;
            }

            $rows[$code] = [
                'url' => $url,
                'merchant' => (string) ($record['merchant'] ?? ''),
                'merchant_slug' => (string) ($record['merchant_slug'] ?? ''),
                'product_slug' => (string) ($record['product_slug'] ?? ''),
                'link_type' => (string) ($record['link_type'] ?? 'affiliate'),
                'source' => basename($path),
            ];
        }

        fclose($handle);
        ksort($rows);
        return $rows;
    }
}

if (!function_exists('interessa_admin_import_affiliate_rows')) {
    function interessa_admin_import_affiliate_rows(array $rows): int {
        $links = interessa_admin_affiliate_links();
        $count = 0;
        foreach ($rows as $code => $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalizedCode = interessa_admin_slugify((string) $code);
            if ($normalizedCode === '') {
                continue;
            }
            $links[$normalizedCode] = interessa_admin_normalize_affiliate_record($normalizedCode, $row);
            $count++;
        }

        if ($count > 0) {
            interessa_admin_save_affiliate_links($links);
        }

        return $count;
    }
}
