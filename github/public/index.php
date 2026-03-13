<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/category-hubs.php';
require_once __DIR__ . '/inc/article-commerce.php';

$page_title = 'Interesa.sk - vyziva, proteiny, vitaminy a mineraly';
$page_description = 'Nezavisle porovnania, nakupne navody a prakticke clanky o proteinoch, vyzive, vitaminoch, mineraloch a regeneracii.';
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
        'count' => count(category_articles($slug)),
        'featured_count' => count((array) ($hub['featured_guides'] ?? [])),
        'commercial_count' => count(array_filter(array_values(category_articles($slug)), static function (array $item): bool {
            return interessa_article_has_commerce((string) ($item['slug'] ?? ''));
        })),
    ];
}

$featuredGuides = [];
foreach ($featuredGuideSlugs as $slug) {
    $meta = article_meta($slug);
    $categorySlug = normalize_category_slug((string) ($meta['category'] ?? ''));
    $articleFile = __DIR__ . '/content/articles/' . $slug . '.html';
    $featuredGuides[] = [
        'slug' => $slug,
        'title' => $meta['title'],
        'description' => trim((string) ($meta['description'] ?? '')) !== '' ? $meta['description'] : interessa_article_teaser_description($slug),
        'image' => interessa_article_image_meta($slug, 'hero', true),
        'format_label' => interessa_article_format_label($slug, (string) ($meta['title'] ?? '')),
        'commerce_summary' => interessa_article_commerce_summary($slug),
        'category_meta' => $categorySlug !== '' ? category_meta($categorySlug) : null,
        'updated_date' => is_file($articleFile) ? date('d.m.Y', (int) @filemtime($articleFile)) : '',
    ];
}

$homeLeadSlug = 'najlepsi-protein-na-chudnutie-wpc-vs-wpi';
$homeLeadMeta = article_meta($homeLeadSlug);
$homeLeadImage = interessa_article_image_meta($homeLeadSlug, 'hero', true);
$homeLeadCommerceSummary = interessa_article_commerce_summary($homeLeadSlug);
$homeLeadFile = __DIR__ . '/content/articles/' . $homeLeadSlug . '.html';
$homeLeadUpdated = is_file($homeLeadFile) ? date('d.m.Y', (int) @filemtime($homeLeadFile)) : '';
$allIndexedArticles = array_values(indexed_articles());
$recentWindow = strtotime('-60 days');
$recentArticlesCount = count(array_filter($allIndexedArticles, static function (array $item) use ($recentWindow): bool {
    $file = __DIR__ . '/content/articles/' . (string) ($item['slug'] ?? '') . '.html';
    return is_file($file) && (int) @filemtime($file) >= $recentWindow;
}));
$commercialArticleCount = count(array_filter($allIndexedArticles, static function (array $item): bool {
    return interessa_article_has_commerce((string) ($item['slug'] ?? ''));
}));
$categoryCount = count(category_registry());
$guideCount = count($allIndexedArticles);

include __DIR__ . '/inc/head.php';
?>
<section class="hero">
  <div class="container hero-inner">
    <div class="hero-copy">
      <p class="hub-eyebrow">Affiliate magazin o vyzive</p>
      <h1>Vyber si doplnky a vyzivu bez chaosu a marketingoveho balastu</h1>
      <p>Interesa spaja tematicke huby, nakupne navody, recenzie a porovnania tak, aby si sa vedel rychlo dostat k rozumnemu vyberu podla ciela.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="/clanky/najlepsie-proteiny-2025">Pozriet porovnania</a>
        <a class="btn btn-ghost" href="/kategorie">Prejst kategorie</a>
      </div>
    </div>

    <div class="hero-media">
      <figure class="hero-figure">
        <?= interessa_render_image($homeHeroImage, ['style' => 'aspect-ratio:16/9;object-fit:cover;']) ?>
        <figcaption>Prakticke navody, ciste /go/ odkazy a obsah stavany na dlhodobe pouzivanie.</figcaption>
      </figure>
    </div>
  </div>
</section>

<section class="container stats-strip" aria-label="Co na webe najdes">
  <article class="stats-card">
    <strong><?= esc((string) $categoryCount) ?> tematickych hubov</strong>
    <p>Zacni podla ciela a az potom ries konkretny produkt.</p>
  </article>
  <article class="stats-card">
    <strong><?= esc((string) $guideCount) ?> clankov v archive</strong>
    <p>Porovnania, navody, recenzie a top vybery napriec hlavnymi temami.</p>
  </article>
  <article class="stats-card">
    <strong><?= esc((string) $recentArticlesCount) ?> aktualizovanych za 60 dni</strong>
    <p>Affiliate vrstva je oddelena od obsahu a spravovana cez interne <code>/go/</code> route.</p>
  </article>
  <article class="stats-card">
    <strong><?= esc((string) $commercialArticleCount) ?> clankov so shortlistom</strong>
    <p>Na tychto strankach uz vies prejst priamo na porovnane produkty a obchody.</p>
  </article>
</section>

<section class="container home-section">
  <div class="section-head">
    <h2>Zacni podla temy</h2>
    <p class="meta">Najvacsie obsahove huby webu. Kazda kategoria zhromazduje hlavne clanky, ktore davaju zmysel otvorit ako prve.</p>
  </div>

  <div class="hub-grid">
    <?php foreach ($featuredCategories as $category): ?>
      <article class="hub-card">
        <?= interessa_render_image($category['image'], ['class' => 'hub-card-image', 'alt' => $category['title']]) ?>
        <div class="hub-card-body">
          <span class="hub-card-icon" aria-hidden="true"><?= interessa_category_icon((string) $category['slug']) ?></span>
          <div class="article-card-meta">
            <span class="hub-card-label"><?= esc((string) $category['count']) ?> <?= esc(interessa_pluralize_slovak((int) $category['count'], 'clanok', 'clanky', 'clankov')) ?> v teme</span>
            <span class="article-card-date"><?= esc((string) $category['featured_count']) ?> <?= esc(interessa_pluralize_slovak((int) $category['featured_count'], 'klucovy clanok', 'klucove clanky', 'klucovych clankov')) ?></span>
          </div>
          <?php if ((int) ($category['commercial_count'] ?? 0) > 0): ?>
            <div class="article-card-submeta">
              <span class="article-card-subchip">Shortlist v <?= esc((string) $category['commercial_count']) ?> <?= esc(interessa_pluralize_slovak((int) $category['commercial_count'], 'clanku', 'clankoch', 'clankoch')) ?></span>
            </div>
          <?php endif; ?>
          <h3><a href="<?= esc(category_url((string) $category['slug'])) ?>"><?= esc((string) $category['title']) ?></a></h3>
          <p><?= esc((string) $category['description']) ?></p>
          <a class="btn" href="<?= esc(category_url((string) $category['slug'])) ?>">Otvorit kategoriu</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="container home-section">
  <div class="section-head">
    <h2>Najdolezitejsie nakupne navody</h2>
    <p class="meta">Najpraktickejsie clanky, od ktorych sa oplati zacat, ak uz riesis konkretny vyber produktu.</p>
  </div>

  <div class="hub-grid">
    <?php foreach ($featuredGuides as $guide): ?>
      <article class="hub-card">
        <?= interessa_render_image($guide['image'], ['class' => 'hub-card-image', 'alt' => $guide['title']]) ?>
        <div class="hub-card-body">
        <div class="article-card-meta">
          <span class="article-card-chip is-format"><?= esc((string) ($guide['format_label'] ?? 'Sprievodca')) ?></span>
          <?php if (is_array($guide['category_meta'] ?? null)): ?><span class="article-card-chip"><?= esc((string) ($guide['category_meta']['title'] ?? '')) ?></span><?php endif; ?>
          <?php if (($guide['updated_date'] ?? '') !== ''): ?><span class="article-card-date">Aktualizovane: <?= esc((string) $guide['updated_date']) ?></span><?php endif; ?>
        </div>
        <?= interessa_render_article_commerce_submeta((string) $guide['slug']) ?>
        <h3><a href="<?= esc(article_url((string) $guide['slug'])) ?>"><?= esc((string) $guide['title']) ?></a></h3>
        <?php if ($guide['description'] !== ''): ?><p><?= esc((string) $guide['description']) ?></p><?php endif; ?>
          <a class="btn" href="<?= esc(article_url((string) $guide['slug'])) ?>"><?= esc(interessa_article_cta_label((string) $guide['slug'], (string) $guide['title'])) ?></a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="container two-col home-story">
  <div class="content">
    <article class="lead-article">
      <header>
        <p class="hub-eyebrow">Odporucany start</p>
        <h2><?= esc($homeLeadMeta['title']) ?></h2>
        <div class="article-card-meta">
          <span class="article-card-chip is-format"><?= esc(interessa_article_format_label($homeLeadSlug, (string) $homeLeadMeta['title'])) ?></span>
          <?php $homeLeadCategory = category_meta(normalize_category_slug((string) ($homeLeadMeta['category'] ?? ''))); ?>
          <?php if ($homeLeadCategory !== null): ?><span class="article-card-chip"><?= esc((string) ($homeLeadCategory['title'] ?? '')) ?></span><?php endif; ?>
          <?php if ($homeLeadUpdated !== ''): ?><span class="article-card-date">Aktualizovane: <?= esc($homeLeadUpdated) ?></span><?php endif; ?>
        </div>
        <?= interessa_render_article_commerce_submeta($homeLeadSlug) ?>
        <p class="meta">Ak prave riesis chudnutie alebo chces rozumiet rozdielu medzi WPC a WPI, tu ma zmysel zacat.</p>
      </header>

      <figure class="inline-figure">
        <?= interessa_render_image($homeLeadImage, ['style' => 'object-fit:cover;']) ?>
      </figure>

      <p>Najcastejsia chyba pri kupe proteinu je, ze sa riesi znacka skor nez typ proteinu, ciel a tolerancia laktozy. Tento clanok pomaha rozlisit, kedy dava zmysel klasicky koncentrat a kedy sa oplati izolat.</p>
      <ul class="hub-checklist">
        <li>WPC byva praktickejsie pri rozpocte a kazdodennom pouzivani.</li>
        <li>WPI dava vacsi zmysel pri diete alebo citlivosti na laktozu.</li>
        <li>V clanku uz mas aj pripraveny shortlist produktov a ciste CTA do obchodu.</li>
      </ul>
      <p><a class="btn btn-primary" href="<?= esc(article_url($homeLeadSlug)) ?>"><?= esc(interessa_article_cta_label($homeLeadSlug, (string) $homeLeadMeta['title'])) ?></a></p>
    </article>
  </div>

  <?php include __DIR__ . '/inc/sidebar.php'; ?>
</section>

<section class="container home-section home-trust">
  <div class="section-head">
    <h2>Ako je web postaveny</h2>
    <p class="meta">Cielom nie je len rychly klik, ale zrozumitelny a udrziavatelny affiliate web s dlhodobym SEO zakladom.</p>
  </div>

  <div class="card-grid home-trust-grid">
    <article class="card">
      <div class="card-body">
        <h3>Obsah oddeleny od affiliate vrstvy</h3>
        <p>Clanky nie su zahltene tvrdymi affiliate URL. Produkty a odkazy sa spravuju centralne.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>Pripravene kategorie a huby</h3>
        <p>Kategorie funguju ako obsahove uzly pre interne prelinkovanie, SEO a lepsiu orientaciu pouzivatela.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>Image workflow s fallbackmi</h3>
        <p>Aj tam, kde este chyba vlastny hero obrazok, web uz pouziva tematicky vizual namiesto rozbiteho placeholdera.</p>
      </div>
    </article>
  </div>
</section>

<?php include __DIR__ . '/inc/footer.php'; ?>
