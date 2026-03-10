<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';

$page_title = 'Interesa.sk – výživa, doplnky a praktické porovnania';
$page_description = 'Praktický sprievodca výživou, doplnkami a funkčnými produktmi. Prehľadné porovnania, recenzie a články, v ktorých sa dá rýchlo zorientovať.';
$page_type = 'WebSite';
$page_image = asset('img/hero/hero-1.webp');

$featuredCategories = [
    ['slug' => 'proteiny', 'article' => 'najlepsie-proteiny-2025', 'label' => 'Najčítanejšia téma'],
    ['slug' => 'vyziva', 'article' => 'doplnky-vyzivy', 'label' => 'Každodenný výber'],
    ['slug' => 'mineraly', 'article' => 'vitamin-d3', 'label' => 'Silný evergreen'],
];

include __DIR__ . '/inc/head.php';
?>
<section class="hero hero-home">
  <div class="container hero-inner">
    <div class="hero-copy">
      <span class="eyebrow">Praktický sprievodca výživou a doplnkami</span>
      <h1>Vyber si doplnky a výživu, ktoré naozaj dávajú zmysel.</h1>
      <p>Na Interesa nájdeš zrozumiteľné porovnania, recenzie a praktické návody. Cieľ je jednoduchý: pomôcť rýchlo sa zorientovať a vybrať si to, čo je pre teba vhodné.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="/clanky/najlepsie-proteiny-2025">Pozrieť top výbery</a>
        <a class="btn btn-ghost" href="/clanky/">Prejsť články</a>
      </div>
      <ul class="hero-badges">
        <li>Prehľadné porovnania bez zbytočného balastu</li>
        <li>Kategórie podľa cieľa a typu produktu</li>
        <li>Obsah pre otázky, ktoré ľudia riešia najčastejšie</li>
      </ul>
    </div>

    <div class="hero-media">
      <figure class="hero-figure hero-visual-card">
        <picture>
          <source srcset="<?= asset('img/hero/hero-1.webp') ?>" type="image/webp">
          <img src="<?= asset('img/og-default.jpg') ?>" alt="Miska s ovocím a zdravou výživou" width="1920" height="1080" loading="eager" fetchpriority="high" style="aspect-ratio:16/9;object-fit:cover;">
        </picture>
        <figcaption>Obsah, v ktorom sa dá rýchlo zorientovať.</figcaption>
      </figure>
    </div>
  </div>
</section>

<section class="container trust-strip">
  <article class="trust-card">
    <strong>Zrozumiteľné vysvetlenie</strong>
    <p>Každá téma má čitateľovi pomôcť pochopiť rozdiely a rozhodnúť sa bez chaosu.</p>
  </article>
  <article class="trust-card">
    <strong>Praktický výber</strong>
    <p>Namiesto všeobecných fráz ukazujeme, čo sa hodí na konkrétny cieľ alebo situáciu.</p>
  </article>
  <article class="trust-card">
    <strong>Prepojený obsah</strong>
    <p>Články, kategórie a odporúčania sú navrhnuté tak, aby na seba prirodzene nadväzovali.</p>
  </article>
</section>

<section class="container content-stack">
  <div class="section-heading">
    <div>
      <span class="eyebrow">Najdôležitejšie témy</span>
      <h2>Začíname tam, kde ľudia hľadajú najviac odpovedí</h2>
      <p class="section-intro">Proteíny, zdravá výživa a vitamíny sú témy, kde sa oplatí mať poruke jasné porovnania aj praktické odporúčania.</p>
    </div>
    <a class="btn btn-ghost" href="/kategorie/">Pozrieť všetky kategórie</a>
  </div>

  <div class="hub-grid">
    <?php foreach ($featuredCategories as $item): $category = category_meta($item['slug']); ?>
      <article class="hub-card">
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
      <p>Téma, kde ľudia hľadajú jasné odporúčanie a rýchle porovnanie jednotlivých typov.</p>
      <a class="card-link" href="/clanky/protein-na-chudnutie">Zobraziť článok</a>
    </div>
  </article>

  <article class="card">
    <img src="<?= asset('img/cards/vyziva.webp') ?>" alt="Zdravá výživa a každodenné produkty" width="1200" height="800" loading="lazy" style="object-fit:cover;">
    <div class="card-body">
      <h3>Zdravá výživa</h3>
      <p>Praktické články o snackoch, kašiach, orechoch a produktoch do bežného dňa.</p>
      <a class="card-link" href="/kategorie/vyziva">Prejsť do kategórie</a>
    </div>
  </article>

  <article class="card">
    <img src="<?= asset('img/cards/vitaminy.webp') ?>" alt="Vitamíny a minerály" width="1200" height="800" loading="lazy" style="object-fit:cover;">
    <div class="card-body">
      <h3>Vitamíny a minerály</h3>
      <p>Evergreen témy, pri ktorých ľudia riešia dávkovanie, rozdiely medzi formami aj vhodný výber.</p>
      <a class="card-link" href="/kategorie/mineraly">Zistiť viac</a>
    </div>
  </article>
</section>

<section class="container two-col">
  <div class="content content-stack">
    <article class="lead-article">
      <span class="eyebrow">Ako používať tento web</span>
      <h2>Začni témou, ktorá ťa zaujíma, a pokračuj cez porovnania a súvisiace články</h2>
      <p class="meta">Interesa je postavená tak, aby sa dalo rýchlo prejsť z kategórie na konkrétny problém alebo výber produktu.</p>
      <p>Ak vieš, čo hľadáš, začni vo vyhľadávaní. Ak sa ešte len orientuješ, najrýchlejšia cesta je prejsť cez kategórie a následne si otvoriť články, ktoré riešia konkrétnu otázku, napríklad ktorý proteín zvoliť, aký horčík vybrať alebo kedy má zmysel kreatín.</p>
      <div class="article-actions compact-actions">
        <a class="btn btn-primary" href="/clanky/najlepsie-proteiny-2025">Otvoriť top článok</a>
        <a class="btn btn-ghost" href="/search?q=protein">Vyskúšať vyhľadávanie</a>
      </div>
    </article>
  </div>

  <aside class="sidebar" aria-label="Pravý panel">
    <?php include __DIR__ . '/inc/components/latest_articles.php'; ?>

    <article class="ad-card info-panel">
      <h3>Ako sa tu nestratiť</h3>
      <ul class="bullet-list">
        <li>Začni kategóriou, ak ešte len zbieraš prehľad.</li>
        <li>Choď do konkrétneho článku, ak riešiš jeden problém.</li>
        <li>Pri výbere si vždy otvor aj súvisiace porovnania.</li>
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