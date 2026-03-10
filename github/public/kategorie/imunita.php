<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';
$page_title = 'Imunita | Interesa';
$page_description = 'Základy aj praktické tipy pre podporu imunity: D3, C, zinok a probiotiká.';
include __DIR__ . '/../inc/head.php';
?>
<section class="container two-col">
  <div class="content">
    <article class="card">
      <h1>Imunita</h1>
      <p>Najdôležitejšie živiny a návyky pre stabilnú obranyschopnosť.</p>
      <h2>Odporúčané články</h2>
      <ul class="article-list">
        <li><a href="/clanky/vitamin-d3-a-imunita">Vitamín D3 a imunita</a></li>
        <li><a href="/clanky/vitamin-c">Vitamín C</a></li>
        <li><a href="/clanky/probiotika-a-travenie">Probiotiká a trávenie</a></li>
        <li><a href="/clanky/zinek">Zinok</a></li>
      </ul>
    </article>
  </div>
  <?php include __DIR__ . '/../inc/sidebar.php'; ?>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>