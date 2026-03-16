<?php

declare(strict_types=1);

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

if (!function_exists('interessa_admin_feed_xml_value')) {
    function interessa_admin_feed_xml_value(SimpleXMLElement $item, string $tag): string {
        return trim((string) ($item->{$tag} ?? ''));
    }
}

if (!function_exists('interessa_admin_parse_feed_file')) {
    function interessa_admin_parse_feed_file(string $path, string $merchantSlug, int $limit = 0): array {
        if (!is_file($path)) {
            throw new RuntimeException('Feed subor neexistuje.');
        }

        if (interessa_admin_feed_is_json($path)) {
            return interessa_admin_parse_json_feed($path, $merchantSlug, $limit);
        }

        return interessa_admin_feed_is_xml($path)
            ? interessa_admin_parse_xml_feed($path, $merchantSlug, $limit)
            : interessa_admin_parse_csv_feed($path, $merchantSlug, $limit);
    }
}

if (!function_exists('interessa_admin_parse_xml_feed')) {
    function interessa_admin_parse_xml_feed(string $path, string $merchantSlug, int $limit = 0): array {
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

            $slug = $merchantSlug . '-' . interessa_admin_feed_slugify($name);
            $url = interessa_admin_feed_xml_value($item, 'URL');
            $rows[] = [
                'slug' => $slug,
                'name' => $name,
                'merchant' => ucfirst($merchantSlug),
                'merchant_slug' => $merchantSlug,
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

            if ($limit > 0 && count($rows) >= $limit) {
                break;
            }
        }

        $reader->close();
        return $rows;
    }
}

if (!function_exists('interessa_admin_parse_csv_feed')) {
    function interessa_admin_parse_csv_feed(string $path, string $merchantSlug, int $limit = 0): array {
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

            $slug = $merchantSlug . '-' . interessa_admin_feed_slugify($name);
            $rows[] = [
                'slug' => $slug,
                'name' => $name,
                'merchant' => ucfirst($merchantSlug),
                'merchant_slug' => $merchantSlug,
                'category' => trim((string) ($record['category'] ?? $record['category_text'] ?? '')),
                'product_type' => trim((string) ($record['product_type'] ?? $record['type'] ?? '')),
                'price' => trim((string) ($record['price'] ?? $record['price_vat'] ?? '')),
                'url' => trim((string) ($record['deeplink'] ?? $record['url'] ?? '')),
                'fallback_url' => trim((string) ($record['deeplink'] ?? $record['url'] ?? '')),
                'image_remote_src' => trim((string) ($record['image'] ?? $record['image_url'] ?? '')),
                'summary' => trim((string) ($record['category'] ?? $record['category_text'] ?? '')),
                'merchant_product_id' => trim((string) ($record['id'] ?? $record['product_id'] ?? '')),
                'ean' => trim((string) ($record['ean'] ?? '')),
                'feed_source' => 'csv',
            ];

            if ($limit > 0 && count($rows) >= $limit) {
                break;
            }
        }

        fclose($handle);
        return $rows;
    }
}

if (!function_exists('interessa_admin_parse_json_feed')) {
    function interessa_admin_parse_json_feed(string $path, string $merchantSlug, int $limit = 0): array {
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

            $slug = $merchantSlug . '-' . interessa_admin_feed_slugify($name);
            $url = trim((string) ($item['url'] ?? $item['product_url'] ?? $item['link'] ?? ''));
            $rows[] = [
                'slug' => $slug,
                'name' => $name,
                'merchant' => ucfirst($merchantSlug),
                'merchant_slug' => $merchantSlug,
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
