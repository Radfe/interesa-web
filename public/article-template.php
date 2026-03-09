<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';
$slug = $GLOBALS['__ARTICLE_SLUG'] ?? '';
$ART = require __DIR__ . '/inc/articles.php';
[$PAGE_TITLE,$PAGE_DESCRIPTION] = $ART[$slug] ?? ['Článok',''];
require __DIR__ . '/inc/head.php';
?>
<nav class="muted" style="margin:12px 0">
  <a href="/">Domov</a> › <a href="/clanky/">Články</a> › <?= esc($PAGE_TITLE) ?>
</nav>
<article class="article">
  <h1><?= esc($PAGE_TITLE) ?></h1>
  <?php if ($PAGE_DESCRIPTION): ?><p class="lead"><?= esc($PAGE_DESCRIPTION) ?></p><?php endif; ?>
  <div class="article-body">
    <?php readfile(__DIR__ . '/content/articles/' . $slug . '.html'); ?>
  </div>
</article>
<?php require __DIR__ . '/inc/footer.php';
