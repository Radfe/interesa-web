<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';

$page_title = 'Interesa.sk – výživa, doplnky a praktické porovnania';
$page_description = 'Obsahový web o doplnkoch a výžive, ktorý prepája porovnania, evergreen návody a praktické články podľa cieľa aj typu produktu.';
$page_type = 'WebSite';
$page_image = asset('img/hero/hero-1.webp');

$featuredCategories = [
    ['slug' => 'proteiny', 'article' => 'najlepsie-proteiny-2025', 'label' => 'Silný vstupný cluster'],
    ['slug' => 'kreatin', 'article' => 'kreatin-porovnanie', 'label' => 'Výkon a regenerácia'],
    ['slug' => 'mineraly', 'article' => 'horcik', 'label' => 'Formy a dávkovanie'],
    ['slug' => 'imunita', 'article' => 'imunita-prirodne-latky-ktore-funguju', 'label' => 'Evergreen dopyt'],
];

include __DIR__ . '/inc/head.php';
?>
<section class="hero hero-home">
  <div class="container hero-inner">
    <div class="hero-copy">
      <span class="eyebrow">Obsahový web pre výber doplnkov bez chaosu</span>
      <h1>Prejdi od otázky k správnemu výberu bez zbytočného marketingu.</h1>
      <p>Interesa spája prehľadné porovnania, evergreen návody a obsahové clustre, cez ktoré sa vieš rýchlo dostať od všeobecnej témy ku konkrétnemu rozhodnutiu. Či riešiš proteíny, kreatín, horčík alebo imunitu, pointa je rovnaká: rýchlo pochopiť rozdiely a vybrať si rozumne.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="/clanky/">Prejsť hlavné články</a>
        <a class="btn btn-ghost" href="/kategorie/">Otvoriť kategórie</a>
      </div>
      <ul class="hero-badges">
        <li>Silné clustre: proteíny, kreatín, minerály, imunita</li>
        <li>FAQ, porovnania a prepojenie medzi súvisiacimi témami</li>
        <li>Výber podľa cieľa, nie podľa reklamy na etikete</li>
      </ul>
    </div>

    <div class="hero-media">
      <figure class="hero-figure hero-visual-card">
        <picture>
          <source srcset="<?= asset('img/hero/hero-1.webp') ?>" type="image/webp">
          <img src="<?= asset('img/og-default.jpg') ?>" alt="Miska s ovocím a zdravou výživou" width="1920" height="1080" loading="eager" fetchpriority="high" style="aspect-ratio:16/9;object-fit:cover;">
        </picture>
        <figcaption>Obsah, v ktorom sa dá rýchlo zorientovať a pokračovať ďalej podľa témy.</figcaption>
      </figure>
    </div>
  </div>
</section>

<section class="container trust-strip">
  <article class="trust-card">
    <strong>Jasné vysvetlenie</strong>
    <p>Každá téma má čitateľovi pomôcť pochopiť rozdiely, nie ho zahltiť marketingovým slovníkom.</p>
  </article>
  <article class="trust-card">
    <strong>Prepojené clustre</strong>
    <p>Vstupné články, detailné porovnania a kategórie na seba prirodzene nadväzujú.</p>
  </article>
  <article class="trust-card">
    <strong>Praktický výber</strong>
    <p>Obsah je postavený tak, aby si sa vedel rozhodnúť podľa cieľa, tolerancie a reálneho použitia.</p>
  </article>
</section>

<section class="container content-stack">
  <div class="section-heading">
    <div>
      <span class="eyebrow">Hlavné vstupné clustre</span>
      <h2>Začni témou, ktorú riešiš práve teraz</h2>
      <p class="section-intro">Tieto clustre dnes držia najsilnejší základ webu a každá z nich ťa zavedie z úvodnej otázky až ku konkrétnym porovnaniam a praktickým článkom.</p>
    </div>
    <a class="btn btn-ghost" href="/clanky/">Pozrieť všetky články</a>
  </div>

  <div class="hub-grid">
    <?php foreach ($featuredCategories as $item): $category = category_meta($item['slug']); ?>
      <article class="hub-card">
        <span class="hub-label"><?= esc($item['label']) ?></span>
        <h3><?= esc($category['title']) ?></h3>
        <p><?= esc($category['description']) ?></p>
        <div class="hub-actions">
          <a class="btn btn-primary" href="<?= esc(category_url($item['slug'])) ?>">Otvoriť kategóriu</a>
          <a class="btn btn-ghost" href="<?= esc(article_url($item['article'])) ?>">Začať článkom</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="promo-cards container">
  <article class="card">
    <img src="<?= asset('img/cards/proteiny.webp') ?>" alt="Proteíny na chudnutie a výber podľa cieľa" width="1200" height="800" loading="lazy" style="object-fit:cover;">
    <div class="card-body">
      <h3>Proteíny podľa cieľa</h3>
      <p>Od všeobecného prehľadu cez chudnutie až po clear protein a rastlinné varianty.</p>
      <a class="card-link" href="/kategorie/proteiny">Prejsť do clusteru</a>
    </div>
  </article>

  <article class="card">
    <img src="<?= asset('img/cards/vyziva.webp') ?>" alt="Kreatín, výkon a silové doplnky" width="1200" height="800" loading="lazy" style="object-fit:cover;">
    <div class="card-body">
      <h3>Kreatín a výkon</h3>
      <p>Formy, dávkovanie, vedľajšie účinky aj praktické otázky pre začiatočníka.</p>
      <a class="card-link" href="/clanky/kreatin-porovnanie">Otvoriť kreatínový základ</a>
    </div>
  </article>

  <article class="card">
    <img src="<?= asset('img/cards/vitaminy.webp') ?>" alt="Horčík, vitamín D3 a imunita" width="1200" height="800" loading="lazy" style="object-fit:cover;">
    <div class="card-body">
      <h3>Minerály a imunita</h3>
      <p>Horčík, D3, zinok a ďalšie evergreen témy, kde ľudia riešia formy, dávkovanie a použitie.</p>
      <a class="card-link" href="/kategorie/mineraly">Pozrieť minerály</a>
    </div>
  </article>
</section>

<section class="container two-col">
  <div class="content content-stack">
    <article class="lead-article">
      <span class="eyebrow">Ako sa po webe pohybovať</span>
      <h2>Najrýchlejšia cesta je začať clusterom a pokračovať do detailnejších článkov</h2>
      <p class="meta">Interesa už nie je len zoznam tém, ale prepojený obsahový web, kde sa z úvodného článku vieš dostať až ku konkrétnemu rozhodnutiu.</p>
      <p>Ak presne vieš, čo hľadáš, otvor si článkový archív alebo vyhľadávanie. Ak sa ešte len orientuješ, najlepšie funguje prejsť cez kategóriu, otvoriť vstupný článok a odtiaľ pokračovať do porovnaní, FAQ a súvisiacich tém. Takto je dnes postavený najmä cluster proteínov, kreatínu, minerálov a imunity.</p>
      <div class="article-actions compact-actions">
        <a class="btn btn-primary" href="/clanky/">Otvoriť archív článkov</a>
        <a class="btn btn-ghost" href="/search?q=horcik">Vyskúšať vyhľadávanie</a>
      </div>
    </article>
  </div>

  <aside class="sidebar" aria-label="Pravý panel">
    <?php include __DIR__ . '/inc/components/latest_articles.php'; ?>

    <article class="ad-card info-panel">
      <h3>Najlepší štart</h3>
      <ul class="bullet-list">
        <li>Ak riešiš cieľ, otvor si najprv príslušnú kategóriu.</li>
        <li>Ak riešiš konkrétnu otázku, choď rovno do článkového archívu.</li>
        <li>Pri výbere produktu si vždy otvor aj súvisiace porovnania.</li>
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
