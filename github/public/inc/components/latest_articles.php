<?php
declare(strict_types=1);

$items = [];
foreach (article_registry() as $slug => $meta) {
    $file = __DIR__ . '/../../content/articles/' . $slug . '.html';
    $items[] = [
        'slug' => $slug,
        'title' => $meta[0] ?? humanize_slug($slug),
        'url' => article_url($slug),
        'mtime' => is_file($file) ? ((int) filemtime($file) ?: time()) : time(),
    ];
}

usort($items, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);
$items = array_slice($items, 0, 6);
?>
<article class="ad-card latest-articles">
  <h3>Najnovšie články</h3>

  <?php if (!$items): ?>
    <p class="muted">Zatiaľ nemáme publikované články.</p>
  <?php else: ?>
    <ul class="latest-list">
      <?php foreach ($items as $item): ?>
        <li>
          <a href="<?= esc($item['url']) ?>"><?= esc($item['title']) ?></a>
          <span class="date"><?= date('d.m.Y', $item['mtime']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</article>