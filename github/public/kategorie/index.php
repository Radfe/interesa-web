<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';

$page_title = 'Kategórie | Interesa';
$page_description = 'Tematické huby pre proteíny, zdravú výživu, vitamíny, minerály, imunitu, výkon a ďalšie oblasti s vysokým SEO potenciálom.';
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
        'icon' => category_icon($slug),
    ];
}
?>
<section class="container content-stack">
  <div class="section-heading">
    <div>
      <span class="eyebrow">Tematické huby</span>
      <h1>Kategórie, ktoré budú ťahať rast webu</h1>
      <p class="section-intro">Každá kategória funguje ako obsahový hub. Z nej sa bude vetviť séria SEO článkov, porovnaní a nákupných tém s vysokým potenciálom návštevnosti.</p>
    </div>
  </div>

  <div class="hub-grid">
    <?php foreach ($items as $item): ?>
      <article class="hub-card category-hub-card">
        <div class="hub-icon-wrap">
          <img src="<?= esc($item['icon']) ?>" alt="<?= esc($item['title']) ?>" width="48" height="48">
        </div>
        <h2><?= esc($item['title']) ?></h2>
        <p><?= esc($item['description']) ?></p>
        <a class="btn btn-primary" href="<?= esc($item['url']) ?>">Otvoriť kategóriu</a>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php include __DIR__ . '/../inc/footer.php'; ?>