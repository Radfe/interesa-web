<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/category-hubs.php';

$page_title = interessa_text('Kateg&oacute;rie | Interesa');
$page_description = interessa_text('Tematick&eacute; huby pre prote&iacute;ny, v&yacute;živu, vitam&iacute;ny a miner&aacute;ly, imunitu, silu a v&yacute;kon aj kĺby a kožu.');
$page_canonical = '/kategorie';
$page_image = asset('img/brand/og-default.svg');
$page_og_type = 'website';
$hubs = interessa_category_hubs();

$primarySlugs = ['proteiny', 'vyziva', 'mineraly', 'sila', 'klby-koza', 'imunita'];
$secondarySlugs = ['chudnutie', 'kreatin', 'pre-workout', 'probiotika-travenie', 'aminokyseliny', 'doplnkove-prislusenstvo'];

$primaryHubs = [];
foreach ($primarySlugs as $slug) {
    if (!isset($hubs[$slug])) {
        continue;
    }

    $primaryHubs[$slug] = $hubs[$slug];
}

$secondaryHubs = [];
foreach ($secondarySlugs as $slug) {
    if (!isset($hubs[$slug])) {
        continue;
    }

    $secondaryHubs[$slug] = $hubs[$slug];
}

$page_schema = [
    breadcrumb_schema([
        ['name' => 'Domov', 'url' => '/'],
        ['name' => interessa_text('Kategórie'), 'url' => '/kategorie'],
    ]),
    [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => interessa_text('Kategórie'),
        'description' => $page_description,
        'url' => absolute_url('/kategorie'),
    ],
];

include __DIR__ . '/../inc/head.php';
?>
<section class="container home-section">
  <article class="card hub-hero-card categories-overview">
    <p class="hub-eyebrow">Obsahov&eacute; huby</p>
    <h1>Kateg&oacute;rie</h1>
    <p class="lead">Kateg&oacute;rie na Interese s&uacute; postaven&eacute; ako tematick&eacute; huby. Každ&aacute; z nich ťa m&aacute; dostať od všeobecnej orient&aacute;cie k najd&ocirc;ležitejš&iacute;m článkom a až potom ku konkr&eacute;tnym produktom.</p>
    <div class="stats-strip categories-stats" aria-label="Prehľad kateg&oacute;ri&iacute;">
      <article class="stats-card">
        <strong><?= count($primaryHubs) ?></strong>
        <p>hlavn&yacute;ch kateg&oacute;ri&iacute; s najväčším SEO a obchodn&yacute;m potenci&aacute;lom</p>
      </article>
      <article class="stats-card">
        <strong><?= count($secondaryHubs) ?></strong>
        <p>podporn&yacute;ch t&eacute;m pre intern&eacute; prelinkovanie a dlhš&iacute; chvost</p>
      </article>
      <article class="stats-card">
        <strong><?= count($hubs) ?></strong>
        <p>hubov pripraven&yacute;ch na ďalšie články, porovnania a recenzie</p>
      </article>
    </div>
  </article>
</section>

<section class="container home-section">
  <div class="section-head">
    <h2>Hlavn&eacute; kateg&oacute;rie</h2>
    <p class="meta">Najsilnejšie t&eacute;my webu, na ktor&yacute;ch stoj&iacute; väčšina n&aacute;kupn&yacute;ch n&aacute;vodov, porovnan&iacute; a recenzi&iacute;.</p>
  </div>

  <div class="hub-grid">
    <?php foreach ($primaryHubs as $slug => $hub): ?>
      <?php $guideCount = count((array) ($hub['featured_guides'] ?? [])); ?>
      <article class="hub-card">
        <?= interessa_render_image(interessa_category_image_meta($slug, 'hero', true), ['class' => 'hub-card-image', 'alt' => $hub['title']]) ?>
        <div class="hub-card-body">
          <span class="hub-card-icon" aria-hidden="true"><?= interessa_category_icon((string) $slug) ?></span>
          <span class="hub-card-label"><?= esc((string) $guideCount) ?> kľúčové články</span>
          <h3><a href="<?= esc(category_url($slug)) ?>"><?= esc((string) $hub['title']) ?></a></h3>
          <p><?= esc((string) ($hub['description'] ?? '')) ?></p>
          <a class="btn" href="<?= esc(category_url($slug)) ?>">Otvoriť kateg&oacute;riu</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="container home-section">
  <div class="section-head">
    <h2>Špecializovan&eacute; t&eacute;my</h2>
    <p class="meta">Podporn&eacute; huby pre detailnejšie otázky, konkr&eacute;tne typy doplnkov a lepšie tematick&eacute; prelinkovanie.</p>
  </div>

  <div class="hub-grid">
    <?php foreach ($secondaryHubs as $slug => $hub): ?>
      <?php $guideCount = count((array) ($hub['featured_guides'] ?? [])); ?>
      <article class="hub-card">
        <?= interessa_render_image(interessa_category_image_meta($slug, 'hero', true), ['class' => 'hub-card-image', 'alt' => $hub['title']]) ?>
        <div class="hub-card-body">
          <span class="hub-card-icon" aria-hidden="true"><?= interessa_category_icon((string) $slug) ?></span>
          <span class="hub-card-label"><?= esc((string) $guideCount) ?> články</span>
          <h3><a href="<?= esc(category_url($slug)) ?>"><?= esc((string) $hub['title']) ?></a></h3>
          <p><?= esc((string) ($hub['description'] ?? '')) ?></p>
          <a class="btn" href="<?= esc(category_url($slug)) ?>">Pozrieť hub</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="container home-section">
  <div class="section-head">
    <h2>Ako s kateg&oacute;riami pracovať</h2>
    <p class="meta">Najlepší workflow pre n&aacute;vštevn&iacute;ka aj pre bud&uacute;ce dopĺňanie obsahu.</p>
  </div>

  <div class="card-grid home-trust-grid">
    <article class="card">
      <div class="card-body">
        <h3>1. Začni širokou t&eacute;mou</h3>
        <p>Najprv si otvor hlavn&uacute; kateg&oacute;riu podľa cieľa, napr&iacute;klad prote&iacute;ny, sila a v&yacute;kon alebo vitam&iacute;ny a miner&aacute;ly.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>2. Prejdi kľúčov&eacute; články</h3>
        <p>Každ&yacute; hub m&aacute; vlastn&yacute; shortlist článkov, ktor&eacute; maj&uacute; byť vstupom do t&eacute;my, nie len ďalš&iacute;m zoznamom odkazov.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>3. Až potom rieš produkt</h3>
        <p>Produktov&eacute; boxy a CTA d&aacute;vaj&uacute; najväčš&iacute; zmysel až v momente, keď použ&iacute;vateľ rozumie typu produktu a svojmu cieľu.</p>
      </div>
    </article>
  </div>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>