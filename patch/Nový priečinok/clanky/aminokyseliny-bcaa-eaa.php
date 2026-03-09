<?php
declare(strict_types=1);
/**
 * /clanky/aminokyseliny-bcaa-eaa.php – generované copy‑paste
 * Štruktúra: H1, cover, perex, CTA, obsah, FAQ.
 */
$slug = 'aminokyseliny-bcaa-eaa';
$page = [
  'title'       => 'Aminokyseliny BCAA EAA',
  'description' => 'EAA majú širší význam – obsahujú všetky esenciálne AK. BCAA samé zmysel obvykle nemajú, ak máš dosť bielkovín.',
  'faq'         => [{{"q": "EAA vs. BCAA – čo je lepšie?", "a": "EAA sú kompletné esenciálne aminokyseliny; BCAA sú len tri z nich."}}, {{"q": "Má zmysel mimo tréningu?", "a": "Skôr nie – vhodnejšie je doplniť kvalitné bielkoviny v strave."}}, {{"q": "Koľko mg leucínu?", "a": "Prahová dávka je približne 2–3 g leucínu na stimuláciu MPS."}}]
];
require __DIR__ . '/../inc/head.php';
require_once __DIR__ . '/../inc/metrics.php';
require_once __DIR__ . '/../inc/components/cta_button.php';
interessa_count_view($slug);
?>
<div class="container article-layout">
  <div class="content">
    <article class="article">
      <h1>Aminokyseliny BCAA EAA</h1>

      <figure class="cover">
        <picture>
          <source type="image/webp" srcset="/assets/img/articles/aminokyseliny-bcaa-eaa-1600.webp 1600w, /assets/img/articles/aminokyseliny-bcaa-eaa-1200.webp 1200w, /assets/img/articles/aminokyseliny-bcaa-eaa-800.webp 800w, /assets/img/articles/aminokyseliny-bcaa-eaa-600.webp 600w" sizes="(min-width: 800px) 800px, 100vw">
          <img src="/assets/img/articles/aminokyseliny-bcaa-eaa-1200.webp" alt="Aminokyseliny BCAA EAA" width="1200" height="675" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/assets/img/og-default.jpg'">
        </picture>
        <figcaption>EAA majú širší význam – obsahujú všetky esenciálne AK. BCAA samé zmysel obvykle nemajú, ak máš dosť bielkovín.</figcaption>
      </figure>

      <p class="perex">EAA majú širší význam – obsahujú všetky esenciálne AK. BCAA samé zmysel obvykle nemajú, ak máš dosť bielkovín.</p>

      <div class="cta-grid">
        <?= cta_button('aminokyseliny-bcaa-eaa-gymbeam', 'Pozrieť v GymBeam') ?>
        <?= cta_button('aminokyseliny-bcaa-eaa-aktin', 'Pozrieť v Aktin') ?>
        <?= cta_button('aminokyseliny-bcaa-eaa-myprotein', 'Pozrieť v MyProtein') ?>
        <?= cta_button('aminokyseliny-bcaa-eaa-proteinsk', 'Pozrieť v Protein.sk') ?>
        <?= cta_button('aminokyseliny-bcaa-eaa-biotechusa', 'Pozrieť v BioTechUSA') ?>
      </div>

      <div class="article-body">
        <?php
        $contentFile = __DIR__ . '/../content/articles/aminokyseliny-bcaa-eaa.html';
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
