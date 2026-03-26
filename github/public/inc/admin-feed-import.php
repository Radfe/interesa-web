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

if (!function_exists('interessa_admin_feed_clean_product_url')) {
    function interessa_admin_feed_clean_product_url(string $url): string {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (function_exists('aff_extract_final_url')) {
            $url = trim((string) aff_extract_final_url($url));
        }

        if (!preg_match('~^https?://~i', $url)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts) || trim((string) ($parts['host'] ?? '')) === '') {
            return '';
        }

        $query = [];
        parse_str((string) ($parts['query'] ?? ''), $query);
        $cleanQuery = [];
        foreach ($query as $key => $value) {
            $normalizedKey = strtolower(trim((string) $key));
            if ($normalizedKey === '' || str_starts_with($normalizedKey, 'utm_') || in_array($normalizedKey, ['fbclid', 'gclid', 'source', 'aff', 'affiliate', 'campaign'], true)) {
                continue;
            }
            $cleanQuery[$key] = $value;
        }

        $clean = (string) ($parts['scheme'] ?? 'https') . '://' . (string) $parts['host'];
        if (isset($parts['port'])) {
            $clean .= ':' . (int) $parts['port'];
        }
        $path = trim((string) ($parts['path'] ?? ''));
        $clean .= $path !== '' ? $path : '/';
        if ($cleanQuery !== []) {
            $clean .= '?' . http_build_query($cleanQuery);
        }

        return $clean;
    }
}

if (!function_exists('interessa_admin_feed_normalize_price')) {
    function interessa_admin_feed_normalize_price(mixed $value): float {
        $price = trim((string) $value);
        if ($price === '') {
            return 0.0;
        }

        $price = preg_replace('~[^0-9,\.]~', '', $price) ?? '';
        if ($price === '') {
            return 0.0;
        }

        if (substr_count($price, ',') === 1 && substr_count($price, '.') === 0) {
            $price = str_replace(',', '.', $price);
        } elseif (substr_count($price, ',') > 0 && substr_count($price, '.') > 0) {
            $price = str_replace(',', '', $price);
        } else {
            $price = str_replace(',', '.', $price);
        }

        return max(0.0, round((float) $price, 2));
    }
}

if (!function_exists('interessa_admin_feed_normalize_category')) {
    function interessa_admin_feed_normalize_category(string $category = '', string $productType = '', string $name = ''): string {
        $haystack = strtolower(trim(implode(' ', array_filter([$category, $productType, $name]))));
        $map = [
            'pre-workout' => ['pre-workout', 'predtrening', 'pred trening', 'nakopavac', 'nakopávač', 'pump', 'stim'],
            'kreatin' => ['kreatin', 'creatine', 'creapure'],
            'proteiny' => ['protein', 'proteiny', 'whey', 'isolate', 'isolat', 'kasein', 'casein', 'gainer', 'vegan protein', 'vegansky protein'],
            'probiotika-travenie' => ['probiotik', 'traven', 'digest', 'gut', 'enzym'],
            'klby-koza' => ['kolagen', 'collagen', 'klby', 'kĺby', 'joint', 'glukosamin', 'glucosamine', 'msm'],
            'mineraly' => ['magnesium', 'magnez', 'horcik', 'mineraly', 'minerály', 'zinc', 'zinok', 'electroly'],
            'imunita' => ['imunita', 'immunity', 'vitamin c', 'vitamin d', 'immune'],
        ];

        foreach ($map as $normalized => $tokens) {
            foreach ($tokens as $token) {
                if ($token !== '' && str_contains($haystack, $token)) {
                    return normalize_category_slug($normalized);
                }
            }
        }

        return normalize_category_slug($category !== '' ? $category : $productType);
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
            $resolvedSlug = interessa_admin_feed_slugify($merchantSlug);
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
}

if (!function_exists('interessa_admin_feed_with_canonical_source')) {
    function interessa_admin_feed_with_canonical_source(array $row, array $merchantMeta): array {
        $row['campaign_id'] = (int) ($merchantMeta['campaign_id'] ?? 0);
        $row['feed_url'] = trim((string) ($merchantMeta['feed_url'] ?? ''));
        $row['affiliate_supported'] = !empty($merchantMeta['affiliate_supported']);
        return $row;
    }
}

if (!function_exists('interessa_admin_feed_canonical_source_url')) {
    function interessa_admin_feed_canonical_source_url(string $url, string $merchantSlug): string {
        $url = trim($url);
        if ($url !== '') {
            return $url;
        }

        $resolvedFeedUrl = function_exists('aff_supported_affiliate_feed_url')
            ? aff_supported_affiliate_feed_url($merchantSlug)
            : null;
        return trim((string) $resolvedFeedUrl);
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
        $url = interessa_admin_feed_canonical_source_url($url, $merchantSlug);
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
            $cleanUrl = interessa_admin_feed_clean_product_url($url);
            $rawCategory = interessa_admin_feed_xml_value($item, 'CATEGORYTEXT');
            $rawProductType = interessa_admin_feed_xml_value($item, 'CATEGORYNAME');
            $merchantMeta = interessa_admin_feed_merchant_identity($merchantSlug, $url);
            $resolvedMerchantSlug = (string) ($merchantMeta['merchant_slug'] ?? $merchantSlug);
            $resolvedMerchant = (string) ($merchantMeta['merchant'] ?? ucfirst($merchantSlug));
            $slug = $resolvedMerchantSlug . '-' . interessa_admin_feed_slugify($name);
            $row = [
                'slug' => $slug,
                'name' => $name,
                'merchant' => $resolvedMerchant,
                'merchant_slug' => $resolvedMerchantSlug,
                'category' => interessa_admin_feed_normalize_category($rawCategory, $rawProductType, $name),
                'product_type' => $rawProductType,
                'price' => interessa_admin_feed_normalize_price(interessa_admin_feed_xml_value($item, 'PRICE_VAT')),
                'url' => $cleanUrl,
                'fallback_url' => $cleanUrl,
                'image_remote_src' => interessa_admin_feed_xml_value($item, 'IMGURL'),
                'summary' => $rawCategory,
                'merchant_product_id' => interessa_admin_feed_xml_value($item, 'ITEM_ID'),
                'ean' => interessa_admin_feed_xml_value($item, 'EAN'),
                'feed_source' => 'xml',
            ];
            $row = interessa_admin_feed_with_canonical_source($row, $merchantMeta);

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
            $cleanUrl = interessa_admin_feed_clean_product_url($url);
            $rawCategory = trim((string) ($record['category'] ?? $record['category_text'] ?? ''));
            $rawProductType = trim((string) ($record['product_type'] ?? $record['type'] ?? ''));
            $merchantMeta = interessa_admin_feed_merchant_identity($merchantSlug, $url);
            $resolvedMerchantSlug = (string) ($merchantMeta['merchant_slug'] ?? $merchantSlug);
            $resolvedMerchant = (string) ($merchantMeta['merchant'] ?? ucfirst($merchantSlug));
            $slug = $resolvedMerchantSlug . '-' . interessa_admin_feed_slugify($name);
            $rowData = [
                'slug' => $slug,
                'name' => $name,
                'merchant' => $resolvedMerchant,
                'merchant_slug' => $resolvedMerchantSlug,
                'category' => interessa_admin_feed_normalize_category($rawCategory, $rawProductType, $name),
                'product_type' => $rawProductType,
                'price' => interessa_admin_feed_normalize_price((string) ($record['price'] ?? $record['price_vat'] ?? '')),
                'url' => $cleanUrl,
                'fallback_url' => $cleanUrl,
                'image_remote_src' => trim((string) ($record['image'] ?? $record['image_url'] ?? '')),
                'summary' => $rawCategory,
                'merchant_product_id' => trim((string) ($record['id'] ?? $record['product_id'] ?? '')),
                'ean' => trim((string) ($record['ean'] ?? '')),
                'feed_source' => 'csv',
            ];
            $rowData = interessa_admin_feed_with_canonical_source($rowData, $merchantMeta);

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
            $cleanUrl = interessa_admin_feed_clean_product_url($url);
            $rawCategory = trim((string) ($item['category'] ?? $item['category_text'] ?? ''));
            $rawProductType = trim((string) ($item['product_type'] ?? $item['type'] ?? ''));
            $merchantMeta = interessa_admin_feed_merchant_identity($merchantSlug, $url);
            $resolvedMerchantSlug = (string) ($merchantMeta['merchant_slug'] ?? $merchantSlug);
            $resolvedMerchant = (string) ($merchantMeta['merchant'] ?? ucfirst($merchantSlug));
            $slug = $resolvedMerchantSlug . '-' . interessa_admin_feed_slugify($name);
            $rowData = [
                'slug' => $slug,
                'name' => $name,
                'merchant' => $resolvedMerchant,
                'merchant_slug' => $resolvedMerchantSlug,
                'category' => interessa_admin_feed_normalize_category($rawCategory, $rawProductType, $name),
                'product_type' => $rawProductType,
                'price' => interessa_admin_feed_normalize_price((string) ($item['price'] ?? $item['price_vat'] ?? '')),
                'url' => $cleanUrl,
                'fallback_url' => $cleanUrl,
                'image_remote_src' => trim((string) ($item['image'] ?? $item['image_url'] ?? '')),
                'summary' => trim((string) ($item['summary'] ?? $item['description'] ?? '')),
                'merchant_product_id' => trim((string) ($item['id'] ?? $item['product_id'] ?? '')),
                'ean' => trim((string) ($item['ean'] ?? '')),
                'feed_source' => 'json',
            ];
            $rowData = interessa_admin_feed_with_canonical_source($rowData, $merchantMeta);

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
                'category' => $row['category'] ?? '',
                'price' => $row['price'] ?? 0,
                'url' => $row['url'] ?? $row['fallback_url'] ?? '',
                'fallback_url' => $row['fallback_url'] ?? '',
                'summary' => $row['summary'] ?? '',
                'image_remote_src' => $row['image_remote_src'] ?? '',
                'merchant_product_id' => $row['merchant_product_id'] ?? '',
                'feed_source' => $row['feed_source'] ?? '',
            ]);
            $imported[] = $slug;
        }

        if ($imported !== []) {
            interessa_admin_save_products($products);
        }

        return $imported;
    }
}

if (!function_exists('interessa_admin_feed_rows_to_affiliate_records')) {
    function interessa_admin_feed_rows_to_affiliate_records(array $rows): array {
        $records = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $productSlug = interessa_admin_slugify((string) ($row['slug'] ?? ''));
            $merchantSlug = interessa_admin_slugify((string) ($row['merchant_slug'] ?? ''));
            $url = trim((string) ($row['url'] ?? $row['fallback_url'] ?? ''));
            if ($productSlug === '' || $merchantSlug === '' || $url === '') {
                continue;
            }

            $code = $productSlug . '-' . $merchantSlug;
            $records[$code] = [
                'url' => $url,
                'merchant' => (string) ($row['merchant'] ?? ''),
                'merchant_slug' => $merchantSlug,
                'product_slug' => $productSlug,
                'link_type' => aff_detect_link_type_from_url($url, 'product'),
                'source' => !empty($row['affiliate_supported']) ? 'supported-affiliate-feed' : 'feed-import',
            ];
        }

        ksort($records);
        return $records;
    }
}

if (!function_exists('interessa_admin_supported_affiliate_merchants')) {
    function interessa_admin_supported_affiliate_merchants(): array {
        return function_exists('aff_supported_affiliate_merchants')
            ? aff_supported_affiliate_merchants()
            : [];
    }
}

if (!function_exists('interessa_admin_supported_affiliate_import_status')) {
    function interessa_admin_supported_affiliate_import_status(string $merchantSlug): array {
        $merchantSlug = interessa_admin_slugify($merchantSlug);
        $products = interessa_admin_products();
        $productCount = 0;
        $affiliateCount = 0;

        foreach ($products as $slug => $product) {
            if (!is_array($product)) {
                continue;
            }

            $normalizedMerchantSlug = interessa_admin_slugify((string) ($product['merchant_slug'] ?? ''));
            if ($normalizedMerchantSlug !== $merchantSlug) {
                continue;
            }

            $productCount++;
        }

        foreach (interessa_admin_affiliate_links() as $code => $record) {
            if (!is_array($record)) {
                continue;
            }

            $normalizedMerchantSlug = interessa_admin_slugify((string) ($record['merchant_slug'] ?? ''));
            if ($normalizedMerchantSlug !== $merchantSlug) {
                continue;
            }

            $affiliateCount++;
        }

        return [
            'product_count' => $productCount,
            'affiliate_count' => $affiliateCount,
            'state' => $productCount > 0 ? 'imported' : 'missing',
            'label' => $productCount > 0
                ? ('Naimportovane: ' . $productCount . ' produktov')
                : 'Zatial neimportovane',
        ];
    }
}

if (!function_exists('interessa_admin_import_supported_affiliate_feed')) {
    function interessa_admin_import_supported_affiliate_feed(string $merchantSlug, int $limit = 0): array {
        $merchantSlug = interessa_admin_slugify($merchantSlug);
        $merchantMeta = interessa_admin_supported_affiliate_merchants()[$merchantSlug] ?? null;
        if (!is_array($merchantMeta)) {
            throw new RuntimeException('Tento merchant nie je v podporovanom Dognet affiliate sete.');
        }

        $feedUrl = trim((string) ($merchantMeta['feed_url'] ?? ''));
        if ($feedUrl === '') {
            throw new RuntimeException('Merchant nema nastaveny canonical feed URL.');
        }

        $rows = interessa_admin_parse_feed_url($feedUrl, $merchantSlug, $limit);
        if ($rows === []) {
            return [
                'merchant_slug' => $merchantSlug,
                'merchant' => (string) ($merchantMeta['name'] ?? $merchantSlug),
                'campaign_id' => (int) ($merchantMeta['campaign_id'] ?? 0),
                'feed_url' => $feedUrl,
                'product_count' => 0,
                'affiliate_count' => 0,
            ];
        }

        $imported = interessa_admin_import_feed_products($rows);
        $affiliateRows = interessa_admin_feed_rows_to_affiliate_records($rows);
        $affiliateCount = interessa_admin_import_affiliate_rows($affiliateRows);

        return [
            'merchant_slug' => $merchantSlug,
            'merchant' => (string) ($merchantMeta['name'] ?? $merchantSlug),
            'campaign_id' => (int) ($merchantMeta['campaign_id'] ?? 0),
            'feed_url' => $feedUrl,
            'product_count' => count($imported),
            'affiliate_count' => $affiliateCount,
        ];
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
