<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';

$page_title = 'Kategorie | Interesa';
$page_description = 'Tematicke huby pre proteiny, zdravu vyzivu, vitaminy, mineraly, imunitu, vykon a dalsie oblasti s vysokym SEO potencialom.';
$page_type = 'CollectionPage';

include __DIR__ . '/../inc/head.php';

$preferredOrder = ['proteiny', 'vyziva', 'mineraly', 'imunita', 'sila', 'klby-koza', 'kreatin', 'pre-workout', 'aminokyseliny', 'probiotika-travenie', 'chudnutie'];
$cats = category_registry();
$items = [];
foreach ($preferredOrder as $slug) {
    if (!isset($cats[$slug])) {
        continue;
    }

    $items[] = [
        'slug' => $slug,
        'title' => $cats[$slug]['title'],
        'description' => $cats[$slug]['description'],
        'url' => category_url($slug),
        'icon' => category_icon($slug),
        'visual' => category_visual($slug),
    ];
}
?>
<section class="container content-stack">
  <div class="section-heading">
    <div>
      <span class="eyebrow">Tematicke huby</span>
      <h1>Kategorie, ktore budu tahat rast webu</h1>
      <p class="section-intro">Kazda kategoria funguje ako obsahovy hub. Z nej sa bude vetvit seria SEO clankov, porovnani a nakupnych tem s vysokym potencialom navstevnosti.</p>
    </div>
  </div>

  <div class="hub-grid">
    <?php foreach ($items as $item): ?>
      <article class="hub-card category-hub-card">
        <div class="hub-media">
          <img class="hub-visual" src="<?= esc($item['visual']) ?>" alt="<?= esc($item['title']) ?>" width="1200" height="800" loading="lazy" decoding="async">
          <div class="hub-icon-wrap">
            <img src="<?= esc($item['icon']) ?>" alt="<?= esc($item['title']) ?>" width="48" height="48">
          </div>
        </div>
        <h2><?= esc($item['title']) ?></h2>
        <p><?= esc($item['description']) ?></p>
        <a class="btn btn-primary" href="<?= esc($item['url']) ?>">Otvorit kategoriu</a>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php include __DIR__ . '/../inc/footer.php'; ?>
