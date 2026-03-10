<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';
$page_title = 'Zdravá výživa | Interesa';
$page_description = 'Snacky, raňajky, zloženie produktov a praktické tipy pre každodennú zdravšiu výživu.';
include __DIR__ . '/../inc/head.php';
?>
<section class="container two-col">
  <div class="content">
    <article class="card">
      <h1>Zdravá výživa</h1>
      <p>Prehľad praktických článkov o doplnkoch výživy, snackoch a zmysluplnom výbere produktov.</p>
      <h2>Odporúčané články</h2>
      <ul class="article-list">
        <li><a href="/clanky/doplnky-vyzivy">Doplnky výživy – top výber</a></li>
        <li><a href="/clanky/probiotika-a-travenie">Probiotiká a trávenie</a></li>
        <li><a href="/clanky/probiotika-ako-vybrat">Ako vybrať probiotiká</a></li>
      </ul>
    </article>
  </div>
  <?php include __DIR__ . '/../inc/sidebar.php'; ?>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>