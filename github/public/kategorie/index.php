<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/category-hubs.php';

$page_title = 'Kategórie | Interesa';
$page_description = 'Tematické huby pre proteíny, výživu, vitamíny a minerály, imunitu, silu a výkon aj kĺby a kožu.';
$page_canonical = '/kategorie';
$page_image = asset('img/brand/og-default.svg');
$page_og_type = 'website';
$hubs = interessa_category_hubs();

$page_schema = [
    breadcrumb_schema([
        ['name' => 'Domov', 'url' => '/'],
        ['name' => 'Kategórie', 'url' => '/kategorie'],
    ]),
    [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'Kategórie',
        'description' => $page_description,
        'url' => absolute_url('/kategorie'),
    ],
];

include __DIR__ . '/../inc/head.php';
?>
<section class="container">
  <article class="card hub-hero-card">
    <p class="hub-eyebrow">Obsahové huby</p>
    <h1>Kategórie</h1>
    <p class="lead">Tieto landingy sú postavené ako tematické huby. V každej kategórii nájdeš najdôležitejšie články, ktoré dáva zmysel otvoriť ako prvé.</p>
  </article>

  <div class="hub-grid" style="margin-top:1rem;">
    <?php foreach ($hubs as $slug => $hub): ?>
      <article class="hub-card">
        <?= interessa_render_image(interessa_category_image_meta($slug, 'hero', true), ['class' => 'hub-card-image', 'alt' => $hub['title']]) ?>
        <div class="hub-card-body">
          <span class="hub-card-label"><?= count($hub['featured_guides']) ?> články</span>
          <h3><a href="<?= esc(category_url($slug)) ?>"><?= esc($hub['title']) ?></a></h3>
          <p><?= esc($hub['description']) ?></p>
          <a class="btn" href="<?= esc(category_url($slug)) ?>">Zobraziť kategóriu</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>