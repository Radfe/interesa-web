<?php
declare(strict_types=1);

$dir = __DIR__ . '/../../content/articles';
$items = [];

if (is_dir($dir)) {
    foreach (glob($dir . '/*.html') ?: [] as $file) {
        $slug  = basename($file, '.html');
        $title = ucwords(str_replace(['-', '_'], [' ', ' '], $slug));
        $html  = @file_get_contents($file);

        if ($html !== false) {
            if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $m)) {
                $title = trim(strip_tags($m[1]));
            } elseif (preg_match('/<title>(.*?)<\/title>/is', $html, $m2)) {
                $title = trim(strip_tags($m2[1]));
            }
        }

        $items[] = [
            'slug' => $slug,
            'title' => $title,
            'mtime' => @filemtime($file) ?: time(),
        ];
    }
}

usort($items, static fn($a, $b) => $b['mtime'] <=> $a['mtime']);
$items = array_slice($items, 0, 6);

echo '<article class="ad-card latest-articles">';
echo '<h3>Najnovšie články</h3>';

if (!$items) {
    echo '<p class="muted">Zatiaľ tu nie sú žiadne články.</p>';
    echo '</article>';
    return;
}

echo '<ul class="latest-list">';
foreach ($items as $item) {
    $url  = '/clanky/' . $item['slug'];
    $date = date('d.m.Y', (int) $item['mtime']);
    echo '<li>';
    echo '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '">' . htmlspecialchars($item['title'], ENT_QUOTES) . '</a>';
    echo '<span class="date">' . htmlspecialchars($date, ENT_QUOTES) . '</span>';
    echo '</li>';
}
echo '</ul>';
echo '</article>';