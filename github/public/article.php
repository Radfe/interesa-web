<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$slug = preg_replace('~[^a-z0-9\-_]+~i', '', (string) ($_GET['slug'] ?? ''));
$file = __DIR__ . '/content/articles/' . $slug . '.html';
if ($slug === '' || !is_file($file)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    return;
}

$meta = article_meta($slug);
$page_title = $meta['title'] . ' | Interesa';
$page_description = $meta['description'];
include __DIR__ . '/inc/head.php';
?>
<section class="container two-col">
  <div class="content">
    <article class="lead-article article-shell">
      <nav class="muted" aria-label="Breadcrumb">
        <a href="/">Domov</a> &rsaquo; <a href="/clanky/">Články</a>
        <?php if ($meta['category'] !== ''): ?>
          &rsaquo; <a href="<?= esc(category_url($meta['category'])) ?>"><?= esc(category_meta($meta['category'])['title'] ?? humanize_slug($meta['category'])) ?></a>
        <?php endif; ?>
      </nav>
      <h1><?= esc($meta['title']) ?></h1>
      <?php if ($meta['description'] !== ''): ?><p class="lead"><?= esc($meta['description']) ?></p><?php endif; ?>
      <div class="article-body">
        <?php readfile($file); ?>
      </div>
    </article>
  </div>

  <?php include __DIR__ . '/inc/sidebar.php'; ?>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>