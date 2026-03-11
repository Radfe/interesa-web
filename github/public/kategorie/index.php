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
<section class="container home-section">
  <article class="card hub-hero-card categories-overview">
    <p class="hub-eyebrow">Obsahové huby</p>
    <h1>Kategórie</h1>
    <p class="lead">Kategórie na Interese sú postavené ako tematické huby. Každá z nich ťa má dostať od všeobecnej orientácie k najdôležitejším článkom a až potom ku konkrétnym produktom.</p>
    <div class="stats-strip categories-stats" aria-label="Prehľad kategórií">
      <article class="stats-card">
        <strong><?= count($primaryHubs) ?></strong>
        <p>hlavných kategórií s najväčším SEO a obchodným potenciálom</p>
      </article>
      <article class="stats-card">
        <strong><?= count($secondaryHubs) ?></strong>
        <p>podporných tém pre interné prelinkovanie a dlhší chvost</p>
      </article>
      <article class="stats-card">
        <strong><?= count($hubs) ?></strong>
        <p>hubov pripravených na ďalšie články, porovnania a recenzie</p>
      </article>
    </div>
  </article>
</section>

<section class="container home-section">
  <div class="section-head">
    <h2>Hlavné kategórie</h2>
    <p class="meta">Najsilnejšie témy webu, na ktorých stojí väčšina nákupných návodov, porovnaní a recenzií.</p>
  </div>

  <div class="hub-grid">
    <?php foreach ($primaryHubs as $slug => $hub): ?>
      <?php $guideCount = count((array) ($hub['featured_guides'] ?? [])); ?>
      <article class="hub-card">
        <?= interessa_render_image(interessa_category_image_meta($slug, 'hero', true), ['class' => 'hub-card-image', 'alt' => $hub['title']]) ?>
        <div class="hub-card-body">
          <span class="hub-card-label"><?= esc((string) $guideCount) ?> kľúčové články</span>
          <h3><a href="<?= esc(category_url($slug)) ?>"><?= esc((string) $hub['title']) ?></a></h3>
          <p><?= esc((string) ($hub['description'] ?? '')) ?></p>
          <a class="btn" href="<?= esc(category_url($slug)) ?>">Otvoriť kategóriu</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="container home-section">
  <div class="section-head">
    <h2>Špecializované témy</h2>
    <p class="meta">Podporné huby pre detailnejšie otázky, konkrétne typy doplnkov a lepšie tematické prelinkovanie.</p>
  </div>

  <div class="hub-grid">
    <?php foreach ($secondaryHubs as $slug => $hub): ?>
      <?php $guideCount = count((array) ($hub['featured_guides'] ?? [])); ?>
      <article class="hub-card">
        <?= interessa_render_image(interessa_category_image_meta($slug, 'hero', true), ['class' => 'hub-card-image', 'alt' => $hub['title']]) ?>
        <div class="hub-card-body">
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
    <h2>Ako s kategóriami pracovať</h2>
    <p class="meta">Najlepší workflow pre návštevníka aj pre budúce dopĺňanie obsahu.</p>
  </div>

  <div class="card-grid home-trust-grid">
    <article class="card">
      <div class="card-body">
        <h3>1. Začni širokou témou</h3>
        <p>Najprv si otvor hlavnú kategóriu podľa cieľa, napríklad proteíny, sila a výkon alebo vitamíny a minerály.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>2. Prejdi kľúčové články</h3>
        <p>Každý hub má vlastný shortlist článkov, ktoré majú byť vstupom do témy, nie len ďalším zoznamom odkazov.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>3. Až potom rieš produkt</h3>
        <p>Produktové boxy a CTA dávajú najväčší zmysel až v momente, keď používateľ rozumie typu produktu a svojmu cieľu.</p>
      </div>
    </article>
  </div>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>