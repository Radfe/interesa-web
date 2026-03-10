<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';

$page_title = 'Články a porovnania | Interesa';
$page_description = 'Archív článkov a porovnaní o proteínoch, kreatíne, mineráloch, imunite, chudnutí a ďalších témach, ktoré ľudia reálne vyhľadávajú.';
$page_type = 'CollectionPage';
include __DIR__ . '/../inc/head.php';

$items = latest_article_items(200);
$featuredStarts = [
    ['slug' => 'najlepsie-proteiny-2025', 'label' => 'Začni proteínmi', 'summary' => 'Najsilnejší vstupný článok pre výber podľa cieľa.'],
    ['slug' => 'kreatin-porovnanie', 'label' => 'Začni kreatínom', 'summary' => 'Formy, dávkovanie a praktické otázky na jednom mieste.'],
    ['slug' => 'horcik', 'label' => 'Začni minerálmi', 'summary' => 'Vstupný prehľad horčíka, foriem a súvisiacich evergreen tém.'],
    ['slug' => 'chudnutie-tip', 'label' => 'Začni redukciou', 'summary' => 'Jednoduchý základ pre chudnutie bez extrémov a chaosu.'],
];
?>
<section class="container content-stack">
  <nav class="breadcrumbs" aria-label="Breadcrumb">
    <a href="/">Domov</a>
    <span aria-hidden="true">/</span>
    <span>Články</span>
  </nav>

  <div class="section-heading">
    <div>
      <span class="eyebrow">Obsahový archív</span>
      <h1>Články, porovnania a vstupné témy</h1>
      <p class="section-intro">Archív už neslúži len ako zoznam článkov. Nájdeš tu aj najlepšie vstupné body do hlavných clusterov, cez ktoré sa dá rýchlo prejsť od všeobecnej otázky ku konkrétnemu výberu.</p>
    </div>
    <a class="btn btn-ghost" href="/search">Vyhľadávať v článkoch</a>
  </div>

  <div class="hub-grid">
    <?php foreach ($featuredStarts as $start): $meta = article_meta($start['slug']); ?>
      <article class="hub-card">
        <span class="hub-label"><?= esc($start['label']) ?></span>
        <h3><?= esc($meta['title']) ?></h3>
        <p><?= esc($start['summary']) ?></p>
        <div class="hub-actions">
          <a class="btn btn-primary" href="<?= esc(article_url($start['slug'])) ?>">Otvoriť článok</a>
          <a class="btn btn-ghost" href="<?= esc(category_url($meta['category'])) ?>">Súvisiaca kategória</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <?php if (!$items): ?>
    <article class="card legal-card">
      <h2>Zatiaľ tu nie sú žiadne články</h2>
      <p>Najprv doplníme základné témy a potom rozšírime obsah o ďalšie praktické otázky.</p>
    </article>
  <?php else: ?>
    <div class="section-heading section-heading-tight">
      <div>
        <span class="eyebrow">Všetky články</span>
        <h2>Celý archív podľa najnovších úprav</h2>
        <p class="section-intro">Pod touto sekciou nájdeš kompletný zoznam článkov vrátane čerstvo dopracovaných evergreen tém.</p>
      </div>
    </div>

    <div class="grid-cards article-card-grid">
      <?php foreach ($items as $item): ?>
        <article class="post-card">
          <a href="<?= esc($item['url']) ?>">
            <img class="thumb" src="<?= esc($item['image']) ?>" alt="<?= esc($item['title']) ?>" loading="lazy" decoding="async">
          </a>
          <div class="post-card-body">
            <a class="chip" href="<?= esc($item['category_url']) ?>"><?= esc($item['category_title']) ?></a>
            <h3><a href="<?= esc($item['url']) ?>"><?= esc($item['title']) ?></a></h3>
            <?php if ($item['description'] !== ''): ?><p><?= esc($item['description']) ?></p><?php endif; ?>
            <a class="btn btn-ghost" href="<?= esc($item['url']) ?>">Čítať článok</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/../inc/footer.php'; ?>
