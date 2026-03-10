<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';
$page_title = 'Zdravé proteíny | Interesa';
$page_description = 'Srvátkové WPC/WPI, vegánske a clear proteíny. Ako vybrať, dávkovanie a najlepšie tipy.';
include __DIR__ . '/../inc/head.php';
?>
<section class="container two-col">
  <div class="content">
    <article class="card">
      <h1>Zdravé proteíny</h1>
      <p>Všetko o proteínoch: druhy, dávkovanie, mýty a ako vybrať správny produkt podľa cieľa.</p>
      <h2>Odporúčané články</h2>
      <ul class="article-list">
        <li><a href="/clanky/najlepsie-proteiny-2025">Najlepšie proteíny 2025</a></li>
        <li><a href="/clanky/protein-na-chudnutie">Proteín na chudnutie</a></li>
        <li><a href="/clanky/clear-protein">Clear proteín – čo to je?</a></li>
        <li><a href="/clanky/srvatkovy-protein-vs-izolat-vs-hydro">WPC vs WPI vs Hydro</a></li>
        <li><a href="/clanky/veganske-proteiny-top-vyber-2025">Vegánske proteíny 2025</a></li>
      </ul>
    </article>
  </div>
  <?php include __DIR__ . '/../inc/sidebar.php'; ?>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>