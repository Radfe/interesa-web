<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';

$page_title = 'Kategórie | Interesa';
$page_description = 'Tematické huby pre proteíny, zdravú výživu, vitamíny, minerály, imunitu, výkon a ďalšie oblasti, v ktorých sa dá rýchlo zorientovať.';
$page_type = 'CollectionPage';
include __DIR__ . '/../inc/head.php';

$preferredOrder = ['proteiny', 'vyziva', 'mineraly', 'imunita', 'sila', 'klby-koza', 'kreatin', 'pre-workout', 'aminokyseliny', 'probiotika-travenie', 'chudnutie'];
$cats = category_registry();
$items = [];
foreach ($preferredOrder as $slug) {
    if (!isset($cats[$slug])) {
        continue;
    }

    $items[] = [
        'slug' => $slug,
        'title' => $cats[$slug]['title'],
        'description' => $cats[$slug]['description'],
        'url' => category_url($slug),
    ];
}
?>
<section class="container content-stack">
  <nav class="breadcrumbs" aria-label="Breadcrumb">
    <a href="/">Domov</a>
    <span aria-hidden="true">/</span>
    <span>Kategórie</span>
  </nav>

  <div class="section-heading">
    <div>
      <span class="eyebrow">Tematické huby</span>
      <h1>Kategórie, podľa ktorých sa dá rýchlo zorientovať</h1>
      <p class="section-intro">Každá kategória združuje články, porovnania a praktické návody pre jednu konkrétnu oblasť.</p>
    </div>
  </div>

  <div class="hub-grid category-grid-simple">
    <?php foreach ($items as $item): ?>
      <article class="hub-card category-hub-card">
        <span class="hub-label">Kategória</span>
        <h2><?= esc($item['title']) ?></h2>
        <p><?= esc($item['description']) ?></p>
        <a class="btn btn-primary" href="<?= esc($item['url']) ?>">Otvoriť kategóriu</a>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php include __DIR__ . '/../inc/footer.php'; ?>