<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/functions.php';

$dir = dirname(__DIR__, 2) . '/content/articles';
$items = [];

if (is_dir($dir)) {
    foreach (glob($dir . '/*.html') ?: [] as $file) {
        $slug = basename($file, '.html');
        $meta = article_meta($slug);
        $title = trim((string) ($meta['title'] ?? ''));
        if ($title === '') {
            $title = humanize_slug($slug);
        }

        $items[] = [
            'slug' => $slug,
            'title' => $title,
            'mtime' => @filemtime($file) ?: time(),
        ];
    }
}

usort($items, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);
$items = array_slice($items, 0, 6);

echo '<article class="ad-card latest-articles">';
echo '<h3>Najnovšie články</h3>';

if ($items === []) {
    echo '<p class="muted">Zatiaľ tu nie sú žiadne články.</p>';
    echo '</article>';
    return;
}

echo '<ul class="latest-list">';
foreach ($items as $item) {
    $url = '/clanky/' . $item['slug'];
    $date = date('d.m.Y', (int) $item['mtime']);
    echo '<li>';
    echo '<a href="' . esc($url) . '">' . esc($item['title']) . '</a>';
    echo '<span class="date">' . esc($date) . '</span>';
    echo '</li>';
}
echo '</ul>';
echo '</article>';