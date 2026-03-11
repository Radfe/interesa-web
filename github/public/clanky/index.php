<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';

$categoryFilter = normalize_category_slug((string) ($_GET['category'] ?? ''));
$categoryMeta = $categoryFilter !== '' ? category_meta($categoryFilter) : null;
$page_title = ($categoryMeta !== null ? $categoryMeta['title'] . ' | ' . interessa_text('Články') : interessa_text('Články')) . ' | Interesa';
$page_description = $categoryMeta !== null
    ? interessa_text('Prehľad článkov v téme ') . $categoryMeta['title'] . '.'
    : interessa_text('Prehľad článkov o proteínoch, výžive, vitamínoch a ďalších doplnkoch.');
$page_canonical = '/clanky' . ($categoryMeta !== null ? '?category=' . rawurlencode($categoryMeta['slug']) : '');
$page_og_type = 'website';
include __DIR__ . '/../inc/head.php';

$allItems = array_values(indexed_articles());
$categoryCounts = [];
foreach ($allItems as &$item) {
    $slug = (string) ($item['slug'] ?? '');
    $file = dirname(__DIR__) . '/content/articles/' . $slug . '.html';
    $item['mtime'] = is_file($file) ? (int) @filemtime($file) : 0;
    $item['image'] = interessa_article_image_meta($slug, 'thumb', true);
    $itemCategorySlug = normalize_category_slug((string) ($item['category'] ?? ''));
    $item['category_meta'] = $itemCategorySlug !== '' ? category_meta($itemCategorySlug) : null;
    if ($itemCategorySlug !== '') {
        $categoryCounts[$itemCategorySlug] = ($categoryCounts[$itemCategorySlug] ?? 0) + 1;
    }
}
unset($item);

$items = $allItems;
if ($categoryMeta !== null) {
    $items = array_values(array_filter($items, static function (array $item) use ($categoryMeta): bool {
        $itemCategory = is_array($item['category_meta'] ?? null) ? (string) ($item['category_meta']['slug'] ?? '') : '';
        return $itemCategory === (string) $categoryMeta['slug'];
    }));
}

usort($items, static fn(array $a, array $b): int => ((int) ($b['mtime'] ?? 0)) <=> ((int) ($a['mtime'] ?? 0)));
$categories = [];
foreach (category_registry() as $slug => $row) {
    $meta = category_meta($slug);
    if ($meta !== null) {
        $categories[$slug] = $meta;
    }
}
?>
<section class="container two-col">
  <div class="content">
    <article class="card">
      <div class="section-head">
        <h1><?= esc($categoryMeta['title'] ?? interessa_text('Články')) ?></h1>
        <p class="meta">
          <?php if ($categoryMeta !== null): ?>
            Kurátorovaný prehľad článkov v téme <?= esc($categoryMeta['title']) ?>.
          <?php else: ?>
            Prehľad buying guide článkov, porovnaní, recenzií a základných návodov naprieč hlavnými témami webu.
          <?php endif; ?>
        </p>
      </div>

      <div class="filters-bar" aria-label="Filtre kategórií">
        <a class="filter-chip<?= $categoryMeta === null ? ' is-active' : '' ?>" href="/clanky/"><?= interessa_text('Všetko') ?> (<?= esc((string) count($allItems)) ?>)</a>
        <?php foreach ($categories as $slug => $row): ?>
          <?php $active = $categoryMeta !== null && $categoryMeta['slug'] === $slug; ?>
          <a class="filter-chip<?= $active ? ' is-active' : '' ?>" href="/clanky/?category=<?= esc($slug) ?>"><span class="filter-chip__icon" aria-hidden="true"><?= interessa_category_icon((string) $slug) ?></span><?= esc((string) $row['title']) ?> (<?= esc((string) ($categoryCounts[$slug] ?? 0)) ?>)</a>
        <?php endforeach; ?>
      </div>

      <?php if (!$items): ?>
        <p class="note">Pre tento filter zatiaľ nie sú žiadne články.</p>
      <?php else: ?>
        <p class="search-summary muted">Zobrazené články: <strong><?= esc((string) count($items)) ?></strong></p>
        <div class="hub-grid article-teaser-grid">
          <?php foreach ($items as $item): ?>
            <?php
            $slug = (string) ($item['slug'] ?? '');
            $url = article_url($slug);
            $title = (string) ($item['title'] ?? '');
            $description = (string) ($item['description'] ?? '');
            $itemCategoryMeta = is_array($item['category_meta'] ?? null) ? $item['category_meta'] : null;
            ?>
            <article class="hub-card article-teaser-card">
              <a href="<?= esc($url) ?>">
                <?= interessa_render_image((array) $item['image'], ['class' => 'hub-card-image', 'alt' => $title]) ?>
              </a>
              <div class="hub-card-body article-teaser-body">
                <?php if ($itemCategoryMeta !== null): ?>
                  <a class="hub-card-label" href="/clanky/?category=<?= esc((string) $itemCategoryMeta['slug']) ?>"><?= esc((string) $itemCategoryMeta['title']) ?></a>
                <?php endif; ?>
                <h3><a href="<?= esc($url) ?>"><?= esc($title) ?></a></h3>
                <?php if ($description !== ''): ?><p><?= esc($description) ?></p><?php endif; ?>
                <?php if (!empty($item['mtime'])): ?><p class="meta">Aktualizované: <?= esc(date('d.m.Y', (int) $item['mtime'])) ?></p><?php endif; ?>
                <a class="card-link" href="<?= esc($url) ?>">Otvoriť článok</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </article>
  </div>

  <?php include __DIR__ . '/../inc/sidebar.php'; ?>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>