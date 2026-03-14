<?php

declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/article-commerce.php';
require_once __DIR__ . '/inc/top-products.php';
require_once __DIR__ . '/inc/article-trust.php';
require_once __DIR__ . '/inc/article-related.php';
require_once __DIR__ . '/inc/article-enhancements.php';
require_once __DIR__ . '/inc/article-outline.php';
require_once __DIR__ . '/inc/admin-content.php';
require_once __DIR__ . '/inc/category-hubs.php';

$requestedSlug = preg_replace('~[^a-z0-9\-_]+~i', '', (string) ($_GET['slug'] ?? ''));
$preferredSlug = interessa_article_preferred_slug($requestedSlug);
if ($requestedSlug !== '' && $preferredSlug !== '' && $preferredSlug !== $requestedSlug) {
    header('Location: ' . article_url($preferredSlug), true, 301);
    exit;
}
$slug = interessa_article_source_slug($requestedSlug);
$file = __DIR__ . '/content/articles/' . $slug . '.html';
$usesAdminContent = interessa_admin_article_has_structured_content($slug);
if ($slug === '' || (!is_file($file) && !$usesAdminContent)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    return;
}

$meta = article_meta($slug);
$adminArticle = interessa_admin_article_content($slug);
$articleHero = interessa_article_image_meta($slug, 'hero', true);
$commerce = interessa_article_commerce($slug);
$categoryMeta = $meta['category'] !== '' ? category_meta($meta['category']) : null;
$updatedMeta = is_file($file) ? interessa_article_updated_meta($file) : null;
$faq = interessa_article_faq_items($slug);
$heroAsset = trim((string) ($articleHero['asset'] ?? ''));
$heroIsSvg = $heroAsset !== '' && str_ends_with(strtolower($heroAsset), '.svg');
$articleFormatLabel = interessa_article_format_label($slug, (string) ($meta['title'] ?? ''));
$categoryStats = interessa_article_category_stats($slug, (string) ($meta['category'] ?? ''));
$shortlistStats = interessa_commerce_shortlist_stats($commerce);
$shortlistCoveragePercent = interessa_shortlist_coverage_percent($shortlistStats);
$shortlistCoverageLabel = interessa_shortlist_coverage_label($shortlistStats);
$hasDecisionLayer = $comparisonTable !== null || $commerce !== null;
$crossThemePaths = $categoryMeta !== null ? interessa_cross_theme_paths((string) ($categoryMeta['slug'] ?? '')) : [];

if ($usesAdminContent) {
    $adminPayload = interessa_admin_article_content_payload($slug);
    $articleBodyHtml = (string) ($adminPayload['html'] ?? '');
    $articleHeadings = is_array($adminPayload['headings'] ?? null) ? $adminPayload['headings'] : [];
    $readingTime = (int) ($adminPayload['reading_time'] ?? 1);
    if (!empty($adminPayload['has_recommendations'])) {
        $commerce = null;
    }
} else {
    $articleBodyHtml = interessa_fix_mojibake((string) file_get_contents($file));
    $articlePrepared = interessa_article_prepare_body($articleBodyHtml);
    $articleBodyHtml = (string) ($articlePrepared['html'] ?? $articleBodyHtml);
    $articleHeadings = is_array($articlePrepared['headings'] ?? null) ? $articlePrepared['headings'] : [];
    $readingTime = (int) ($articlePrepared['reading_time'] ?? 1);
}

$comparisonTable = interessa_article_comparison_table_payload($slug, $commerce);

$pageTitleBase = trim((string) ($meta['meta_title'] ?? '')) !== '' ? trim((string) $meta['meta_title']) : $meta['title'];
$pageDescriptionBase = trim((string) ($meta['meta_description'] ?? '')) !== ''
    ? trim((string) $meta['meta_description'])
    : ($meta['description'] !== '' ? $meta['description'] : $meta['title']);

$page_title = $pageTitleBase . ' | Interesa';
$page_description = $pageDescriptionBase;
$articleLead = trim((string) ($adminArticle['intro'] ?? '')) !== ''
    ? trim((string) $adminArticle['intro'])
    : ($meta['description'] !== '' ? $meta['description'] : $meta['title']);
$page_canonical = article_url($slug);
$page_image = $articleHero['src'] ?? article_img($slug);
$page_og_type = 'article';

$breadcrumbs = [
    ['name' => 'Domov', 'url' => '/'],
    ['name' => 'Clanky', 'url' => '/clanky'],
];
if ($categoryMeta !== null) {
    $breadcrumbs[] = ['name' => $categoryMeta['title'], 'url' => category_url($categoryMeta['slug'])];
}
$breadcrumbs[] = ['name' => $meta['title'], 'url' => $page_canonical];

$articleSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $meta['title'],
    'description' => $pageDescriptionBase,
    'url' => absolute_url($page_canonical),
    'mainEntityOfPage' => absolute_url($page_canonical),
    'image' => page_image_url(),
    'articleSection' => $categoryMeta['title'] ?? 'Clanky',
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Interesa',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => absolute_url(asset('img/brand/logo-full.svg')),
        ],
    ],
];
if ($updatedMeta !== null) {
    $articleSchema['dateModified'] = $updatedMeta['iso'];
}

$page_schema = [
    breadcrumb_schema($breadcrumbs),
    $articleSchema,
];

if ($commerce !== null) {
    $commerceSchema = interessa_top_products_schema(
        $commerce['products'] ?? [],
        $commerce['title'] ?? 'Odporucane produkty',
        $page_canonical
    );

    if ($commerceSchema !== null) {
        $page_schema[] = $commerceSchema;
    }
}

$faqSchema = interessa_article_faq_schema($faq);
if ($faqSchema !== null) {
    $page_schema[] = $faqSchema;
}

$seoMeta = interessa_article_seo_meta($slug);
$pageTitleBase = trim((string) ($seoMeta['meta_title'] ?? $pageTitleBase)) !== '' ? trim((string) ($seoMeta['meta_title'] ?? $pageTitleBase)) : $pageTitleBase;
$pageDescriptionBase = trim((string) ($seoMeta['meta_description'] ?? $pageDescriptionBase)) !== '' ? trim((string) ($seoMeta['meta_description'] ?? $pageDescriptionBase)) : $pageDescriptionBase;

$page_title = $pageTitleBase . ' | Interesa';
$page_description = $pageDescriptionBase;

$page_schema[1]['headline'] = $meta['title'];
$page_schema[1]['description'] = $pageDescriptionBase;

include __DIR__ . '/inc/head.php';
?>
<section class="container two-col">
  <div class="content">
    <article class="lead-article article-shell<?= $usesAdminContent ? ' article-shell-admin' : '' ?>">
      <nav class="muted" aria-label="Breadcrumb">
        <a href="/">Domov</a> &rsaquo; <a href="/clanky/">Clanky</a>
        <?php if ($categoryMeta !== null): ?>
          &rsaquo; <a href="<?= esc(category_url($categoryMeta['slug'])) ?>"><?= esc($categoryMeta['title']) ?></a>
        <?php endif; ?>
      </nav>

      <div class="article-meta-bar">
        <span class="article-meta-chip"><?= esc($articleFormatLabel) ?></span>
        <?php if ($categoryMeta !== null): ?>
          <span class="article-meta-chip"><?= esc($categoryMeta['title']) ?></span>
        <?php endif; ?>
        <?php if ($updatedMeta !== null): ?>
          <span class="article-meta-chip">Aktualizovane: <?= esc($updatedMeta['date']) ?></span>
        <?php endif; ?>
        <span class="article-meta-chip"><?= esc((string) $readingTime) ?> min citania</span>
      </div>

      <h1><?= esc($meta['title']) ?></h1>
      <?php if ($articleLead !== ''): ?><p class="lead"><?= esc($articleLead) ?></p><?php endif; ?>

      <?php if ($articleHero !== null): ?>
        <figure class="article-hero<?= $heroIsSvg ? ' is-editorial' : '' ?>">
          <?= interessa_render_image($articleHero, ['class' => 'article-hero-image']) ?>
          <figcaption class="article-hero-caption">
            <span class="article-hero-chip"><?= esc($articleFormatLabel) ?></span>
            <?php if ($categoryMeta !== null): ?>
              <span class="article-hero-chip"><?= esc($categoryMeta['title']) ?></span>
            <?php endif; ?>
            <?php if ($updatedMeta !== null): ?>
              <span class="article-hero-chip"><?= esc('Aktualizovane ' . $updatedMeta['date']) ?></span>
            <?php endif; ?>
            <?php if ($heroIsSvg): ?>
              <span class="article-hero-chip is-soft"><?= esc('Editorial vizual') ?></span>
            <?php endif; ?>
          </figcaption>
        </figure>
      <?php endif; ?>

      <?php if ($categoryMeta !== null || $commerce !== null || $faq !== []): ?>
        <div class="article-quick-actions" aria-label="Rychla navigacia v clanku">
          <?php if ($categoryMeta !== null): ?>
            <a class="btn btn-ghost" href="<?= esc(category_url((string) $categoryMeta['slug'])) ?>">Pozriet temu</a>
          <?php endif; ?>
          <?php if ($comparisonTable !== null): ?>
            <a class="btn btn-ghost" href="#porovnanie-produktov">Porovnanie produktov</a>
          <?php endif; ?>
          <?php if ($commerce !== null): ?>
            <a class="btn btn-ghost" href="#odporucane-produkty">Odporucane produkty</a>
          <?php endif; ?>
          <?php if ($faq !== []): ?>
            <a class="btn btn-ghost" href="#caste-otazky">Caste otazky</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($commerce !== null): ?>
        <div id="rychly-vyber">
          <?php interessa_render_commerce_verdict($commerce); ?>
        </div>
      <?php endif; ?>

      <?php if ($hasDecisionLayer): ?>
        <section class="section-head">
          <h2>Rychle rozhodnutie</h2>
          <p class="meta">Najprv mas po ruke rychle porovnanie a shortlist odporucanych produktov, az potom hlbsi rozbor temy.</p>
        </section>
      <?php endif; ?>

      <?php if ($comparisonTable !== null): ?>
        <section class="topbox" id="porovnanie-produktov">
          <div class="topbox-head">
            <h2><?= esc((string) ($comparisonTable['title'] ?? 'Porovnanie produktov')) ?></h2>
            <?php if (!empty($comparisonTable['intro'])): ?>
              <p class="topbox-intro"><?= esc((string) $comparisonTable['intro']) ?></p>
            <?php endif; ?>
          </div>
          <?php
          echo interessa_render_comparison_table(
              is_array($comparisonTable['rows'] ?? null) ? $comparisonTable['rows'] : [],
              is_array($comparisonTable['columns'] ?? null) ? $comparisonTable['columns'] : []
          );
          ?>
        </section>
      <?php endif; ?>

      <?php
      if ($commerce !== null) {
          interessa_render_top_products(
              $commerce['products'] ?? [],
              $commerce['title'] ?? 'Odporucane produkty',
              $commerce['intro'] ?? null,
              'odporucane-produkty'
          );
      }
      ?>

      <?php interessa_render_article_audience_box($slug); ?>
      <?php interessa_render_article_outline($articleHeadings, $readingTime); ?>

      <?php if ($crossThemePaths !== []): ?>
        <section class="card">
          <div class="section-head">
            <h2>Kam dalej, ak riesis pribuzny ciel</h2>
            <p class="meta">Ak ta tato tema zaujima sirsie, tieto suvisiace smery ti pomozu prejst k dalsiemu logickemu kroku.</p>
          </div>
          <div class="hub-grid article-related-grid">
            <?php foreach ($crossThemePaths as $path): ?>
              <article class="hub-card article-teaser-card">
                <div class="hub-card-body article-teaser-body">
                  <span class="hub-card-label"><?= esc((string) ($categoryMeta['title'] ?? 'Tema')) ?></span>
                  <h3><a href="<?= esc((string) ($path['href'] ?? '/')) ?>"><?= esc((string) ($path['title'] ?? 'Dalsia tema')) ?></a></h3>
                  <?php if (trim((string) ($path['description'] ?? '')) !== ''): ?><p><?= esc((string) ($path['description'] ?? '')) ?></p><?php endif; ?>
                  <a class="card-link" href="<?= esc((string) ($path['href'] ?? '/')) ?>"><?= esc((string) ($path['cta'] ?? 'Otvorit temu')) ?></a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>

      <div class="article-body">
        <?php echo $articleBodyHtml; ?>
      </div>
      <?php
      interessa_render_article_trust_box($slug, $meta, $commerce, is_file($file) ? $file : null);
      interessa_render_article_faq_box($slug, 'caste-otazky');
      interessa_render_related_articles($slug, 3);
      ?>
    </article>
  </div>

  <?php $sidebarContextCategorySlug = $categoryMeta['slug'] ?? ''; ?>
  <?php include __DIR__ . '/inc/sidebar.php'; ?>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>

