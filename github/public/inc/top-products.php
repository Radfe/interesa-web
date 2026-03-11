<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/affiliate-ui.php';

if (!function_exists('interessa_top_product_link')) {
    function interessa_top_product_link(array $row): array {
        return interessa_affiliate_target($row);
    }
}

if (!function_exists('interessa_top_product_absolute_url')) {
    function interessa_top_product_absolute_url(array $row): ?string {
        $target = interessa_affiliate_target($row);
        $href = trim((string) ($target['href'] ?? ''));
        if ($href === '') {
            return null;
        }
        if (preg_match('~^https?://~i', $href)) {
            return $href;
        }
        return absolute_url($href);
    }
}

if (!function_exists('interessa_render_stars')) {
    function interessa_render_stars(float $rating): string {
        $rating = max(0.0, min(5.0, $rating));
        $filled = (int) round($rating);
        $stars = str_repeat('&#9733;', $filled) . str_repeat('&#9734;', max(0, 5 - $filled));
        return '<span class="top-product-stars" aria-hidden="true">' . $stars . '</span><span class="top-product-score">' . esc(number_format($rating, 1)) . '/5</span>';
    }
}

if (!function_exists('interessa_top_products_schema')) {
    function interessa_top_products_schema(array $products, string $title, string $pagePath): ?array {
        $items = [];

        foreach ($products as $index => $row) {
            $resolved = interessa_resolve_product_reference($row);
            $name = trim((string) ($resolved['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $product = [
                '@type' => 'Product',
                'name' => $name,
            ];

            $subtitle = trim((string) ($resolved['subtitle'] ?? $resolved['summary'] ?? ''));
            if ($subtitle !== '') {
                $product['description'] = $subtitle;
            }

            $image = is_array($resolved['_image'] ?? null) ? $resolved['_image'] : null;
            if ($image !== null && !empty($image['src'])) {
                $product['image'] = absolute_url((string) $image['src']);
            }

            $url = interessa_top_product_absolute_url($resolved);
            if ($url !== null) {
                $product['url'] = $url;
            }

            $rating = (float) ($resolved['rating'] ?? 0);
            if ($rating > 0) {
                $product['aggregateRating'] = [
                    '@type' => 'AggregateRating',
                    'ratingValue' => number_format($rating, 1, '.', ''),
                    'bestRating' => '5',
                    'ratingCount' => '1',
                ];
            }

            $items[] = [
                '@type' => 'ListItem',
                'position' => (int) ($index + 1),
                'item' => $product,
            ];
        }

        if ($items === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $title,
            'url' => absolute_url($pagePath),
            'numberOfItems' => count($items),
            'itemListElement' => $items,
        ];
    }
}

if (!function_exists('interessa_render_top_products')) {
    function interessa_render_top_products(array $products, string $title = 'Top produkty', ?string $intro = null): void {
        if ($products === []) {
            return;
        }

        $resolvedProducts = [];
        foreach ($products as $row) {
            $resolvedProducts[] = interessa_resolve_product_reference($row);
        }

        echo '<section class="topbox">';
        echo '<div class="topbox-head">';
        echo '<h2>' . esc($title) . '</h2>';
        if ($intro !== null && $intro !== '') {
            echo '<p class="topbox-intro">' . esc($intro) . '</p>';
        }
        echo '</div>';
        echo '<div class="top-products-grid">';

        foreach ($resolvedProducts as $index => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $subtitle = trim((string) ($row['subtitle'] ?? $row['summary'] ?? ''));
            $merchant = trim((string) ($row['merchant'] ?? ''));
            $bestFor = trim((string) ($row['best_for'] ?? ''));
            $rating = (float) ($row['rating'] ?? 0);
            $pros = is_array($row['pros'] ?? null) ? array_values($row['pros']) : [];
            $cons = is_array($row['cons'] ?? null) ? array_values($row['cons']) : [];
            $image = is_array($row['_image'] ?? null) ? $row['_image'] : interessa_product_image_meta(trim((string) ($row['slug'] ?? '')), [], true);

            echo '<article class="top-product-card">';
            echo '<div class="top-product-rank">#' . (int) ($index + 1) . '</div>';
            echo interessa_render_image($image, ['class' => 'top-product-image']);
            echo '<div class="top-product-body">';
            echo '<h3>' . esc($name) . '</h3>';
            if ($subtitle !== '') {
                echo '<p class="top-product-subtitle">' . esc($subtitle) . '</p>';
            }
            if ($bestFor !== '') {
                echo '<div class="top-product-bestfor"><span>Najlepsie pre:</span> ' . esc($bestFor) . '</div>';
            }
            if ($rating > 0) {
                echo '<div class="top-product-rating">' . interessa_render_stars($rating) . '</div>';
            }
            if ($merchant !== '') {
                echo '<div class="top-product-merchant">Obchod: ' . esc($merchant) . '</div>';
            }
            if ($pros !== [] || $cons !== []) {
                echo '<div class="top-product-highlights">';
                if ($pros !== []) {
                    echo '<div class="top-product-list is-pros"><div class="top-product-list-title">Plusy</div><ul>';
                    foreach ($pros as $item) {
                        echo '<li>' . esc((string) $item) . '</li>';
                    }
                    echo '</ul></div>';
                }
                if ($cons !== []) {
                    echo '<div class="top-product-list is-cons"><div class="top-product-list-title">Minusy</div><ul>';
                    foreach ($cons as $item) {
                        echo '<li>' . esc((string) $item) . '</li>';
                    }
                    echo '</ul></div>';
                }
                echo '</div>';
            }
            echo '</div>';
            echo '<div class="top-product-actions">';
            echo interessa_affiliate_cta_html($row, ['class' => 'btn']);
            echo '</div>';
            echo '</article>';
        }

        echo '</div>';
        echo '</section>';
    }
}