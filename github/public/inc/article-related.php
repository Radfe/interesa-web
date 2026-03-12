<?php
declare(strict_types=1);

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
                $items[] = [
                    'slug' => $itemSlug,
                    'title' => (string) ($item['title'] ?? humanize_slug($itemSlug)),
                    'description' => (string) ($item['description'] ?? ''),
                    'category' => $category,
                    'mtime' => is_file($file) ? (int) @filemtime($file) : 0,
                ];
                $seen[$itemSlug] = true;
            }
        }

        usort($items, static fn(array $a, array $b): int => ($b['mtime'] ?? 0) <=> ($a['mtime'] ?? 0));
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
        echo '<h2>' . esc('S?visiace ?l?nky') . '</h2>';
        if ($categoryMeta !== null) {
            echo '<p class="meta">' . esc('?al?ie n?vody a porovnania v t?me') . ' ' . esc((string) ($categoryMeta['title'] ?? '')) . '.</p>';
        }
        echo '</div>';
        echo '<div class="hub-grid article-related-grid">';

        foreach ($items as $item) {
            $itemSlug = (string) ($item['slug'] ?? '');
            $itemTitle = function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake((string) ($item['title'] ?? humanize_slug($itemSlug))) : (string) ($item['title'] ?? humanize_slug($itemSlug));
            $itemDescription = function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake((string) ($item['description'] ?? '')) : (string) ($item['description'] ?? '');
            $itemCategorySlug = normalize_category_slug((string) ($item['category'] ?? ''));
            $itemCategoryMeta = $itemCategorySlug !== '' ? category_meta($itemCategorySlug) : null;
            $itemDate = !empty($item['mtime']) ? date('d.m.Y', (int) $item['mtime']) : '';
            $image = interessa_article_image_meta($itemSlug, 'thumb', true);

            echo '<article class="hub-card">';
            echo '<a href="' . esc(article_url($itemSlug)) . '">';
            echo interessa_render_image($image, ['class' => 'hub-card-image', 'alt' => $itemTitle]);
            echo '</a>';
            echo '<div class="hub-card-body">';
            echo '<div class="article-card-meta">';
            if ($itemCategoryMeta !== null) {
                echo '<span class="article-card-chip">' . esc((string) ($itemCategoryMeta['title'] ?? '')) . '</span>';
            }
            if ($itemDate !== '') {
                echo '<span class="article-card-date">' . esc($itemDate) . '</span>';
            }
            echo '</div>';
            echo '<h3><a href="' . esc(article_url($itemSlug)) . '">' . esc($itemTitle) . '</a></h3>';
            if ($itemDescription !== '') {
                echo '<p>' . esc($itemDescription) . '</p>';
            }
            echo '<a class="card-link" href="' . esc(article_url($itemSlug)) . '">' . esc('??ta? ?l?nok') . '</a>';
            echo '</div>';
            echo '</article>';
        }

        echo '</div>';
        echo '</section>';
    }
}
