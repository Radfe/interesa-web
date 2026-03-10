<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/affiliates.php';

if (!function_exists('interessa_top_product_link')) {
    function interessa_top_product_link(array $row): array {
        $code = trim((string) ($row['code'] ?? ''));
        $fallback = trim((string) ($row['url'] ?? ''));

        if ($code !== '' && aff_resolve($code) !== null) {
            return [
                'href' => '/go/' . rawurlencode($code),
                'rel' => 'nofollow sponsored',
                'label' => 'Do obchodu',
                'note' => 'Affiliate odkaz',
            ];
        }

        if ($fallback !== '') {
            return [
                'href' => $fallback,
                'rel' => 'nofollow',
                'label' => 'Pozrieť produkt',
                'note' => 'Priamy odkaz',
            ];
        }

        return [
            'href' => '',
            'rel' => '',
            'label' => 'Čoskoro',
            'note' => '',
        ];
    }
}

if (!function_exists('interessa_top_product_has_affiliate')) {
    function interessa_top_product_has_affiliate(array $row): bool {
        $code = trim((string) ($row['code'] ?? ''));
        return $code !== '' && aff_resolve($code) !== null;
    }
}

if (!function_exists('interessa_top_product_absolute_url')) {
    function interessa_top_product_absolute_url(array $row): ?string {
        $link = interessa_top_product_link($row);
        $href = trim((string) ($link['href'] ?? ''));
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
        $stars = str_repeat('★', $filled) . str_repeat('☆', max(0, 5 - $filled));
        return '<span class="top-product-stars" aria-hidden="true">' . esc($stars) . '</span><span class="top-product-score">' . esc(number_format($rating, 1)) . '/5</span>';
    }
}

if (!function_exists('interessa_top_products_schema')) {
    function interessa_top_products_schema(array $products, string $title, string $pagePath): ?array {
        $items = [];

        foreach ($products as $index => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $product = [
                '@type' => 'Product',
                'name' => $name,
            ];

            $subtitle = trim((string) ($row['subtitle'] ?? ''));
            if ($subtitle !== '') {
                $product['description'] = $subtitle;
            }

            $img = trim((string) ($row['img'] ?? ''));
            if ($img !== '') {
                $product['image'] = absolute_url($img);
            }

            $url = interessa_top_product_absolute_url($row);
            if ($url !== null) {
                $product['url'] = $url;
            }

            $rating = (float) ($row['rating'] ?? 0);
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

        $hasAffiliate = false;
        foreach ($products as $row) {
            if (interessa_top_product_has_affiliate($row)) {
                $hasAffiliate = true;
                break;
            }
        }

        echo '<section class="topbox">';
        echo '<div class="topbox-head">';
        echo '<h2>' . esc($title) . '</h2>';
        if ($intro !== null && $intro !== '') {
            echo '<p class="topbox-intro">' . esc($intro) . '</p>';
        }
        if ($hasAffiliate) {
            echo '<p class="topbox-disclosure">Niektoré odkazy nižšie sú affiliate. Ak cez ne nakúpiš, web môže získať províziu bez navýšenia ceny pre teba.</p>';
        }
        echo '</div>';
        echo '<div class="top-products-grid">';

        foreach ($products as $index => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $subtitle = trim((string) ($row['subtitle'] ?? ''));
            $merchant = trim((string) ($row['merchant'] ?? ''));
            $rating = (float) ($row['rating'] ?? 0);
            $img = trim((string) ($row['img'] ?? ''));
            $link = interessa_top_product_link($row);

            if ($img === '') {
                $img = '/assets/img/placeholder-16x9.svg';
            }

            echo '<article class="top-product-card">';
            echo '<div class="top-product-rank">#' . (int) ($index + 1) . '</div>';
            echo '<img class="top-product-image" src="' . esc($img) . '" alt="' . esc($name) . '" loading="lazy">';
            echo '<div class="top-product-body">';
            echo '<h3>' . esc($name) . '</h3>';
            if ($subtitle !== '') {
                echo '<p class="top-product-subtitle">' . esc($subtitle) . '</p>';
            }
            if ($rating > 0) {
                echo '<div class="top-product-rating">' . interessa_render_stars($rating) . '</div>';
            }
            if ($merchant !== '') {
                echo '<div class="top-product-merchant">Obchod: ' . esc($merchant) . '</div>';
            }
            echo '</div>';
            echo '<div class="top-product-actions">';
            if ($link['href'] !== '') {
                echo '<a class="btn" href="' . esc($link['href']) . '" target="_blank" rel="' . esc($link['rel']) . '">' . esc($link['label']) . '</a>';
            } else {
                echo '<button class="btn" type="button" disabled>' . esc($link['label']) . '</button>';
            }
            if ($link['note'] !== '') {
                echo '<div class="top-product-note">' . esc($link['note']) . '</div>';
            }
            echo '</div>';
            echo '</article>';
        }

        echo '</div>';
        echo '</section>';
    }
}