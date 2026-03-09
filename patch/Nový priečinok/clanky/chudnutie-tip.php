<?php
declare(strict_types=1);
/**
 * /clanky/chudnutie-tip.php – generované copy‑paste
 * Štruktúra: H1, cover, perex, CTA, obsah, FAQ.
 */
$slug = 'chudnutie-tip';
$page = [
  'title'       => 'Chudnutie Tip',
  'description' => 'Základ je kalorický deficit, bielkoviny 1.6–2.2 g/kg a silový tréning. Doplnky (proteín, vláknina, kofeín) sú len pomocníci.',
  'faq'         => [{{"q": "Ako si vybrať kvalitný produkt?", "a": "Pozri sa na zloženie, dávkovanie a cenu za účinnú látku."}}, {{"q": "Kde hľadať hodnotenia?", "a": "Skontroluj nezávislé recenzie a skúsenosti používateľov."}}, {{"q": "Môžem kombinovať s inými doplnkami?", "a": "Vo väčšine prípadov áno, ale sleduj celkové dávky a možné interakcie."}}]
];
require __DIR__ . '/../inc/head.php';
require_once __DIR__ . '/../inc/metrics.php';
require_once __DIR__ . '/../inc/components/cta_button.php';
interessa_count_view($slug);
?>
<div class="container article-layout">
  <div class="content">
    <article class="article">
      <h1>Chudnutie Tip</h1>

      <figure class="cover">
        <picture>
          <source type="image/webp" srcset="/assets/img/articles/chudnutie-tip-1600.webp 1600w, /assets/img/articles/chudnutie-tip-1200.webp 1200w, /assets/img/articles/chudnutie-tip-800.webp 800w, /assets/img/articles/chudnutie-tip-600.webp 600w" sizes="(min-width: 800px) 800px, 100vw">
          <img src="/assets/img/articles/chudnutie-tip-1200.webp" alt="Chudnutie Tip" width="1200" height="675" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/assets/img/og-default.jpg'">
        </picture>
        <figcaption>Základ je kalorický deficit, bielkoviny 1.6–2.2 g/kg a silový tréning. Doplnky (proteín, vláknina, kofeín) sú len pomocníci.</figcaption>
      </figure>

      <p class="perex">Základ je kalorický deficit, bielkoviny 1.6–2.2 g/kg a silový tréning. Doplnky (proteín, vláknina, kofeín) sú len pomocníci.</p>

      <div class="cta-grid">
        <?= cta_button('chudnutie-tip-gymbeam', 'Pozrieť v GymBeam') ?>
        <?= cta_button('chudnutie-tip-aktin', 'Pozrieť v Aktin') ?>
        <?= cta_button('chudnutie-tip-myprotein', 'Pozrieť v MyProtein') ?>
        <?= cta_button('chudnutie-tip-proteinsk', 'Pozrieť v Protein.sk') ?>
        <?= cta_button('chudnutie-tip-biotechusa', 'Pozrieť v BioTechUSA') ?>
      </div>

      <div class="article-body">
        <?php
        $contentFile = __DIR__ . '/../content/articles/chudnutie-tip.html';
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
