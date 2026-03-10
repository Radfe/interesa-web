<?php
declare(strict_types=1);
/**
 * /clanky/kolagen-na-klby-porovnanie.php – generované copy‑paste
 * Štruktúra: H1, cover, perex, CTA, obsah, FAQ.
 */
$slug = 'kolagen-na-klby-porovnanie';
$page = [
  'title'       => 'Kolagén Na Kĺby Porovnanie',
  'description' => 'Hydrolyzovaný kolagén (peptidy) má lepšiu vstrebateľnosť. Na kĺby sa často využíva typ II , pre pleť skôr typ I .',
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
      <h1>Kolagén Na Kĺby Porovnanie</h1>

      <figure class="cover">
        <picture>
          <source type="image/webp" srcset="/assets/img/articles/kolagen-na-klby-porovnanie-1600.webp 1600w, /assets/img/articles/kolagen-na-klby-porovnanie-1200.webp 1200w, /assets/img/articles/kolagen-na-klby-porovnanie-800.webp 800w, /assets/img/articles/kolagen-na-klby-porovnanie-600.webp 600w" sizes="(min-width: 800px) 800px, 100vw">
          <img src="/assets/img/articles/kolagen-na-klby-porovnanie-1200.webp" alt="Kolagén Na Kĺby Porovnanie" width="1200" height="675" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/assets/img/og-default.jpg'">
        </picture>
        <figcaption>Hydrolyzovaný kolagén (peptidy) má lepšiu vstrebateľnosť. Na kĺby sa často využíva typ II , pre pleť skôr typ I .</figcaption>
      </figure>

      <p class="perex">Hydrolyzovaný kolagén (peptidy) má lepšiu vstrebateľnosť. Na kĺby sa často využíva typ II , pre pleť skôr typ I .</p>

      <div class="cta-grid">
        <?= cta_button('kolagen-na-klby-porovnanie-gymbeam', 'Pozrieť v GymBeam') ?>
        <?= cta_button('kolagen-na-klby-porovnanie-aktin', 'Pozrieť v Aktin') ?>
        <?= cta_button('kolagen-na-klby-porovnanie-myprotein', 'Pozrieť v MyProtein') ?>
        <?= cta_button('kolagen-na-klby-porovnanie-proteinsk', 'Pozrieť v Protein.sk') ?>
        <?= cta_button('kolagen-na-klby-porovnanie-biotechusa', 'Pozrieť v BioTechUSA') ?>
      </div>

      <div class="article-body">
        <?php
        $contentFile = __DIR__ . '/../content/articles/kolagen-na-klby-porovnanie.html';
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
