<?php
declare(strict_types=1);
/**
 * /clanky/probiotika-ako-vybrat.php – generované copy‑paste
 * Štruktúra: H1, cover, perex, CTA, obsah, FAQ.
 */
$slug = 'probiotika-ako-vybrat';
$page = [
  'title'       => 'Probiotiká Ako Vybrat',
  'description' => 'Probiotiká – na čo pozerať Konkrétne kmene s klinickými štúdiami (napr. Lactobacillus rhamnosus GG). Dávkovanie CFU a skladovanie (chladničné vs. stabilné formy).',
  'faq'         => [{{"q": "Koľko CFU hľadať na etikete?", "a": "Bežne 1–10 miliárd CFU denne; dôležitejšie sú overené kmene a pravidelné užívanie."}}, {{"q": "Mám probiotiká brať s jedlom?", "a": "Áno, väčšinou spolu s jedlom alebo krátko po ňom."}}, {{"q": "Ako dlho užívať?", "a": "Ideálne aspoň 4–8 týždňov a následne vyhodnotiť účinok."}}]
];
require __DIR__ . '/../inc/head.php';
require_once __DIR__ . '/../inc/metrics.php';
require_once __DIR__ . '/../inc/components/cta_button.php';
interessa_count_view($slug);
?>
<div class="container article-layout">
  <div class="content">
    <article class="article">
      <h1>Probiotiká Ako Vybrat</h1>

      <figure class="cover">
        <picture>
          <source type="image/webp" srcset="/assets/img/articles/probiotika-ako-vybrat-1600.webp 1600w, /assets/img/articles/probiotika-ako-vybrat-1200.webp 1200w, /assets/img/articles/probiotika-ako-vybrat-800.webp 800w, /assets/img/articles/probiotika-ako-vybrat-600.webp 600w" sizes="(min-width: 800px) 800px, 100vw">
          <img src="/assets/img/articles/probiotika-ako-vybrat-1200.webp" alt="Probiotiká Ako Vybrat" width="1200" height="675" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/assets/img/og-default.jpg'">
        </picture>
        <figcaption>Probiotiká – na čo pozerať Konkrétne kmene s klinickými štúdiami (napr. Lactobacillus rhamnosus GG). Dávkovanie CFU a skladovanie (chladničné vs. stabilné formy).</figcaption>
      </figure>

      <p class="perex">Probiotiká – na čo pozerať Konkrétne kmene s klinickými štúdiami (napr. Lactobacillus rhamnosus GG). Dávkovanie CFU a skladovanie (chladničné vs. stabilné formy).</p>

      <div class="cta-grid">
        <?= cta_button('probiotika-ako-vybrat-gymbeam', 'Pozrieť v GymBeam') ?>
        <?= cta_button('probiotika-ako-vybrat-aktin', 'Pozrieť v Aktin') ?>
        <?= cta_button('probiotika-ako-vybrat-myprotein', 'Pozrieť v MyProtein') ?>
        <?= cta_button('probiotika-ako-vybrat-proteinsk', 'Pozrieť v Protein.sk') ?>
        <?= cta_button('probiotika-ako-vybrat-biotechusa', 'Pozrieť v BioTechUSA') ?>
      </div>

      <div class="article-body">
        <?php
        $contentFile = __DIR__ . '/../content/articles/probiotika-ako-vybrat.html';
        if (is_file($contentFile)) {
          readfile($contentFile);
        } else {
          echo '<p>Obsah sa pripravuje.</p>';
        }
        ?>
      </div>

      <?php if (!empty($page['faq'])): ?>
      <section id="faq" class="faq">
        <h2>Často kladené otázky</h2>
        <dl>
          <?php foreach ($page['faq'] as $qa): ?>
            <dt><?= esc($qa['q']) ?></dt>
            <dd><?= esc($qa['a']) ?></dd>
          <?php endforeach; ?>
        </dl>
      </section>
      <?php endif; ?>
    </article>
  </div>

  <div class="sidebar-col">
    <?php include __DIR__ . '/../inc/sidebar.php'; ?>
  </div>
</div>
<?php include __DIR__ . '/../inc/footer.php'; ?>
