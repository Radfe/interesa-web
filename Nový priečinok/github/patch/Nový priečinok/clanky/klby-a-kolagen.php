<?php
declare(strict_types=1);
/**
 * /clanky/klby-a-kolagen.php – generované copy‑paste
 * Štruktúra: H1, cover, perex, CTA, obsah, FAQ.
 */
$slug = 'klby-a-kolagen';
$page = [
  'title'       => 'Kĺby A Kolagén',
  'description' => 'Typ I (koža, šľachy), typ II (kĺby). Najbežnejší je hydrolyzovaný mix.',
  'faq'         => [{{"q": "Hydrolyzovaný kolagén alebo želatína?", "a": "Hydrolyzovaný kolagén sa lepšie rozpúšťa; oba sú zdrojom kolagénnych peptidov."}}, {{"q": "Koľko mg denne?", "a": "Zvyčajne 5–10 g denne; pri kĺboch často 10 g a viac."}}, {{"q": "Pomáha pridať vitamín C?", "a": "Áno, podporuje endogénnu syntézu kolagénu."}}]
];
require __DIR__ . '/../inc/head.php';
require_once __DIR__ . '/../inc/metrics.php';
require_once __DIR__ . '/../inc/components/cta_button.php';
interessa_count_view($slug);
?>
<div class="container article-layout">
  <div class="content">
    <article class="article">
      <h1>Kĺby A Kolagén</h1>

      <figure class="cover">
        <picture>
          <source type="image/webp" srcset="/assets/img/articles/klby-a-kolagen-1600.webp 1600w, /assets/img/articles/klby-a-kolagen-1200.webp 1200w, /assets/img/articles/klby-a-kolagen-800.webp 800w, /assets/img/articles/klby-a-kolagen-600.webp 600w" sizes="(min-width: 800px) 800px, 100vw">
          <img src="/assets/img/articles/klby-a-kolagen-1200.webp" alt="Kĺby A Kolagén" width="1200" height="675" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/assets/img/og-default.jpg'">
        </picture>
        <figcaption>Typ I (koža, šľachy), typ II (kĺby). Najbežnejší je hydrolyzovaný mix.</figcaption>
      </figure>

      <p class="perex">Typ I (koža, šľachy), typ II (kĺby). Najbežnejší je hydrolyzovaný mix.</p>

      <div class="cta-grid">
        <?= cta_button('klby-a-kolagen-gymbeam', 'Pozrieť v GymBeam') ?>
        <?= cta_button('klby-a-kolagen-aktin', 'Pozrieť v Aktin') ?>
        <?= cta_button('klby-a-kolagen-myprotein', 'Pozrieť v MyProtein') ?>
        <?= cta_button('klby-a-kolagen-proteinsk', 'Pozrieť v Protein.sk') ?>
        <?= cta_button('klby-a-kolagen-biotechusa', 'Pozrieť v BioTechUSA') ?>
      </div>

      <div class="article-body">
        <?php
        $contentFile = __DIR__ . '/../content/articles/klby-a-kolagen.html';
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
