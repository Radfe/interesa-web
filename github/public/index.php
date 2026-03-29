<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$page_title = 'Interesa.sk - porovnania doplnkov vyzivy, proteinov a vitaminov';
$page_description = 'Prakticke porovnania, nakupne navody a odporucania pre proteiny, vitaminy, mineraly a dalsie doplnky vyzivy.';
$page_canonical = '/';
$brandOgImage = interessa_brand_image_meta('og-default', true);
$page_image = (string) ($brandOgImage['src'] ?? asset('img/brand/og-default.svg'));
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

$leadArticleSlug = 'najlepsie-proteiny-2026';
$leadArticleMeta = article_meta($leadArticleSlug);
$leadArticleTitle = function_exists('interessa_fix_mojibake')
    ? interessa_fix_mojibake((string) ($leadArticleMeta['title'] ?? humanize_slug($leadArticleSlug)))
    : (string) ($leadArticleMeta['title'] ?? humanize_slug($leadArticleSlug));

$firstClickArticles = [
    [
        'slug' => 'najlepsie-proteiny-2026',
        'problem' => 'Chces rychlo vybrat protein podla ciela, nie podla marketingu alebo nahodnej akcie.',
        'why_start' => 'Je to najlepsi univerzalny vstup do webu aj do realneho vyberu proteinu.',
        'cta' => 'Otvorit vyber proteinov',
    ],
    [
        'slug' => 'kreatin-porovnanie',
        'problem' => 'Nevies, ci ti staci monohydrat alebo ma zmysel pozerat aj ine formy kreatinu.',
        'why_start' => 'Dostanes rychle rozhodnutie aj porovnanie bez zbytocneho studovania detailov.',
        'cta' => 'Otvorit porovnanie kreatinu',
    ],
    [
        'slug' => 'pre-workout-ako-vybrat',
        'problem' => 'Riesis predtreningovku a nechces kupit prilis silny alebo zle zvoleny stimulant.',
        'why_start' => 'Clanok rychlo rozdeli, ci potrebujes vyvazeny stim, silnejsiu volbu alebo pump-focused cestu.',
        'cta' => 'Otvorit vyber pre-workoutu',
    ],
    [
        'slug' => 'doplnky-vyzivy',
        'problem' => 'Chces si upratat zakladne doplnky a vediet, co ma zmysel brat pravidelne.',
        'why_start' => 'Je to najrychlejsi vstup do kazdodennej rutiny, ak nechces riesit cely web naraz.',
        'cta' => 'Otvorit zaklad doplnkov',
    ],
];

include __DIR__ . '/inc/head.php';
?>
<section class="hero">
  <div class="container hero-inner">
    <div class="hero-copy">
      <p class="hub-eyebrow">Prvy klik bez chaosu</p>
      <h1>Najrychlejsia cesta na Interese vedie cez vyber najlepsieho proteinu alebo cez ciel, ktory chces vyriesit.</h1>
      <p>Ak chces jeden silny start nad celym webom, otvor clanok <?= esc($leadArticleTitle) ?>. Ak sa radsej rozhodujes podla problemu, chod rovno na kategorie a vyber si temu.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="<?= esc(article_url($leadArticleSlug)) ?>">Chcem si vybrat najlepsi protein</a>
        <a class="btn btn-ghost" href="/kategorie">Chcem sa rozhodnut podla ciela</a>
      </div>
    </div>

    <div class="hero-media">
      <figure class="hero-figure">
        <?= interessa_render_image($homeHeroImage, ['style' => 'aspect-ratio:16/9;object-fit:cover;']) ?>
        <figcaption>Najprv jedna jasna odpoved, az potom dalsie clanky a vyber produktov.</figcaption>
      </figure>
    </div>
  </div>
</section>

<section class="container home-section home-section--quickstart">
  <div class="section-head">
    <p class="hub-eyebrow">Najlepsie prve kliky</p>
    <h2>Ak nechces bludit po webe, zacni jednym z tychto styroch clankov</h2>
    <p class="meta">Kazdy z nich riesi iny typ rozhodnutia a hned ta posunie k najsilnejsiemu dalsiemu kroku.</p>
  </div>

  <div class="hub-grid article-teaser-grid">
    <?php foreach ($firstClickArticles as $entry): ?>
      <?php
      $slug = (string) ($entry['slug'] ?? '');
      $meta = article_meta($slug);
      $title = function_exists('interessa_fix_mojibake')
          ? interessa_fix_mojibake((string) ($meta['title'] ?? humanize_slug($slug)))
          : (string) ($meta['title'] ?? humanize_slug($slug));
      $image = interessa_article_image_meta($slug, 'thumb', true);
      ?>
      <article class="hub-card article-teaser-card">
        <a href="<?= esc(article_url($slug)) ?>">
          <?= interessa_render_image($image, ['class' => 'hub-card-image', 'alt' => $title]) ?>
        </a>
        <div class="hub-card-body article-teaser-body">
          <span class="hub-card-label">Prvy klik</span>
          <h3><a href="<?= esc(article_url($slug)) ?>"><?= esc($title) ?></a></h3>
          <p><strong>Riesi:</strong> <?= esc((string) ($entry['problem'] ?? '')) ?></p>
          <p><strong>Preco zacat tu:</strong> <?= esc((string) ($entry['why_start'] ?? '')) ?></p>
          <a class="btn btn-primary" href="<?= esc(article_url($slug)) ?>"><?= esc((string) ($entry['cta'] ?? 'Otvorit clanok')) ?></a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="container home-section home-trust">
  <div class="section-head">
    <h2>Ako pristupujeme k odporucaniam</h2>
    <p class="meta">Cielom je rychle rozhodnutie bez zbytocneho pretlaku a bez slubov, ktore web nevie realne obhajit.</p>
  </div>

  <div class="card-grid home-trust-grid">
    <article class="card">
      <div class="card-body">
        <h3>Porovnavame viac obchodov</h3>
        <p>Kde to dava zmysel, porovnavame viac merchantov a netlacime jednu moznost do kazdeho clanku.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>Vyberame produkty podla realneho pouzitia</h3>
        <p>Najprv riesime, pre koho a na co produkt dava zmysel. Az potom cenu, formu a konkretne CTA do obchodu.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>Affiliate odkazy nemenia cenu</h3>
        <p>Niektore odkazy vedu do partnerskych obchodov. Ak cez ne nakupis, web moze ziskat proviziu bez navysenia ceny pre teba.</p>
      </div>
    </article>
  </div>
</section>

<?php include __DIR__ . '/inc/footer.php'; ?>
