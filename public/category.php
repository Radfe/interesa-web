<?php declare(strict_types=1);
$ROOT = __DIR__;
require_once $ROOT . '/inc/functions.php';

$CATS = [
  'proteiny'   => ['Proteíny','🥤'],
  'vyziva'     => ['Výživa','🍎'],
  'mineraly'   => ['Minerály','🧪'],
  'imunita'    => ['Imunita','🛡️'],
  'sila'       => ['Sila','🏋️'],
  'klby-koža' => ['Kĺby & pokožka','🧍'],
];

$ART = [];
@include $ROOT . '/inc/articles.php';
@include $ROOT . '/inc/articles_ext.php';

$slug = preg_replace('~[^a-z0-9\-\_]+~i', '', (string)($_GET['slug'] ?? ''));
if (!isset($CATS[$slug])) { http_response_code(404); echo 'Not Found'; exit; }

[$catName] = $CATS[$slug];
$PAGE_TITLE = $catName . ' | Interesa.sk';
require $ROOT . '/inc/head.php';
?>
<h1><?= esc($catName) ?></h1>

<div class="grid">
<?php foreach($ART as $s=>$row): if (($row[2] ?? '') !== $slug) continue;
  $title = $row[0]; $perex = $row[1] ?? ''; $url = '/clanky/'.$s; ?>
  <article class="card">
    <a class="thumb" href="<?= $url ?>"><img src="/assets/img/placeholder-16x9.svg" data-src="<?= esc(article_img($s)) ?>" alt="<?= esc($title) ?>" loading="lazy"></a>
    <div class="inner">
      <h3><a href="<?= $url ?>"><?= esc($title) ?></a></h3>
      <?php if($perex): ?><div class="meta"><?= esc($perex) ?></div><?php endif; ?>
    </div>
    <div class="actions"><a class="btn" href="<?= $url ?>">Čítať</a></div>
  </article>
<?php endforeach; ?>
</div>

<?php require $ROOT . '/inc/footer.php';
