<?php
declare(strict_types=1);

require_once __DIR__ . '/article-commerce.php';

if (!function_exists('interessa_related_articles')) {
    function interessa_related_articles(string $slug, int $limit = 3): array {
        $canonicalSlug = canonical_article_slug($slug);
        $meta = article_meta($canonicalSlug);
        $category = normalize_category_slug((string) ($meta['category'] ?? ''));
        $items = [];
        $seen = [$canonicalSlug => true];

        if ($category !== '') {
            foreach (category_articles($category) as $item) {
                $itemSlug = canonical_article_slug((string) ($item['slug'] ?? ''));
                if ($itemSlug === '' || isset($seen[$itemSlug])) {
                    continue;
                }

                $file = __DIR__ . '/../content/articles/' . $itemSlug . '.html';
                $commerceSummary = interessa_article_commerce_summary($itemSlug);
                $items[] = [
                    'slug' => $itemSlug,
                    'title' => (string) ($item['title'] ?? humanize_slug($itemSlug)),
                    'description' => (string) ($item['description'] ?? ''),
                    'category' => $category,
                    'mtime' => is_file($file) ? (int) @filemtime($file) : 0,
                    'commerce_summary' => $commerceSummary,
                    'has_commerce' => is_array($commerceSummary) && (int) ($commerceSummary['count'] ?? 0) > 0,
                    'has_full_coverage' => interessa_article_has_full_packshot_coverage($itemSlug),
                ];
                $seen[$itemSlug] = true;
            }
        }

        usort($items, static function (array $a, array $b): int {
            $coverageCompare = ((int) (!empty($b['has_full_coverage']))) <=> ((int) (!empty($a['has_full_coverage'])));
            if ($coverageCompare !== 0) {
                return $coverageCompare;
            }

            $commerceCompare = ((int) (!empty($b['has_commerce']))) <=> ((int) (!empty($a['has_commerce'])));
            if ($commerceCompare !== 0) {
                return $commerceCompare;
            }

            return ((int) ($b['mtime'] ?? 0)) <=> ((int) ($a['mtime'] ?? 0));
        });
        $items = array_slice($items, 0, $limit);

        if (count($items) < $limit) {
            foreach (indexed_articles() as $item) {
                $itemSlug = canonical_article_slug((string) ($item['slug'] ?? ''));
                if ($itemSlug === '' || isset($seen[$itemSlug])) {
                    continue;
                }

                $items[] = [
                    'slug' => $itemSlug,
                    'title' => (string) ($item['title'] ?? humanize_slug($itemSlug)),
                    'description' => (string) ($item['description'] ?? ''),
                    'category' => normalize_category_slug((string) ($item['category'] ?? '')),
                    'mtime' => 0,
                    'commerce_summary' => interessa_article_commerce_summary($itemSlug),
                    'has_commerce' => interessa_article_has_commerce($itemSlug),
                    'has_full_coverage' => interessa_article_has_full_packshot_coverage($itemSlug),
                ];
                $seen[$itemSlug] = true;

                if (count($items) >= $limit) {
                    break;
                }
            }
        }

        return array_values($items);
    }
}

if (!function_exists('interessa_render_related_articles')) {
    function interessa_render_related_articles(string $slug, int $limit = 3): void {
        $items = interessa_related_articles($slug, $limit);
        if ($items === []) {
            return;
        }

        $categoryMeta = category_meta((string) (article_meta($slug)['category'] ?? ''));
        echo '<section class="article-related">';
        echo '<div class="section-head">';
        echo '<h2>' . esc('Suvisiace clanky') . '</h2>';
        if ($categoryMeta !== null) {
            echo '<p class="meta">' . esc('Dalsie navody a porovnania v teme') . ' ' . esc((string) ($categoryMeta['title'] ?? '')) . '.</p>';
        }
        echo '</div>';
        echo '<div class="hub-grid article-related-grid">';

        foreach ($items as $item) {
            $itemSlug = (string) ($item['slug'] ?? '');
            $itemMeta = article_meta($itemSlug);
            $itemTitle = function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake((string) ($item['title'] ?? $itemMeta['title'] ?? humanize_slug($itemSlug))) : (string) ($item['title'] ?? $itemMeta['title'] ?? humanize_slug($itemSlug));
            $itemDescription = interessa_article_card_description($itemSlug, trim((string) ($item['description'] ?? $itemMeta['description'] ?? '')), 20);
            $itemCategorySlug = normalize_category_slug((string) ($item['category'] ?? ''));
            $itemCategoryMeta = $itemCategorySlug !== '' ? category_meta($itemCategorySlug) : null;
            $itemDate = !empty($item['mtime']) ? date('d.m.Y', (int) $item['mtime']) : '';
            $image = interessa_article_image_meta($itemSlug, 'thumb', true);
            $formatLabel = interessa_article_format_label($itemSlug, $itemTitle);
            $summary = is_array($item['commerce_summary'] ?? null) ? $item['commerce_summary'] : null;
            $showComparisonReady = !empty($item['has_full_coverage']);
            $showRecommendations = !$showComparisonReady && !empty($item['has_commerce']);
            echo '<article class="hub-card">';
            echo '<a href="' . esc(article_url($itemSlug)) . '">';
            echo interessa_render_image($image, ['class' => 'hub-card-image', 'alt' => $itemTitle]);
            echo '</a>';
            echo '<div class="hub-card-body">';
            echo '<div class="article-card-meta">';
            echo '<span class="article-card-chip is-format">' . esc($formatLabel) . '</span>';
            if ($itemCategoryMeta !== null) {
                echo '<span class="article-card-chip">' . esc((string) ($itemCategoryMeta['title'] ?? '')) . '</span>';
            }
            if ($itemDate !== '') {
                echo '<span class="article-card-date">' . esc($itemDate) . '</span>';
            }
            echo '</div>';
            if ($showComparisonReady || $showRecommendations) {
                echo '<div class="article-card-submeta">';
                if ($showComparisonReady) {
                    echo '<span class="article-card-subchip is-coverage is-full">Porovnanie aj shortlist pripraveny</span>';
                } elseif ($showRecommendations && $summary !== null) {
                    echo '<span class="article-card-subchip">Odporucania v ' . esc((string) ((int) ($summary['count'] ?? 0))) . ' produktoch</span>';
                }
                echo '</div>';
            }
            echo '<h3><a href="' . esc(article_url($itemSlug)) . '">' . esc($itemTitle) . '</a></h3>';
            if ($itemDescription !== '') {
                echo '<p>' . esc($itemDescription) . '</p>';
            }
            echo '<a class="card-link" href="' . esc(article_url($itemSlug)) . '">' . esc(interessa_article_cta_label($itemSlug, $itemTitle)) . '</a>';
            echo '</div>';
            echo '</article>';
        }

        echo '</div>';
        echo '</section>';
    }
}
