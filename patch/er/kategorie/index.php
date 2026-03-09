<?php
$page=['title'=>'Kategórie | Interesa','description'=>'Témy a sprievodcovia.'];
include __DIR__.'/../inc/head.php';
require __DIR__.'/../inc/articles.php';
require_once __DIR__.'/../inc/articles_ext.php';
?>
<section class="content-main">
  <h1>Kategórie</h1>
  <div class="grid-cards">
  <?php foreach($CATS as $slug => [$name,$desc]): ?>
    <article class="post-card">
      <a class="chip" href="<?= site_url('/kategorie/'.$slug.'.php') ?>"><?= esc($name) ?></a>
      <h3><?= esc($name) ?></h3>
      <p class="meta"><?= esc($desc) ?></p>
      <a class="btn" href="<?= site_url('/kategorie/'.$slug.'.php') ?>">Zobraziť</a>
    </article>
  <?php endforeach; ?>
  </div>
</section>
<?php include __DIR__.'/../inc/sidebar.php'; ?><?php include __DIR__.'/../inc/footer.php'; ?>