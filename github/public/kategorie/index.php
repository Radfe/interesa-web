<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/category-hubs.php';

$page_title = 'Kategorie | Interesa';
$page_description = 'Tematicke huby pre proteiny, vyzivu, vitaminy a mineraly, imunitu, silu a vykon aj klby a kozu.';
$page_canonical = '/kategorie';
$page_image = asset('img/brand/og-default.svg');
$page_og_type = 'website';
$hubs = interessa_category_hubs();

$page_schema = [
    breadcrumb_schema([
        ['name' => 'Domov', 'url' => '/'],
        ['name' => 'Kategorie', 'url' => '/kategorie'],
    ]),
    [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'Kategorie',
        'description' => $page_description,
        'url' => absolute_url('/kategorie'),
    ],
];

include __DIR__ . '/../inc/head.php';
?>
<section class="container">
  <article class="card hub-hero-card">
    <p class="hub-eyebrow">Obsahove huby</p>
    <h1>Kategorie</h1>
    <p class="lead">Tieto landingy su postavene ako tematicke huby. V kazdej kategorii najdes najdolezitejsie clanky, ktore dava zmysel otvorit ako prve.</p>
  </article>

  <div class="hub-grid" style="margin-top:1rem;">
    <?php foreach ($hubs as $slug => $hub): ?>
      <article class="hub-card">
        <?= interessa_render_image(interessa_category_image_meta($slug, 'hero', true), ['class' => 'hub-card-image', 'alt' => $hub['title']]) ?>
        <div class="hub-card-body">
          <span class="hub-card-label"><?= count($hub['featured_guides']) ?> clanky</span>
          <h3><a href="<?= esc(category_url($slug)) ?>"><?= esc($hub['title']) ?></a></h3>
          <p><?= esc($hub['description']) ?></p>
          <a class="btn" href="<?= esc(category_url($slug)) ?>">Zobrazit kategoriu</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>