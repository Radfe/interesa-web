<?php declare(strict_types=1); ?>
<?php
$metaTitle = raw_page_title();
$metaDescription = raw_page_description();
$metaCanonical = canonical_url();
$metaImage = page_image();
$metaType = page_type();
$metaRobots = page_robots();
$searchQuery = trim((string) ($_GET['q'] ?? ''));

$schema = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'Interesa',
        'url' => absolute_url('/'),
        'logo' => absolute_url(asset('img/logo-full.svg')),
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'Interesa',
        'url' => absolute_url('/'),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => absolute_url('/search?q={search_term_string}'),
            'query-input' => 'required name=search_term_string',
        ],
    ],
];

$breadcrumbs = breadcrumb_items();
if (count($breadcrumbs) > 1) {
    $schema[] = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_map(
            static fn(array $item, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ],
            $breadcrumbs,
            array_keys($breadcrumbs)
        ),
    ];
}

if ($metaType === 'Article') {
    $schema[] = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $metaTitle,
        'description' => $metaDescription,
        'image' => [$metaImage],
        'mainEntityOfPage' => $metaCanonical,
        'author' => ['@type' => 'Organization', 'name' => 'Redakcia Interesa'],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Interesa',
            'logo' => ['@type' => 'ImageObject', 'url' => absolute_url(asset('img/logo-full.svg'))],
        ],
    ];
}
?>
<!doctype html>
<html lang="sk">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="<?= esc($metaRobots) ?>" />
  <meta name="theme-color" content="#0f8f5b" />
  <meta name="format-detection" content="telephone=no" />
  <title><?= esc($metaTitle) ?></title>
  <meta name="description" content="<?= esc($metaDescription) ?>" />
  <link rel="canonical" href="<?= esc($metaCanonical) ?>" />

  <meta property="og:locale" content="sk_SK" />
  <meta property="og:type" content="<?= $metaType === 'Article' ? 'article' : 'website' ?>" />
  <meta property="og:title" content="<?= esc($metaTitle) ?>" />
  <meta property="og:description" content="<?= esc($metaDescription) ?>" />
  <meta property="og:url" content="<?= esc($metaCanonical) ?>" />
  <meta property="og:image" content="<?= esc($metaImage) ?>" />
  <meta property="og:site_name" content="Interesa" />

  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?= esc($metaTitle) ?>" />
  <meta name="twitter:description" content="<?= esc($metaDescription) ?>" />
  <meta name="twitter:image" content="<?= esc($metaImage) ?>" />

  <link rel="icon" href="<?= asset('img/logo-full.svg') ?>" type="image/svg+xml" />
  <link rel="stylesheet" href="<?= asset('css/main.css') ?>" />
  <link rel="stylesheet" href="<?= asset('css/compat.css') ?>" />
  <link rel="stylesheet" href="<?= asset('css/sidebar.css') ?>" />
  <link rel="stylesheet" href="<?= asset('css/patch.css') ?>" />
  <link rel="stylesheet" href="<?= asset('css/home-b12.css') ?>" />
  <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body>
  <a class="skip-link" href="#obsah">Preskočiť na obsah</a>

  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="/" aria-label="Domovská stránka Interesa">
        <img src="<?= asset('img/logo-full.svg') ?>" alt="Interesa.sk" width="148" height="32" />
      </a>

      <div class="header-tools">
        <form class="site-search" action="/search" method="get" role="search" aria-label="Vyhľadávanie na webe">
          <label class="sr-only" for="site-search-input">Hľadať články</label>
          <input id="site-search-input" class="search-input" type="search" name="q" value="<?= esc($searchQuery) ?>" placeholder="Hľadať články, porovnania a tipy" />
          <button class="search-btn" type="submit">Hľadať</button>
        </form>
        <a class="header-cta" href="/clanky/najlepsie-proteiny-2025">Top výber</a>
      </div>

      <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-hidden="true" />
      <label for="nav-toggle" class="nav-toggle-btn" aria-label="Zobraziť menu" aria-controls="hlavne-menu" aria-expanded="false">
        <span class="bar"></span><span class="bar"></span><span class="bar"></span>
      </label>

      <nav id="hlavne-menu" class="main-nav" aria-label="Hlavná navigácia">
        <ul class="menu-root">
          <li class="has-mega">
            <a href="/kategorie/proteiny" data-mega="proteiny">Proteíny</a>
            <input type="checkbox" id="mm-proteiny" class="mega-toggle" aria-hidden="true" />
            <label class="mega-caret" for="mm-proteiny" aria-label="Rozbaliť menu Proteíny"></label>
            <div class="mega" role="region" aria-label="Proteíny podmenu">
              <div class="mega-col">
                <h3>Typy</h3>
                <ul>
                  <li><a href="/kategorie/proteiny#srvatkove">Srvátkové WPC/WPI</a></li>
                  <li><a href="/kategorie/proteiny#rastlinne">Rastlinné</a></li>
                  <li><a href="/kategorie/proteiny#vegan">Vegan blend</a></li>
                </ul>
              </div>
              <div class="mega-col">
                <h3>Ciele</h3>
                <ul>
                  <li><a href="/kategorie/proteiny#chudnutie">Chudnutie</a></li>
                  <li><a href="/kategorie/proteiny#regeneracia">Regenerácia</a></li>
                  <li><a href="/kategorie/proteiny#rychly-snack">Rýchly snack</a></li>
                </ul>
              </div>
              <div class="mega-col">
                <h3>Money články</h3>
                <ul>
                  <li><a href="/clanky/najlepsie-proteiny-2025">Najlepšie proteíny 2025</a></li>
                  <li><a href="/clanky/protein-na-chudnutie">Proteín na chudnutie</a></li>
                  <li><a href="/clanky/srvatkovy-protein-vs-izolat-vs-hydro">WPC vs. izolát vs. hydro</a></li>
                </ul>
              </div>
            </div>
          </li>
          <li><a href="/kategorie/vyziva">Zdravá výživa</a></li>
          <li><a href="/kategorie/mineraly">Vitamíny a minerály</a></li>
          <li><a href="/kategorie/imunita">Imunita</a></li>
          <li><a href="/kategorie/sila">Sila a výkon</a></li>
          <li><a href="/kategorie/klby-koza">Kĺby a koža</a></li>
          <li><a href="/clanky/">Články</a></li>
          <li><a href="/kontakt">Kontakt</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <div id="megaTray" class="mega-tray" aria-hidden="true"></div>
  <main id="obsah">