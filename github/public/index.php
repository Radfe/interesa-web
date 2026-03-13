<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$page_title = 'Interesa.sk - vyziva, proteiny, vitaminy a mineraly';
$page_description = 'Kvalitny obsahovy web o doplnkoch, vyzive a zdravom vybere produktov. Porovnania, navody, recenzie a prakticke odporucania.';
$page_type = 'WebSite';
$page_image = asset('img/hero/hero-1.webp');

$featuredCategories = [
    ['slug' => 'proteiny', 'article' => 'najlepsie-proteiny-2025', 'label' => 'Najpredavanejsia tema'],
    ['slug' => 'vyziva', 'article' => 'doplnky-vyzivy', 'label' => 'Kazdodenne navyky'],
    ['slug' => 'mineraly', 'article' => 'vitamin-d3', 'label' => 'Silny evergreen'],
];

$promoCards = [
    [
        'title' => 'Proteiny na chudnutie',
        'description' => 'Najcastejsi nakupny intent, kde ludia hladaju jasne odporucanie, nie len vseobecne rady.',
        'href' => '/clanky/protein-na-chudnutie',
        'slug' => 'protein-na-chudnutie',
        'cta' => 'Zobrazit clanok',
    ],
    [
        'title' => 'Zdrava vyziva',
        'description' => 'Obsah pre sirsi long-tail: snacky, kase, orechy, ranajky a funkcne potraviny.',
        'href' => '/kategorie/vyziva',
        'slug' => 'doplnky-vyzivy',
        'cta' => 'Prejst do kategorie',
    ],
    [
        'title' => 'Vitaminy a mineraly',
        'description' => 'Evergreen dotazy s vysokou sancou na organicku navstevnost aj opakovane konverzie.',
        'href' => '/kategorie/mineraly',
        'slug' => 'vitamin-d3',
        'cta' => 'Zistit viac',
    ],
];

include __DIR__ . '/inc/head.php';
?>
<section class="hero">
  <div class="container hero-inner">
    <div class="hero-copy">
      <span class="eyebrow">Obsahovy web zamerany na organiku a affiliate</span>
      <h1>Pomahame ludom vybrat lepsie doplnky, vyzivu a funkcne produkty bez marketingoveho balastu.</h1>
      <p>Interesa prepaja kvalitny obsah, prehladne porovnania a prakticke odporucania. Cielom je prinasat navstevnost z vyhladavania a menit ju na doveryhodne kliky na affiliate ponuky.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="/clanky/najlepsie-proteiny-2025">Pozriet top odporucania</a>
        <a class="btn btn-ghost" href="/clanky/">Prejst vsetky clanky</a>
      </div>
      <ul class="hero-badges">
        <li>SEO clanky s praktickym zamerom</li>
        <li>Ciste kategorie a interne prelinkovanie</li>
        <li>Affiliate monetizacia cez odporucania</li>
      </ul>
    </div>

    <div class="hero-media">
      <figure class="hero-figure">
        <picture>
          <source srcset="<?= asset('img/hero/hero-1.webp') ?>" type="image/webp">
          <img src="<?= asset('img/og-default.jpg') ?>" alt="Prehlad kvalitnych doplnkov a vyzivy" width="1920" height="1080" loading="eager" fetchpriority="high" style="aspect-ratio:16/9;object-fit:cover;">
        </picture>
        <figcaption>Obsah, ktory buduje doveru aj navstevnost z vyhladavania.</figcaption>
      </figure>
    </div>
  </div>
</section>

<section class="container trust-strip">
  <article class="trust-card">
    <strong>Poctive porovnania</strong>
    <p>Ziadne genericke texty. Kazda tema smeruje k jasnemu vyberu, co kupit a pre koho.</p>
  </article>
  <article class="trust-card">
    <strong>Silne money temy</strong>
    <p>Proteiny, kreatin, horcik, vitamin D3, kolagen a dalsie kategorie s vysokym potencialom vyhladavania.</p>
  </article>
  <article class="trust-card">
    <strong>Obsah + vykon</strong>
    <p>Web je navrhnuty tak, aby bol rychly, indexovatelny a pripraveny pre dalsiu CRO optimalizaciu.</p>
  </article>
</section>

<section class="container content-stack">
  <div class="section-heading">
    <div>
      <span class="eyebrow">Najdolezitejsie vstupne stranky</span>
      <h2>Zaciname temami, ktore maju najvacsi obchodny potencial</h2>
    </div>
    <a class="btn btn-ghost" href="/kategorie/">Pozriet vsetky kategorie</a>
  </div>

  <div class="hub-grid">
    <?php foreach ($featuredCategories as $item): $category = category_meta($item['slug']); ?>
      <article class="hub-card">
        <div class="hub-media">
          <img class="hub-visual" src="<?= esc(category_visual($item['slug'])) ?>" alt="<?= esc($category['title']) ?>" width="1200" height="800" loading="lazy" decoding="async">
          <div class="hub-icon-wrap">
            <img src="<?= esc(category_icon($item['slug'])) ?>" alt="<?= esc($category['title']) ?>" width="48" height="48">
          </div>
        </div>
        <span class="hub-label"><?= esc($item['label']) ?></span>
        <h3><?= esc($category['title']) ?></h3>
        <p><?= esc($category['description']) ?></p>
        <div class="hub-actions">
          <a class="btn btn-primary" href="<?= esc(category_url($item['slug'])) ?>">Otvorit kategoriu</a>
          <a class="btn btn-ghost" href="<?= esc(article_url($item['article'])) ?>">Prejst na clanok</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="promo-cards container">
  <?php foreach ($promoCards as $item): ?>
    <article class="card">
      <img src="<?= esc(article_img($item['slug'])) ?>" alt="<?= esc($item['title']) ?>" width="1200" height="800" loading="lazy" decoding="async" style="object-fit:cover;">
      <div class="card-body">
        <h3><?= esc($item['title']) ?></h3>
        <p><?= esc($item['description']) ?></p>
        <a class="card-link" href="<?= esc($item['href']) ?>"><?= esc($item['cta']) ?></a>
      </div>
    </article>
  <?php endforeach; ?>
</section>

<section class="container two-col">
  <div class="content content-stack">
    <article class="lead-article">
      <span class="eyebrow">Redakcny pristup</span>
      <h2>Co musi robit obsahovy affiliate web, aby zarabal</h2>
      <p class="meta">Nie len publikovat clanky, ale systematicky pokryvat temy s realnym nakupnym zamerom.</p>
      <p>Najlepsie funguju stranky, ktore ludom pomozu vybrat konkretny produkt, vysvetlia rozdiely medzi variantmi a zaroven im daju dovod verit odporucaniu. Preto Interesa stoji na troch pilieroch: kvalitny obsah, ciste kategorie a zmysluplne CTA prvky.</p>
      <p>V praxi to znamena kombinovat recenzie, porovnania, FAQ clanky a evergreen clanky s vysokym dopytom. Tieto stranky potom podporuju hlavne money pages ako "najlepsie proteiny", "aky horcik vybrat" alebo "ktory kolagen kupit".</p>
      <div class="article-actions compact-actions">
        <a class="btn btn-primary" href="/clanky/najlepsie-proteiny-2025">Pozriet top money clanok</a>
        <a class="btn btn-ghost" href="/search?q=protein">Vyskusat vyhladavanie</a>
      </div>
    </article>
  </div>

  <aside class="sidebar" aria-label="Pravy panel">
    <?php include __DIR__ . '/inc/components/latest_articles.php'; ?>

    <article class="ad-card info-panel">
      <h3>Preco tento web moze zarabat</h3>
      <ul class="bullet-list">
        <li>Obsah riesi konkretny problem a nakupnu otazku.</li>
        <li>Kazdy clanok smeruje na dalsi krok alebo odporucanie.</li>
        <li>Kategorie sa daju rozsirovat o nove long-tail clanky.</li>
      </ul>
    </article>

    <article class="ad-card">
      <h3>Heureka vyhladavanie</h3>
      <div class="heureka-affiliate-searchpanel" data-trixam-positionid="67512" data-trixam-codetype="iframe" data-trixam-linktarget="top"></div>
    </article>

    <article class="ad-card">
      <h3>Top ponuky</h3>
      <div class="heureka-affiliate-category" data-trixam-positionid="40743" data-trixam-categoryid="731" data-trixam-codetype="iframe" data-trixam-linktarget="top"></div>
    </article>
  </aside>
</section>

<?php include __DIR__ . '/inc/footer.php'; ?>
