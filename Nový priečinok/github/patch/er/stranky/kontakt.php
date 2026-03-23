<?php require __DIR__.'/../inc/functions.php';
$page=["title"=>"Kontakt","description"=>"Napíšte nám"];
$page["canonical"]=site_url("/stranky/kontakt.php");
include __DIR__."/../inc/head.php"; ?>
<article class="post">
  <h1><?= esc($page['title']) ?></h1>
  <p>E-mail: <a href='mailto:redakcia@interesa.sk'>redakcia@interesa.sk</a></p>
</article>
<?php include __DIR__.'/../inc/sidebar.php'; ?>
<?php include __DIR__.'/../inc/footer.php'; ?>