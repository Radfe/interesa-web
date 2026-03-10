<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';
http_response_code(404);

$page_title = '404 – Stránka sa nenašla';
$page_description = 'Je nám ľúto, stránka sa nenašla. Pozrite si hlavné kategórie a články.';

include __DIR__ . '/inc/head.php';
?>
<section class="container two-col">
  <div class="content">
    <article class="card">
      <h1>Stránka sa nenašla (404)</h1>
      <p>Mrzí nás to – adresa neexistuje alebo bola presunutá.</p>
      <h2>Kam ďalej?</h2>
      <ul class="article-list">
        <li><a href="/">Domov</a></li>
        <li><a href="/kategorie/">Kategórie</a></li>
        <li><a href="/clanky/">Články</a></li>
      </ul>
    </article>
  </div>
  <aside class="sidebar" aria-label="Pravý panel">
    <div class="heureka-affiliate-search"
         data-trixam-positionid="67512"
         data-trixam-codetype="iframe"
         data-trixam-linktarget="blank"></div>
  </aside>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>
