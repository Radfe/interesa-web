<?php declare(strict_types=1);
$ROOT = __DIR__;
require_once $ROOT . '/inc/functions.php';

/* register článkov */
$ART = [];
@include $ROOT . '/inc/articles.php';
@include $ROOT . '/inc/articles_ext.php';

$slug = preg_replace('~[^a-z0-9\-\_]+~i', '', (string)($_GET['slug'] ?? ''));
if ($slug === '') { http_response_code(404); echo 'Not Found'; exit; }

$meta = $ART[$slug] ?? [ucwords(str_replace('-',' ',$slug)),'',''];
$PAGE_TITLE       = $meta[0] ?? 'Článok';
$PAGE_DESCRIPTION = $meta[1] ?? '';

require $ROOT . '/inc/head.php';
?>
<nav class="muted" style="margin:12px 0">
  <a href="/">Domov</a> › <a href="/clanky/">Články</a> › <?= esc($PAGE_TITLE) ?>
</nav>

<article class="article">
  <h1><?= esc($PAGE_TITLE) ?></h1>
  <?php if ($PAGE_DESCRIPTION): ?><p class="lead"><?= esc($PAGE_DESCRIPTION) ?></p><?php endif; ?>
  <div class="article-body">
    <?php
      $file = $ROOT . '/content/articles/' . $slug . '.html';
      if (is_file($file)) {
        readfile($file);
      } else {
        echo '<p>Obsah sa nenašiel.</p>';
      }
    ?>
  </div>
</article>

<?php require $ROOT . '/inc/footer.php';
