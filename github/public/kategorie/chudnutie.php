<?php
require __DIR__.'/../inc/articles.php';
require __DIR__.'/../inc/functions.php';
$catSlug = 'chudnutie';
$page=['title'=>$CATS[$catSlug][0].' | Interesa','description'=>$CATS[$catSlug][1]];
include __DIR__.'/../inc/head.php';
?>
<h1><?= esc($CATS[$catSlug][0]) ?></h1>
<div class="grid-cards">
<?php foreach($ART as $s => [$t,$d,$c]): if($c!==$catSlug) continue; ?>
  <article class="post-card">
    <a class="chip" href="<?= site_url('/kategorie/'.$catSlug.'.php') ?>"><?= esc($CATS[$catSlug][0]) ?></a>
    <h3><a href="<?= site_url('/clanky/'.$s.'.php') ?>"><?= esc($t) ?></a></h3>
    <p class="meta"><?= esc($d) ?></p>
    <a class="btn" href="<?= site_url('/clanky/'.$s.'.php') ?>">Čítať</a>
  </article>
<?php endforeach; ?>
</div>
<?php include __DIR__.'/../inc/sidebar.php'; ?>
<?php include __DIR__.'/../inc/footer.php'; ?>