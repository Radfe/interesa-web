<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';

$page_title = 'Články | Interesa';
$page_description = 'Prehľad článkov, recenzií a porovnaní o proteínoch, výžive, vitamínoch a ďalších témach, v ktorých sa dá rýchlo zorientovať.';
$page_type = 'CollectionPage';
include __DIR__ . '/../inc/head.php';

$items = [];
foreach (article_registry() as $slug => $meta) {
    $category = category_meta($meta[2] ?? '');
    $items[] = [
        'slug' => $slug,
        'title' => $meta[0] ?? humanize_slug($slug),
        'description' => $meta[1] ?? '',
        'url' => article_url($slug),
        'image' => article_img($slug),
        'category_title' => $category['title'] ?? 'Článok',
        'category_url' => $category ? category_url($category['slug']) : '/kategorie/',
    ];
}

usort($items, static fn(array $a, array $b): int => strcmp($a['title'], $b['title']));
?>
<section class="container content-stack">
  <nav class="breadcrumbs" aria-label="Breadcrumb">
    <a href="/">Domov</a>
    <span aria-hidden="true">/</span>
    <span>Články</span>
  </nav>

  <div class="section-heading">
    <div>
      <span class="eyebrow">Obsahový archív</span>
      <h1>Články a porovnania</h1>
      <p class="section-intro">Tu nájdeš praktické návody, recenzie a články, ktoré odpovedajú na konkrétne otázky o výžive a doplnkoch.</p>
    </div>
    <a class="btn btn-ghost" href="/search">Vyhľadávať v článkoch</a>
  </div>

  <?php if (!$items): ?>
    <article class="card legal-card">
      <h2>Zatiaľ tu nie sú žiadne články</h2>
      <p>Najprv doplníme základné témy a potom rozšírime obsah o ďalšie praktické otázky.</p>
    </article>
  <?php else: ?>
    <div class="grid-cards article-card-grid">
      <?php foreach ($items as $item): ?>
        <article class="post-card">
          <a href="<?= esc($item['url']) ?>">
            <img class="thumb" src="<?= esc($item['image']) ?>" alt="<?= esc($item['title']) ?>" loading="lazy" decoding="async">
          </a>
          <div class="post-card-body">
            <a class="chip" href="<?= esc($item['category_url']) ?>"><?= esc($item['category_title']) ?></a>
            <h3><a href="<?= esc($item['url']) ?>"><?= esc($item['title']) ?></a></h3>
            <p><?= esc($item['description']) ?></p>
            <a class="btn btn-ghost" href="<?= esc($item['url']) ?>">Čítať článok</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/../inc/footer.php'; ?>