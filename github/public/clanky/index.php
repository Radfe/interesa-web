<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';

$page_title = 'Články | Interesa';
$page_description = 'Prehľad článkov o proteínoch, výžive, vitamínoch a mineráloch.';
include __DIR__ . '/../inc/head.php';

$items = [];
foreach (article_registry() as $slug => $meta) {
    $items[] = [
        'slug' => $slug,
        'title' => $meta[0] ?? humanize_slug($slug),
        'description' => $meta[1] ?? '',
        'url' => article_url($slug),
    ];
}

usort($items, static fn($a, $b) => strcmp($a['title'], $b['title']));
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
              <a href="<?= esc($item['url']) ?>"><?= esc($item['title']) ?></a>
              <?php if ($item['description'] !== ''): ?><div class="meta"><?= esc($item['description']) ?></div><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </article>
  </div>

  <?php include __DIR__ . '/../inc/sidebar.php'; ?>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>