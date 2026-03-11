<?php
declare(strict_types=1);

require_once __DIR__ . '/products.php';

if (!function_exists('interessa_affiliate_disclosure_text')) {
    function interessa_affiliate_disclosure_text(): string {
        return 'Niektoré odkazy na tejto stránke vedú na partnerské obchody. Ak cez ne nakúpiš, web môže získať províziu bez navýšenia ceny pre teba.';
    }
}

if (!function_exists('interessa_render_affiliate_disclosure')) {
    function interessa_render_affiliate_disclosure(?string $text = null, string $class = 'affiliate-disclosure'): string {
        $text = trim((string) ($text ?? interessa_affiliate_disclosure_text()));
        if ($text === '') {
            return '';
        }

        return '<p class="' . esc($class) . '">' . esc($text) . '</p>';
    }
}

if (!function_exists('interessa_affiliate_cta_html')) {
    function interessa_affiliate_cta_html(array $row, array $options = []): string {
        $row = interessa_resolve_product_reference($row);
        $target = interessa_affiliate_target($row);
        $class = trim((string) ($options['class'] ?? 'btn btn-cta')) ?: 'btn btn-cta';
        $label = trim((string) ($options['label'] ?? $target['label'] ?? 'Pozrieť ponuku')) ?: 'Pozrieť ponuku';

        if ($target['href'] === '') {
            return '<button class="' . esc($class) . '" type="button" disabled>' . esc($label) . '</button>';
        }

        return '<a class="' . esc($class) . '" href="' . esc($target['href']) . '" target="_blank" rel="' . esc($target['rel']) . '">' . esc($label) . '</a>';
    }
}

if (!function_exists('interessa_render_product_box')) {
    function interessa_render_product_box(array $row, array $options = []): string {
        $row = interessa_resolve_product_reference($row);
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            return '';
        }

        $image = is_array($row['_image'] ?? null) ? $row['_image'] : null;
        $summary = trim((string) ($row['subtitle'] ?? ''));
        $productName = trim((string) ($row['product_name'] ?? ''));
        $showProductName = $productName !== '' && strcasecmp($productName, $name) !== 0;
        $bestFor = trim((string) ($row['best_for'] ?? ''));
        $pros = is_array($row['pros'] ?? null) ? array_values($row['pros']) : [];
        $cons = is_array($row['cons'] ?? null) ? array_values($row['cons']) : [];
        $merchant = trim((string) ($row['merchant'] ?? ''));
        $showDisclosure = (bool) ($options['show_disclosure'] ?? false);

        $html = '<article class="affiliate-product-box">';
        if ($image !== null) {
            $html .= '<div class="affiliate-product-media">' . interessa_render_image($image, ['class' => 'affiliate-product-image']) . '</div>';
        }
        $html .= '<div class="affiliate-product-body">';
        $html .= '<h3>' . esc($name) . '</h3>';
        if ($summary !== '') {
            $html .= '<p class="affiliate-product-summary">' . esc($summary) . '</p>';
        }
        if ($showProductName) {
            $html .= '<p class="affiliate-product-product-name"><strong>Produkt v obchode:</strong> ' . esc($productName) . '</p>';
        }
        if ($bestFor !== '') {
            $html .= '<p class="affiliate-product-bestfor"><strong>Najlepšie pre:</strong> ' . esc($bestFor) . '</p>';
        }
        if ($merchant !== '') {
            $html .= '<p class="affiliate-product-merchant">Obchod: ' . esc($merchant) . '</p>';
        }
        if ($pros !== [] || $cons !== []) {
            $html .= '<div class="affiliate-product-columns">';
            if ($pros !== []) {
                $html .= '<div><h4>Plusy</h4><ul>';
                foreach ($pros as $item) {
                    $html .= '<li>' . esc((string) $item) . '</li>';
                }
                $html .= '</ul></div>';
            }
            if ($cons !== []) {
                $html .= '<div><h4>Mínusy</h4><ul>';
                foreach ($cons as $item) {
                    $html .= '<li>' . esc((string) $item) . '</li>';
                }
                $html .= '</ul></div>';
            }
            $html .= '</div>';
        }
        $html .= '<div class="affiliate-product-actions">' . interessa_affiliate_cta_html($row) . '</div>';
        if ($showDisclosure) {
            $html .= interessa_render_affiliate_disclosure();
        }
        $html .= '</div></article>';

        return $html;
    }
}

if (!function_exists('interessa_render_recommended_product')) {
    function interessa_render_recommended_product(array $row, array $options = []): string {
        $badge = trim((string) ($options['badge'] ?? 'Odporúčame')) ?: 'Odporúčame';
        $content = interessa_render_product_box($row, $options);
        if ($content === '') {
            return '';
        }

        return '<section class="affiliate-recommended"><div class="affiliate-recommended-badge">' . esc($badge) . '</div>' . $content . '</section>';
    }
}

if (!function_exists('interessa_render_comparison_table')) {
    function interessa_render_comparison_table(array $rows, array $columns): string {
        if ($rows === [] || $columns === []) {
            return '';
        }

        $html = '<div class="affiliate-comparison"><table><thead><tr>';
        foreach ($columns as $column) {
            $html .= '<th>' . esc((string) ($column['label'] ?? '')) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $resolved = interessa_resolve_product_reference(is_array($row) ? $row : []);
            $html .= '<tr>';
            foreach ($columns as $column) {
                $key = (string) ($column['key'] ?? '');
                $value = $resolved[$key] ?? '';
                if (is_array($value)) {
                    $value = implode(', ', array_map('strval', $value));
                }
                $html .= '<td>' . esc((string) $value) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';
        return $html;
    }
}