<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';
$page_title = 'Vitamíny a minerály | Interesa';
$page_description = 'Horčík, zinok, vitamín D3/C a ďalšie mikroživiny. Ako sa zorientovať a čo funguje.';
include __DIR__ . '/../inc/head.php';
?>
<section class="container two-col">
  <div class="content">
    <article class="card">
      <h1>Vitamíny a minerály</h1>
      <p>Praktické návody a výber produktov pre imunitu, energiu aj regeneráciu.</p>
      <h2>Odporúčané články</h2>
      <ul class="article-list">
        <li><a href="/clanky/horcik-ktory-je-najlepsi-a-preco">Horčík: ktorý je najlepší a prečo</a></li>
        <li><a href="/clanky/horcik">Horčík – základ</a></li>
        <li><a href="/clanky/vitamin-d3-a-imunita">Vitamín D3 a imunita</a></li>
        <li><a href="/clanky/vitamin-d3">Vitamín D3: sprievodca</a></li>
        <li><a href="/clanky/vitamin-c">Vitamín C</a></li>
        <li><a href="/clanky/zinek">Zinok</a></li>
      </ul>
    </article>
  </div>
  <?php include __DIR__ . '/../inc/sidebar.php'; ?>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>