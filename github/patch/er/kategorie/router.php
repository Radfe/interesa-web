<?php
require_once __DIR__.'/../inc/articles.php';
require_once __DIR__.'/../inc/articles_ext.php';
require_once __DIR__.'/../inc/functions.php';

$slug = trim($_GET['slug'] ?? '');
if(!isset($CATS[$slug])){
  header("HTTP/1.1 404 Not Found");
  $page=['title'=>'404']; include __DIR__.'/../inc/head.php';
  echo '<section class="content-main"><h1>Kategória sa nenašla</h1></section>';
  include __DIR__.'/../inc/sidebar.php'; include __DIR__.'/../inc/footer.php'; exit;
}

$page=['title'=>$CATS[$slug][0].' | Interesa','description'=>$CATS[$slug][1]];
include __DIR__.'/../inc/head.php';
?>
<section class="content-main">
  <h1><?= esc($CATS[$slug][0]) ?></h1>
  <div class="grid-cards">
  <?php foreach($ART as $s => [$t,$d,$cat]): if($cat!==$slug) continue; ?>
    <article class="post-card">
      <a href="<?= site_url('/clanky/'.$s.'.php') ?>"><img class="thumb" src="<?= esc(article_img($s)) ?>" alt="<?= esc($t) ?>" style="border-radius:12px; border:1px solid #e6e8ee; margin-bottom:8px"></a>
      <a class="chip" href="<?= site_url('/kategorie/'.$slug.'.php') ?>"><?= esc($CATS[$slug][0]) ?></a>
      <h3><a href="<?= site_url('/clanky/'.$s.'.php') ?>"><?= esc($t) ?></a></h3>
      <p class="meta"><?= esc($d) ?></p>
      <a class="btn" href="<?= site_url('/clanky/'.$s.'.php') ?>">Čítať</a>
    </article>
  <?php endforeach; ?>
  </div>
</section>
<?php include __DIR__.'/../inc/sidebar.php'; ?>
<?php include __DIR__.'/../inc/footer.php'; ?>