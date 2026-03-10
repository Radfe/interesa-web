<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/metrics.php';

$slug = preg_replace('~[^a-z0-9\-_]+~i', '', (string) ($_GET['slug'] ?? ''));
$file = __DIR__ . '/content/articles/' . $slug . '.html';
if ($slug === '' || !is_file($file)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    return;
}

views_track($slug);
$meta = article_meta($slug);
$category = $meta['category'] !== '' ? category_meta($meta['category']) : null;
$page_title = $meta['title'] . ' | Interesa';
$page_description = $meta['description'] !== '' ? $meta['description'] : 'Praktický článok a porovnanie na Interesa.';
$page_type = 'Article';
$page_image = article_img($slug);
include __DIR__ . '/inc/head.php';
?>
<section class="container two-col">
  <div class="content content-stack">
    <nav class="breadcrumbs" aria-label="Breadcrumb">
      <a href="/">Domov</a>
      <span aria-hidden="true">/</span>
      <a href="/clanky/">Články</a>
      <?php if ($category !== null): ?>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(category_url($category['slug'])) ?>"><?= esc($category['title']) ?></a>
      <?php endif; ?>
      <span aria-hidden="true">/</span>
      <span><?= esc($meta['title']) ?></span>
    </nav>

    <article class="lead-article article-shell">
      <?php if ($category !== null): ?>
        <a class="chip" href="<?= esc(category_url($category['slug'])) ?>"><?= esc($category['title']) ?></a>
      <?php endif; ?>

      <h1><?= esc($meta['title']) ?></h1>
      <?php if ($meta['description'] !== ''): ?><p class="lead"><?= esc($meta['description']) ?></p><?php endif; ?>

      <div class="article-body">
        <?php readfile($file); ?>
      </div>

      <section class="article-actions">
        <div>
          <h2>Pokračovať v téme</h2>
          <p>Ak si chceš porovnať ďalšie varianty alebo prejsť širší kontext, pokračuj do súvisiacej kategórie alebo ďalších článkov.</p>
        </div>
        <div class="action-row">
          <?php if ($category !== null): ?>
            <a class="btn btn-primary" href="<?= esc(category_url($category['slug'])) ?>">Prejsť do kategórie</a>
          <?php endif; ?>
          <a class="btn btn-ghost" href="/clanky/">Ďalšie články</a>
        </div>
      </section>
    </article>
  </div>

  <aside class="sidebar" aria-label="Pravý panel">
    <?php include __DIR__ . '/inc/components/latest_articles.php'; ?>

    <article class="ad-card info-panel">
      <h3>V skratke</h3>
      <p>Na Interesa sa oplatí prechádzať aj súvisiace témy. Často práve vedľajší článok doplní detail, ktorý pri výbere rozhodne.</p>
    </article>

    <article class="ad-card">
      <h3>Heureka vyhľadávanie</h3>
      <div class="heureka-affiliate-searchpanel" data-trixam-positionid="67512" data-trixam-codetype="iframe" data-trixam-linktarget="top"></div>
    </article>

    <article class="ad-card">
      <h3>Top ponuky</h3>
      <div class="heureka-affiliate-category" data-trixam-positionid="40746" data-trixam-categoryid="5526" data-trixam-codetype="iframe" data-trixam-linktarget="top"></div>
    </article>
  </aside>
</section>
<?php include __DIR__ . '/inc/footer.php';