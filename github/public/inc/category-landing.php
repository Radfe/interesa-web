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
$page_title = $hub['title'] . ' | Interesa';
$page_description = $hub['description'];
$page_canonical = category_url($slug);
$page_image = $categoryHero['src'] ?? asset('img/brand/og-default.svg');
$page_og_type = 'website';

$featuredGuides = is_array($hub['featured_guides'] ?? null) ? $hub['featured_guides'] : [];
$featuredSlugs = [];
$itemList = [];
foreach ($featuredGuides as $guide) {
    $guideSlug = trim((string) ($guide['slug'] ?? ''));
    if ($guideSlug === '') {
        continue;
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
        'description' => $hub['description'],
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
        <figure class="hub-hero-media">
          <?= interessa_render_image($categoryHero, ['class' => 'hub-card-image', 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
          <figcaption class="article-hero-caption">
            <span class="article-hero-chip"><?= esc($hub['title']) ?></span>
            <span class="article-hero-chip"><?= esc((string) $articleCount) ?> <?= esc(interessa_pluralize_slovak($articleCount, 'clanok', 'clanky', 'clankov')) ?> v teme</span>
            <span class="article-hero-chip is-soft">Prehlad temy</span>
          </figcaption>
        </figure>
      <?php endif; ?>
      <p class="hub-eyebrow">Prehlad temy</p>
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
        <a class="btn btn-ghost" href="/clanky/?category=<?= esc($slug) ?>&amp;commercial=1">Clanky s odporucaniami</a>
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
        <div class="hub-stat-inline">
          <strong><?= esc((string) $commercialCount) ?></strong>
          <span>clanky s odporucaniami</span>
        </div>
        <div class="hub-stat-inline">
          <strong><?= esc((string) $fullCoverageCount) ?></strong>
          <span>clanky s hotovymi obrazkami</span>
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
      <?php if (!empty($hub['focus_points'])): ?>
        <ul class="hub-checklist">
          <?php foreach ($hub['focus_points'] as $point): ?>
            <li><?= esc((string) $point) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </article>

    <section class="card">
      <div class="section-head">
        <h2>Klucove clanky</h2>
        <p class="meta">Najlepsia cesta je zacat jednym z tychto clankov a az potom riesit konkretny produkt.</p>
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
            $description = trim((string) ($guide['description'] ?? $meta['description']));
            if ($description === '') {
                $description = interessa_article_teaser_description($guideSlug);
            }
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
                  <?php if (interessa_article_has_full_packshot_coverage($guideSlug)): ?><span class="article-card-chip">Pripraveny vyber</span><?php endif; ?>
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

    <?php if ($readyArticles !== []): ?>
      <section class="card">
        <div class="section-head">
          <h2>Najviac pripravene clanky v teme</h2>
          <p class="meta">Clanky v tejto teme, kde su odporucania uz najprehladnejsie a pripraveny rychly vyber je najjednoduchsi.</p>
        </div>
        <div class="hub-grid article-related-grid">
          <?php foreach ($readyArticles as $item): ?>
            <?php
            $itemSlug = (string) ($item['slug'] ?? '');
            $itemTitle = function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake((string) ($item['title'] ?? '')) : (string) ($item['title'] ?? '');
            $itemDescription = trim((string) ($item['description'] ?? ''));
            if ($itemDescription === '') {
                $itemDescription = interessa_article_teaser_description($itemSlug);
            } else {
                $itemDescription = function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake($itemDescription) : $itemDescription;
            }
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
                  <span class="article-card-chip">Pripraveny vyber</span>
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
            $itemDescription = trim((string) ($item['description'] ?? ''));
            if ($itemDescription === '') {
                $itemDescription = interessa_article_teaser_description($itemSlug);
            } else {
                $itemDescription = function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake($itemDescription) : $itemDescription;
            }
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
                  <?php if (interessa_article_has_full_packshot_coverage($itemSlug)): ?><span class="article-card-chip">Pripraveny vyber</span><?php endif; ?>
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
  </div>

  <?php include dirname(__DIR__) . '/inc/sidebar.php'; ?>
</section>
<?php include dirname(__DIR__) . '/inc/footer.php'; ?>
