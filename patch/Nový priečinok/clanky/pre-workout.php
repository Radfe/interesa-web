<?php
declare(strict_types=1);
/**
 * /clanky/pre-workout.php – generované copy‑paste
 * Štruktúra: H1, cover, perex, CTA, obsah, FAQ.
 */
$slug = 'pre-workout';
$page = [
  'title'       => 'Pre workout',
  'description' => 'Sleduj denný kofeín; vyhni sa „zmesiam bez dávok“.',
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
      <h1>Pre workout</h1>

      <figure class="cover">
        <picture>
          <source type="image/webp" srcset="/assets/img/articles/pre-workout-1600.webp 1600w, /assets/img/articles/pre-workout-1200.webp 1200w, /assets/img/articles/pre-workout-800.webp 800w, /assets/img/articles/pre-workout-600.webp 600w" sizes="(min-width: 800px) 800px, 100vw">
          <img src="/assets/img/articles/pre-workout-1200.webp" alt="Pre workout" width="1200" height="675" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/assets/img/og-default.jpg'">
        </picture>
        <figcaption>Sleduj denný kofeín; vyhni sa „zmesiam bez dávok“.</figcaption>
      </figure>

      <p class="perex">Sleduj denný kofeín; vyhni sa „zmesiam bez dávok“.</p>

      <div class="cta-grid">
        <?= cta_button('pre-workout-gymbeam', 'Pozrieť v GymBeam') ?>
        <?= cta_button('pre-workout-aktin', 'Pozrieť v Aktin') ?>
        <?= cta_button('pre-workout-myprotein', 'Pozrieť v MyProtein') ?>
        <?= cta_button('pre-workout-proteinsk', 'Pozrieť v Protein.sk') ?>
        <?= cta_button('pre-workout-biotechusa', 'Pozrieť v BioTechUSA') ?>
      </div>

      <div class="article-body">
        <?php
        $contentFile = __DIR__ . '/../content/articles/pre-workout.html';
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
