<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';

$page_title = 'Clanky | Interesa';
$page_description = 'Prehlad clankov, porovnani a navodov o proteinoch, vyzive, vitaminoch, kreatine a dalsich temach s nakupnym zamerom.';
$page_type = 'CollectionPage';

include __DIR__ . '/../inc/head.php';

$items = [];
foreach (article_registry() as $slug => $meta) {
    $category = category_meta($meta[2] ?? '');
    $media = article_media($slug);
    $items[] = [
        'slug' => $slug,
        'title' => $meta[0] ?? humanize_slug($slug),
        'description' => $meta[1] ?? '',
        'url' => article_url($slug),
        'image' => $media['card_image'],
        'image_alt' => $media['card_alt'],
        'category_title' => $category['title'] ?? 'Clanok',
        'category_url' => $category ? category_url($category['slug']) : '/kategorie/',
    ];
}

usort($items, static fn(array $a, array $b): int => strcmp($a['title'], $b['title']));
?>
<section class="container content-stack">
  <div class="section-heading">
    <div>
      <span class="eyebrow">Obsahovy archiv</span>
      <h1>Clanky a porovnania</h1>
      <p class="section-intro">Budujeme kniznicu clankov, ktore odpovedaju na realne otazky pouzivatelov a zaroven podporuju affiliate navstevnost z vyhladavacov.</p>
    </div>
    <a class="btn btn-ghost" href="/search">Vyhladavat v clankoch</a>
  </div>

  <?php if (!$items): ?>
    <article class="card legal-card">
      <h2>Zatial tu nie su ziadne clanky</h2>
      <p>Najprv doplnime zakladne money temy a potom rozsirime obsah o long-tail otazky.</p>
    </article>
  <?php else: ?>
    <div class="grid-cards article-card-grid">
      <?php foreach ($items as $item): ?>
        <article class="post-card">
          <a href="<?= esc($item['url']) ?>">
            <img class="thumb" src="<?= esc($item['image']) ?>" alt="<?= esc($item['image_alt']) ?>" loading="lazy" decoding="async">
          </a>
          <div class="post-card-body">
            <a class="chip" href="<?= esc($item['category_url']) ?>"><?= esc($item['category_title']) ?></a>
            <h3><a href="<?= esc($item['url']) ?>"><?= esc($item['title']) ?></a></h3>
            <p><?= esc($item['description']) ?></p>
            <a class="btn btn-ghost" href="<?= esc($item['url']) ?>">Citat clanok</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/../inc/footer.php'; ?>
