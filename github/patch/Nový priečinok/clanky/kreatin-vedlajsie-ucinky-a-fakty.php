<?php
declare(strict_types=1);
/**
 * /clanky/kreatin-vedlajsie-ucinky-a-fakty.php – generované copy‑paste
 * Štruktúra: H1, cover, perex, CTA, obsah, FAQ.
 */
$slug = 'kreatin-vedlajsie-ucinky-a-fakty';
$page = [
  'title'       => 'Kreatin Vedlajsie Ucinky A Fakty',
  'description' => 'Monohydrát kreatínu je jedným z najlepšie preskúmaných doplnkov. Pri odporúčaných dávkach je bezpečný pre zdravých ľudí.',
  'faq'         => [{{"q": "Kedy brať kreatín – pred alebo po tréningu?", "a": "Najdôležitejšia je pravidelnosť. Na načasovaní až tak nezáleží, zvoľ si čas, ktorý vieš dlhodobo dodržať."}}, {{"q": "Koľko kreatínu denne?", "a": "Bežná udržiavacia dávka je 3–5 g denne. Nasycovacia fáza nie je nutná."}}, {{"q": "Je monohydrát lepší než HCL?", "a": "Väčšina dôkazov favorizuje monohydrát – je overený, cenovo dostupný a účinný."}}]
];
require __DIR__ . '/../inc/head.php';
require_once __DIR__ . '/../inc/metrics.php';
require_once __DIR__ . '/../inc/components/cta_button.php';
interessa_count_view($slug);
?>
<div class="container article-layout">
  <div class="content">
    <article class="article">
      <h1>Kreatin Vedlajsie Ucinky A Fakty</h1>

      <figure class="cover">
        <picture>
          <source type="image/webp" srcset="/assets/img/articles/kreatin-vedlajsie-ucinky-a-fakty-1600.webp 1600w, /assets/img/articles/kreatin-vedlajsie-ucinky-a-fakty-1200.webp 1200w, /assets/img/articles/kreatin-vedlajsie-ucinky-a-fakty-800.webp 800w, /assets/img/articles/kreatin-vedlajsie-ucinky-a-fakty-600.webp 600w" sizes="(min-width: 800px) 800px, 100vw">
          <img src="/assets/img/articles/kreatin-vedlajsie-ucinky-a-fakty-1200.webp" alt="Kreatin Vedlajsie Ucinky A Fakty" width="1200" height="675" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/assets/img/og-default.jpg'">
        </picture>
        <figcaption>Monohydrát kreatínu je jedným z najlepšie preskúmaných doplnkov. Pri odporúčaných dávkach je bezpečný pre zdravých ľudí.</figcaption>
      </figure>

      <p class="perex">Monohydrát kreatínu je jedným z najlepšie preskúmaných doplnkov. Pri odporúčaných dávkach je bezpečný pre zdravých ľudí.</p>

      <div class="cta-grid">
        <?= cta_button('kreatin-vedlajsie-ucinky-a-fakty-gymbeam', 'Pozrieť v GymBeam') ?>
        <?= cta_button('kreatin-vedlajsie-ucinky-a-fakty-aktin', 'Pozrieť v Aktin') ?>
        <?= cta_button('kreatin-vedlajsie-ucinky-a-fakty-myprotein', 'Pozrieť v MyProtein') ?>
        <?= cta_button('kreatin-vedlajsie-ucinky-a-fakty-proteinsk', 'Pozrieť v Protein.sk') ?>
        <?= cta_button('kreatin-vedlajsie-ucinky-a-fakty-biotechusa', 'Pozrieť v BioTechUSA') ?>
      </div>

      <div class="article-body">
        <?php
        $contentFile = __DIR__ . '/../content/articles/kreatin-vedlajsie-ucinky-a-fakty.html';
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
