<?php

declare(strict_types=1);

require_once __DIR__ . '/admin-store.php';
require_once __DIR__ . '/affiliate-ui.php';

if (!function_exists('interessa_admin_article_content_payload')) {
    function interessa_admin_article_content_payload(string $slug): array {
        $article = interessa_admin_article_content($slug);
        $headings = [];
        $wordCount = 0;

        ob_start();

        if (trim((string) ($article['intro'] ?? '')) !== '') {
            $intro = trim((string) $article['intro']);
            $wordCount += str_word_count(strip_tags($intro));
        }

        foreach (($article['sections'] ?? []) as $section) {
            if (!is_array($section)) {
                continue;
            }

            $heading = trim((string) ($section['heading'] ?? ''));
            $body = trim((string) ($section['body'] ?? ''));
            if ($heading === '' && $body === '') {
                continue;
            }

            $anchor = $heading !== '' ? interessa_admin_slugify($heading) : '';
            if ($heading !== '') {
                $headings[] = [
                    'id' => $anchor,
                    'level' => 2,
                    'text' => $heading,
                ];
                echo '<section class="article-admin-section">';
                echo '<h2 class="article-admin-heading" id="' . esc($anchor) . '">' . esc($heading) . '</h2>';
            } else {
                echo '<section class="article-admin-section">';
            }

            if ($body !== '') {
                $wordCount += str_word_count(strip_tags($body));
                $paragraphs = preg_split('~\R{2,}~', $body) ?: [$body];
                foreach ($paragraphs as $paragraph) {
                    $paragraph = trim($paragraph);
                    if ($paragraph === '') {
                        continue;
                    }
                    echo '<p>' . nl2br(esc($paragraph)) . '</p>';
                }
            }

            echo '</section>';
        }

        $comparison = is_array($article['comparison'] ?? null) ? $article['comparison'] : [];
        if (($comparison['columns'] ?? []) !== [] && ($comparison['rows'] ?? []) !== []) {
            $comparisonTitle = trim((string) ($comparison['title'] ?? 'Porovnanie produktov')) ?: 'Porovnanie produktov';
            $comparisonIntro = trim((string) ($comparison['intro'] ?? ''));
            $anchor = interessa_admin_slugify($comparisonTitle);
            $headings[] = [
                'id' => $anchor,
                'level' => 2,
                'text' => $comparisonTitle,
            ];

            echo '<section class="article-admin-comparison">';
            echo '<h2 class="article-admin-heading" id="' . esc($anchor) . '">' . esc($comparisonTitle) . '</h2>';
            if ($comparisonIntro !== '') {
                $wordCount += str_word_count(strip_tags($comparisonIntro));
                echo '<p class="article-admin-comparison-intro">' . esc($comparisonIntro) . '</p>';
            }
            echo interessa_render_comparison_table(
                is_array($comparison['rows']) ? $comparison['rows'] : [],
                is_array($comparison['columns']) ? $comparison['columns'] : []
            );
            echo '</section>';
        }

        $recommendedProducts = [];
        foreach (($article['recommended_products'] ?? []) as $productSlug) {
            $productSlug = trim((string) $productSlug);
            if ($productSlug === '') {
                continue;
            }
            $recommendedProducts[] = [
                'product_slug' => $productSlug,
            ];
        }

        if ($recommendedProducts !== []) {
            $headings[] = [
                'id' => 'odporucane-produkty',
                'level' => 2,
                'text' => 'Odporucane produkty',
            ];
            echo '<section class="article-admin-recommended">';
            echo '<h2 class="article-admin-heading" id="odporucane-produkty">Odporucane produkty</h2>';
            echo '<div class="article-admin-recommended-grid">';
            foreach ($recommendedProducts as $row) {
                echo interessa_render_product_box($row);
            }
            echo '</div>';
            echo '</section>';
        }

        $html = (string) ob_get_clean();
        $readingTime = max(1, (int) ceil($wordCount / 180));

        return [
            'html' => $html,
            'headings' => $headings,
            'reading_time' => $readingTime,
            'has_recommendations' => $recommendedProducts !== [],
            'has_comparison' => ($comparison['columns'] ?? []) !== [] && ($comparison['rows'] ?? []) !== [],
            'intro' => trim((string) ($article['intro'] ?? '')),
        ];
    }
}
