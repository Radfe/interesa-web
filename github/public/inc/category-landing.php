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
        ['name' => 'Kategórie', 'url' => '/kategorie'],
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
        'name' => 'Kľúčové články: ' . $hub['title'],
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
      <p class="hub-eyebrow">Tematický hub</p>
      <div class="hub-heading-row">
        <span class="hub-icon-badge" aria-hidden="true"><?= interessa_category_icon($slug) ?></span>
        <h1><?= esc($hub['title']) ?></h1>
      </div>
      <p class="lead"><?= esc($hub['intro']) ?></p>
      <div class="hub-meta-row">
        <span class="article-meta-chip"><?= esc((string) $articleCount) ?> článkov v téme</span>
        <a class="card-link" href="/clanky/?category=<?= esc($slug) ?>">Všetky články v téme</a>
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
        <h2>Kľúčové články</h2>
        <p class="meta">Najlepšia cesta je začať jedným z týchto článkov a až potom riešiť konkrétny produkt.</p>
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
            ?>
            <article class="hub-card">
              <?= interessa_render_image($guideImage, ['class' => 'hub-card-image', 'alt' => $title]) ?>
              <div class="hub-card-body">
                <h3><a href="<?= esc(article_url($guideSlug)) ?>"><?= esc($title) ?></a></h3>
                <span class="hub-card-label"><?= esc($label) ?></span>
                <?php if ($description !== ''): ?><p><?= esc($description) ?></p><?php endif; ?>
                <a class="btn" href="<?= esc(article_url($guideSlug)) ?>">Otvoriť článok</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="note"><?= esc((string) ($hub['empty_message'] ?? 'Táto kategória sa ešte dopĺňa. Zatiaľ tu čoskoro pribudnú odporúčané články.')) ?></p>
      <?php endif; ?>
    </section>

    <?php if ($extraArticles !== []): ?>
      <section class="card">
        <div class="section-head">
          <h2>Ďalšie články v téme</h2>
          <p class="meta">Dopĺňujúce články pre hlbšie preštudovanie témy.</p>
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