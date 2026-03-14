<?php
declare(strict_types=1);

require_once __DIR__ . '/products.php';

if (!function_exists('interessa_affiliate_disclosure_text')) {
    function interessa_affiliate_disclosure_text(): string {
        return interessa_text('Niektore odkazy na tejto stranke vedu na partnerske obchody. Ak cez ne nakupis, web moze ziskat proviziu bez navysenia ceny pre teba.');
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
        $label = trim((string) ($options['label'] ?? $target['label'] ?? interessa_text('Do obchodu'))) ?: interessa_text('Do obchodu');

        if ($target['href'] === '') {
            return '<button class="' . esc($class) . '" type="button" disabled>' . esc($label) . '</button>';
        }

        return '<a class="' . esc($class) . '" href="' . esc($target['href']) . '" target="_blank" rel="' . esc($target['rel']) . '">' . esc($label) . '</a>';
    }
}

if (!function_exists('interessa_product_image_status_label')) {
    function interessa_product_image_status_label(string $sourceType): string {
        $sourceType = strtolower(trim($sourceType));

        return match ($sourceType) {
            'local' => interessa_text('Packshot z e-shopu'),
            'remote' => interessa_text('Packshot z obchodu'),
            'placeholder' => interessa_text('Redakcny vizual'),
            default => '',
        };
    }
}

if (!function_exists('interessa_product_image_status_class')) {
    function interessa_product_image_status_class(string $sourceType): string {
        $sourceType = strtolower(trim($sourceType));

        return match ($sourceType) {
            'local', 'remote' => 'is-packshot',
            'placeholder' => 'is-editorial',
            default => 'is-unknown',
        };
    }
}

if (!function_exists('interessa_product_editorial_note_text')) {
    function interessa_product_editorial_note_text(string $merchant = ''): string {
        $merchant = trim($merchant);

        if ($merchant !== '') {
            return interessa_text('Zatial pouzivame redakcny vizual. Odkaz aj odporucanie smeruju na konkretny produkt v obchode ') . $merchant . '.';
        }

        return interessa_text('Zatial pouzivame redakcny vizual. Odkaz aj odporucanie smeruju na konkretny produkt v obchode.');
    }
}

if (!function_exists('interessa_product_merchant_initials')) {
    function interessa_product_merchant_initials(string $merchant): string {
        $merchant = trim($merchant);
        if ($merchant === '') {
            return 'I';
        }

        $parts = preg_split('~\s+~', $merchant) ?: [];
        $initials = '';
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $initials .= strtoupper(substr($part, 0, 1));
            if (strlen($initials) >= 2) {
                break;
            }
        }

        return $initials !== '' ? $initials : strtoupper(substr($merchant, 0, 1));
    }
}

if (!function_exists('interessa_product_media_meta')) {
    function interessa_product_media_meta(array $row): array {
        $resolved = interessa_resolve_product_reference($row);
        $image = is_array($resolved['_image'] ?? null) ? $resolved['_image'] : null;
        $sourceType = trim((string) ($image['source_type'] ?? $resolved['image_mode'] ?? 'placeholder')) ?: 'placeholder';
        $merchant = trim((string) ($resolved['merchant'] ?? ''));
        $merchantSlug = trim((string) ($resolved['merchant_slug'] ?? ''));
        $productName = trim((string) ($resolved['product_name'] ?? $resolved['name'] ?? ''));
        $summary = trim((string) ($resolved['product_summary'] ?? $resolved['subtitle'] ?? $resolved['summary'] ?? ''));
        $categorySlug = normalize_category_slug((string) ($resolved['category'] ?? ''));
        $categoryMeta = $categorySlug !== '' ? category_meta($categorySlug) : null;

        return [
            'row' => $resolved,
            'image' => $image,
            'source_type' => $sourceType,
            'merchant' => $merchant,
            'merchant_slug' => $merchantSlug,
            'product_name' => $productName,
            'summary' => $summary,
            'category_label' => $categoryMeta['title'] ?? humanize_slug($categorySlug),
        ];
    }
}

if (!function_exists('interessa_render_product_media')) {
    function interessa_render_product_media(array $row, array $options = []): string {
        $meta = interessa_product_media_meta($row);
        $image = $meta['image'];
        $sourceType = $meta['source_type'];
        $merchant = $meta['merchant'];
        $merchantSlug = trim((string) ($meta['merchant_slug'] ?? ''));
        $productName = $meta['product_name'];
        $summary = $meta['summary'];
        $categoryLabel = $meta['category_label'];
        $imageStatus = interessa_product_image_status_label($sourceType);
        $imageStatusClass = interessa_product_image_status_class($sourceType);

        $wrapperClass = trim((string) ($options['wrapper_class'] ?? 'affiliate-product-media')) ?: 'affiliate-product-media';
        $frameClass = trim((string) ($options['frame_class'] ?? ($wrapperClass . '-frame'))) ?: ($wrapperClass . '-frame');
        $imageClass = trim((string) ($options['image_class'] ?? 'affiliate-product-image')) ?: 'affiliate-product-image';
        $badgeClass = trim((string) ($options['badge_class'] ?? ($wrapperClass . '-badge'))) ?: ($wrapperClass . '-badge');
        $titleClass = trim((string) ($options['title_class'] ?? ($wrapperClass . '-title'))) ?: ($wrapperClass . '-title');
        $metaClass = trim((string) ($options['meta_class'] ?? ($wrapperClass . '-meta'))) ?: ($wrapperClass . '-meta');
        $showBadge = array_key_exists('show_badge', $options) ? (bool) $options['show_badge'] : true;

        if (is_array($image) && !empty($image['src']) && $sourceType !== 'placeholder') {
            $html = '<div class="' . esc($wrapperClass . ' is-real is-' . $sourceType) . '">';
            if ($showBadge && $merchant !== '') {
                $html .= '<span class="' . esc($badgeClass) . '">' . esc($merchant) . '</span>';
            }
            $html .= '<div class="' . esc($frameClass) . '">';
            $html .= interessa_render_image($image, ['class' => $imageClass]);
            $html .= '</div></div>';
            return $html;
        }

        $merchantSlugClass = $merchantSlug !== '' ? ' is-merchant-' . $merchantSlug : '';
        $html = '<div class="' . esc($wrapperClass . ' is-fallback' . $merchantSlugClass) . '">';
        if ($showBadge && $merchant !== '') {
            $html .= '<span class="' . esc($badgeClass) . '">' . esc($merchant) . '</span>';
        }
        $html .= '<div class="' . esc($frameClass . ' is-fallback-frame') . '">';
        $html .= '<div class="product-fallback-copy">';
        if ($imageStatus !== '') {
            $html .= '<span class="product-fallback-badge ' . esc($imageStatusClass) . '">' . esc($imageStatus) . '</span>';
        }
        $html .= '<span class="product-fallback-emblem" aria-hidden="true">' . esc(interessa_product_merchant_initials($merchant)) . '</span>';
        if ($productName !== '') {
            $html .= '<strong class="' . esc($titleClass) . '">' . esc($productName) . '</strong>';
        }
        $fallbackMeta = $categoryLabel !== '' ? $categoryLabel : $summary;
        if ($fallbackMeta !== '') {
            $html .= '<span class="' . esc($metaClass) . '">' . esc($fallbackMeta) . '</span>';
        }
        if ($summary !== '' && strcasecmp($summary, $fallbackMeta) !== 0) {
            $html .= '<span class="product-fallback-summary">' . esc($summary) . '</span>';
        }
        $html .= '</div></div>';
        $html .= '</div>';

        return $html;
    }
}

if (!function_exists('interessa_render_product_box')) {
    function interessa_render_product_box(array $row, array $options = []): string {
        $row = interessa_resolve_product_reference($row);
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            return '';
        }

        $summary = trim((string) ($row['subtitle'] ?? ''));
        $productName = trim((string) ($row['product_name'] ?? ''));
        $catalogResolved = interessa_product_catalog_resolved($row);
        $showProductName = $catalogResolved && $productName !== '' && strcasecmp($productName, $name) !== 0;
        $bestFor = trim((string) ($row['best_for'] ?? ''));
        $pros = is_array($row['pros'] ?? null) ? array_values($row['pros']) : [];
        $cons = is_array($row['cons'] ?? null) ? array_values($row['cons']) : [];
        $merchant = trim((string) ($row['merchant'] ?? ''));
        $showDisclosure = (bool) ($options['show_disclosure'] ?? false);
        $imageMode = trim((string) ($row['image_mode'] ?? (($row['_image']['source_type'] ?? 'placeholder'))));
        $imageStatus = interessa_product_image_status_label($imageMode);
        $imageStatusClass = interessa_product_image_status_class($imageMode);
        $rating = (float) ($row['rating'] ?? 0);
        $showEditorialNote = !$catalogResolved && $imageMode === 'placeholder' && $merchant !== '';

        $html = '<article class="affiliate-product-box">';
        $html .= interessa_render_product_media($row, [
            'wrapper_class' => 'affiliate-product-media',
            'frame_class' => 'affiliate-product-media-frame',
            'image_class' => 'affiliate-product-image',
        ]);
        $html .= '<div class="affiliate-product-body">';
        $html .= '<h3>' . esc($name) . '</h3>';
        if ($summary !== '') {
            $html .= '<p class="affiliate-product-summary">' . esc($summary) . '</p>';
        }
        if ($showProductName) {
            $html .= '<p class="affiliate-product-product-name"><strong>' . esc(interessa_text('Produkt v obchode:')) . '</strong> ' . esc($productName) . '</p>';
        }
        if ($bestFor !== '') {
            $html .= '<p class="affiliate-product-bestfor"><strong>' . esc(interessa_text('Najlepsie pre:')) . '</strong> ' . esc($bestFor) . '</p>';
        }
        if ($rating > 0 || $imageStatus !== '') {
            $html .= '<div class="affiliate-product-meta-row">';
            if ($rating > 0) {
                $html .= '<div class="affiliate-product-rating">' . interessa_render_stars($rating) . '</div>';
            }
            if ($imageStatus !== '') {
                $html .= '<span class="affiliate-product-image-status ' . esc($imageStatusClass) . '">' . esc($imageStatus) . '</span>';
            }
            $html .= '</div>';
        }
        if ($merchant !== '') {
            $html .= '<p class="affiliate-product-merchant">' . esc(interessa_text('Obchod:')) . ' ' . esc($merchant) . '</p>';
        }
        if ($showEditorialNote) {
            $html .= '<p class="affiliate-product-editorial-note">' . esc(interessa_product_editorial_note_text($merchant)) . '</p>';
        }
        if ($pros !== [] || $cons !== []) {
            $html .= '<div class="affiliate-product-columns">';
            if ($pros !== []) {
                $html .= '<div><h4>' . esc(interessa_text('Plusy')) . '</h4><ul>';
                foreach ($pros as $item) {
                    $html .= '<li>' . esc((string) $item) . '</li>';
                }
                $html .= '</ul></div>';
            }
            if ($cons !== []) {
                $html .= '<div><h4>' . esc(interessa_text('Minusy')) . '</h4><ul>';
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
        $badge = trim((string) ($options['badge'] ?? interessa_text('Odporucame'))) ?: interessa_text('Odporucame');
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
                $type = trim((string) ($column['type'] ?? 'text')) ?: 'text';
                $value = $resolved[$key] ?? '';

                if ($type === 'product') {
                    $merchant = trim((string) ($resolved['merchant'] ?? ''));
                    $imageMode = trim((string) ($resolved['image_mode'] ?? (($resolved['_image']['source_type'] ?? 'placeholder'))));
                    $imageStatus = interessa_product_image_status_label($imageMode);
                    $imageStatusClass = interessa_product_image_status_class($imageMode);
                    $catalogResolved = interessa_product_catalog_resolved($resolved);
                    $html .= '<td>';
                    $html .= '<div class="comparison-product-cell">';
                    $html .= interessa_render_product_media($resolved, [
                        'wrapper_class' => 'comparison-product-media',
                        'frame_class' => 'comparison-product-media-frame',
                        'image_class' => 'comparison-product-image',
                    ]);
                    $html .= '<div class="comparison-product-copy">';
                    $html .= '<strong>' . esc((string) ($resolved['name'] ?? '')) . '</strong>';
                    if ($catalogResolved && !empty($resolved['product_name']) && strcasecmp((string) $resolved['product_name'], (string) ($resolved['name'] ?? '')) !== 0) {
                        $html .= '<span>' . esc((string) $resolved['product_name']) . '</span>';
                    }
                    if ($merchant !== '' || $imageStatus !== '') {
                        $html .= '<div class="comparison-product-meta-row">';
                        if ($merchant !== '') {
                            $html .= '<span class="comparison-product-merchant">' . esc($merchant) . '</span>';
                        }
                        if ($imageStatus !== '') {
                            $html .= '<span class="comparison-product-image-status ' . esc($imageStatusClass) . '">' . esc($imageStatus) . '</span>';
                        }
                        $html .= '</div>';
                    }
                    $html .= '</div></div></td>';
                    continue;
                }

                if ($type === 'cta') {
                    $html .= '<td class="comparison-cta-cell">' . interessa_affiliate_cta_html($resolved, ['class' => 'btn btn-small']) . '</td>';
                    continue;
                }

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
