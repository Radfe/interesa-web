<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';

$page_title = 'Odkaz do e-shopu sa nenašiel';
$page_description = 'Je nám ľúto, ale affiliate kód neexistuje alebo nie je zatiaľ priradený.';

include __DIR__ . '/inc/head.php';
?>
<article class="container card" style="margin:24px auto;">
  <h1>Odkaz sa nenašiel</h1>
  <p>Affiliate kód <strong><?= esc($_GET['code'] ?? '') ?></strong> nemá priradený odkaz.</p>
  <p>Vráť sa prosím na <a href="/">domov</a> alebo vyber kategóriu v menu.</p>
</article>
<?php include __DIR__ . '/inc/footer.php'; ?>
