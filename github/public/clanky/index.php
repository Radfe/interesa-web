<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';

$page_title = 'Clanky | Interesa';
$page_description = 'Prehlad clankov o proteinoch, vyzive, vitaminoch a mineraloch.';
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
      <h1>Clanky</h1>
      <?php if (!$items): ?>
        <p class="note">Zatial tu nie je ziaden clanok.</p>
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

  <aside class="sidebar" aria-label="Pravy panel">
    <?php include __DIR__ . '/../inc/components/latest_articles.php'; ?>
    <article class="ad-card">
      <h3>Heureka vyhladavanie</h3>
      <div class="heureka-affiliate-searchpanel" data-trixam-positionid="67512" data-trixam-codetype="iframe" data-trixam-linktarget="top"></div>
    </article>
  </aside>
</section>
<?php include __DIR__ . '/../inc/footer.php';