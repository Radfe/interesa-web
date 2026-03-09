<?php require __DIR__.'/../inc/functions.php';
$page=["title"=>"Zásady ochrany osobných údajov","description"=>"Ako chránime súkromie"];
$page["canonical"]=site_url("/stranky/zasady-ochrany-osobnych-udajov.php");
include __DIR__."/../inc/head.php"; ?>
<article class="post">
  <h1><?= esc($page['title']) ?></h1>
  <p>Používame iba nevyhnutné cookies. V otázkach súkromia píšte na <a href='mailto:redakcia@interesa.sk'>redakcia@interesa.sk</a>.</p>
</article>
<?php include __DIR__.'/../inc/sidebar.php'; ?>
<?php include __DIR__.'/../inc/footer.php'; ?>