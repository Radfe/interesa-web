<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';

$page_title = 'Interesa.sk - výživa, proteíny, vitamíny a minerály';
$page_description = 'Nezávislé porovnania, návody a tipy pre doplnky výživy, proteíny, vitamíny a zdravší výber.';
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
        'alt' => 'Zdravá výživa a doplnky v jemnom editorial štýle',
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
      <h1>Vyber si to najlepšie pre svoje zdravie</h1>
      <p>Praktické porovnania, nákupné návody a prehľadné články o proteínoch, výžive, vitamínoch a regenerácii.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="/clanky/najlepsie-proteiny-2025">Pozrieť porovnania</a>
        <a class="btn btn-ghost" href="/clanky/">Čítať články</a>
      </div>
    </div>

    <div class="hero-media">
      <figure class="hero-figure">
        <?= interessa_render_image($homeHeroImage, ['style' => 'aspect-ratio:16/9;object-fit:cover;']) ?>
        <figcaption>Prehľadné odporúčania podľa cieľa.</figcaption>
      </figure>
    </div>
  </div>
</section>

<section class="promo-cards container">
  <article class="card">
    <?= interessa_render_image(interessa_category_image_meta('proteiny', 'hero', true), ['style' => 'object-fit:cover;']) ?>
    <div class="card-body">
      <h3>Proteíny na chudnutie</h3>
      <p>Jasné vysvetlenia, porovnania a tipy na výber podľa cieľa.</p>
      <a class="card-link" href="/clanky/protein-na-chudnutie">Zobraziť tipy</a>
    </div>
  </article>

  <article class="card">
    <?= interessa_render_image(interessa_category_image_meta('vyziva', 'hero', true), ['style' => 'object-fit:cover;']) ?>
    <div class="card-body">
      <h3>Zdravá výživa</h3>
      <p>Praktické odporúčania pre snacky, doplnky a každodenný výber.</p>
      <a class="card-link" href="/clanky/doplnky-vyzivy">Odporúčané produkty</a>
    </div>
  </article>

  <article class="card">
    <?= interessa_render_image(interessa_category_image_meta('mineraly', 'hero', true), ['style' => 'object-fit:cover;']) ?>
    <div class="card-body">
      <h3>Vitamíny a minerály</h3>
      <p>Zrozumiteľný prehľad základných doplnkov pre imunitu, energiu a regeneráciu.</p>
      <a class="card-link" href="/kategorie/mineraly">Zistiť viac</a>
    </div>
  </article>
</section>

<section class="container two-col">
  <div class="content">
    <article class="lead-article">
      <header>
        <h2>Najlepší proteín na chudnutie: WPC vs WPI</h2>
        <p class="meta">Rýchle porovnanie srvátkových proteínov pre redukciu hmotnosti.</p>
      </header>

      <p>WPC býva dostupnejší a chuťovo príjemnejší. WPI má menej laktózy a viac bielkovín na dávku. Ak riešiš citlivosť na laktózu alebo chceš čistejší profil, WPI dáva väčší zmysel.</p>
      <p>Ak chceš ísť rovno do nákupných tipov, pozri si naše <a href="/clanky/najlepsie-proteiny-2025">porovnanie proteínov</a>.</p>

      <figure class="inline-figure">
        <?= interessa_render_image($homeLeadImage, ['style' => 'object-fit:cover;']) ?>
      </figure>
    </article>
  </div>

  <?php include __DIR__ . '/inc/sidebar.php'; ?>
</section>

<?php include __DIR__ . '/inc/footer.php'; ?>