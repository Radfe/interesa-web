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
        $recommendedProducts = array_values(array_filter(array_map(static fn(mixed $value): string => trim((string) $value), (array) ($article['recommended_products'] ?? []))));

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
