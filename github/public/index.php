<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';

$page_title = 'Interesa.sk - vyziva, proteiny, vitaminy a mineraly';
$page_description = 'Nezavisle porovnania, navody a tipy pre doplnky vyzivy, proteiny, vitaminy a zdravsi vyber.';
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
        'alt' => 'Zdrava vyziva a doplnky v jemnom editorial style',
        'sizes' => '(min-width: 1200px) 540px, 100vw',
        'loading' => 'eager',
        'fetchpriority' => 'high',
    ],
    'article',
    true
);
$homeLeadImage = interessa_article_image_meta('najlepsi-protein-na-chudnutie-wpc-vs-wpi', 'hero', true);

include __DIR__ . '/inc/head.php';
?>
<section class="hero">
  <div class="container hero-inner">
    <div class="hero-copy">
      <h1>Vyber si to najlepsie pre svoje zdravie</h1>
      <p>Prakticke porovnania, nakupne navody a prehladne clanky o proteinoch, vyzive, vitaminoch a regeneracii.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="/clanky/najlepsie-proteiny-2025">Pozriet porovnania</a>
        <a class="btn btn-ghost" href="/clanky/">Citat clanky</a>
      </div>
    </div>

    <div class="hero-media">
      <figure class="hero-figure">
        <?= interessa_render_image($homeHeroImage, ['style' => 'aspect-ratio:16/9;object-fit:cover;']) ?>
        <figcaption>Prehladne odporucania podla ciela.</figcaption>
      </figure>
    </div>
  </div>
</section>

<section class="promo-cards container">
  <article class="card">
    <?= interessa_render_image(interessa_category_image_meta('proteiny', 'hero', true), ['style' => 'object-fit:cover;']) ?>
    <div class="card-body">
      <h3>Proteiny na chudnutie</h3>
      <p>Jasne vysvetlenia, porovnania a tipy na vyber podla ciela.</p>
      <a class="card-link" href="/clanky/protein-na-chudnutie">Zobrazit tipy</a>
    </div>
  </article>

  <article class="card">
    <?= interessa_render_image(interessa_category_image_meta('vyziva', 'hero', true), ['style' => 'object-fit:cover;']) ?>
    <div class="card-body">
      <h3>Zdrava vyziva</h3>
      <p>Prakticke odporucania pre snacky, doplnky a kazdodenny vyber.</p>
      <a class="card-link" href="/clanky/doplnky-vyzivy">Odporucane produkty</a>
    </div>
  </article>

  <article class="card">
    <?= interessa_render_image(interessa_category_image_meta('mineraly', 'hero', true), ['style' => 'object-fit:cover;']) ?>
    <div class="card-body">
      <h3>Vitaminy a mineraly</h3>
      <p>Zrozumitelny prehlad zakladnych doplnkov pre imunitu, energiu a regeneraciu.</p>
      <a class="card-link" href="/kategorie/mineraly">Zistit viac</a>
    </div>
  </article>
</section>

<section class="container two-col">
  <div class="content">
    <article class="lead-article">
      <header>
        <h2>Najlepsi protein na chudnutie: WPC vs WPI</h2>
        <p class="meta">Rychle porovnanie srvatkovych proteinov pre redukciu hmotnosti.</p>
      </header>

      <p>WPC byva dostupnejsi a chutovo prijemnejsi. WPI ma menej laktozy a viac bielkovin na davku. Ak riesis citlivost na laktozu alebo chces cistejsi profil, WPI dava vacsi zmysel.</p>
      <p>Ak chces ist rovno do nakupnych tipov, pozri si nase <a href="/clanky/najlepsie-proteiny-2025">porovnanie proteinov</a>.</p>

      <figure class="inline-figure">
        <?= interessa_render_image($homeLeadImage, ['style' => 'object-fit:cover;']) ?>
      </figure>
    </article>
  </div>

  <?php include __DIR__ . '/inc/sidebar.php'; ?>
</section>

<?php include __DIR__ . '/inc/footer.php'; ?>