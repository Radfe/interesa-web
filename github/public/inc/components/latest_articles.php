<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/article-commerce.php';

$dir = dirname(__DIR__, 2) . '/content/articles';
$items = [];
$latestArticlesCategorySlug = '';
if (isset($latestArticlesContextCategorySlug) && is_string($latestArticlesContextCategorySlug) && trim($latestArticlesContextCategorySlug) !== '') {
    $latestArticlesCategorySlug = normalize_category_slug($latestArticlesContextCategorySlug);
}

if (is_dir($dir)) {
    foreach (glob($dir . '/*.html') ?: [] as $file) {
        $slug = basename($file, '.html');
        $canonicalSlug = canonical_article_slug($slug);
        $meta = article_meta($canonicalSlug);
        $title = trim((string) ($meta['title'] ?? ''));
        if ($title === '') {
            $title = humanize_slug($canonicalSlug);
        }

        $mtime = @filemtime($file) ?: time();
        $existing = $items[$canonicalSlug] ?? null;
        if (is_array($existing) && (int) ($existing['mtime'] ?? 0) >= $mtime) {
            continue;
        }

        $categorySlug = normalize_category_slug((string) ($meta['category'] ?? ''));
        $commerceSummary = interessa_article_commerce_summary($canonicalSlug);
        $items[$canonicalSlug] = [
            'slug' => $canonicalSlug,
            'title' => function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake($title) : $title,
            'mtime' => $mtime,
            'category_slug' => $categorySlug,
            'category_meta' => $categorySlug !== '' ? category_meta($categorySlug) : null,
            'image' => interessa_article_image_meta($canonicalSlug, 'thumb', true),
            'commerce_summary' => $commerceSummary,
            'has_commerce' => is_array($commerceSummary) && (int) ($commerceSummary['count'] ?? 0) > 0,
            'has_full_coverage' => interessa_article_has_full_packshot_coverage($canonicalSlug),
            'coverage_percent' => interessa_shortlist_coverage_percent($commerceSummary),
        ];
    }
}

$items = array_values($items);
usort($items, static function (array $a, array $b): int {
    global $latestArticlesCategorySlug;

    if ($latestArticlesCategorySlug !== '') {
        $sameCategoryCompare = ((int) (((string) ($b['category_slug'] ?? '')) === $latestArticlesCategorySlug))
            <=> ((int) (((string) ($a['category_slug'] ?? '')) === $latestArticlesCategorySlug));
        if ($sameCategoryCompare !== 0) {
            return $sameCategoryCompare;
        }
    }

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
$items = array_slice($items, 0, 4);

echo '<article class="ad-card latest-articles">';
if ($latestArticlesCategorySlug !== '') {
    $latestCategoryMeta = category_meta($latestArticlesCategorySlug);
    $latestCategoryTitle = trim((string) ($latestCategoryMeta['title'] ?? ''));
    echo '<h3>' . esc(interessa_text('Nove v tejto teme')) . '</h3>';
    echo '<p class="muted">' . esc($latestCategoryTitle !== '' ? ('Aktualne clanky a vybery v teme ' . $latestCategoryTitle . '.') : interessa_text('Aktualne clanky a vybery v tejto teme.')) . '</p>';
} else {
    echo '<h3>' . esc(interessa_text('Nove a aktualizovane clanky')) . '</h3>';
    echo '<p class="muted">' . esc(interessa_text('Clanky, ktore sa oplati otvorit, ak chces nove informacie alebo rychly prechod k vhodnemu vyberu.')) . '</p>';
}

if ($items === []) {
    echo '<p class="muted">' . esc(interessa_text('Zatial tu nie su ziadne clanky.')) . '</p>';
    echo '</article>';
    return;
}

echo '<ul class="latest-list">';
foreach ($items as $item) {
    $url = article_url((string) $item['slug']);
    $date = date('d.m.Y', (int) $item['mtime']);
    $categoryMeta = is_array($item['category_meta'] ?? null) ? $item['category_meta'] : null;
    $formatLabel = interessa_article_format_label((string) $item['slug'], (string) $item['title']);
    $summary = is_array($item['commerce_summary'] ?? null) ? $item['commerce_summary'] : null;
    $showComparisonReady = !empty($item['has_full_coverage']);
    $showRecommendations = !$showComparisonReady && !empty($item['has_commerce']);
    echo '<li class="latest-card">';
    echo '<a class="latest-card-thumb" href="' . esc($url) . '">';
    echo interessa_render_image((array) $item['image'], ['class' => 'latest-card-image', 'alt' => (string) $item['title']]);
    echo '</a>';
    echo '<div class="latest-card-body">';
    echo '<div class="latest-card-meta">';
    echo '<span class="article-card-chip is-format">' . esc($formatLabel) . '</span>';
    if ($categoryMeta !== null) {
        echo '<span class="article-card-chip">' . esc((string) ($categoryMeta['title'] ?? '')) . '</span>';
    }
    echo '<span class="date">' . esc($date) . '</span>';
    echo '</div>';
    if ($showComparisonReady || $showRecommendations) {
        echo '<div class="article-card-submeta">';
        if ($showComparisonReady) {
            echo '<span class="article-card-subchip is-coverage is-full">V clanku najdes rychly prehlad</span>';
        } elseif ($showRecommendations && $summary !== null) {
            echo '<span class="article-card-subchip">Vyber produktov v clanku</span>';
        }
        if ((int) ($item['coverage_percent'] ?? 0) > 0) {
            echo '<span class="article-card-subchip">Realne fotky: ' . esc((string) ((int) ($item['coverage_percent'] ?? 0))) . '%</span>';
        }
        echo '</div>';
    }
    echo '<a class="latest-card-title" href="' . esc($url) . '">' . esc((string) $item['title']) . '</a>';
    echo '</div>';
    echo '</li>';
}
echo '</ul>';
echo '</article>';
