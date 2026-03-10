<?php
declare(strict_types=1);
/**
 * Interesa – category-template.php
 * Kompletná šablóna kategórie. Nezasahuje do CSS, iba renderuje obsah.
 *
 * Predpoklady:
 * - V scope sú dostupné: $slug, $CATS, $ART (z router.php)
 * - Helpers z inc/functions.php (esc(), site_url(), article_img()…)
 */

// Bezpečnostná poistka
if (!isset($slug, $CATS[$slug])) {
  http_response_code(500);
  exit('Category template missing context.');
}

[$catName, $catDesc] = $CATS[$slug];

// Priprav $page meta (technické)
$page = [
  'title'       => $catName . ' | Interesa',
  'description' => $catDesc,
  'og_type'     => 'website',
];

include __DIR__ . '/inc/head.php';

// Zozbieraj články z danej kategórie
$list = [];
foreach ($ART as $s => $row) {
  if (($row[2] ?? '') === $slug) $list[] = $s;
}
?>
<section class="content-main">
  <header class="category-head">
    <h1><?= esc($catName) ?></h1>
    <?php if (!empty($catDesc)): ?>
      <p class="lead"><?= esc($catDesc) ?></p>
    <?php endif; ?>
  </header>

  <?php if ($list): ?>
    <div class="grid-cards">
      <?php foreach ($list as $s): [$t, $d, $c] = $ART[$s]; ?>
        <article class="post-card">
          <a href="<?= esc(site_url('/clanky/' . $s . '.php')) ?>">
            <img class="thumb" loading="lazy" decoding="async" src="<?= esc(article_img($s)) ?>" alt="<?= esc($t) ?>">
          </a>
          <a class="chip" href="<?= esc(site_url('/kategorie/' . $c . '.php')) ?>"><?= esc($CATS[$c][0] ?? $c) ?></a>
          <h3><a href="<?= esc(site_url('/clanky/' . $s . '.php')) ?>"><?= esc($t) ?></a></h3>
          <p class="meta"><?= esc($d) ?></p>
          <a class="btn" href="<?= esc(site_url('/clanky/' . $s . '.php')) ?>">Čítať</a>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="note">V tejto kategórii zatiaľ nemáme články.</p>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/inc/sidebar.php'; ?>
<?php include __DIR__ . '/inc/footer.php'; ?>
