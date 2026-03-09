<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/head.php';
require_once __DIR__ . '/../inc/articles.php';
$ART = articles_all();
?>
<div class="wrap" style="max-width:1100px;margin:0 auto;padding:16px;">
  <h1>Články</h1>
  <div class="card-list">
    <?php foreach ($ART as $slug=>$a): ?>
      <article class="card">
        <h3><a href="/clanky/<?= htmlspecialchars($slug) ?>"><?= htmlspecialchars($a[0]) ?></a></h3>
        <p><?= htmlspecialchars($a[1]) ?></p>
        <p><a class="cta" href="/clanky/<?= htmlspecialchars($slug) ?>">Čítať</a></p>
      </article>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
