<?php require __DIR__.'/../inc/functions.php';
$page=["title"=>"Affiliate & podmienky","description"=>"Ako funguje financovanie webu"];
$page["canonical"]=site_url("/stranky/affiliate.php");
include __DIR__."/../inc/head.php"; ?>
<article class="post">
  <h1><?= esc($page['title']) ?></h1>
  <p>Nákupom cez naše odkazy nás podporíte bez navýšenia ceny. Texty neslúžia ako zdravotné odporúčanie.</p>
</article>
<?php include __DIR__.'/../inc/sidebar.php'; ?>
<?php include __DIR__.'/../inc/footer.php'; ?>