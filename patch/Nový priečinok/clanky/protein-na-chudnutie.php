<?php
declare(strict_types=1);
/**
 * /clanky/protein-na-chudnutie.php – generované copy‑paste
 * Štruktúra: H1, cover, perex, CTA, obsah, FAQ.
 */
$slug = 'protein-na-chudnutie';
$page = [
  'title'       => 'Proteín Na Chudnutie',
  'description' => 'Pri redukcii hmotnosti sleduj kalórie na porciu , podiel bielkovín a minimum pridaného cukru/tuku. Skvelá je srvátka (WPI) alebo „clear“ proteín, ak ti nevyhovuje mliečna chuť.',
  'faq'         => [{{"q": "Koľko proteínu denne potrebujem?", "a": "Väčšine aktívnych ľudí vyhovuje 1,6–2,2 g bielkovín na kg hmotnosti denne."}}, {{"q": "Je WPI lepší na chudnutie ako WPC?", "a": "WPI má menej laktózy a tukov. Rozdiely v chudnutí sú malé – dôležitý je kalorický deficit."}}, {{"q": "Čo je „clear“ proteín?", "a": "Srvátkový izolát upravený tak, že chutí a správa sa ako limonáda – je ľahší na žalúdok."}}]
];
require __DIR__ . '/../inc/head.php';
require_once __DIR__ . '/../inc/metrics.php';
require_once __DIR__ . '/../inc/components/cta_button.php';
interessa_count_view($slug);
?>
<div class="container article-layout">
  <div class="content">
    <article class="article">
      <h1>Proteín Na Chudnutie</h1>

      <figure class="cover">
        <picture>
          <source type="image/webp" srcset="/assets/img/articles/protein-na-chudnutie-1600.webp 1600w, /assets/img/articles/protein-na-chudnutie-1200.webp 1200w, /assets/img/articles/protein-na-chudnutie-800.webp 800w, /assets/img/articles/protein-na-chudnutie-600.webp 600w" sizes="(min-width: 800px) 800px, 100vw">
          <img src="/assets/img/articles/protein-na-chudnutie-1200.webp" alt="Proteín Na Chudnutie" width="1200" height="675" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/assets/img/og-default.jpg'">
        </picture>
        <figcaption>Pri redukcii hmotnosti sleduj kalórie na porciu , podiel bielkovín a minimum pridaného cukru/tuku. Skvelá je srvátka (WPI) alebo „clear“ proteín, ak ti nevyhovuje mliečna chuť.</figcaption>
      </figure>

      <p class="perex">Pri redukcii hmotnosti sleduj kalórie na porciu , podiel bielkovín a minimum pridaného cukru/tuku. Skvelá je srvátka (WPI) alebo „clear“ proteín, ak ti nevyhovuje mliečna chuť.</p>

      <div class="cta-grid">
        <?= cta_button('protein-na-chudnutie-gymbeam', 'Pozrieť v GymBeam') ?>
        <?= cta_button('protein-na-chudnutie-aktin', 'Pozrieť v Aktin') ?>
        <?= cta_button('protein-na-chudnutie-myprotein', 'Pozrieť v MyProtein') ?>
        <?= cta_button('protein-na-chudnutie-proteinsk', 'Pozrieť v Protein.sk') ?>
        <?= cta_button('protein-na-chudnutie-biotechusa', 'Pozrieť v BioTechUSA') ?>
      </div>

      <div class="article-body">
        <?php
        $contentFile = __DIR__ . '/../content/articles/protein-na-chudnutie.html';
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
