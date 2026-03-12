<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/functions.php';

$dir = dirname(__DIR__, 2) . '/content/articles';
$items = [];

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

        $items[$canonicalSlug] = [
            'slug' => $canonicalSlug,
            'title' => $title,
            'mtime' => $mtime,
        ];
    }
}

$items = array_values($items);
usort($items, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);
$items = array_slice($items, 0, 6);

echo '<article class="ad-card latest-articles">';
echo '<h3>Najnovsie clanky</h3>';

if ($items === []) {
    echo '<p class="muted">Zatial tu nie su ziadne clanky.</p>';
    echo '</article>';
    return;
}

echo '<ul class="latest-list">';
foreach ($items as $item) {
    $url = article_url((string) $item['slug']);
    $date = date('d.m.Y', (int) $item['mtime']);
    echo '<li>';
    echo '<a href="' . esc($url) . '">' . esc((string) $item['title']) . '</a>';
    echo '<span class="date">' . esc($date) . '</span>';
    echo '</li>';
}
echo '</ul>';
echo '</article>';