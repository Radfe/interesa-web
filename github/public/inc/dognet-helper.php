<?php
declare(strict_types=1);

require_once __DIR__ . '/affiliates.php';

if (!function_exists('dognet_helper_csv_path')) {
    function dognet_helper_csv_path(): string {
        return dirname(__DIR__) . '/storage/dognet/gymbeam-first-batch-template.csv';
    }
}

if (!function_exists('dognet_helper_overrides_path')) {
    function dognet_helper_overrides_path(): string {
        return dirname(__DIR__) . '/content/affiliates/links_overrides.php';
    }
}

if (!function_exists('dognet_helper_campaign_url')) {
    function dognet_helper_campaign_url(): string {
        return 'https://app.dognet.com/campaigns/detail/4101';
    }
}

if (!function_exists('dognet_helper_detect_delimiter')) {
    function dognet_helper_detect_delimiter(string $line): string {
        if (str_contains($line, ';')) {
            return ';';
        }
        if (str_contains($line, "\t")) {
            return "\t";
        }
        return ',';
    }
}

if (!function_exists('dognet_helper_load_rows')) {
    function dognet_helper_load_rows(): array {
        $path = dognet_helper_csv_path();
        if (!is_file($path)) {
            return ['headers' => [], 'rows' => []];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException('Nepodarilo sa otvorit GymBeam CSV sablonu.');
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return ['headers' => [], 'rows' => []];
        }

        $delimiter = dognet_helper_detect_delimiter($firstLine);
        rewind($handle);
        $headers = fgetcsv($handle, 0, $delimiter, '"', '\\') ?: [];
        $headers = array_map(static fn($value) => trim((string) $value), $headers);
        $rows = [];

        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            if (!is_array($row)) {
                continue;
            }

            $assoc = array_combine($headers, array_pad($row, count($headers), '')) ?: [];
            foreach ($assoc as $key => $value) {
                $assoc[$key] = trim((string) $value);
            }

            $code = trim((string) ($assoc['code'] ?? ''));
            $record = $code !== '' ? aff_record($code) : null;
            if (($assoc['deeplink_url'] ?? '') === '' && aff_is_affiliate_record($record)) {
                $assoc['deeplink_url'] = trim((string) ($record['url'] ?? ''));
                $assoc['link_type'] = 'affiliate';
            }

            $assoc['_is_complete'] = (($assoc['deeplink_url'] ?? '') !== '');
            $assoc['_status'] = $assoc['_is_complete'] ? 'hotovo' : 'caka';
            $rows[] = $assoc;
        }

        fclose($handle);
        return ['headers' => $headers, 'rows' => $rows];
    }
}

if (!function_exists('dognet_helper_write_rows')) {
    function dognet_helper_write_rows(array $headers, array $rows): void {
        $path = dognet_helper_csv_path();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            throw new RuntimeException('Chyba priecinok pre Dognet helper data.');
        }

        $tmpPath = $path . '.tmp';
        $handle = fopen($tmpPath, 'w');
        if ($handle === false) {
            throw new RuntimeException('Nepodarilo sa zapisat GymBeam CSV sablonu.');
        }

        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = (string) ($row[$header] ?? '');
            }
            fputcsv($handle, $line);
        }

        fclose($handle);
        if (!rename($tmpPath, $path)) {
            @unlink($tmpPath);
            throw new RuntimeException('Nepodarilo sa ulozit GymBeam CSV sablonu.');
        }
    }
}

if (!function_exists('dognet_helper_sync_overrides')) {
    function dognet_helper_sync_overrides(array $rows): void {
        $path = dognet_helper_overrides_path();
        $overrides = is_file($path) ? include $path : [];
        if (!is_array($overrides)) {
            $overrides = [];
        }

        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            $deeplink = trim((string) ($row['deeplink_url'] ?? ''));
            if ($code === '' || $deeplink === '' || !preg_match('~^https?://~i', $deeplink)) {
                continue;
            }

            $existing = is_array($overrides[$code] ?? null) ? $overrides[$code] : [];
            $overrides[$code] = [
                'url' => $deeplink,
                'merchant' => trim((string) ($row['merchant'] ?? ($existing['merchant'] ?? ''))),
                'merchant_slug' => trim((string) ($row['merchant_slug'] ?? ($existing['merchant_slug'] ?? ''))),
                'product_slug' => trim((string) ($row['product_slug'] ?? ($existing['product_slug'] ?? ''))),
                'link_type' => 'affiliate',
                'source' => 'dognet-helper',
            ];
        }

        ksort($overrides);
        $content = "<?php\n";
        $content .= "declare(strict_types=1);\n\n";
        $content .= 'return ' . var_export($overrides, true) . ";\n";
        file_put_contents($path, $content);
    }
}

if (!function_exists('dognet_helper_save_deeplink')) {
    function dognet_helper_save_deeplink(string $code, string $deeplinkUrl): void {
        $code = trim($code);
        $deeplinkUrl = trim($deeplinkUrl);

        if ($code === '') {
            throw new RuntimeException('Chyba kod produktu.');
        }
        if ($deeplinkUrl === '' || !preg_match('~^https?://~i', $deeplinkUrl)) {
            throw new RuntimeException('Zadaj platny Dognet deeplink.');
        }

        $table = dognet_helper_load_rows();
        $headers = $table['headers'];
        $rows = $table['rows'];
        $found = false;

        foreach ($rows as &$row) {
            if (trim((string) ($row['code'] ?? '')) !== $code) {
                continue;
            }

            $row['deeplink_url'] = $deeplinkUrl;
            $row['link_type'] = 'affiliate';
            $row['_is_complete'] = true;
            $row['_status'] = 'hotovo';
            $found = true;
            break;
        }
        unset($row);

        if (!$found) {
            throw new RuntimeException('Produktovy kod sa v helperi nenasiel.');
        }

        dognet_helper_write_rows($headers, $rows);
        dognet_helper_sync_overrides($rows);
    }
}

if (!function_exists('dognet_helper_find_row')) {
    function dognet_helper_find_row(array $rows, ?string $code): ?array {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        foreach ($rows as $row) {
            if (trim((string) ($row['code'] ?? '')) === $code) {
                return $row;
            }
        }

        return null;
    }
}

if (!function_exists('dognet_helper_first_pending')) {
    function dognet_helper_first_pending(array $rows): ?array {
        foreach ($rows as $row) {
            if (empty($row['_is_complete'])) {
                return $row;
            }
        }

        return $rows[0] ?? null;
    }
}

if (!function_exists('dognet_helper_next_pending_code')) {
    function dognet_helper_next_pending_code(array $rows, string $currentCode = ''): ?string {
        $currentCode = trim($currentCode);
        $foundCurrent = ($currentCode === '');

        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            if ($code === '') {
                continue;
            }

            if (!$foundCurrent) {
                if ($code === $currentCode) {
                    $foundCurrent = true;
                }
                continue;
            }

            if (empty($row['_is_complete']) && $code !== $currentCode) {
                return $code;
            }
        }

        foreach ($rows as $row) {
            if (empty($row['_is_complete'])) {
                return trim((string) ($row['code'] ?? '')) ?: null;
            }
        }

        return null;
    }
}