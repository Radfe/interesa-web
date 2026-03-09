<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';

$page_title = 'Články – Interesa';
$page_description = 'Prehľad článkov o proteínoch, výžive, vitamínoch a mineráloch. Porovnania, návody a tipy.';

include __DIR__ . '/../inc/head.php';

/** načítaj všetky HTML články z /content/articles */
$dir = __DIR__ . '/../content/articles';
$items = [];
if (is_dir($dir)) {
  $files = glob($dir . '/*.html') ?: [];
  usort($files, fn($a,$b)=> filemtime($b) <=> filemtime($a));
  foreach ($files as $f) {
    $slug = basename($f, '.html');
    $items[] = [
      'slug' => $slug,
      'title'=> humanize_slug($slug),
      'url'  => '/clanky/' . $slug
    ];
  }
}
?>
<section class="container two-col">
  <div class="content">
    <article class="card">
      <h1>Články</h1>
      <?php if (!$items): ?>
        <p>Ľutujeme, zatiaľ tu nie je žiaden článok.</p>
      <?php else: ?>
        <ul class="article-list">
          <?php foreach ($items as $a): ?>
            <li><a href="<?= esc($a['url']) ?>"><?= esc($a['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </article>
  </div>

  <aside class="sidebar" aria-label="Pravý panel">
    <div class="heureka-affiliate-search"
         data-trixam-positionid="67512"
         data-trixam-codetype="iframe"
         data-trixam-linktarget="blank"></div>

    <div class="heureka-affiliate-category"
         data-trixam-positionid="40746"
         data-trixam-categoryid="5526"
         data-trixam-codetype="iframe"
         data-trixam-linktarget="blank"></div>
  </aside>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>
