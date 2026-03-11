<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$slug = preg_replace('~[^a-z0-9\-_]+~i', '', (string) ($_GET['slug'] ?? ''));
$category = category_meta($slug);
if ($slug === '' || $category === null) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    return;
}

$articles = category_articles($slug);
$articleCount = count($articles);
$categoryHero = interessa_category_image_meta($slug, 'hero', true);
$page_title = $category['title'] . ' | Interesa';
$page_description = $category['description'];
$page_canonical = category_url($category['slug']);
$page_image = $categoryHero['src'] ?? asset('img/brand/og-default.svg');
$page_og_type = 'website';
$page_schema = [
    breadcrumb_schema([
        ['name' => 'Domov', 'url' => '/'],
        ['name' => 'Kategórie', 'url' => '/kategorie'],
        ['name' => $category['title'], 'url' => $page_canonical],
    ]),
    [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $category['title'],
        'description' => $category['description'],
        'url' => absolute_url($page_canonical),
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => 'Interesa',
            'url' => absolute_url('/'),
        ],
    ],
];

include __DIR__ . '/inc/head.php';
?>
<section class="container two-col">
  <div class="content">
    <article class="card">
      <?php if ($categoryHero !== null): ?>
        <figure class="hub-hero-media">
          <?= interessa_render_image($categoryHero, ['class' => 'hub-card-image', 'loading' => 'eager']) ?>
        </figure>
      <?php endif; ?>
      <div class="hub-heading-row">
        <span class="hub-icon-badge" aria-hidden="true"><?= interessa_category_icon((string) $category['slug']) ?></span>
        <h1><?= esc($category['title']) ?></h1>
      </div>
      <p class="meta"><?= esc($category['description']) ?></p>
      <div class="hub-meta-row">
        <span class="article-meta-chip"><?= esc((string) $articleCount) ?> článkov v téme</span>
        <a class="card-link" href="/clanky/?category=<?= esc($category['slug']) ?>">Všetky články v téme</a>
      </div>

      <?php if (!$articles): ?>
        <p class="note">V tejto kategórii zatiaľ nemáme žiadne články.</p>
      <?php else: ?>
        <div class="hub-grid article-teaser-grid">
          <?php foreach ($articles as $item): ?>
            <?php $articleImage = interessa_article_image_meta($item['slug'], 'thumb', true); ?>
            <article class="hub-card article-teaser-card">
              <a href="<?= esc(article_url($item['slug'])) ?>">
                <?= interessa_render_image($articleImage, ['class' => 'hub-card-image', 'alt' => $item['title']]) ?>
              </a>
              <div class="hub-card-body article-teaser-body">
                <a class="hub-card-label" href="<?= esc(category_url($category['slug'])) ?>"><?= esc($category['title']) ?></a>
                <h3><a href="<?= esc(article_url($item['slug'])) ?>"><?= esc($item['title']) ?></a></h3>
                <?php if ($item['description'] !== ''): ?><p><?= esc($item['description']) ?></p><?php endif; ?>
                <a class="card-link" href="<?= esc(article_url($item['slug'])) ?>">Otvoriť článok</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </article>
  </div>

  <?php include __DIR__ . '/inc/sidebar.php'; ?>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>