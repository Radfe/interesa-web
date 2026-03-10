<?php declare(strict_types=1); ?>
<?php
$metaTitle = raw_page_title();
$metaDescription = raw_page_description();
$metaCanonical = canonical_url();
$metaImage = page_image();
$metaType = page_type();
$metaRobots = page_robots();
$searchQuery = trim((string) ($_GET['q'] ?? ''));
$pagePublished = isset($page_published) ? (string) $page_published : '';
$pageModified = isset($page_modified) ? (string) $page_modified : '';
$pageSection = isset($page_section) ? (string) $page_section : '';
$pageSchemaExtra = isset($page_schema_extra) && is_array($page_schema_extra) ? $page_schema_extra : [];

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
    $articleSchema = [
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

    if ($pagePublished !== '') {
        $articleSchema['datePublished'] = $pagePublished;
    }
    if ($pageModified !== '') {
        $articleSchema['dateModified'] = $pageModified;
    }
    if ($pageSection !== '') {
        $articleSchema['articleSection'] = $pageSection;
    }

    $schema[] = $articleSchema;
}

foreach ($pageSchemaExtra as $schemaExtra) {
    if (is_array($schemaExtra) && $schemaExtra !== []) {
        $schema[] = $schemaExtra;
    }
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
    <div class="container header-top">
      <a class="brand" href="/" aria-label="Domovská stránka Interesa">
        <img class="brand-icon" src="<?= asset('img/logo-icon.svg') ?>" alt="" width="44" height="44" aria-hidden="true" />
        <span class="brand-copy">
          <strong>Interesa</strong>
          <span>Výživa a doplnky bez chaosu</span>
        </span>
      </a>

      <div class="header-search-wrap">
        <form class="site-search" action="/search" method="get" role="search" aria-label="Vyhľadávanie na webe">
          <label class="sr-only" for="site-search-input">Hľadať články</label>
          <input id="site-search-input" class="search-input" type="search" name="q" value="<?= esc($searchQuery) ?>" placeholder="Hľadať články, recenzie a porovnania" />
          <button class="search-btn" type="submit">Hľadať</button>
        </form>
      </div>

      <a class="header-cta" href="/clanky/najlepsie-proteiny-2025">Top výbery</a>
      <label for="nav-toggle" class="nav-toggle-btn" aria-label="Zobraziť menu" aria-controls="hlavne-menu" aria-expanded="false">
        <span class="bar"></span><span class="bar"></span><span class="bar"></span>
      </label>
    </div>

    <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-hidden="true" />

    <div class="nav-shell">
      <div class="container nav-shell-inner">
        <nav id="hlavne-menu" class="main-nav" aria-label="Hlavná navigácia">
          <ul class="menu-root">
            <li><a href="/clanky/">Porovnania</a></li>
            <li><a href="/kategorie/proteiny">Proteíny</a></li>
            <li><a href="/kategorie/vyziva">Zdravá výživa</a></li>
            <li><a href="/kategorie/mineraly">Vitamíny</a></li>
            <li><a href="/kategorie/sila">Výkon</a></li>
            <li><a href="/kategorie/klby-koza">Kĺby</a></li>
            <li><a href="/kontakt">Kontakt</a></li>
          </ul>
        </nav>
      </div>
    </div>
  </header>

  <main id="obsah">