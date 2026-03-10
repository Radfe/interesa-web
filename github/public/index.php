<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';

$page_title = 'Interesa.sk – výživa, proteíny, vitamíny a minerály';
$page_description = 'Kvalitný obsahový web o doplnkoch, výžive a zdravom výbere produktov. Porovnania, návody, recenzie a praktické odporúčania.';
$page_type = 'WebSite';
$page_image = asset('img/hero/hero-1.webp');

$featuredCategories = [
    ['slug' => 'proteiny', 'article' => 'najlepsie-proteiny-2025', 'label' => 'Najpredávanejšia téma'],
    ['slug' => 'vyziva', 'article' => 'doplnky-vyzivy', 'label' => 'Každodenné návyky'],
    ['slug' => 'mineraly', 'article' => 'vitamin-d3', 'label' => 'Silný evergreen'],
];

include __DIR__ . '/inc/head.php';
?>
<section class="hero">
  <div class="container hero-inner">
    <div class="hero-copy">
      <span class="eyebrow">Obsahový web zameraný na organiku a affiliate</span>
      <h1>Pomáhame ľuďom vybrať lepšie doplnky, výživu a funkčné produkty bez marketingového balastu.</h1>
      <p>Interesa prepája kvalitný obsah, prehľadné porovnania a praktické odporúčania. Cieľom je prinášať návštevnosť z vyhľadávania a meniť ju na dôveryhodné kliky na affiliate ponuky.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="/clanky/najlepsie-proteiny-2025">Pozrieť top odporúčania</a>
        <a class="btn btn-ghost" href="/clanky/">Prejsť všetky články</a>
      </div>
      <ul class="hero-badges">
        <li>SEO články s praktickým zámerom</li>
        <li>Čisté kategórie a interné prelinkovanie</li>
        <li>Affiliate monetizácia cez odporúčania</li>
      </ul>
    </div>

    <div class="hero-media">
      <figure class="hero-figure">
        <picture>
          <source srcset="<?= asset('img/hero/hero-1.webp') ?>" type="image/webp">
          <img src="<?= asset('img/og-default.jpg') ?>" alt="Prehľad kvalitných doplnkov a výživy" width="1920" height="1080" loading="eager" fetchpriority="high" style="aspect-ratio:16/9;object-fit:cover;">
        </picture>
        <figcaption>Obsah, ktorý buduje dôveru aj návštevnosť z vyhľadávania.</figcaption>
      </figure>
    </div>
  </div>
</section>

<section class="container trust-strip">
  <article class="trust-card">
    <strong>Poctivé porovnania</strong>
    <p>Žiadne generické texty. Každá téma smeruje k jasnému výberu, čo kúpiť a pre koho.</p>
  </article>
  <article class="trust-card">
    <strong>Silné money témy</strong>
    <p>Proteíny, kreatín, horčík, vitamín D3, kolagén a ďalšie kategórie s vysokým potenciálom vyhľadávania.</p>
  </article>
  <article class="trust-card">
    <strong>Obsah + výkon</strong>
    <p>Web je navrhnutý tak, aby bol rýchly, indexovateľný a pripravený pre ďalšiu CRO optimalizáciu.</p>
  </article>
</section>

<section class="container content-stack">
  <div class="section-heading">
    <div>
      <span class="eyebrow">Najdôležitejšie vstupné stránky</span>
      <h2>Začíname témami, ktoré majú najväčší obchodný potenciál</h2>
    </div>
    <a class="btn btn-ghost" href="/kategorie/">Pozrieť všetky kategórie</a>
  </div>

  <div class="hub-grid">
    <?php foreach ($featuredCategories as $item): $category = category_meta($item['slug']); ?>
      <article class="hub-card">
        <div class="hub-icon-wrap">
          <img src="<?= esc(category_icon($item['slug'])) ?>" alt="<?= esc($category['title']) ?>" width="48" height="48">
        </div>
        <span class="hub-label"><?= esc($item['label']) ?></span>
        <h3><?= esc($category['title']) ?></h3>
        <p><?= esc($category['description']) ?></p>
        <div class="hub-actions">
          <a class="btn btn-primary" href="<?= esc(category_url($item['slug'])) ?>">Otvoriť kategóriu</a>
          <a class="btn btn-ghost" href="<?= esc(article_url($item['article'])) ?>">Prejsť na článok</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="promo-cards container">
  <article class="card">
    <img src="<?= asset('img/cards/proteiny.webp') ?>" alt="Proteíny a výber na chudnutie" width="1200" height="800" loading="lazy" style="object-fit:cover;">
    <div class="card-body">
      <h3>Proteíny na chudnutie</h3>
      <p>Najčastejší nákupný intent, kde ľudia hľadajú jasné odporúčanie, nie len všeobecné rady.</p>
      <a class="card-link" href="/clanky/protein-na-chudnutie">Zobraziť článok</a>
    </div>
  </article>

  <article class="card">
    <img src="<?= asset('img/cards/vyziva.webp') ?>" alt="Zdravá výživa a každodenné produkty" width="1200" height="800" loading="lazy" style="object-fit:cover;">
    <div class="card-body">
      <h3>Zdravá výživa</h3>
      <p>Obsah pre širší long-tail: snacky, kaše, orechy, raňajky a funkčné potraviny.</p>
      <a class="card-link" href="/kategorie/vyziva">Prejsť do kategórie</a>
    </div>
  </article>

  <article class="card">
    <img src="<?= asset('img/cards/vitaminy.webp') ?>" alt="Vitamíny a minerály" width="1200" height="800" loading="lazy" style="object-fit:cover;">
    <div class="card-body">
      <h3>Vitamíny a minerály</h3>
      <p>Evergreen dotazy s vysokou šancou na organickú návštevnosť aj opakované konverzie.</p>
      <a class="card-link" href="/kategorie/mineraly">Zistiť viac</a>
    </div>
  </article>
</section>

<section class="container two-col">
  <div class="content content-stack">
    <article class="lead-article">
      <span class="eyebrow">Redakčný prístup</span>
      <h2>Čo musí robiť obsahový affiliate web, aby zarábal</h2>
      <p class="meta">Nie len publikovať články, ale systematicky pokrývať témy s reálnym nákupným zámerom.</p>
      <p>Najlepšie fungujú stránky, ktoré ľuďom pomôžu vybrať konkrétny produkt, vysvetlia rozdiely medzi variantmi a zároveň im dajú dôvod veriť odporúčaniu. Preto Interesa stojí na troch pilieroch: kvalitný obsah, čisté kategórie a zmysluplné CTA prvky.</p>
      <p>V praxi to znamená kombinovať recenzie, porovnania, FAQ články a evergreen články s vysokým dopytom. Tieto stránky potom podporujú hlavné money pages ako „najlepšie proteíny“, „aký horčík vybrať“ alebo „ktorý kolagén kúpiť“.</p>
      <div class="article-actions compact-actions">
        <a class="btn btn-primary" href="/clanky/najlepsie-proteiny-2025">Pozrieť top money článok</a>
        <a class="btn btn-ghost" href="/search?q=protein">Vyskúšať vyhľadávanie</a>
      </div>
    </article>
  </div>

  <aside class="sidebar" aria-label="Pravý panel">
    <?php include __DIR__ . '/inc/components/latest_articles.php'; ?>

    <article class="ad-card info-panel">
      <h3>Prečo tento web môže zarábať</h3>
      <ul class="bullet-list">
        <li>Obsah rieši konkrétny problém a nákupnú otázku.</li>
        <li>Každý článok smeruje na ďalší krok alebo odporúčanie.</li>
        <li>Kategórie sa dajú rozširovať o nové long-tail články.</li>
      </ul>
    </article>

    <article class="ad-card">
      <h3>Heureka vyhľadávanie</h3>
      <div class="heureka-affiliate-searchpanel" data-trixam-positionid="67512" data-trixam-codetype="iframe" data-trixam-linktarget="top"></div>
    </article>

    <article class="ad-card">
      <h3>Top ponuky</h3>
      <div class="heureka-affiliate-category" data-trixam-positionid="40743" data-trixam-categoryid="731" data-trixam-codetype="iframe" data-trixam-linktarget="top"></div>
    </article>
  </aside>
</section>

<?php include __DIR__ . '/inc/footer.php'; ?>