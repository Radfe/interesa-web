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
        echo '<h2>Súvisiace články</h2>';
        if ($categoryMeta !== null) {
            echo '<p class="meta">Ďalšie návody a porovnania v téme ' . esc($categoryMeta['title']) . '.</p>';
        }
        echo '</div>';
        echo '<div class="hub-grid article-related-grid">';

        foreach ($items as $item) {
            $itemSlug = (string) ($item['slug'] ?? '');
            $itemTitle = (string) ($item['title'] ?? humanize_slug($itemSlug));
            $itemDescription = (string) ($item['description'] ?? '');
            $image = interessa_article_image_meta($itemSlug, 'thumb', true);

            echo '<article class="hub-card">';
            echo '<a href="' . esc(article_url($itemSlug)) . '">';
            echo interessa_render_image($image, ['class' => 'hub-card-image', 'alt' => $itemTitle]);
            echo '</a>';
            echo '<div class="hub-card-body">';
            echo '<h3><a href="' . esc(article_url($itemSlug)) . '">' . esc($itemTitle) . '</a></h3>';
            if ($itemDescription !== '') {
                echo '<p>' . esc($itemDescription) . '</p>';
            }
            echo '<a class="card-link" href="' . esc(article_url($itemSlug)) . '">Čítať článok</a>';
            echo '</div>';
            echo '</article>';
        }

        echo '</div>';
        echo '</section>';
    }
}