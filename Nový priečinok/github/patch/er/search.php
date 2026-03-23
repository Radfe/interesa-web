<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/head.php';
require_once __DIR__ . '/inc/articles.php';
$q = trim((string)($_GET['q'] ?? ''));
$results = [];
if ($q !== '') {
  foreach (articles_all() as $slug=>$a) {
    $title = $a[0]; $perex = $a[1]; $score = 0;
    if (mb_stripos($title, $q, 0, 'UTF-8') !== false) $score += 3;
    if (mb_stripos($perex, $q, 0, 'UTF-8') !== false) $score += 1;
    $file = __DIR__ . '/content/articles/'.$slug.'.html';
    if (is_file($file)) {
      $txt = strip_tags((string)file_get_contents($file));
      if (mb_stripos($txt, $q, 0, 'UTF-8') !== false) $score += 2;
    }
    if ($score > 0) $results[] = ['slug'=>$slug,'title'=>$title,'perex'=>$perex,'score'=>$score];
  }
  usort($results, fn($a,$b)=> $b['score'] <=> $a['score']);
}
?>
<div class="wrap" style="max-width:1100px;margin:0 auto;padding:16px;">
  <h1>Hľadať</h1>
  <form action="/search.php" method="get" style="margin: 10px 0 20px; display:flex; gap:8px;">
    <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Zadaj hľadané slovo…" style="flex:1;padding:8px 10px;border:1px solid #cbd5e1;border-radius:8px;">
    <button type="submit" class="cta">Hľadať</button>
  </form>

  <?php if ($q===''): ?>
    <p>Zadaj kľúčové slovo a potvrď.</p>
  <?php else: ?>
    <p>Nájdené: <strong><?= count($results) ?></strong></p>
    <div class="card-list">
      <?php foreach ($results as $r): ?>
        <article class="card">
          <h3><a href="/clanky/<?= htmlspecialchars($r['slug']) ?>"><?= htmlspecialchars($r['title']) ?></a></h3>
          <p><?= htmlspecialchars($r['perex']) ?></p>
        </article>
      <?php endforeach; ?>
      <?php if (!count($results)): ?>
        <p>Nenašlo sa nič relevantné. Skús iné kľúčové slovo.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
