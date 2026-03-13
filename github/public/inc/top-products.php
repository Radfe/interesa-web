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
            $name = trim((string) ($resolved['product_name'] ?? $resolved['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $product = [
                '@type' => 'Product',
                'name' => $name,
            ];

            $subtitle = trim((string) ($resolved['product_summary'] ?? $resolved['subtitle'] ?? $resolved['summary'] ?? ''));
            if ($subtitle !== '') {
                $product['description'] = $subtitle;
            }

            $image = is_array($resolved['_image'] ?? null) ? $resolved['_image'] : null;
            if ($image !== null && !empty($image['src']) && ($image['source_type'] ?? '') !== 'placeholder') {
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

if (!function_exists('interessa_render_commerce_verdict')) {
    function interessa_render_commerce_verdict(array $commerce): void {
        $products = is_array($commerce['products'] ?? null) ? $commerce['products'] : [];
        if ($products === []) {
            return;
        }

        $resolvedProducts = [];
        foreach ($products as $row) {
            $resolvedProducts[] = interessa_resolve_product_reference($row);
        }

        $lead = $resolvedProducts[0] ?? null;
        if (!is_array($lead)) {
            return;
        }

        $title = trim((string) ($commerce['title'] ?? 'Odporucane produkty'));
        $leadName = trim((string) ($lead['name'] ?? ''));
        $leadMerchant = trim((string) ($lead['merchant'] ?? ''));
        $leadBestFor = trim((string) ($lead['best_for'] ?? ''));
        $leadSummary = trim((string) ($lead['subtitle'] ?? $lead['summary'] ?? ''));
        $rating = (float) ($lead['rating'] ?? 0);
        $shortlistStats = interessa_commerce_shortlist_stats(['products' => $resolvedProducts]);
        $merchantNames = is_array($shortlistStats['merchant_names'] ?? null) ? $shortlistStats['merchant_names'] : [];
        $merchantCount = (int) ($shortlistStats['merchant_count'] ?? 0);
        $realPackshotCount = (int) ($shortlistStats['real_packshots'] ?? 0);
        $editorialCount = (int) ($shortlistStats['editorial_visuals'] ?? 0);
        $coveragePercent = interessa_shortlist_coverage_percent($shortlistStats);
        $coverageState = interessa_shortlist_coverage_state($shortlistStats);

        echo '<section class="commerce-verdict" aria-label="Rychly vyber">';
        echo '<div class="commerce-verdict-copy">';
        echo '<p class="hub-eyebrow">Rychly vyber</p>';
        echo '<h2>' . esc($leadName !== '' ? $leadName : $title) . '</h2>';
        if ($leadSummary !== '') {
            echo '<p class="commerce-verdict-lead">' . esc($leadSummary) . '</p>';
        }
        if ($leadBestFor !== '') {
            echo '<p class="commerce-verdict-bestfor"><strong>' . esc('Najlepsie pre:') . '</strong> ' . esc($leadBestFor) . '</p>';
        }
        echo '<div class="commerce-verdict-actions">';
        echo interessa_affiliate_cta_html($lead, ['class' => 'btn btn-primary']);
        echo '<a class="btn btn-ghost" href="#odporucane-produkty">Pozriet cely shortlist</a>';
        echo '</div>';
        if ($merchantNames !== []) {
            echo '<div class="commerce-verdict-merchants" aria-label="Porovnane obchody">';
            foreach ($merchantNames as $merchantName) {
                echo '<span class="commerce-verdict-chip">' . esc($merchantName) . '</span>';
            }
            echo '</div>';
        }
        echo '<div class="shortlist-coverage is-' . esc($coverageState) . '" aria-label="Pokrytie shortlistu packshotmi">';
        echo '<div class="shortlist-coverage-bar"><span class="shortlist-coverage-fill" style="width:' . esc((string) $coveragePercent) . '%"></span></div>';
        echo '<p class="shortlist-coverage-copy">' . esc((string) $coveragePercent) . '% shortlistu ma realny packshot</p>';
        echo '</div>';
        if ($editorialCount > 0) {
            echo '<p class="commerce-verdict-coverage-note">' . esc('Shortlist este nema plne packshot pokrytie. Produkty bez realneho balenia su oznacene ako editorialny vizual.') . '</p>';
        } else {
            echo '<p class="commerce-verdict-coverage-note is-complete">' . esc('Vsetky odporucane produkty uz maju realny packshot.') . '</p>';
        }
        echo '</div>';
        echo '<div class="commerce-verdict-stats">';
        echo '<div class="commerce-verdict-stat"><strong>' . esc((string) count($resolvedProducts)) . '</strong><span>odporucane produkty</span></div>';
        if ($merchantCount > 0) {
            echo '<div class="commerce-verdict-stat"><strong>' . esc((string) $merchantCount) . '</strong><span>porovnane obchody</span></div>';
        }
        echo '<div class="commerce-verdict-stat"><strong>' . esc((string) $realPackshotCount) . '/' . esc((string) count($resolvedProducts)) . '</strong><span>produkty s realnym packshotom</span></div>';
        if ($editorialCount > 0) {
            echo '<div class="commerce-verdict-stat"><strong>' . esc((string) $editorialCount) . '</strong><span>produkty s editorialnym vizualom</span></div>';
        }
        if ($rating > 0) {
            echo '<div class="commerce-verdict-stat"><strong>' . esc(number_format($rating, 1)) . '/5</strong><span>redakcne hodnotenie top volby</span></div>';
        }
        if ($leadMerchant !== '') {
            echo '<div class="commerce-verdict-merchant-pill">' . esc($leadMerchant) . '</div>';
        }
        echo '</div>';
        echo '</section>';
    }
}

if (!function_exists('interessa_render_top_products')) {
    function interessa_render_top_products(array $products, string $title = 'Top produkty', ?string $intro = null, string $sectionId = ''): void {
        if ($products === []) {
            return;
        }

        $resolvedProducts = [];
        foreach ($products as $row) {
            $resolvedProducts[] = interessa_resolve_product_reference($row);
        }
        $shortlistStats = interessa_commerce_shortlist_stats(['products' => $resolvedProducts]) ?? [];
        $merchantNames = is_array($shortlistStats['merchant_names'] ?? null) ? $shortlistStats['merchant_names'] : [];
        $realPackshotCount = (int) ($shortlistStats['real_packshots'] ?? 0);
        $catalogResolvedCount = (int) ($shortlistStats['catalog_resolved'] ?? 0);
        $editorialCount = (int) ($shortlistStats['editorial_visuals'] ?? 0);
        $coveragePercent = interessa_shortlist_coverage_percent($shortlistStats);
        $coverageState = interessa_shortlist_coverage_state($shortlistStats);

        $sectionId = trim($sectionId);
        echo '<section class="topbox"' . ($sectionId !== '' ? ' id="' . esc($sectionId) . '"' : '') . '>';
        echo '<div class="topbox-head">';
        echo '<h2>' . esc($title) . '</h2>';
        if ($intro !== null && $intro !== '') {
            echo '<p class="topbox-intro">' . esc($intro) . '</p>';
        }
        echo '<div class="topbox-legend" aria-label="Legenda vizualov produktov">';
        echo '<span class="topbox-legend-item"><span class="topbox-legend-dot is-remote" aria-hidden="true"></span>' . esc(interessa_product_image_status_label('remote')) . '</span>';
        echo '<span class="topbox-legend-item"><span class="topbox-legend-dot is-local" aria-hidden="true"></span>' . esc(interessa_product_image_status_label('local')) . '</span>';
        echo '<span class="topbox-legend-item"><span class="topbox-legend-dot is-editorial" aria-hidden="true"></span>' . esc(interessa_product_image_status_label('placeholder')) . '</span>';
        echo '</div>';
        echo '<div class="topbox-metrics" aria-label="Prehlad shortlistu">';
        echo '<span class="topbox-metric"><strong>' . esc((string) count($resolvedProducts)) . '</strong><span>produkty v shortlistu</span></span>';
        if ($merchantNames !== []) {
            echo '<span class="topbox-metric"><strong>' . esc((string) count($merchantNames)) . '</strong><span>porovnane obchody</span></span>';
        }
        echo '<span class="topbox-metric"><strong>' . esc((string) $realPackshotCount) . '/' . esc((string) count($resolvedProducts)) . '</strong><span>realne packshoty</span></span>';
        echo '<span class="topbox-metric"><strong>' . esc((string) $catalogResolvedCount) . '/' . esc((string) count($resolvedProducts)) . '</strong><span>konkretne produkty v katalogu</span></span>';
        echo '</div>';
        echo '<div class="shortlist-coverage is-compact is-' . esc($coverageState) . '" aria-label="Pokrytie shortlistu packshotmi">';
        echo '<div class="shortlist-coverage-bar"><span class="shortlist-coverage-fill" style="width:' . esc((string) $coveragePercent) . '%"></span></div>';
        echo '<p class="shortlist-coverage-copy">' . esc((string) $coveragePercent) . '% shortlistu ma realny packshot</p>';
        echo '</div>';
        if ($editorialCount > 0) {
            echo '<p class="topbox-coverage-note">' . esc('Niektore produkty v tomto shortlistu zatial pouzivaju editorialny vizual. Realne packshoty doplname priebezne bez zmeny obsahu clanku.') . '</p>';
        }
        echo '</div>';
        echo '<div class="top-products-grid">';

        foreach ($resolvedProducts as $index => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $productName = trim((string) ($row['product_name'] ?? ''));
            $catalogResolved = interessa_product_catalog_resolved($row);
            $showProductName = $catalogResolved && $productName !== '' && strcasecmp($productName, $name) !== 0;
            $subtitle = trim((string) ($row['subtitle'] ?? $row['summary'] ?? ''));
            $merchant = trim((string) ($row['merchant'] ?? ''));
            $bestFor = trim((string) ($row['best_for'] ?? ''));
            $rating = (float) ($row['rating'] ?? 0);
            $pros = is_array($row['pros'] ?? null) ? array_values($row['pros']) : [];
            $cons = is_array($row['cons'] ?? null) ? array_values($row['cons']) : [];
            $imageMode = trim((string) ($row['image_mode'] ?? (($row['_image']['source_type'] ?? 'placeholder'))));
            $imageStatus = interessa_product_image_status_label($imageMode);
            $showEditorialNote = !$catalogResolved && $imageMode === 'placeholder' && $merchant !== '';

            echo '<article class="top-product-card">';
            echo '<div class="top-product-rank">Top ' . (int) ($index + 1) . '</div>';
            if ($merchant !== '') {
                echo '<div class="top-product-merchant-pill">' . esc($merchant) . '</div>';
            }
            echo interessa_render_product_media($row, [
                'wrapper_class' => 'top-product-media',
                'frame_class' => 'top-product-media-frame',
                'image_class' => 'top-product-image',
                'badge_class' => 'top-product-media-badge',
                'show_badge' => false,
                'title_class' => 'top-product-media-title',
                'meta_class' => 'top-product-media-meta',
            ]);
            echo '<div class="top-product-body">';
            echo '<div class="top-product-copy">';
            echo '<h3>' . esc($name) . '</h3>';
            if ($subtitle !== '') {
                echo '<p class="top-product-subtitle">' . esc($subtitle) . '</p>';
            }
            echo '</div>';
            if ($showProductName) {
                echo '<p class="top-product-product-name"><span>' . esc(interessa_text('Produkt v obchode:')) . '</span> ' . esc($productName) . '</p>';
            }
            if ($bestFor !== '') {
                echo '<div class="top-product-bestfor"><span>' . esc(interessa_text('Najlepsie pre:')) . '</span> ' . esc($bestFor) . '</div>';
            }
            if ($rating > 0 || $imageStatus !== '') {
                echo '<div class="top-product-meta-row">';
                if ($rating > 0) {
                    echo '<div class="top-product-rating">' . interessa_render_stars($rating) . '</div>';
                }
                if ($imageStatus !== '') {
                    echo '<span class="top-product-image-status">' . esc($imageStatus) . '</span>';
                }
                echo '</div>';
            }
            if ($showEditorialNote) {
                echo '<p class="top-product-editorial-note">' . esc(interessa_text('Redakcny tip pre tento obchod. Konkretne balenie doplnime, ked bude k dispozicii plny merchant produkt alebo packshot.')) . '</p>';
            }
            if ($pros !== [] || $cons !== []) {
                echo '<div class="top-product-highlights">';
                if ($pros !== []) {
                    echo '<div class="top-product-list is-pros"><div class="top-product-list-title">' . esc(interessa_text('Plusy')) . '</div><ul>';
                    foreach ($pros as $item) {
                        echo '<li>' . esc((string) $item) . '</li>';
                    }
                    echo '</ul></div>';
                }
                if ($cons !== []) {
                    echo '<div class="top-product-list is-cons"><div class="top-product-list-title">' . esc(interessa_text('Minusy')) . '</div><ul>';
                    foreach ($cons as $item) {
                        echo '<li>' . esc((string) $item) . '</li>';
                    }
                    echo '</ul></div>';
                }
                echo '</div>';
            }
            echo '<div class="top-product-actions">';
            echo interessa_affiliate_cta_html($row, ['class' => 'btn btn-cta']);
            echo '</div>';
            echo '</div>';
            echo '</article>';
        }

        echo '</div>';
        echo '</section>';
    }
}
