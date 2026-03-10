<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';

$page_title = 'Interesa.sk – výživa, proteíny, vitamíny & minerály';
$page_description = 'Nezávislé porovnania, ako vybrať, FAQ a tipy. Rýchlo, prehľadne, bez balastu.';

include __DIR__ . '/inc/head.php';
?>

<section class="hero">
  <div class="container hero-inner">
    <div class="hero-copy">
      <h1>Vyber si to najlepšie pre svoje zdravie</h1>
      <p>Kurátorovaný výber proteínov, výživy a mikroživín. Prehľadné porovnania, jasné vysvetlenia, žiadny balast.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="/go/demo-kod" rel="nofollow sponsored" target="_blank">Pozrieť odporúčania</a>
        <a class="btn btn-ghost" href="/clanky/">Čítať články</a>
      </div>
    </div>

    <div class="hero-media">
      <figure class="hero-figure">
        <picture>
          <source srcset="/assets/img/hero/hero-1.webp" type="image/webp">
          <img src="/assets/img/og-default.jpg" alt="Hero – zdravá výživa a doplnky, čistá kompozícia"
               width="1920" height="1080" loading="eager" fetchpriority="high"
               style="aspect-ratio:16/9;object-fit:cover;">
        </picture>
        <figcaption>Prehľadné odporúčania pre tvoj cieľ.</figcaption>
      </figure>
    </div>
  </div>
</section>

<section class="promo-cards container">
  <article class="card">
    <img src="/assets/img/cards/proteiny.webp" alt="Proteíny na chudnutie – výber a tipy"
         width="1200" height="800" loading="lazy" style="object-fit:cover;">
    <div class="card-body">
      <h3>Proteíny na chudnutie</h3>
      <p>Udrž svaly a zraz kalórie – zrozumiteľné tipy a tabuľky.</p>
      <a class="card-link" href="/clanky/protein-na-chudnutie">Zobraziť tipy</a>
    </div>
  </article>

  <article class="card">
    <img src="/assets/img/cards/vyziva.webp" alt="Vyvážená výživa – raňajky a snacky"
         width="1200" height="800" loading="lazy" style="object-fit:cover;">
    <div class="card-body">
      <h3>Vyvážená výživa</h3>
      <p>Rýchle snacky a raňajky z reálnych ingrediencií.</p>
      <a class="card-link" href="/clanky/doplnky-vyzivy">Odporúčané produkty</a>
    </div>
  </article>

  <article class="card">
    <img src="/assets/img/cards/vitaminy.webp" alt="Vitamíny a minerály – odporúčané doplnky"
         width="1200" height="800" loading="lazy" style="object-fit:cover;">
    <div class="card-body">
      <h3>Vitamíny &amp; minerály</h3>
      <p>Jednoduché balíčky pre imunitu, energiu a regeneráciu.</p>
      <a class="card-link" href="/kategorie/mineraly">Zistiť viac</a>
    </div>
  </article>
</section>

<section class="container two-col">
  <div class="content">
    <article class="lead-article">
      <header>
        <h2>Najlepší proteín na chudnutie: WPC vs WPI</h2>
        <p class="meta">Rýchle porovnanie srvátkových proteínov pre redukciu hmotnosti.</p>
      </header>

      <p>WPC je chutnejší a dostupnejší, WPI má menej laktózy a viac bielkovín na dávku. Ak rátate každé percento alebo zle znášate laktózu, siahnite po WPI – pre väčšinu ľudí však kvalitné WPC úplne stačí.</p>
      <p>Tip: pozrite si náš <a href="/go/demo-kod" rel="nofollow sponsored" target="_blank">výber proteínov</a> – hodnotíme chuť, zloženie a cenu za gram bielkovín.</p>

      <figure class="inline-figure">
        <img src="/assets/img/cards/proteiny.webp" alt="Ilustračný obrázok k téme proteínov"
             width="1200" height="800" loading="lazy" style="object-fit:cover;">
      </figure>
    </article>
  </div>

  <aside class="sidebar" aria-label="Pravý panel">
    <!-- Najnovšie články (nový widget) -->
    <?php include __DIR__ . '/inc/components/latest_articles.php'; ?>

    <!-- Heureka vyhľadávanie -->
    <article class="ad-card">
      <h3>Heureka vyhľadávanie</h3>
      <div class="heureka-affiliate-searchpanel"
           data-trixam-positionid="67512"
           data-trixam-codetype="iframe"
           data-trixam-linktarget="top"></div>
    </article>

    <!-- Heureka: Mobily -->
    <article class="ad-card">
      <h3>Top ponuky – Mobily</h3>
      <div class="heureka-affiliate-category"
           data-trixam-positionid="40746"
           data-trixam-categoryid="5526"
           data-trixam-categoryfilters=""
           data-trixam-codetype="iframe"
           data-trixam-linktarget="top"></div>
    </article>

    <!-- Heureka: Vitamíny & minerály -->
    <article class="ad-card">
      <h3>Vitamíny &amp; minerály</h3>
      <div class="heureka-affiliate-category"
           data-trixam-positionid="40743"
           data-trixam-categoryid="731"
           data-trixam-categoryfilters=""
           data-trixam-codetype="iframe"
           data-trixam-linktarget="top"></div>
    </article>
  </aside>
</section>

<?php include __DIR__ . '/inc/footer.php'; ?>
