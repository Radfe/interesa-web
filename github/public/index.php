<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/category-hubs.php';

$page_title = 'Interesa.sk - výživa, proteíny, vitamíny a minerály';
$page_description = 'Nezávislé porovnania, nákupné návody a praktické články o proteínoch, výžive, vitamínoch, mineráloch a regenerácii.';
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
      <p class="hub-eyebrow">Affiliate magazín o výžive</p>
      <h1>Vyber si doplnky a výživu bez chaosu a marketingového balastu</h1>
      <p>Interesa spája tematické huby, nákupné návody, recenzie a porovnania tak, aby si sa vedel rýchlo dostať k rozumnému výberu podľa cieľa.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="/clanky/najlepsie-proteiny-2025">Pozrieť porovnania</a>
        <a class="btn btn-ghost" href="/kategorie">Prejsť kategórie</a>
      </div>
    </div>

    <div class="hero-media">
      <figure class="hero-figure">
        <?= interessa_render_image($homeHeroImage, ['style' => 'aspect-ratio:16/9;object-fit:cover;']) ?>
        <figcaption>Praktické návody, čisté /go/ odkazy a obsah stavaný na dlhodobé používanie.</figcaption>
      </figure>
    </div>
  </div>
</section>

<section class="container stats-strip" aria-label="Čo na webe nájdeš">
  <article class="stats-card">
    <strong>Tematické huby</strong>
    <p>Začni podľa cieľa a až potom rieš konkrétny produkt.</p>
  </article>
  <article class="stats-card">
    <strong>Nákupné návody</strong>
    <p>Porovnania pre proteíny, kreatín, kolagén aj vegánske alternatívy.</p>
  </article>
  <article class="stats-card">
    <strong>Centrálne odkazy</strong>
    <p>Affiliate vrstva je oddelená od obsahu a spravovaná cez interné <code>/go/</code> route.</p>
  </article>
</section>

<section class="container home-section">
  <div class="section-head">
    <h2>Začni podľa témy</h2>
    <p class="meta">Najväčšie obsahové huby webu. Každá kategória zhromažďuje hlavné články, ktoré dávajú zmysel otvoriť ako prvé.</p>
  </div>

  <div class="hub-grid">
    <?php foreach ($featuredCategories as $category): ?>
      <article class="hub-card">
        <?= interessa_render_image($category['image'], ['class' => 'hub-card-image', 'alt' => $category['title']]) ?>
        <div class="hub-card-body">
          <span class="hub-card-label"><?= esc((string) $category['count']) ?> články</span>
          <h3><a href="<?= esc(category_url((string) $category['slug'])) ?>"><?= esc((string) $category['title']) ?></a></h3>
          <p><?= esc((string) $category['description']) ?></p>
          <a class="btn" href="<?= esc(category_url((string) $category['slug'])) ?>">Otvoriť kategóriu</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="container home-section">
  <div class="section-head">
    <h2>Najdôležitejšie nákupné návody</h2>
    <p class="meta">Najpraktickejšie články, od ktorých sa oplatí začať, ak už riešiš konkrétny výber produktu.</p>
  </div>

  <div class="hub-grid">
    <?php foreach ($featuredGuides as $guide): ?>
      <article class="hub-card">
        <?= interessa_render_image($guide['image'], ['class' => 'hub-card-image', 'alt' => $guide['title']]) ?>
        <div class="hub-card-body">
          <span class="hub-card-label">Sprievodca</span>
          <h3><a href="<?= esc(article_url((string) $guide['slug'])) ?>"><?= esc((string) $guide['title']) ?></a></h3>
          <?php if ($guide['description'] !== ''): ?><p><?= esc((string) $guide['description']) ?></p><?php endif; ?>
          <a class="btn" href="<?= esc(article_url((string) $guide['slug'])) ?>">Čítať článok</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="container two-col home-story">
  <div class="content">
    <article class="lead-article">
      <header>
        <p class="hub-eyebrow">Odporúčaný štart</p>
        <h2><?= esc($homeLeadMeta['title']) ?></h2>
        <p class="meta">Ak práve riešiš chudnutie alebo chceš rozumieť rozdielu medzi WPC a WPI, tu má zmysel začať.</p>
      </header>

      <figure class="inline-figure">
        <?= interessa_render_image($homeLeadImage, ['style' => 'object-fit:cover;']) ?>
      </figure>

      <p>Najčastejšia chyba pri kúpe proteínu je, že sa rieši značka skôr než typ proteínu, cieľ a tolerancia laktózy. Tento článok pomáha rozlíšiť, kedy dáva zmysel klasický koncentrát a kedy sa oplatí izolát.</p>
      <ul class="hub-checklist">
        <li>WPC býva praktickejšie pri rozpočte a každodennom používaní.</li>
        <li>WPI dáva väčší zmysel pri diéte alebo citlivosti na laktózu.</li>
        <li>V článku už máš aj pripravený shortlist produktov a čisté CTA do obchodu.</li>
      </ul>
      <p><a class="btn btn-primary" href="<?= esc(article_url($homeLeadSlug)) ?>">Otvoriť článok</a></p>
    </article>
  </div>

  <?php include __DIR__ . '/inc/sidebar.php'; ?>
</section>

<section class="container home-section home-trust">
  <div class="section-head">
    <h2>Ako je web postavený</h2>
    <p class="meta">Cieľom nie je len rýchly klik, ale zrozumiteľný a udržiavateľný affiliate web s dlhodobým SEO základom.</p>
  </div>

  <div class="card-grid home-trust-grid">
    <article class="card">
      <div class="card-body">
        <h3>Obsah oddelený od affiliate vrstvy</h3>
        <p>Články nie sú zahltené tvrdými affiliate URL. Produkty a odkazy sa spravujú centrálne.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>Pripravené kategórie a huby</h3>
        <p>Kategórie fungujú ako obsahové uzly pre interné prelinkovanie, SEO a lepšiu orientáciu používateľa.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>Image workflow s fallbackmi</h3>
        <p>Aj tam, kde ešte chýba vlastný hero obrázok, web už používa tematický vizuál namiesto rozbitého placeholdera.</p>
      </div>
    </article>
  </div>
</section>

<?php include __DIR__ . '/inc/footer.php'; ?>