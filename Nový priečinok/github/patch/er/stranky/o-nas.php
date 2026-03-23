<?php require __DIR__.'/../inc/functions.php';
$page=["title"=>"O nás","description"=>"Kto stojí za Interesa"];
$page["canonical"]=site_url("/stranky/o-nas.php");
include __DIR__."/../inc/head.php"; ?>
<article class="post content-main">
  <h1><?= esc($page['title']) ?></h1>
  <p>Interesa je redakčný web s cieľom prinášať zrozumiteľné porovnania a návody. Dbáme na jasné kritériá hodnotenia a aktuálnosť informácií.</p>
</article>
<?php include __DIR__.'/../inc/sidebar.php'; ?><?php include __DIR__.'/../inc/footer.php'; ?>