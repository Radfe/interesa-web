<?php
declare(strict_types=1);
/**
 * /clanky/probiotika-a-travenie.php – generované copy‑paste
 * Štruktúra: H1, cover, perex, CTA, obsah, FAQ.
 */
$slug = 'probiotika-a-travenie';
$page = [
  'title'       => 'Probiotiká A Trávenie',
  'description' => 'Sleduj konkrétne kmene (napr. L. rhamnosus GG ), CFU a kapsuly odolné voči kyseline.',
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
      <h1>Probiotiká A Trávenie</h1>

      <figure class="cover">
        <picture>
          <source type="image/webp" srcset="/assets/img/articles/probiotika-a-travenie-1600.webp 1600w, /assets/img/articles/probiotika-a-travenie-1200.webp 1200w, /assets/img/articles/probiotika-a-travenie-800.webp 800w, /assets/img/articles/probiotika-a-travenie-600.webp 600w" sizes="(min-width: 800px) 800px, 100vw">
          <img src="/assets/img/articles/probiotika-a-travenie-1200.webp" alt="Probiotiká A Trávenie" width="1200" height="675" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/assets/img/og-default.jpg'">
        </picture>
        <figcaption>Sleduj konkrétne kmene (napr. L. rhamnosus GG ), CFU a kapsuly odolné voči kyseline.</figcaption>
      </figure>

      <p class="perex">Sleduj konkrétne kmene (napr. L. rhamnosus GG ), CFU a kapsuly odolné voči kyseline.</p>

      <div class="cta-grid">
        <?= cta_button('probiotika-a-travenie-gymbeam', 'Pozrieť v GymBeam') ?>
        <?= cta_button('probiotika-a-travenie-aktin', 'Pozrieť v Aktin') ?>
        <?= cta_button('probiotika-a-travenie-myprotein', 'Pozrieť v MyProtein') ?>
        <?= cta_button('probiotika-a-travenie-proteinsk', 'Pozrieť v Protein.sk') ?>
        <?= cta_button('probiotika-a-travenie-biotechusa', 'Pozrieť v BioTechUSA') ?>
      </div>

      <div class="article-body">
        <?php
        $contentFile = __DIR__ . '/../content/articles/probiotika-a-travenie.html';
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
