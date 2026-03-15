<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/category-hubs.php';
require_once __DIR__ . '/article-commerce.php';

$slug = $category_landing_slug ?? '';
$hub = interessa_category_hub($slug);
if ($slug === '' || $hub === null) {
    http_response_code(404);
    require dirname(__DIR__) . '/404.php';
    return;
}

$categoryHero = interessa_category_image_meta($slug, 'hero', true);
$categorySeo = interessa_category_seo_meta($slug);
$page_title = trim((string) ($categorySeo['meta_title'] ?? '')) !== '' ? (string) $categorySeo['meta_title'] : ($hub['title'] . ' | Interesa');
$page_description = trim((string) ($categorySeo['meta_description'] ?? '')) !== '' ? (string) $categorySeo['meta_description'] : (string) $hub['description'];
$page_canonical = category_url($slug);
$page_image = $categoryHero['src'] ?? asset('img/brand/og-default.svg');
$page_og_type = 'website';

$featuredGuides = is_array($hub['featured_guides'] ?? null) ? $hub['featured_guides'] : [];
$featuredSlugs = [];
$itemList = [];
$primaryGuideSlug = '';
$primaryCommercialGuideSlug = '';
foreach ($featuredGuides as $guide) {
    $guideSlug = trim((string) ($guide['slug'] ?? ''));
    if ($guideSlug === '') {
        continue;
    }

    if ($primaryGuideSlug === '') {
        $primaryGuideSlug = $guideSlug;
    }
    if ($primaryCommercialGuideSlug === '' && interessa_article_has_commerce($guideSlug)) {
        $primaryCommercialGuideSlug = $guideSlug;
    }

    $featuredSlugs[] = $guideSlug;
    $meta = article_meta($guideSlug);
    $itemList[] = [
        '@type' => 'ListItem',
        'position' => count($itemList) + 1,
        'url' => absolute_url(article_url($guideSlug)),
        'name' => $guide['title'] ?? $meta['title'],
    ];
}

$categoryArticles = array_values(category_articles($slug));
$articleCount = count($categoryArticles);
$featuredCount = count($featuredGuides);
$recentWindow = strtotime('-60 days');
$recentCount = 0;
$commercialCount = 0;
$fullCoverageCount = 0;
$formatCounts = [];
foreach ($categoryArticles as $articleItem) {
    $articleSlug = (string) ($articleItem['slug'] ?? '');
    $articleTitle = (string) ($articleItem['title'] ?? '');
    $formatLabel = interessa_article_format_label($articleSlug, $articleTitle);
    if (interessa_article_has_commerce($articleSlug)) {
        $commercialCount++;
    }
    if (interessa_article_has_full_packshot_coverage($articleSlug)) {
        $fullCoverageCount++;
    }
    $articleFile = dirname(__DIR__) . '/content/articles/' . $articleSlug . '.html';
    if (is_file($articleFile) && (int) @filemtime($articleFile) >= $recentWindow) {
        $recentCount++;
    }
    if ($formatLabel !== '') {
        $formatCounts[$formatLabel] = ($formatCounts[$formatLabel] ?? 0) + 1;
    }
}
arsort($formatCounts);
$topFormats = array_slice($formatCounts, 0, 4, true);
$extraArticles = array_values(array_filter($categoryArticles, static function (array $item) use ($featuredSlugs): bool {
    return !in_array((string) ($item['slug'] ?? ''), $featuredSlugs, true);
}));
$readyArticles = [];
foreach ($categoryArticles as $item) {
    $itemSlug = (string) ($item['slug'] ?? '');
    $summary = interessa_article_commerce_summary($itemSlug);
    if ($itemSlug === '' || !is_array($summary) || (int) ($summary['count'] ?? 0) <= 0) {
        continue;
    }
    $item['_coverage_percent'] = interessa_shortlist_coverage_percent($summary);
    $item['_coverage_label'] = interessa_shortlist_coverage_label($summary);
    $readyArticles[] = $item;
}
usort($readyArticles, static function (array $a, array $b): int {
    $coverageCompare = ((int) ($b['_coverage_percent'] ?? 0)) <=> ((int) ($a['_coverage_percent'] ?? 0));
    if ($coverageCompare !== 0) {
        return $coverageCompare;
    }
    $aFile = dirname(__DIR__) . '/content/articles/' . (string) ($a['slug'] ?? '') . '.html';
    $bFile = dirname(__DIR__) . '/content/articles/' . (string) ($b['slug'] ?? '') . '.html';
    return ((int) @filemtime($bFile)) <=> ((int) @filemtime($aFile));
});
$readyArticles = array_slice($readyArticles, 0, 3);
$crossThemePaths = interessa_cross_theme_paths($slug);
$primaryCommercialGuideCoverage = $primaryCommercialGuideSlug !== ''
    ? interessa_article_commerce_coverage_state($primaryCommercialGuideSlug)
    : null;
$comparisonReadyArticles = [];
foreach ($categoryArticles as $item) {
    $itemSlug = (string) ($item['slug'] ?? '');
    if ($itemSlug === '' || !interessa_article_has_comparison_table($itemSlug)) {
        continue;
    }

    $summary = interessa_article_commerce_summary($itemSlug);
    $item['_coverage_percent'] = interessa_shortlist_coverage_percent($summary);
    $comparisonReadyArticles[] = $item;
}
usort($comparisonReadyArticles, static function (array $a, array $b): int {
    $coverageCompare = ((int) ($b['_coverage_percent'] ?? 0)) <=> ((int) ($a['_coverage_percent'] ?? 0));
    if ($coverageCompare !== 0) {
        return $coverageCompare;
    }
    $aFile = dirname(__DIR__) . '/content/articles/' . (string) ($a['slug'] ?? '') . '.html';
    $bFile = dirname(__DIR__) . '/content/articles/' . (string) ($b['slug'] ?? '') . '.html';
    return ((int) @filemtime($bFile)) <=> ((int) @filemtime($aFile));
});
$comparisonReadyArticles = array_slice($comparisonReadyArticles, 0, 2);

$page_schema = [
    breadcrumb_schema([
        ['name' => 'Domov', 'url' => '/'],
        ['name' => 'Kategorie', 'url' => '/kategorie'],
        ['name' => $hub['title'], 'url' => $page_canonical],
    ]),
    [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $hub['title'],
        'description' => $page_description,
        'url' => absolute_url($page_canonical),
    ],
];

if ($itemList !== []) {
    $page_schema[] = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Klucove clanky: ' . $hub['title'],
        'itemListElement' => $itemList,
    ];
}

include dirname(__DIR__) . '/inc/head.php';
?>
<section class="container two-col">
  <div class="content">
    <article class="card hub-hero-card">
      <?php if ($categoryHero !== null): ?>
        <figure class="hub-hero-media category-asset-frame category-asset-frame--hero">
          <?= interessa_render_image($categoryHero, ['class' => 'hub-card-image category-asset-image category-hero-image', 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
        </figure>
      <?php endif; ?>
      <div class="hub-heading-row">
        <span class="hub-icon-badge" aria-hidden="true"><?= interessa_category_icon($slug) ?></span>
        <h1><?= esc($hub['title']) ?></h1>
      </div>
      <p class="lead"><?= esc($hub['intro']) ?></p>
      <div class="hub-meta-row">
        <span class="article-meta-chip"><?= esc((string) $articleCount) ?> <?= esc(interessa_pluralize_slovak($articleCount, 'clanok', 'clanky', 'clankov')) ?> v teme</span>
        <a class="card-link" href="/clanky/?category=<?= esc($slug) ?>">Vsetky clanky v teme</a>
      </div>
      <div class="hero-cta">
        <?php if ($primaryGuideSlug !== ''): ?>
          <a class="btn btn-primary" href="<?= esc(article_url($primaryGuideSlug)) ?>">Zacat hlavnym clankom</a>
        <?php endif; ?>
        <?php if ($primaryCommercialGuideSlug !== ''): ?>
          <a class="btn btn-ghost" href="<?= esc(article_url($primaryCommercialGuideSlug)) ?>">
            <?= esc($primaryCommercialGuideCoverage === 'full' ? 'Prejst na porovnanie a vyber' : 'Prejst na odporucane produkty') ?>
          </a>
        <?php else: ?>
          <a class="btn btn-ghost" href="/clanky/?category=<?= esc($slug) ?>&amp;commercial=1">Clanky s odporucaniami</a>
        <?php endif; ?>
        <a class="btn btn-ghost" href="/clanky/?category=<?= esc($slug) ?>">Vsetky clanky v teme</a>
      </div>
      <div class="hub-stats-inline" aria-label="Prehlad obsahu v kategorii">
        <div class="hub-stat-inline">
          <strong><?= esc((string) $featuredCount) ?></strong>
          <span><?= esc(interessa_pluralize_slovak($featuredCount, 'klucovy clanok', 'klucove clanky', 'klucovych clankov')) ?></span>
        </div>
        <div class="hub-stat-inline">
          <strong><?= esc((string) $recentCount) ?></strong>
          <span>aktualizovane za 60 dni</span>
        </div>
        <div class="hub-stat-inline">
          <strong><?= esc((string) $articleCount) ?></strong>
          <span>spolu v teme</span>
        </div>
      </div>
      <?php if ($topFormats !== []): ?>
        <div class="filters-bar archive-format-bar hub-format-summary" aria-label="Formaty clankov v tejto teme">
          <?php foreach ($topFormats as $label => $count): ?>
            <span class="filter-chip is-muted">
              <span class="article-card-chip is-format"><?= esc((string) $label) ?></span>
              <?= esc((string) $count) ?>
            </span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if ($commercialCount > 0): ?>
        <div class="article-card-submeta">
          <span class="article-card-subchip">Vyber produktov v <?= esc((string) $commercialCount) ?> <?= esc(interessa_pluralize_slovak($commercialCount, 'clanku', 'clankoch', 'clankoch')) ?></span>
          <?php if ($fullCoverageCount > 0): ?>
            <span class="article-card-subchip is-coverage is-full">Tabulka a realne fotky v <?= esc((string) $fullCoverageCount) ?> <?= esc(interessa_pluralize_slovak($fullCoverageCount, 'clanku', 'clankoch', 'clankoch')) ?></span>
          <?php else: ?>
            <span class="article-card-subchip is-coverage is-partial">Vyber je pripraveny</span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($hub['focus_points'])): ?>
        <ul class="hub-checklist">
          <?php foreach ($hub['focus_points'] as $point): ?>
            <li><?= esc((string) $point) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </article>

    <?php if ($featuredGuides !== []): ?>
      <section class="card">
        <div class="section-head">
          <h2>Zacat podla toho, co riesis</h2>
          <p class="meta">Vyber si clanok podla toho, co chces vyriesit ako prve: rychly vyber, porovnanie alebo prakticky sprievodca.</p>
        </div>
        <div class="hub-grid article-related-grid">
          <?php foreach ($featuredGuides as $guide): ?>
            <?php
            $guideSlug = trim((string) ($guide['slug'] ?? ''));
            if ($guideSlug === '') {
                continue;
            }
            $meta = article_meta($guideSlug);
            $title = trim((string) ($guide['title'] ?? $meta['title']));
            $description = interessa_article_card_description($guideSlug, trim((string) ($guide['description'] ?? $meta['description'])), 20);
            $label = trim((string) ($guide['label'] ?? 'Start'));
            $formatLabel = interessa_article_format_label($guideSlug, $title);
            ?>
            <article class="hub-card article-teaser-card">
              <div class="hub-card-body article-teaser-body">
                <div class="article-card-meta">
                  <span class="article-card-chip is-format"><?= esc($formatLabel) ?></span>
                  <span class="hub-card-label"><?= esc($label) ?></span>
                </div>
                <?= interessa_render_article_commerce_submeta($guideSlug, 'compact') ?>
                <h3><a href="<?= esc(article_url($guideSlug)) ?>"><?= esc($title) ?></a></h3>
                <?php if ($description !== ''): ?><p><?= esc($description) ?></p><?php endif; ?>
                <a class="card-link" href="<?= esc(article_url($guideSlug)) ?>"><?= esc(interessa_article_cta_label($guideSlug, $title)) ?></a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($comparisonReadyArticles !== []): ?>
      <section class="card">
        <div class="section-head">
          <h2>Kde sa zorientujes najrychlejsie</h2>
          <p class="meta">Ak nechces citat dlhy uvod a chces ist rovno na prehlad produktov, zacni jednym z tychto clankov.</p>
        </div>
        <div class="hub-grid article-related-grid">
          <?php foreach ($comparisonReadyArticles as $item): ?>
            <?php
            $itemSlug = (string) ($item['slug'] ?? '');
            $itemTitle = function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake((string) ($item['title'] ?? '')) : (string) ($item['title'] ?? '');
            $itemDescription = interessa_article_card_description($itemSlug, trim((string) ($item['description'] ?? '')), 18);
            $itemImage = interessa_article_image_meta($itemSlug, 'thumb', true);
            $itemFile = dirname(__DIR__) . '/content/articles/' . $itemSlug . '.html';
            $itemDate = is_file($itemFile) ? date('d.m.Y', (int) @filemtime($itemFile)) : '';
            $formatLabel = interessa_article_format_label($itemSlug, $itemTitle);
            ?>
            <article class="hub-card article-teaser-card">
              <a href="<?= esc(article_url($itemSlug)) ?>">
                <?= interessa_render_image($itemImage, ['class' => 'hub-card-image', 'alt' => $itemTitle]) ?>
              </a>
              <div class="hub-card-body article-teaser-body">
                <div class="article-card-meta">
                  <span class="article-card-chip is-format"><?= esc($formatLabel) ?></span>
                  <span class="hub-card-label"><?= esc($hub['title']) ?></span>
                  <?php if ($itemDate !== ''): ?><span class="article-card-date"><?= esc($itemDate) ?></span><?php endif; ?>
                </div>
                <div class="article-card-submeta">
                  <span class="article-card-subchip is-coverage is-full">Prehladna tabulka vyberu</span>
                  <span class="article-card-subchip">Realne fotky: <?= esc((string) ($item['_coverage_percent'] ?? 0)) ?>%</span>
                </div>
                <h3><a href="<?= esc(article_url($itemSlug)) ?>"><?= esc($itemTitle) ?></a></h3>
                <?php if ($itemDescription !== ''): ?><p><?= esc($itemDescription) ?></p><?php endif; ?>
                <a class="card-link" href="<?= esc(article_url($itemSlug)) ?>">Otvorit porovnanie</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($readyArticles !== []): ?>
      <section class="card">
        <div class="section-head">
          <h2>Ked chces prejst rovno k odporucaniam</h2>
          <p class="meta">Ak uz nechces len studovat temu, ale prejst ku konkretnym produktom, zacni jednym z tychto clankov.</p>
        </div>
        <div class="hub-grid article-related-grid">
          <?php foreach ($readyArticles as $item): ?>
            <?php
            $itemSlug = (string) ($item['slug'] ?? '');
            $itemTitle = function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake((string) ($item['title'] ?? '')) : (string) ($item['title'] ?? '');
            $itemDescription = interessa_article_card_description($itemSlug, trim((string) ($item['description'] ?? '')), 20);
            $itemImage = interessa_article_image_meta($itemSlug, 'thumb', true);
            $itemFile = dirname(__DIR__) . '/content/articles/' . $itemSlug . '.html';
            $itemDate = is_file($itemFile) ? date('d.m.Y', (int) @filemtime($itemFile)) : '';
            $formatLabel = interessa_article_format_label($itemSlug, $itemTitle);
            ?>
            <article class="hub-card article-teaser-card">
              <a href="<?= esc(article_url($itemSlug)) ?>">
                <?= interessa_render_image($itemImage, ['class' => 'hub-card-image', 'alt' => $itemTitle]) ?>
              </a>
              <div class="hub-card-body article-teaser-body">
                <div class="article-card-meta">
                  <span class="article-card-chip is-format"><?= esc($formatLabel) ?></span>
                  <span class="hub-card-label"><?= esc($hub['title']) ?></span>
                  <?php if ($itemDate !== ''): ?><span class="article-card-date"><?= esc($itemDate) ?></span><?php endif; ?>
                </div>
                <?= interessa_render_article_commerce_submeta($itemSlug, 'compact') ?>
                <h3><a href="<?= esc(article_url($itemSlug)) ?>"><?= esc($itemTitle) ?></a></h3>
                <?php if ($itemDescription !== ''): ?><p><?= esc($itemDescription) ?></p><?php endif; ?>
                <a class="card-link" href="<?= esc(article_url($itemSlug)) ?>"><?= esc(interessa_article_cta_label($itemSlug, $itemTitle)) ?></a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <section class="card">
      <div class="section-head">
        <h2>Najdolezitejsie clanky v teme</h2>
        <p class="meta">Toto su clanky, od ktorych sa oplati zacat, ak si chces temu rychlo utriedit a az potom riesit konkretny produkt.</p>
      </div>
      <?php if ($featuredGuides !== []): ?>
        <div class="hub-grid">
          <?php foreach ($featuredGuides as $guide): ?>
            <?php
            $guideSlug = trim((string) ($guide['slug'] ?? ''));
            if ($guideSlug === '') {
                continue;
            }
            $meta = article_meta($guideSlug);
            $title = trim((string) ($guide['title'] ?? $meta['title']));
            $description = interessa_article_card_description($guideSlug, trim((string) ($guide['description'] ?? $meta['description'])), 20);
            $label = trim((string) ($guide['label'] ?? 'Sprievodca'));
            $guideImage = interessa_article_image_meta($guideSlug, 'thumb', true);
            $guideFile = dirname(__DIR__) . '/content/articles/' . $guideSlug . '.html';
            $guideDate = is_file($guideFile) ? date('d.m.Y', (int) @filemtime($guideFile)) : '';
            $formatLabel = interessa_article_format_label($guideSlug, $title);
            ?>
            <article class="hub-card">
              <?= interessa_render_image($guideImage, ['class' => 'hub-card-image', 'alt' => $title]) ?>
              <div class="hub-card-body">
                <div class="article-card-meta">
                  <span class="article-card-chip is-format"><?= esc($formatLabel) ?></span>
                  <span class="hub-card-label"><?= esc($label) ?></span>
                  <?php if ($guideDate !== ''): ?><span class="article-card-date"><?= esc($guideDate) ?></span><?php endif; ?>
                </div>
                <?= interessa_render_article_commerce_submeta($guideSlug, 'compact') ?>
                <h3><a href="<?= esc(article_url($guideSlug)) ?>"><?= esc($title) ?></a></h3>
                <?php if ($description !== ''): ?><p><?= esc($description) ?></p><?php endif; ?>
                <a class="btn" href="<?= esc(article_url($guideSlug)) ?>"><?= esc(interessa_article_cta_label($guideSlug, $title)) ?></a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="note"><?= esc((string) ($hub['empty_message'] ?? 'Tato kategoria sa este doplna. Zatial tu coskoro pribudnu odporucane clanky.')) ?></p>
      <?php endif; ?>
    </section>

    <?php if ($extraArticles !== []): ?>
      <section class="card">
        <div class="section-head">
          <h2>Dalsie clanky v teme</h2>
          <p class="meta">Doplnujuce clanky pre hlbsie prestudovanie temy.</p>
        </div>
        <div class="hub-grid article-related-grid">
          <?php foreach ($extraArticles as $item): ?>
            <?php
            $itemSlug = (string) ($item['slug'] ?? '');
            $itemTitle = function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake((string) ($item['title'] ?? '')) : (string) ($item['title'] ?? '');
            $itemDescription = interessa_article_card_description($itemSlug, trim((string) ($item['description'] ?? '')), 20);
            $itemImage = interessa_article_image_meta($itemSlug, 'thumb', true);
            $itemFile = dirname(__DIR__) . '/content/articles/' . $itemSlug . '.html';
            $itemDate = is_file($itemFile) ? date('d.m.Y', (int) @filemtime($itemFile)) : '';
            $formatLabel = interessa_article_format_label($itemSlug, $itemTitle);
            ?>
            <article class="hub-card article-teaser-card">
              <a href="<?= esc(article_url($itemSlug)) ?>">
                <?= interessa_render_image($itemImage, ['class' => 'hub-card-image', 'alt' => $itemTitle]) ?>
              </a>
              <div class="hub-card-body article-teaser-body">
                <div class="article-card-meta">
                  <span class="article-card-chip is-format"><?= esc($formatLabel) ?></span>
                  <span class="hub-card-label"><?= esc($hub['title']) ?></span>
                  <?php if ($itemDate !== ''): ?><span class="article-card-date"><?= esc($itemDate) ?></span><?php endif; ?>
                </div>
                <?= interessa_render_article_commerce_submeta($itemSlug, 'compact') ?>
                <h3><a href="<?= esc(article_url($itemSlug)) ?>"><?= esc($itemTitle) ?></a></h3>
                <?php if ($itemDescription !== ''): ?><p><?= esc($itemDescription) ?></p><?php endif; ?>
                <a class="card-link" href="<?= esc(article_url($itemSlug)) ?>"><?= esc(interessa_article_cta_label($itemSlug, $itemTitle)) ?></a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($crossThemePaths !== []): ?>
      <section class="card">
        <div class="section-head">
          <h2>Suvisiace temy, ktore davaju zmysel ako dalsi krok</h2>
          <p class="meta">Ak uz mas tuto temu zorientovanu, tieto pribuzne smery ti pomozu prirodzene pokracovat dalej.</p>
        </div>
        <div class="hub-grid article-related-grid">
          <?php foreach ($crossThemePaths as $path): ?>
            <article class="hub-card article-teaser-card">
              <div class="hub-card-body article-teaser-body">
                <span class="hub-card-label"><?= esc($hub['title']) ?></span>
                <h3><a href="<?= esc((string) ($path['href'] ?? '/')) ?>"><?= esc((string) ($path['title'] ?? 'Dalsia tema')) ?></a></h3>
                <?php if (trim((string) ($path['description'] ?? '')) !== ''): ?><p><?= esc((string) ($path['description'] ?? '')) ?></p><?php endif; ?>
                <a class="card-link" href="<?= esc((string) ($path['href'] ?? '/')) ?>"><?= esc((string) ($path['cta'] ?? 'Otvorit temu')) ?></a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </div>

  <?php $sidebarContextCategorySlug = $slug; ?>
  <?php include dirname(__DIR__) . '/inc/sidebar.php'; ?>
</section>
<?php include dirname(__DIR__) . '/inc/footer.php'; ?>
