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
$page_title = $category['title'] . ' | Interesa';
$page_description = $category['description'];
include __DIR__ . '/inc/head.php';
?>
<section class="container two-col">
  <div class="content">
    <article class="card">
      <h1><?= esc($category['title']) ?></h1>
      <p class="meta"><?= esc($category['description']) ?></p>

      <?php if (!$articles): ?>
        <p class="note">V tejto kategorii zatial nemame ziadne clanky.</p>
      <?php else: ?>
        <div class="grid-cards">
          <?php foreach ($articles as $item): ?>
            <article class="post-card">
              <a href="<?= esc(article_url($item['slug'])) ?>">
                <img class="thumb" loading="lazy" decoding="async" src="<?= esc(article_img($item['slug'])) ?>" alt="<?= esc($item['title']) ?>">
              </a>
              <a class="chip" href="<?= esc(category_url($category['slug'])) ?>"><?= esc($category['title']) ?></a>
              <h3><a href="<?= esc(article_url($item['slug'])) ?>"><?= esc($item['title']) ?></a></h3>
              <?php if ($item['description'] !== ''): ?><p class="meta"><?= esc($item['description']) ?></p><?php endif; ?>
              <a class="btn" href="<?= esc(article_url($item['slug'])) ?>">Citat</a>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </article>
  </div>

  <aside class="sidebar" aria-label="Pravy panel">
    <?php include __DIR__ . '/inc/components/latest_articles.php'; ?>
    <article class="ad-card">
      <h3>Heureka vyhladavanie</h3>
      <div class="heureka-affiliate-searchpanel" data-trixam-positionid="67512" data-trixam-codetype="iframe" data-trixam-linktarget="top"></div>
    </article>
    <article class="ad-card">
      <h3>Vitaminy a mineraly</h3>
      <div class="heureka-affiliate-category" data-trixam-positionid="40743" data-trixam-categoryid="731" data-trixam-codetype="iframe" data-trixam-linktarget="top"></div>
    </article>
  </aside>
</section>
<?php include __DIR__ . '/inc/footer.php';