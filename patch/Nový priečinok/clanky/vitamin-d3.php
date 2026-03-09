<?php
declare(strict_types=1);
/**
 * /clanky/vitamin-d3.php – generované copy‑paste
 * Štruktúra: H1, cover, perex, CTA, obsah, FAQ.
 */
$slug = 'vitamin-d3';
$page = [
  'title'       => 'Vitamín D3',
  'description' => 'Bežne 1000–2000 IU denne; pri deficite podľa lekára. Praktická je kombinácia s K2 (MK-7).',
  'faq'         => [{{"q": "Akú formu je dobré zvoliť?", "a": "Preferuj dobre vstrebateľné formy (citráty, bisglycinát, cholekalciferol pri D3)."}}, {{"q": "Kedy dopĺňať – s jedlom alebo nalačno?", "a": "Väčšinu vitamínov a minerálov je vhodné brať s jedlom, hlavne tie rozpustné v tukoch."}}, {{"q": "Dá sa to brať dlhodobo?", "a": "Pri bežných dávkach áno, sleduj odporúčania a neriskuj nadmerné dávkovanie."}}]
];
require __DIR__ . '/../inc/head.php';
require_once __DIR__ . '/../inc/metrics.php';
require_once __DIR__ . '/../inc/components/cta_button.php';
interessa_count_view($slug);
?>
<div class="container article-layout">
  <div class="content">
    <article class="article">
      <h1>Vitamín D3</h1>

      <figure class="cover">
        <picture>
          <source type="image/webp" srcset="/assets/img/articles/vitamin-d3-1600.webp 1600w, /assets/img/articles/vitamin-d3-1200.webp 1200w, /assets/img/articles/vitamin-d3-800.webp 800w, /assets/img/articles/vitamin-d3-600.webp 600w" sizes="(min-width: 800px) 800px, 100vw">
          <img src="/assets/img/articles/vitamin-d3-1200.webp" alt="Vitamín D3" width="1200" height="675" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/assets/img/og-default.jpg'">
        </picture>
        <figcaption>Bežne 1000–2000 IU denne; pri deficite podľa lekára. Praktická je kombinácia s K2 (MK-7).</figcaption>
      </figure>

      <p class="perex">Bežne 1000–2000 IU denne; pri deficite podľa lekára. Praktická je kombinácia s K2 (MK-7).</p>

      <div class="cta-grid">
        <?= cta_button('vitamin-d3-gymbeam', 'Pozrieť v GymBeam') ?>
        <?= cta_button('vitamin-d3-aktin', 'Pozrieť v Aktin') ?>
        <?= cta_button('vitamin-d3-myprotein', 'Pozrieť v MyProtein') ?>
        <?= cta_button('vitamin-d3-proteinsk', 'Pozrieť v Protein.sk') ?>
        <?= cta_button('vitamin-d3-biotechusa', 'Pozrieť v BioTechUSA') ?>
      </div>

      <div class="article-body">
        <?php
        $contentFile = __DIR__ . '/../content/articles/vitamin-d3.html';
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
