<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/metrics.php';
require_once __DIR__ . '/inc/top-products.php';

$slug = preg_replace('~[^a-z0-9\-_]+~i', '', (string) ($_GET['slug'] ?? '')) ?? '';
$file = __DIR__ . '/content/articles/' . $slug . '.html';
if ($slug === '' || !is_file($file)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    return;
}

views_track($slug);
$meta = article_meta($slug);
$category = $meta['category'] !== '' ? category_meta($meta['category']) : null;
$articleMedia = article_media($slug);
$products = article_products($slug);

$page_title = $meta['title'] . ' | Interesa';
$page_description = $meta['description'] !== '' ? $meta['description'] : 'Prakticky clanok a porovnanie na Interesa.';
$page_type = 'Article';
$page_image = article_hero_img($slug);

include __DIR__ . '/inc/head.php';
?>
<section class="container two-col">
  <div class="content content-stack">
    <article class="lead-article article-shell">
      <nav class="muted" aria-label="Breadcrumb">
        <a href="/">Domov</a> &rsaquo; <a href="/clanky/">Clanky</a>
        <?php if ($category !== null): ?>
          &rsaquo; <a href="<?= esc(category_url($category['slug'])) ?>"><?= esc($category['title']) ?></a>
        <?php endif; ?>
      </nav>

      <?php if ($category !== null): ?>
        <a class="chip" href="<?= esc(category_url($category['slug'])) ?>"><?= esc($category['title']) ?></a>
      <?php endif; ?>

      <h1><?= esc($meta['title']) ?></h1>
      <?php if ($meta['description'] !== ''): ?><p class="lead"><?= esc($meta['description']) ?></p><?php endif; ?>

      <figure class="article-hero-media">
        <img src="<?= esc($articleMedia['hero_image']) ?>" alt="<?= esc($articleMedia['hero_alt']) ?>" width="1600" height="900" fetchpriority="high">
      </figure>

      <?php if ($products !== []): ?>
        <section class="article-products">
          <?php interessa_render_top_products($products, 'Odporucane produkty'); ?>
        </section>
      <?php endif; ?>

      <div class="article-body">
        <?php readfile($file); ?>
      </div>

      <section class="article-actions">
        <div>
          <h2>Chces pokracovat vo vybere?</h2>
          <p>Prejdi na dalsie tematicke clanky alebo otvor kategoriu s podobnym obsahom a porovnaniami.</p>
        </div>
        <div class="action-row">
          <?php if ($category !== null): ?>
            <a class="btn btn-primary" href="<?= esc(category_url($category['slug'])) ?>">Prejst do kategorie</a>
          <?php endif; ?>
          <a class="btn btn-ghost" href="/clanky/">Dalsie clanky</a>
        </div>
      </section>
    </article>
  </div>

  <aside class="sidebar" aria-label="Pravy panel">
    <?php include __DIR__ . '/inc/components/latest_articles.php'; ?>

    <article class="ad-card info-panel">
      <h3>Tip pre vyber</h3>
      <p>Najlepsie funguju stranky, ktore citatela posunu dalej: na porovnanie, kategoriu alebo konkretne odporucanie.</p>
    </article>

    <article class="ad-card">
      <h3>Heureka vyhladavanie</h3>
      <div class="heureka-affiliate-searchpanel" data-trixam-positionid="67512" data-trixam-codetype="iframe" data-trixam-linktarget="top"></div>
    </article>

    <article class="ad-card">
      <h3>Top ponuky</h3>
      <div class="heureka-affiliate-category" data-trixam-positionid="40746" data-trixam-categoryid="5526" data-trixam-codetype="iframe" data-trixam-linktarget="top"></div>
    </article>
  </aside>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>
