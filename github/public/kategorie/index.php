<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';

$page_title = 'Kategórie | Interesa';
$page_description = 'Tematické huby pre proteíny, zdravú výživu, vitamíny, minerály, imunitu, výkon a ďalšie oblasti, v ktorých sa dá rýchlo zorientovať.';
$page_type = 'CollectionPage';
include __DIR__ . '/../inc/head.php';

$preferredOrder = ['proteiny', 'mineraly', 'kreatin', 'klby-koza', 'probiotika-travenie', 'chudnutie', 'vyziva', 'imunita', 'pre-workout', 'aminokyseliny', 'sila'];
$activeItems = [];
$buildingItems = [];

foreach ($preferredOrder as $slug) {
    $category = category_meta($slug);
    if ($category === null) {
        continue;
    }

    $hub = category_hub_data($slug);
    $firstFeatured = $hub['featured_articles'][0] ?? null;
    $item = [
        'slug' => $slug,
        'title' => $category['title'],
        'description' => $category['description'],
        'intro' => (string) ($hub['intro'] ?? $category['description']),
        'url' => category_url($slug),
        'count' => (int) ($hub['article_count'] ?? 0),
        'featured' => $firstFeatured,
    ];

    if ($item['count'] > 0 || $firstFeatured !== null) {
        $activeItems[] = $item;
    } else {
        $buildingItems[] = $item;
    }
}

$heroItems = array_slice($activeItems, 0, 3);
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
      <p class="section-intro">Každá kategória funguje ako vstupný bod do jednej témy. Nájdeš v nej odporúčaný štart, praktické články a ďalšie kroky, ktoré dávajú zmysel čítať v správnom poradí.</p>
    </div>
  </div>

  <?php if ($heroItems): ?>
    <div class="hub-grid category-hero-grid">
      <?php foreach ($heroItems as $item): ?>
        <article class="hub-card category-overview-card">
          <span class="hub-label">Najsilnejší cluster</span>
          <div class="hub-card-meta">
            <strong><?= (int) $item['count'] > 0 ? (int) $item['count'] . ' článkov' : 'Tematický cluster' ?></strong>
          </div>
          <h2><?= esc($item['title']) ?></h2>
          <p><?= esc($item['intro']) ?></p>
          <?php if ($item['featured'] !== null): ?>
            <a class="card-link" href="<?= esc($item['featured']['url']) ?>">Začať článkom: <?= esc($item['featured']['title']) ?></a>
          <?php endif; ?>
          <a class="btn btn-primary" href="<?= esc($item['url']) ?>">Otvoriť kategóriu</a>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <section class="content-stack">
    <div class="section-heading section-heading-tight">
      <div>
        <span class="eyebrow">Všetky aktívne huby</span>
        <h2>Kategórie, ktoré už majú obsahový základ</h2>
        <p class="section-intro">Tieto témy už prepájajú články do zmysluplného clustra, takže sú najlepším miestom, kde začať.</p>
      </div>
    </div>

    <div class="hub-grid category-grid-simple">
      <?php foreach ($activeItems as $item): ?>
        <article class="hub-card category-hub-card category-index-card">
          <span class="hub-label">Kategória</span>
          <div class="hub-card-meta">
            <strong><?= (int) $item['count'] > 0 ? (int) $item['count'] . ' článkov' : 'Tematický cluster' ?></strong>
          </div>
          <h2><?= esc($item['title']) ?></h2>
          <p><?= esc($item['description']) ?></p>
          <?php if ($item['featured'] !== null): ?>
            <a class="mini-link" href="<?= esc($item['featured']['url']) ?>">Odporúčaný štart: <?= esc($item['featured']['title']) ?></a>
          <?php endif; ?>
          <a class="btn btn-primary" href="<?= esc($item['url']) ?>">Otvoriť kategóriu</a>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if ($buildingItems): ?>
    <section class="content-stack">
      <div class="section-heading section-heading-tight">
        <div>
          <span class="eyebrow">Rozširujeme obsah</span>
          <h2>Témy, ktoré budeme ďalej dopĺňať</h2>
          <p class="section-intro">Tieto clustre ešte len staviame, ale tematicky už patria do štruktúry webu.</p>
        </div>
      </div>

      <div class="building-grid">
        <?php foreach ($buildingItems as $item): ?>
          <article class="hub-card building-card">
            <span class="hub-label">Pripravujeme</span>
            <h3><?= esc($item['title']) ?></h3>
            <p><?= esc($item['description']) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/../inc/footer.php'; ?>