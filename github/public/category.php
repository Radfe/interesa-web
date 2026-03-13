<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$slug = preg_replace('~[^a-z0-9\-_]+~i', '', (string) ($_GET['slug'] ?? '')) ?? '';
$category = category_meta($slug);
if ($slug === '' || $category === null) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    return;
}

$articles = category_articles($slug);
$page_title = $category['title'] . ' | Interesa';
$page_description = $category['description'];
$page_type = 'CollectionPage';
$page_image = category_visual($slug, 'hero');

include __DIR__ . '/inc/head.php';
?>
<section class="container two-col">
  <div class="content content-stack">
    <article class="lead-article category-hero">
      <span class="eyebrow">Obsahovy hub</span>
      <h1><?= esc($category['title']) ?></h1>
      <p class="lead"><?= esc($category['description']) ?></p>
      <figure class="category-hero-media">
        <img src="<?= esc(category_visual($slug, 'hero')) ?>" alt="<?= esc($category['title']) ?>" width="1600" height="900" loading="eager" fetchpriority="high">
      </figure>
      <div class="article-actions compact-actions">
        <a class="btn btn-primary" href="/clanky/">Pozriet suvisiace clanky</a>
        <a class="btn btn-ghost" href="/search?q=<?= rawurlencode($category['title']) ?>">Vyhladat temu</a>
      </div>
    </article>

    <?php if (!$articles): ?>
      <article class="card legal-card">
        <h2>Obsah pre tuto kategoriu pripravujeme</h2>
        <p>Najblizsi krok je doplnit seriu clankov, ktore pokryju vyber, porovnania aj caste otazky pouzivatelov.</p>
      </article>
    <?php else: ?>
      <div class="grid-cards article-card-grid">
        <?php foreach ($articles as $item): $itemMedia = article_media($item['slug']); ?>
          <article class="post-card">
            <a href="<?= esc(article_url($item['slug'])) ?>">
              <img class="thumb" loading="lazy" decoding="async" src="<?= esc($itemMedia['card_image']) ?>" alt="<?= esc($itemMedia['card_alt']) ?>">
            </a>
            <div class="post-card-body">
              <a class="chip" href="<?= esc(category_url($category['slug'])) ?>"><?= esc($category['title']) ?></a>
              <h3><a href="<?= esc(article_url($item['slug'])) ?>"><?= esc($item['title']) ?></a></h3>
              <?php if ($item['description'] !== ''): ?><p><?= esc($item['description']) ?></p><?php endif; ?>
              <a class="btn btn-ghost" href="<?= esc(article_url($item['slug'])) ?>">Citat clanok</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <aside class="sidebar" aria-label="Pravy panel">
    <?php include __DIR__ . '/inc/components/latest_articles.php'; ?>
    <article class="ad-card info-panel">
      <h3>Ako zaraba tato kategoria</h3>
      <p>Najsilnejsie stranky v kategorii kombinuju evergreen SEO otazky, nakupne porovnania a jasne prelinkovanie na dalsie relevantne temy.</p>
    </article>
    <article class="ad-card">
      <h3>Heureka vyhladavanie</h3>
      <div class="heureka-affiliate-searchpanel" data-trixam-positionid="67512" data-trixam-codetype="iframe" data-trixam-linktarget="top"></div>
    </article>
  </aside>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>
