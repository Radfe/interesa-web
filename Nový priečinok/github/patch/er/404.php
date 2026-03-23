<?php
declare(strict_types=1);
/**
 * Interesa – 404.php (kompletný súbor)
 * Jednoduchá 404 stránka. Bez zásahu do CSS alebo SEO copy.
 */

require_once __DIR__ . '/inc/functions.php';

$page = [
  'title'       => '404 – Stránka nenájdená | Interesa',
  'description' => 'Požadovaná stránka sa nenašla.',
  'noindex'     => true,
];

include __DIR__ . '/inc/head.php';
?>
<section class="content-main">
  <h1>404 – Stránka nenájdená</h1>
  <p>Ľutujeme, požadovaná stránka neexistuje alebo bola presunutá.</p>
  <p><a class="btn" href="<?= esc(site_url('/')) ?>">Späť na úvod</a></p>
</section>
<?php include __DIR__ . '/inc/sidebar.php'; ?>
<?php include __DIR__ . '/inc/footer.php'; ?>
