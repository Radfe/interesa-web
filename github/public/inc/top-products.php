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

if (!function_exists('interessa_render_stars')) {
    function interessa_render_stars(float $rating): string {
        $rating = max(0.0, min(5.0, $rating));
        $filled = (int) round($rating);
        $stars = str_repeat('★', $filled) . str_repeat('☆', max(0, 5 - $filled));
        return '<span class="top-product-stars" aria-hidden="true">' . esc($stars) . '</span><span class="top-product-score">' . esc(number_format($rating, 1)) . '/5</span>';
    }
}

if (!function_exists('interessa_render_top_products')) {
    function interessa_render_top_products(array $products, string $title = 'Top produkty', ?string $intro = null): void {
        if ($products === []) {
            return;
        }

        echo '<section class="topbox">';
        echo '<div class="topbox-head">';
        echo '<h2>' . esc($title) . '</h2>';
        if ($intro !== null && $intro !== '') {
            echo '<p class="topbox-intro">' . esc($intro) . '</p>';
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