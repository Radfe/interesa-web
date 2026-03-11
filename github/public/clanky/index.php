<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';

$page_title = 'Články | Interesa';
$page_description = 'Prehľad článkov o proteínoch, výžive, vitamínoch a mineráloch.';
include __DIR__ . '/../inc/head.php';

$items = array_values(indexed_articles());
usort($items, static fn(array $a, array $b): int => strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? '')));
?>
<section class="container two-col">
  <div class="content">
    <article class="card">
      <h1>Články</h1>
      <?php if (!$items): ?>
        <p class="note">Zatiaľ tu nie je žiaden článok.</p>
      <?php else: ?>
        <ul class="article-list">
          <?php foreach ($items as $item): ?>
            <li>
              <a href="<?= esc(article_url((string) ($item['slug'] ?? ''))) ?>"><?= esc((string) ($item['title'] ?? '')) ?></a>
              <?php if (!empty($item['description'])): ?><div class="meta"><?= esc((string) $item['description']) ?></div><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </article>
  </div>

  <?php include __DIR__ . '/../inc/sidebar.php'; ?>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>