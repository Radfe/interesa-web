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
    'najlepsie-proteiny-2026',
    'kreatin-porovnanie',
    'kolagen-recenzia',
    'veganske-proteiny-top-vyber-2026',
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
        'full_coverage_count' => count(array_filter(array_values(category_articles($slug)), static function (array $item): bool {
            return interessa_article_has_full_packshot_coverage((string) ($item['slug'] ?? ''));
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
$readyShortlistGuides = [];
foreach ($allIndexedArticles as $item) {
    $slug = (string) ($item['slug'] ?? '');
    $summary = interessa_article_commerce_summary($slug);
    if ($slug === '' || !is_array($summary) || (int) ($summary['count'] ?? 0) <= 0) {
        continue;
    }
    $meta = article_meta($slug);
    $categorySlug = normalize_category_slug((string) ($meta['category'] ?? ''));
    $articleFile = __DIR__ . '/content/articles/' . $slug . '.html';
    $readyShortlistGuides[] = [
        'slug' => $slug,
        'title' => $meta['title'],
        'description' => trim((string) ($meta['description'] ?? '')) !== '' ? $meta['description'] : interessa_article_teaser_description($slug),
        'image' => interessa_article_image_meta($slug, 'thumb', true),
        'format_label' => interessa_article_format_label($slug, (string) ($meta['title'] ?? '')),
        'category_meta' => $categorySlug !== '' ? category_meta($categorySlug) : null,
        'coverage_percent' => interessa_shortlist_coverage_percent($summary),
        'coverage_label' => interessa_shortlist_coverage_label($summary),
        'updated_ts' => is_file($articleFile) ? (int) @filemtime($articleFile) : 0,
        'updated_date' => is_file($articleFile) ? date('d.m.Y', (int) @filemtime($articleFile)) : '',
    ];
}
usort($readyShortlistGuides, static function (array $a, array $b): int {
    $coverageCompare = ((int) ($b['coverage_percent'] ?? 0)) <=> ((int) ($a['coverage_percent'] ?? 0));
    if ($coverageCompare !== 0) {
        return $coverageCompare;
    }
    return ((int) ($b['updated_ts'] ?? 0)) <=> ((int) ($a['updated_ts'] ?? 0));
});
$readyShortlistGuides = array_slice($readyShortlistGuides, 0, 3);
$categoryCount = count(category_registry());
$guideCount = count($allIndexedArticles);

include __DIR__ . '/inc/head.php';
?>
<section class="hero">
  <div class="container hero-inner">
    <div class="hero-copy">
      <p class="hub-eyebrow">Prakticky magazin o vyzive</p>
      <h1>Vyber si doplnky a vyzivu bez chaosu a marketingoveho balastu</h1>
      <p>Interesa spaja tematicke huby, nakupne navody, recenzie a porovnania tak, aby si sa vedel rychlo dostat k rozumnemu vyberu podla ciela.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="/clanky/najlepsie-proteiny-2026">Pozriet porovnania</a>
        <a class="btn btn-ghost" href="/kategorie">Prejst kategorie</a>
      </div>
    </div>

    <div class="hero-media">
      <figure class="hero-figure">
        <?= interessa_render_image($homeHeroImage, ['style' => 'aspect-ratio:16/9;object-fit:cover;']) ?>
        <figcaption>Prakticke navody, porovnania a odporucania stavane na dlhodobe pouzivanie, nie len na rychly klik.</figcaption>
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
    <p>Obsah priebezne kontrolujeme a dorovnavame podla toho, co ludia realne najcastejsie hladaju.</p>
  </article>
  <article class="stats-card">
    <strong><?= esc((string) $commercialArticleCount) ?> clankov s odporucaniami</strong>
    <p>Na tychto strankach uz vies prejst od vysvetlenia temy priamo k porovnanym produktom a obchodom.</p>
  </article>
</section>

<section class="container home-section home-discovery-links">
  <div class="hero-cta">
    <a class="btn btn-ghost" href="/clanky?commercial=1">Clanky s odporucaniami</a>
    <a class="btn btn-ghost" href="/clanky?coverage=full">Najviac pripravene porovnania</a>
    <a class="btn btn-ghost" href="/kategorie/chudnutie">Zacat podla ciela</a>
  </div>
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
              <span class="article-card-subchip">Odporucania v <?= esc((string) $category['commercial_count']) ?> <?= esc(interessa_pluralize_slovak((int) $category['commercial_count'], 'clanku', 'clankoch', 'clankoch')) ?></span>
              <?php if ((int) ($category['full_coverage_count'] ?? 0) > 0): ?>
                <span class="article-card-subchip is-coverage is-full">Najlepsie pripravene v <?= esc((string) $category['full_coverage_count']) ?> <?= esc(interessa_pluralize_slovak((int) $category['full_coverage_count'], 'clanku', 'clankoch', 'clankoch')) ?></span>
              <?php endif; ?>
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
        <h3><a href="<?= esc(article_url((string) $guide['slug'])) ?>"><?= esc((string) $guide['title']) ?></a></h3>
        <?php if ($guide['description'] !== ''): ?><p><?= esc((string) $guide['description']) ?></p><?php endif; ?>
          <a class="btn" href="<?= esc(article_url((string) $guide['slug'])) ?>"><?= esc(interessa_article_cta_label((string) $guide['slug'], (string) $guide['title'])) ?></a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php if ($readyShortlistGuides !== []): ?>
<section class="container home-section">
  <div class="section-head">
    <h2>Odporucane vybery</h2>
    <p class="meta">Nakupne clanky, kde uz mas odporucania prehladne usporiadane a vies sa rychlo zorientovat v produktoch.</p>
  </div>

  <div class="hub-grid article-teaser-grid">
    <?php foreach ($readyShortlistGuides as $guide): ?>
      <article class="hub-card article-teaser-card">
        <a href="<?= esc(article_url((string) $guide['slug'])) ?>">
          <?= interessa_render_image((array) $guide['image'], ['class' => 'hub-card-image', 'alt' => (string) $guide['title']]) ?>
        </a>
        <div class="hub-card-body article-teaser-body">
          <div class="article-card-meta">
            <span class="article-card-chip is-format"><?= esc((string) ($guide['format_label'] ?? 'Clanok')) ?></span>
            <?php if (is_array($guide['category_meta'] ?? null)): ?><span class="article-card-chip"><?= esc((string) ($guide['category_meta']['title'] ?? '')) ?></span><?php endif; ?>
            <?php if (($guide['updated_date'] ?? '') !== ''): ?><span class="article-card-date">Aktualizovane: <?= esc((string) $guide['updated_date']) ?></span><?php endif; ?>
          </div>
          <?= interessa_render_article_commerce_submeta((string) $guide['slug'], 'compact') ?>
          <h3><a href="<?= esc(article_url((string) $guide['slug'])) ?>"><?= esc((string) $guide['title']) ?></a></h3>
          <?php if (($guide['description'] ?? '') !== ''): ?><p><?= esc((string) $guide['description']) ?></p><?php endif; ?>
          <a class="btn" href="<?= esc(article_url((string) $guide['slug'])) ?>"><?= esc(interessa_article_cta_label((string) $guide['slug'], (string) $guide['title'])) ?></a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

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
        <p class="meta">Ak prave riesis chudnutie alebo chces rozumiet rozdielu medzi WPC a WPI, tu ma zmysel zacat.</p>
      </header>

      <figure class="inline-figure">
        <?= interessa_render_image($homeLeadImage, ['style' => 'object-fit:cover;']) ?>
      </figure>

      <p>Najcastejsia chyba pri kupe proteinu je, ze sa riesi znacka skor nez typ proteinu, ciel a tolerancia laktozy. Tento clanok pomaha rozlisit, kedy dava zmysel klasicky koncentrat a kedy sa oplati izolat.</p>
      <ul class="hub-checklist">
        <li>WPC byva praktickejsie pri rozpocte a kazdodennom pouzivani.</li>
        <li>WPI dava vacsi zmysel pri diete alebo citlivosti na laktozu.</li>
        <li>V clanku uz mas aj pripraveny vyber produktov a ciste CTA do obchodu.</li>
      </ul>
      <p><a class="btn btn-primary" href="<?= esc(article_url($homeLeadSlug)) ?>"><?= esc(interessa_article_cta_label($homeLeadSlug, (string) $homeLeadMeta['title'])) ?></a></p>
    </article>
  </div>

  <?php include __DIR__ . '/inc/sidebar.php'; ?>
</section>

<section class="container home-section home-trust">
  <div class="section-head">
    <h2>Preco sa na webe vies rychlo zorientovat</h2>
    <p class="meta">Interesa ma fungovat ako redakcny pomocnik pri vybere, nie ako agresivna predajna stranka.</p>
  </div>

  <div class="card-grid home-trust-grid">
    <article class="card">
      <div class="card-body">
        <h3>Ako hodnotime produkty</h3>
        <p>Pozerame sa na ciel pouzitia, zlozenie, davku, cenu na porciu a to, ci produkt dava zmysel v realnej rutine.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>Porovnavame viac obchodov</h3>
        <p>V odporucaniach nechavame priestor viacerym merchantom. GymBeam je silny partner, ale web nema stat len na jednej znacke.</p>
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
