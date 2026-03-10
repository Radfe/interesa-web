<?php
declare(strict_types=1);
require_once __DIR__ . '/articles.php';
require_once __DIR__ . '/metrics.php';
$top = views_top(6);
$cats = articles_categories();
?>
<aside class="sidebar">
  <section class="box">
    <h3>Najčítanejšie</h3>
    <ol>
      <?php foreach ($top as $slug => $n): $a = article_get($slug); if(!$a) continue; ?>
        <li><a href="/clanky/<?= htmlspecialchars($slug) ?>"><?= htmlspecialchars($a[0]) ?></a></li>
      <?php endforeach; ?>
      <?php if (!$top): ?><li>— Zbierame dáta —</li><?php endif; ?>
    </ol>
  </section>
  <section class="box">
    <h3>Kategórie</h3>
    <ul>
      <?php foreach ($cats as $slug=>$c): ?>
        <li><a href="/kategorie/<?= htmlspecialchars($slug) ?>"><?= htmlspecialchars($c[0]) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </section>
</aside>
