<?php
declare(strict_types=1);
/**
 * /clanky/pre-workout-ako-vybrat.php – generované copy‑paste
 * Štruktúra: H1, cover, perex, CTA, obsah, FAQ.
 */
$slug = 'pre-workout-ako-vybrat';
$page = [
  'title'       => 'Pre workout Ako Vybrat',
  'description' => 'Predtréningovky – čo hľadať Kofeín: 150–300 mg podľa tolerancie. Citrulín malát: 6–8 g pre pumpu/výkon. Beta-alanín: 3.2–6.4 g denne (mravčenie je bežné).',
  'faq'         => [{{"q": "Koľko kofeínu je rozumné?", "a": "Väčšine ľudí stačí 150–250 mg. Vyššie dávky môžu zhoršiť spánok."}}, {{"q": "Čo má mať dobrý pre‑workout?", "a": "Citrulín malát (6–8 g), beta‑alanín (3.2 g), kreatín (3–5 g), kofeín podľa tolerancie."}}, {{"q": "Brať každý tréning?", "a": "Nie je nutné; šetriť na ťažšie tréningy."}}]
];
require __DIR__ . '/../inc/head.php';
require_once __DIR__ . '/../inc/metrics.php';
require_once __DIR__ . '/../inc/components/cta_button.php';
interessa_count_view($slug);
?>
<div class="container article-layout">
  <div class="content">
    <article class="article">
      <h1>Pre workout Ako Vybrat</h1>

      <figure class="cover">
        <picture>
          <source type="image/webp" srcset="/assets/img/articles/pre-workout-ako-vybrat-1600.webp 1600w, /assets/img/articles/pre-workout-ako-vybrat-1200.webp 1200w, /assets/img/articles/pre-workout-ako-vybrat-800.webp 800w, /assets/img/articles/pre-workout-ako-vybrat-600.webp 600w" sizes="(min-width: 800px) 800px, 100vw">
          <img src="/assets/img/articles/pre-workout-ako-vybrat-1200.webp" alt="Pre workout Ako Vybrat" width="1200" height="675" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/assets/img/og-default.jpg'">
        </picture>
        <figcaption>Predtréningovky – čo hľadať Kofeín: 150–300 mg podľa tolerancie. Citrulín malát: 6–8 g pre pumpu/výkon. Beta-alanín: 3.2–6.4 g denne (mravčenie je bežné).</figcaption>
      </figure>

      <p class="perex">Predtréningovky – čo hľadať Kofeín: 150–300 mg podľa tolerancie. Citrulín malát: 6–8 g pre pumpu/výkon. Beta-alanín: 3.2–6.4 g denne (mravčenie je bežné).</p>

      <div class="cta-grid">
        <?= cta_button('pre-workout-ako-vybrat-gymbeam', 'Pozrieť v GymBeam') ?>
        <?= cta_button('pre-workout-ako-vybrat-aktin', 'Pozrieť v Aktin') ?>
        <?= cta_button('pre-workout-ako-vybrat-myprotein', 'Pozrieť v MyProtein') ?>
        <?= cta_button('pre-workout-ako-vybrat-proteinsk', 'Pozrieť v Protein.sk') ?>
        <?= cta_button('pre-workout-ako-vybrat-biotechusa', 'Pozrieť v BioTechUSA') ?>
      </div>

      <div class="article-body">
        <?php
        $contentFile = __DIR__ . '/../content/articles/pre-workout-ako-vybrat.html';
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
