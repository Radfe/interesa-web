<?php
declare(strict_types=1);
/**
 * Interesa – index.php (kompletný súbor)
 * Mandát: čisto PHP štruktúra a prepojenia. Nemení sa CSS ani obsahové SEO texty.
 *
 * Predpoklady:
 * - inc/functions.php je volané z inc/head.php
 * - dostupné mapy $CATS a $ART z inc/articles.php (+ voliteľne inc/articles_ext.php)
 */

// Dáta majú byť dostupné už v <head>, preto ich includneme pred head.php
require __DIR__ . '/inc/articles.php';
if (is_file(__DIR__ . '/inc/articles_ext.php')) {
  require_once __DIR__ . '/inc/articles_ext.php';
}

// Základné meta pre home (technické – copy ponechané)
$page = [
  'title'       => 'Interesa – recenzie, porovnania a návody',
  'description' => 'Redakčný web so sprievodcami a porovnaniami.',
  'og_type'     => 'website',
];

// <head>, header, breadcrumbs…
include __DIR__ . '/inc/head.php';
?>

<section class="hero" style="grid-column:1 / -1">
  <h1>Nezávislé porovnania a testy – vyber si správne</h1>
  <form class="big-search" action="<?= esc(site_url('/search.php')) ?>" method="get" role="search" aria-label="Vyhľadávanie">
    <label class="visually-hidden" for="q">Čo hľadáš?</label>
    <input id="q" name="q" type="search" placeholder="Napr. proteín na chudnutie, kreatín, vitamín D3…" autocomplete="off" spellcheck="false">
    <button type="submit">Hľadať</button>
  </form>
</section>

<section class="content-main">
  <h2>Kategórie</h2>
  <div class="grid-cards">
    <?php foreach (($CATS ?? []) as $slug => [$name, $desc]): ?>
      <article class="post-card">
        <a class="chip" href="<?= esc(site_url('/kategorie/' . $slug . '.php')) ?>"><?= esc($name) ?></a>
        <h3><?= esc($name) ?></h3>
        <p class="meta"><?= esc($desc) ?></p>
        <a class="btn" href="<?= esc(site_url('/kategorie/' . $slug . '.php')) ?>">Zobraziť</a>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="content-main">
  <h2>Najnovšie články</h2>
  <div class="grid-cards">
    <?php
      $artKeys = array_keys($ART ?? []);
      $slugs = array_slice($artKeys, 0, 9);
      foreach ($slugs as $s):
        [$t, $d, $cat] = $ART[$s];
    ?>
      <article class="post-card">
        <a href="<?= esc(site_url('/clanky/' . $s . '.php')) ?>">
          <img class="thumb" loading="lazy" decoding="async" src="<?= esc(article_img($s)) ?>" alt="<?= esc($t) ?>">
        </a>
        <a class="chip" href="<?= esc(site_url('/kategorie/' . $cat . '.php')) ?>"><?= esc($CATS[$cat][0] ?? $cat) ?></a>
        <h3><a href="<?= esc(site_url('/clanky/' . $s . '.php')) ?>"><?= esc($t) ?></a></h3>
        <p class="meta"><?= esc($d) ?></p>
        <a class="btn" href="<?= esc(site_url('/clanky/' . $s . '.php')) ?>">Čítať</a>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="content-main">
  <h2>Najčítanejšie</h2>
  <div class="grid-cards">
    <?php foreach (top_articles(6) as $s): [$t, $d, $cat] = $ART[$s]; ?>
      <article class="post-card">
        <a href="<?= esc(site_url('/clanky/' . $s . '.php')) ?>">
          <img class="thumb" loading="lazy" decoding="async" src="<?= esc(article_img($s)) ?>" alt="<?= esc($t) ?>">
        </a>
        <a class="chip" href="<?= esc(site_url('/kategorie/' . $cat . '.php')) ?>"><?= esc($CATS[$cat][0] ?? $cat) ?></a>
        <h3><a href="<?= esc(site_url('/clanky/' . $s . '.php')) ?>"><?= esc($t) ?></a></h3>
        <p class="meta"><?= esc($d) ?></p>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="content-main">
  <h2>Prečo nám veriť</h2>
  <div class="benefits">
    <div class="benefit">
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v18M3 12h18" stroke="currentColor" stroke-width="2"/></svg>
      <div><strong>Nezávislá redakcia</strong><p>Obsah tvoríme bez PR tlakov. Zdrojujeme a porovnávame štúdie.</p></div>
    </div>
    <div class="benefit">
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12l4 4 12-12" stroke="currentColor" stroke-width="2"/></svg>
      <div><strong>Jasné kritériá</strong><p>Vždy vysvetľujeme prečo je produkt vybraný a pre koho.</p></div>
    </div>
    <div class="benefit">
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/></svg>
      <div><strong>Transparentnosť</strong><p>Odkazy môžu byť partnerské, pre teba cenu nemenia.</p></div>
    </div>
    <div class="benefit">
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 19h16M4 5h16M4 12h16" stroke="currentColor" stroke-width="2"/></svg>
      <div><strong>Prehľadnosť</strong><p>Krátko, vecne, s obsahom a FAQ.</p></div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/inc/sidebar.php'; ?>
<?php include __DIR__ . '/inc/footer.php'; ?>
