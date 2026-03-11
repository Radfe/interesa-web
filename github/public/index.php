<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/category-hubs.php';

$page_title = interessa_text('Interesa.sk - v&yacute;živa, prote&iacute;ny, vitam&iacute;ny a miner&aacute;ly');
$page_description = interessa_text('Nez&aacute;visl&eacute; porovnania, n&aacute;kupn&eacute; n&aacute;vody a praktick&eacute; články o prote&iacute;noch, v&yacute;žive, vitam&iacute;noch, miner&aacute;loch a regener&aacute;cii.');
$page_canonical = '/';
$page_image = asset('img/brand/og-default.svg');
$page_og_type = 'website';
$page_styles = [asset('css/home-b12.css')];
$page_schema = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'Interesa',
        'url' => absolute_url('/'),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => absolute_url('/search?q={search_term_string}'),
            'query-input' => 'required name=search_term_string',
        ],
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => 'Interesa.sk',
        'description' => $page_description,
        'url' => absolute_url('/'),
    ],
];

$homeHeroImage = interessa_build_image_meta(
    interessa_collect_asset_candidates(['img/hero/hero-1']),
    [
        'alt' => interessa_text('Zdrav&aacute; v&yacute;živa a doplnky v jemnom editorial št&yacute;le'),
        'sizes' => '(min-width: 1200px) 540px, 100vw',
        'loading' => 'eager',
        'fetchpriority' => 'high',
    ],
    'article',
    true
);

$featuredCategorySlugs = ['proteiny', 'vyziva', 'mineraly', 'sila', 'klby-koza', 'imunita'];
$featuredGuideSlugs = [
    'najlepsie-proteiny-2025',
    'kreatin-porovnanie',
    'kolagen-recenzia',
    'veganske-proteiny-top-vyber-2025',
];

$featuredCategories = [];
foreach ($featuredCategorySlugs as $slug) {
    $meta = category_meta($slug);
    $hub = interessa_category_hub($slug);
    if ($meta === null || $hub === null) {
        continue;
    }

    $featuredCategories[] = [
        'slug' => $slug,
        'title' => $meta['title'],
        'description' => $hub['description'] ?? $meta['description'],
        'image' => interessa_category_image_meta($slug, 'hero', true),
        'count' => count((array) ($hub['featured_guides'] ?? [])),
    ];
}

$featuredGuides = [];
foreach ($featuredGuideSlugs as $slug) {
    $meta = article_meta($slug);
    $featuredGuides[] = [
        'slug' => $slug,
        'title' => $meta['title'],
        'description' => $meta['description'],
        'image' => interessa_article_image_meta($slug, 'hero', true),
    ];
}

$homeLeadSlug = 'najlepsi-protein-na-chudnutie-wpc-vs-wpi';
$homeLeadMeta = article_meta($homeLeadSlug);
$homeLeadImage = interessa_article_image_meta($homeLeadSlug, 'hero', true);

include __DIR__ . '/inc/head.php';
?>
<section class="hero">
  <div class="container hero-inner">
    <div class="hero-copy">
      <p class="hub-eyebrow">Affiliate magaz&iacute;n o v&yacute;žive</p>
      <h1>Vyber si doplnky a v&yacute;živu bez chaosu a marketingov&eacute;ho balastu</h1>
      <p>Interesa sp&aacute;ja tematick&eacute; huby, n&aacute;kupn&eacute; n&aacute;vody, recenzie a porovnania tak, aby si sa vedel r&yacute;chlo dostať k rozumn&eacute;mu v&yacute;beru podľa cieľa.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="/clanky/najlepsie-proteiny-2025">Pozrieť porovnania</a>
        <a class="btn btn-ghost" href="/kategorie">Prejsť kateg&oacute;rie</a>
      </div>
    </div>

    <div class="hero-media">
      <figure class="hero-figure">
        <?= interessa_render_image($homeHeroImage, ['style' => 'aspect-ratio:16/9;object-fit:cover;']) ?>
        <figcaption>Praktick&eacute; n&aacute;vody, čist&eacute; /go/ odkazy a obsah stavan&yacute; na dlhodob&eacute; použ&iacute;vanie.</figcaption>
      </figure>
    </div>
  </div>
</section>

<section class="container stats-strip" aria-label="Čo na webe n&aacute;jdeš">
  <article class="stats-card">
    <strong>Tematick&eacute; huby</strong>
    <p>Začni podľa cieľa a až potom rieš konkr&eacute;tny produkt.</p>
  </article>
  <article class="stats-card">
    <strong>N&aacute;kupn&eacute; n&aacute;vody</strong>
    <p>Porovnania pre prote&iacute;ny, kreat&iacute;n, kolag&eacute;n aj veg&aacute;nske alternat&iacute;vy.</p>
  </article>
  <article class="stats-card">
    <strong>Centr&aacute;lne odkazy</strong>
    <p>Affiliate vrstva je oddelen&aacute; od obsahu a spravovan&aacute; cez intern&eacute; <code>/go/</code> route.</p>
  </article>
</section>

<section class="container home-section">
  <div class="section-head">
    <h2>Začni podľa t&eacute;my</h2>
    <p class="meta">Najväčšie obsahov&eacute; huby webu. Každ&aacute; kateg&oacute;ria zhromažďuje hlavn&eacute; články, ktor&eacute; d&aacute;vaj&uacute; zmysel otvoriť ako prv&eacute;.</p>
  </div>

  <div class="hub-grid">
    <?php foreach ($featuredCategories as $category): ?>
      <article class="hub-card">
        <?= interessa_render_image($category['image'], ['class' => 'hub-card-image', 'alt' => $category['title']]) ?>
        <div class="hub-card-body">
          <span class="hub-card-icon" aria-hidden="true"><?= interessa_category_icon((string) $category['slug']) ?></span>
          <span class="hub-card-label"><?= esc((string) $category['count']) ?> články</span>
          <h3><a href="<?= esc(category_url((string) $category['slug'])) ?>"><?= esc((string) $category['title']) ?></a></h3>
          <p><?= esc((string) $category['description']) ?></p>
          <a class="btn" href="<?= esc(category_url((string) $category['slug'])) ?>">Otvoriť kateg&oacute;riu</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="container home-section">
  <div class="section-head">
    <h2>Najd&ocirc;ležitejšie n&aacute;kupn&eacute; n&aacute;vody</h2>
    <p class="meta">Najpraktickejšie články, od ktorých sa oplat&iacute; začať, ak už riešiš konkr&eacute;tny v&yacute;ber produktu.</p>
  </div>

  <div class="hub-grid">
    <?php foreach ($featuredGuides as $guide): ?>
      <article class="hub-card">
        <?= interessa_render_image($guide['image'], ['class' => 'hub-card-image', 'alt' => $guide['title']]) ?>
        <div class="hub-card-body">
          <span class="hub-card-label">Sprievodca</span>
          <h3><a href="<?= esc(article_url((string) $guide['slug'])) ?>"><?= esc((string) $guide['title']) ?></a></h3>
          <?php if ($guide['description'] !== ''): ?><p><?= esc((string) $guide['description']) ?></p><?php endif; ?>
          <a class="btn" href="<?= esc(article_url((string) $guide['slug'])) ?>">Č&iacute;tať článok</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="container two-col home-story">
  <div class="content">
    <article class="lead-article">
      <header>
        <p class="hub-eyebrow">Odpor&uacute;čan&yacute; štart</p>
        <h2><?= esc($homeLeadMeta['title']) ?></h2>
        <p class="meta">Ak pr&aacute;ve riešiš chudnutie alebo chceš rozumieť rozdielu medzi WPC a WPI, tu m&aacute; zmysel začať.</p>
      </header>

      <figure class="inline-figure">
        <?= interessa_render_image($homeLeadImage, ['style' => 'object-fit:cover;']) ?>
      </figure>

      <p>Najčastejšia chyba pri k&uacute;pe prote&iacute;nu je, že sa rieši značka sk&ocirc;r než typ prote&iacute;nu, cieľ a tolerancia lakt&oacute;zy. Tento článok pom&aacute;ha rozl&iacute;šiť, kedy d&aacute;va zmysel klasick&yacute; koncentr&aacute;t a kedy sa oplat&iacute; izol&aacute;t.</p>
      <ul class="hub-checklist">
        <li>WPC b&yacute;va praktickejšie pri rozpočte a každodennom použ&iacute;van&iacute;.</li>
        <li>WPI d&aacute;va väčš&iacute; zmysel pri di&eacute;te alebo citlivosti na lakt&oacute;zu.</li>
        <li>V článku už m&aacute;š aj pripraven&yacute; shortlist produktov a čist&eacute; CTA do obchodu.</li>
      </ul>
      <p><a class="btn btn-primary" href="<?= esc(article_url($homeLeadSlug)) ?>">Otvoriť článok</a></p>
    </article>
  </div>

  <?php include __DIR__ . '/inc/sidebar.php'; ?>
</section>

<section class="container home-section home-trust">
  <div class="section-head">
    <h2>Ako je web postaven&yacute;</h2>
    <p class="meta">Cieľom nie je len r&yacute;chly klik, ale zrozumiteľn&yacute; a udržiavateľn&yacute; affiliate web s dlhodob&yacute;m SEO z&aacute;kladom.</p>
  </div>

  <div class="card-grid home-trust-grid">
    <article class="card">
      <div class="card-body">
        <h3>Obsah oddelen&yacute; od affiliate vrstvy</h3>
        <p>Články nie s&uacute; zahlten&eacute; tvrd&yacute;mi affiliate URL. Produkty a odkazy sa spravuj&uacute; centr&aacute;lne.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>Pripraven&eacute; kateg&oacute;rie a huby</h3>
        <p>Kateg&oacute;rie funguj&uacute; ako obsahov&eacute; uzly pre intern&eacute; prelinkovanie, SEO a lepšiu orient&aacute;ciu použ&iacute;vateľa.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>Image workflow s fallbackmi</h3>
        <p>Aj tam, kde ešte ch&yacute;ba vlastn&yacute; hero obr&aacute;zok, web už použ&iacute;va tematick&yacute; vizu&aacute;l namiesto rozbit&eacute;ho placeholdera.</p>
      </div>
    </article>
  </div>
</section>

<?php include __DIR__ . '/inc/footer.php'; ?>