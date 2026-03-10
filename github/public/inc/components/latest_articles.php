<?php
declare(strict_types=1);

$items = latest_article_items(6);
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
          <span class="date"><?= date('d.m.Y', (int) $item['mtime']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</article>
