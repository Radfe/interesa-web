<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';

$page_title = 'Kategórie – Interesa';
$page_description = 'Prehľad kategórií: proteíny, výživa, vitamíny & minerály, imunita, sila, kĺby & koža.';

include __DIR__ . '/../inc/head.php';

$cats = [
  ['slug'=>'proteiny','title'=>'Zdravé proteíny','desc'=>'Porovnania WPC/WPI, Clear, vegánske proteíny, dávkovanie.'],
  ['slug'=>'vyziva','title'=>'Zdravá výživa','desc'=>'Snacky, kaše, orechy & maslá, recepty a praktické tipy.'],
  ['slug'=>'mineraly','title'=>'Vitamíny & minerály','desc'=>'Horčík, zinok, vitamín D3/C a ďalšie mikroživiny.'],
  ['slug'=>'imunita','title'=>'Imunita','desc'=>'Komplexy pre obranyschopnosť, D3, C, zinok, probiotiká.'],
  ['slug'=>'sila','title'=>'Sila a výkon','desc'=>'Kreatín, pre-workout, regenerácia a doplnky pre výkon.'],
  ['slug'=>'klby-koza','title'=>'Kĺby & koža','desc'=>'Kolagén, kĺbové výživy a ako ich vybrať.'],
];
?>
<section class="container">
  <article class="card">
    <h1>Kategórie</h1>
    <div class="promo-cards" style="margin:0;">
      <?php foreach ($cats as $c): ?>
        <a class="card" href="/kategorie/<?= esc($c['slug']) ?>" style="text-decoration:none;">
          <img src="<?= asset('img/og-default.jpg') ?>" alt="<?= esc($c['title']) ?>" loading="lazy" width="600" height="400">
          <div class="card-body">
            <h3><?= esc($c['title']) ?></h3>
            <p><?= esc($c['desc']) ?></p>
            <span class="card-link">Zobraziť kategóriu</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </article>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>
