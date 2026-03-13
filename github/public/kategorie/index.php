<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/category-hubs.php';
require_once __DIR__ . '/../inc/article-commerce.php';

$page_title = 'Kategorie | Interesa';
$page_description = 'Tematicke huby pre proteiny, vyzivu, vitaminy a mineraly, imunitu, silu a vykon aj klby a kozu.';
$page_canonical = '/kategorie';
$page_image = asset('img/brand/og-default.svg');
$page_og_type = 'website';
$hubs = interessa_category_hubs();

$primarySlugs = ['proteiny', 'vyziva', 'mineraly', 'sila', 'klby-koza', 'imunita'];
$secondarySlugs = ['chudnutie', 'kreatin', 'pre-workout', 'probiotika-travenie', 'aminokyseliny', 'doplnkove-prislusenstvo'];

$primaryHubs = [];
foreach ($primarySlugs as $slug) {
    if (isset($hubs[$slug])) {
        $primaryHubs[$slug] = $hubs[$slug];
    }
}

$secondaryHubs = [];
foreach ($secondarySlugs as $slug) {
    if (isset($hubs[$slug])) {
        $secondaryHubs[$slug] = $hubs[$slug];
    }
}

$hubArticleCount = [];
$hubCommercialCount = [];
$hubFullCoverageCount = [];
$totalCommercialArticles = 0;
$totalFullCoverageArticles = 0;
foreach (array_keys($hubs) as $slug) {
    $articles = array_values(category_articles($slug));
    $hubArticleCount[$slug] = count($articles);
    $hubCommercialCount[$slug] = count(array_filter($articles, static function (array $item): bool {
        return interessa_article_has_commerce((string) ($item['slug'] ?? ''));
    }));
    $hubFullCoverageCount[$slug] = count(array_filter($articles, static function (array $item): bool {
        return interessa_article_has_full_packshot_coverage((string) ($item['slug'] ?? ''));
    }));
    $totalCommercialArticles += (int) ($hubCommercialCount[$slug] ?? 0);
    $totalFullCoverageArticles += (int) ($hubFullCoverageCount[$slug] ?? 0);
}
$activeCommercialHubs = count(array_filter($hubCommercialCount, static fn(int $count): bool => $count > 0));

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
<section class="container home-section">
  <article class="card hub-hero-card categories-overview">
    <p class="hub-eyebrow">Obsahove huby</p>
    <h1>Kategorie</h1>
    <p class="lead">Kategorie na Interese su stavane ako tematicke huby. Ich cielom je dostat citatela od sirokej orientacie k najdolezitejsim clankom a az potom ku konkretnym produktom.</p>
    <div class="stats-strip categories-stats" aria-label="Prehlad kategorii">
      <article class="stats-card">
        <strong><?= count($primaryHubs) ?></strong>
        <p>hlavnych kategorii s najvacsim SEO a obchodnym potencialom</p>
      </article>
      <article class="stats-card">
        <strong><?= count($secondaryHubs) ?></strong>
        <p>specializovanych tem pre interne prelinkovanie a dlhsi chvost</p>
      </article>
      <article class="stats-card">
        <strong><?= count($hubs) ?></strong>
        <p>hubov pripravenych na dalsie clanky, porovnania a recenzie</p>
      </article>
      <article class="stats-card">
        <strong><?= esc((string) $totalCommercialArticles) ?></strong>
        <p>clankov s odporucaniami napriec kategoriami</p>
      </article>
      <article class="stats-card">
        <strong><?= esc((string) $activeCommercialHubs) ?></strong>
        <p>hubov, kde uz je pripraveny komercny obsah</p>
      </article>
      <article class="stats-card">
        <strong><?= esc((string) $totalFullCoverageArticles) ?></strong>
        <p>clankov s hotovymi obrazkami</p>
      </article>
    </div>
    <div class="hero-cta">
      <a class="btn btn-ghost" href="/clanky?commercial=1">Otvorit clanky s odporucaniami</a>
      <a class="btn btn-ghost" href="/clanky?coverage=full">Pozriet najviac pripravene</a>
    </div>
  </article>
</section>

<section class="container home-section">
  <div class="section-head">
    <h2>Hlavne kategorie</h2>
    <p class="meta">Najsilnejsie temy webu, na ktorych stoji vacsina nakupnych navodov, porovnani a recenzii.</p>
  </div>

  <div class="hub-grid">
    <?php foreach ($primaryHubs as $slug => $hub): ?>
      <?php $guideCount = count((array) ($hub['featured_guides'] ?? [])); ?>
      <?php $articleCount = (int) ($hubArticleCount[$slug] ?? 0); ?>
      <?php $commercialCount = (int) ($hubCommercialCount[$slug] ?? 0); ?>
      <?php $fullCoverageCount = (int) ($hubFullCoverageCount[$slug] ?? 0); ?>
      <article class="hub-card">
        <?= interessa_render_image(interessa_category_image_meta($slug, 'hero', true), ['class' => 'hub-card-image', 'alt' => $hub['title']]) ?>
        <div class="hub-card-body">
          <span class="hub-card-icon" aria-hidden="true"><?= interessa_category_icon((string) $slug) ?></span>
          <div class="article-card-meta">
            <span class="hub-card-label"><?= esc((string) $guideCount) ?> klucove clanky</span>
            <span class="article-card-chip"><?= esc((string) $articleCount) ?> <?= esc(interessa_pluralize_slovak($articleCount, 'clanok', 'clanky', 'clankov')) ?></span>
          </div>
          <?php if ($commercialCount > 0): ?>
            <div class="article-card-submeta">
              <span class="article-card-subchip">Odporucania v <?= esc((string) $commercialCount) ?> <?= esc(interessa_pluralize_slovak($commercialCount, 'clanku', 'clankoch', 'clankoch')) ?></span>
              <?php if ($fullCoverageCount > 0): ?>
                <span class="article-card-subchip is-coverage is-full">Najviac pripravene v <?= esc((string) $fullCoverageCount) ?> <?= esc(interessa_pluralize_slovak($fullCoverageCount, 'clanku', 'clankoch', 'clankoch')) ?></span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <h3><a href="<?= esc(category_url($slug)) ?>"><?= esc((string) $hub['title']) ?></a></h3>
          <p><?= esc((string) ($hub['description'] ?? '')) ?></p>
          <a class="btn" href="<?= esc(category_url($slug)) ?>">Otvorit kategoriu</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="container home-section">
  <div class="section-head">
    <h2>Specializovane temy</h2>
    <p class="meta">Podporne huby pre detailnejsie otazky, konkretne typy doplnkov a lepsie tematicke prelinkovanie.</p>
  </div>

  <div class="hub-grid">
    <?php foreach ($secondaryHubs as $slug => $hub): ?>
      <?php $guideCount = count((array) ($hub['featured_guides'] ?? [])); ?>
      <?php $articleCount = (int) ($hubArticleCount[$slug] ?? 0); ?>
      <?php $commercialCount = (int) ($hubCommercialCount[$slug] ?? 0); ?>
      <?php $fullCoverageCount = (int) ($hubFullCoverageCount[$slug] ?? 0); ?>
      <article class="hub-card">
        <?= interessa_render_image(interessa_category_image_meta($slug, 'hero', true), ['class' => 'hub-card-image', 'alt' => $hub['title']]) ?>
        <div class="hub-card-body">
          <span class="hub-card-icon" aria-hidden="true"><?= interessa_category_icon((string) $slug) ?></span>
          <div class="article-card-meta">
            <span class="hub-card-label"><?= esc((string) $guideCount) ?> klucove clanky</span>
            <span class="article-card-chip"><?= esc((string) $articleCount) ?> <?= esc(interessa_pluralize_slovak($articleCount, 'clanok', 'clanky', 'clankov')) ?></span>
          </div>
          <?php if ($commercialCount > 0): ?>
            <div class="article-card-submeta">
              <span class="article-card-subchip">Odporucania v <?= esc((string) $commercialCount) ?> <?= esc(interessa_pluralize_slovak($commercialCount, 'clanku', 'clankoch', 'clankoch')) ?></span>
              <?php if ($fullCoverageCount > 0): ?>
                <span class="article-card-subchip is-coverage is-full">Najviac pripravene v <?= esc((string) $fullCoverageCount) ?> <?= esc(interessa_pluralize_slovak($fullCoverageCount, 'clanku', 'clankoch', 'clankoch')) ?></span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <h3><a href="<?= esc(category_url($slug)) ?>"><?= esc((string) $hub['title']) ?></a></h3>
          <p><?= esc((string) ($hub['description'] ?? '')) ?></p>
          <a class="btn" href="<?= esc(category_url($slug)) ?>">Pozriet hub</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="container home-section">
  <div class="section-head">
    <h2>Ako s kategoriami pracovat</h2>
    <p class="meta">Jednoduchy workflow pre navstevnika aj pre dalsie doplnanie obsahu.</p>
  </div>

  <div class="card-grid home-trust-grid">
    <article class="card">
      <div class="card-body">
        <h3>1. Zacni sirsou temou</h3>
        <p>Najprv otvor hlavnu kategoriu podla ciela, napr. proteiny, sila a vykon alebo vitaminy a mineraly.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>2. Prejdi klucove clanky</h3>
        <p>Kazda tema ma vlastny vyber clankov, ktore maju byt vstupom do temy, nie len dalsim zoznamom odkazov.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>3. Az potom ries produkt</h3>
        <p>Produktove boxy a CTA davaju najvacsi zmysel az v momente, ked citatel rozumie typu produktu a svojmu cielu.</p>
      </div>
    </article>
  </div>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>
