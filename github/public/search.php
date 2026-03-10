<?php
declare(strict_types=1);
$ROOT = __DIR__;
$ART = [];
@include $ROOT . '/inc/articles.php';
@include $ROOT . '/inc/articles_ext.php';

$q = trim((string)($_GET['q'] ?? ''));
$PAGE_TITLE = ($q ? "Hľadať: $q" : 'Hľadať články').' | Interesa.sk';
$PAGE_DESCRIPTION = 'Vyhľadávanie článkov na Interesa.sk';
$PAGE_CANONICAL = '/search.php'.($q ? '?q='.rawurlencode($q) : '');
require $ROOT . '/inc/head.php';

function match_article(string $slug, array $meta, string $q): ?array {
  $title = $meta[0] ?? ucwords(str_replace('-',' ',$slug));
  $perex = $meta[1] ?? '';
  $hay = mb_strtolower($title.' '.$perex);
  $needle = mb_strtolower($q);
  $score = 0;
  if ($q === '') return null;
  if (mb_strpos($hay, $needle) !== false) $score += 5;
  $path = __DIR__ . '/content/articles/'.$slug.'.html';
  if (is_file($path)) {
    $txt = mb_strtolower(strip_tags(file_get_contents($path)));
    if (mb_strpos($txt, $needle) !== false) $score += 3;
  }
  return $score > 0 ? [$score,$title,$perex,"/clanky/$slug.php"] : null;
}
?>
<h1>Hľadať</h1>
<?php if ($q === ''): ?>
  <p class="note">Zadaj výraz do vyhľadávania hore.</p>
<?php else: ?>
  <p class="note">Výsledky pre: <strong><?= htmlspecialchars($q, ENT_QUOTES) ?></strong></p>
  <div class="card-grid">
    <?php
    $hits = [];
    foreach ($ART as $slug=>$meta) {
      $m = match_article($slug,$meta,$q);
      if ($m) $hits[] = $m;
    }
    usort($hits, fn($a,$b)=>$b[0]<=>$a[0]);
    if (!$hits) echo '<p class="note">Nenašli sa žiadne články.</p>';
    foreach ($hits as $h): [$score,$title,$perex,$url] = $h; ?>
      <article class="card">
        <h3><a href="<?= $url ?>"><?= htmlspecialchars($title, ENT_QUOTES) ?></a></h3>
        <?php if ($perex): ?><div class="meta"><?= htmlspecialchars($perex, ENT_QUOTES) ?></div><?php endif; ?>
        <div class="actions"><a class="btn" href="<?= $url ?>">Čítať</a></div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require $ROOT . '/inc/footer.php';
