<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';

$slug = $GLOBALS['__ARTICLE_SLUG'] ?? ($slug ?? '');
if ($slug === '' && isset($_SERVER['SCRIPT_FILENAME'])) {
    $slug = basename((string) $_SERVER['SCRIPT_FILENAME'], '.php');
}

$meta = article_meta($slug);
$PAGE_TITLE = $meta['title'];
$PAGE_DESCRIPTION = $meta['description'];

require __DIR__ . '/inc/head.php';
?>
<nav class="muted" style="margin:12px 0">
  <a href="/">Domov</a> &rsaquo; <a href="/clanky/">Clanky</a> &rsaquo; <?= esc($PAGE_TITLE) ?>
</nav>
<article class="article">
  <h1><?= esc($PAGE_TITLE) ?></h1>
  <?php if ($PAGE_DESCRIPTION): ?><p class="lead"><?= esc($PAGE_DESCRIPTION) ?></p><?php endif; ?>
  <div class="article-body">
    <?php
    $file = __DIR__ . '/content/articles/' . $slug . '.html';
    if (is_file($file)) {
        readfile($file);
    } else {
        http_response_code(404);
        echo '<p>Obsah sa nenasiel.</p>';
    }
    ?>
  </div>
</article>
<?php require __DIR__ . '/inc/footer.php';
