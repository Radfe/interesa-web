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

$slug = preg_replace('~[^a-z0-9\-_]+~i', '', (string) ($_GET['slug'] ?? ''));
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
        <figure class="article-hero">
          <?= interessa_render_image($articleHero, ['class' => 'article-hero-image']) ?>
        </figure>
      <?php endif; ?>

      <?php interessa_render_article_audience_box($slug); ?>
      <?php interessa_render_article_outline($articleHeadings, $readingTime); ?>

      <div class="article-body">
        <?php echo $articleBodyHtml; ?>
      </div>

      <?php
      if ($commerce !== null) {
          interessa_render_top_products(
              $commerce['products'] ?? [],
              $commerce['title'] ?? 'Odporucane produkty',
              $commerce['intro'] ?? null
          );
      }
      interessa_render_article_trust_box($slug, $meta, $commerce, is_file($file) ? $file : null);
      interessa_render_article_faq_box($slug);
      interessa_render_related_articles($slug, 3);
      ?>
    </article>
  </div>

  <?php include __DIR__ . '/inc/sidebar.php'; ?>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>

