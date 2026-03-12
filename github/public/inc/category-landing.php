<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/category-hubs.php';

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
$extraArticles = array_values(array_filter($categoryArticles, static function (array $item) use ($featuredSlugs): bool {
    return !in_array((string) ($item['slug'] ?? ''), $featuredSlugs, true);
}));

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
        </figure>
      <?php endif; ?>
      <p class="hub-eyebrow">Tematicky hub</p>
      <div class="hub-heading-row">
        <span class="hub-icon-badge" aria-hidden="true"><?= interessa_category_icon($slug) ?></span>
        <h1><?= esc($hub['title']) ?></h1>
      </div>
      <p class="lead"><?= esc($hub['intro']) ?></p>
      <div class="hub-meta-row">
        <span class="article-meta-chip"><?= esc((string) $articleCount) ?> clankov v teme</span>
        <a class="card-link" href="/clanky/?category=<?= esc($slug) ?>">Vsetky clanky v teme</a>
      </div>
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
            $label = trim((string) ($guide['label'] ?? 'Sprievodca'));
            $guideImage = interessa_article_image_meta($guideSlug, 'thumb', true);
            $guideFile = dirname(__DIR__) . '/content/articles/' . $guideSlug . '.html';
            $guideDate = is_file($guideFile) ? date('d.m.Y', (int) @filemtime($guideFile)) : '';
            ?>
            <article class="hub-card">
              <?= interessa_render_image($guideImage, ['class' => 'hub-card-image', 'alt' => $title]) ?>
              <div class="hub-card-body">
                <div class="article-card-meta">
                  <span class="hub-card-label"><?= esc($label) ?></span>
                  <?php if ($guideDate !== ''): ?><span class="article-card-date"><?= esc($guideDate) ?></span><?php endif; ?>
                </div>
                <h3><a href="<?= esc(article_url($guideSlug)) ?>"><?= esc($title) ?></a></h3>
                <?php if ($description !== ''): ?><p><?= esc($description) ?></p><?php endif; ?>
                <a class="btn" href="<?= esc(article_url($guideSlug)) ?>">Otvorit clanok</a>
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
        <ul class="article-list">
          <?php foreach ($extraArticles as $item): ?>
            <li>
              <a href="<?= esc(article_url($item['slug'])) ?>"><?= esc($item['title']) ?></a>
              <?php if (!empty($item['description'])): ?><p class="meta"><?= esc($item['description']) ?></p><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>
  </div>

  <?php include dirname(__DIR__) . '/inc/sidebar.php'; ?>
</section>
<?php include dirname(__DIR__) . '/inc/footer.php'; ?>