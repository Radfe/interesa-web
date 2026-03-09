<?php
declare(strict_types=1);
/**
 * /clanky/spalovace-tukov-realita.php – generované copy‑paste
 * Štruktúra: H1, cover, perex, CTA, obsah, FAQ.
 */
$slug = 'spalovace-tukov-realita';
$page = [
  'title'       => 'Spaľovače tukov realita',
  'description' => 'Ide zväčša o mix stimulantov a látok s malým účinkom. Kľúčom je kalorický deficit , pohyb a spánok; spaľovač môže subjektívne pomôcť s energiou/chuťou do tréningu.',
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
      <h1>Spaľovače tukov realita</h1>

      <figure class="cover">
        <picture>
          <source type="image/webp" srcset="/assets/img/articles/spalovace-tukov-realita-1600.webp 1600w, /assets/img/articles/spalovace-tukov-realita-1200.webp 1200w, /assets/img/articles/spalovace-tukov-realita-800.webp 800w, /assets/img/articles/spalovace-tukov-realita-600.webp 600w" sizes="(min-width: 800px) 800px, 100vw">
          <img src="/assets/img/articles/spalovace-tukov-realita-1200.webp" alt="Spaľovače tukov realita" width="1200" height="675" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/assets/img/og-default.jpg'">
        </picture>
        <figcaption>Ide zväčša o mix stimulantov a látok s malým účinkom. Kľúčom je kalorický deficit , pohyb a spánok; spaľovač môže subjektívne pomôcť s energiou/chuťou do tréningu.</figcaption>
      </figure>

      <p class="perex">Ide zväčša o mix stimulantov a látok s malým účinkom. Kľúčom je kalorický deficit , pohyb a spánok; spaľovač môže subjektívne pomôcť s energiou/chuťou do tréningu.</p>

      <div class="cta-grid">
        <?= cta_button('spalovace-tukov-realita-gymbeam', 'Pozrieť v GymBeam') ?>
        <?= cta_button('spalovace-tukov-realita-aktin', 'Pozrieť v Aktin') ?>
        <?= cta_button('spalovace-tukov-realita-myprotein', 'Pozrieť v MyProtein') ?>
        <?= cta_button('spalovace-tukov-realita-proteinsk', 'Pozrieť v Protein.sk') ?>
        <?= cta_button('spalovace-tukov-realita-biotechusa', 'Pozrieť v BioTechUSA') ?>
      </div>

      <div class="article-body">
        <?php
        $contentFile = __DIR__ . '/../content/articles/spalovace-tukov-realita.html';
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
