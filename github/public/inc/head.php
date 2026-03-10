<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="sk">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= page_title() ?></title>
  <meta name="description" content="<?= page_description() ?>" />
  <meta name="robots" content="<?= esc(page_robots()) ?>" />
  <link rel="canonical" href="<?= esc(page_canonical()) ?>" />

  <meta property="og:type" content="<?= esc(page_og_type()) ?>" />
  <meta property="og:site_name" content="Interesa" />
  <meta property="og:url" content="<?= esc(page_canonical()) ?>" />
  <meta property="og:title" content="<?= page_title() ?>" />
  <meta property="og:description" content="<?= page_description() ?>" />
  <meta property="og:image" content="<?= esc(page_image_url()) ?>" />

  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?= page_title() ?>" />
  <meta name="twitter:description" content="<?= page_description() ?>" />
  <meta name="twitter:image" content="<?= esc(page_image_url()) ?>" />

  <link rel="icon" href="<?= asset('img/logo-full.svg') ?>" type="image/svg+xml" />
  <link rel="stylesheet" href="<?= asset('css/main.css') ?>" />
  <link rel="stylesheet" href="<?= asset('css/compat.css') ?>" />
  <link rel="stylesheet" href="<?= asset('css/sidebar.css') ?>" />
  <link rel="stylesheet" href="<?= asset('css/patch.css') ?>" />
  <link rel="stylesheet" href="<?= asset('css/home-b12.css') ?>" />
  <?= schema_script_tags() ?>
</head>
<body>
  <a class="skip-link" href="#obsah">Preskocit na obsah</a>

  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="/" aria-label="Domov">
        <img src="<?= asset('img/logo-full.svg') ?>" alt="Interesa.sk logo" width="148" height="32" />
      </a>

      <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-hidden="true" />
      <label for="nav-toggle" class="nav-toggle-btn" aria-label="Zobrazit menu" aria-controls="hlavne-menu" aria-expanded="false">
        <span class="bar"></span><span class="bar"></span><span class="bar"></span>
      </label>

      <nav id="hlavne-menu" class="main-nav" aria-label="Hlavna navigacia">
        <ul class="menu-root">
          <li class="has-mega">
            <a href="/kategorie/proteiny" data-mega="proteiny">Zdrave proteiny</a>
            <input type="checkbox" id="mm-proteiny" class="mega-toggle" aria-hidden="true" />
            <label class="mega-caret" for="mm-proteiny" aria-label="Rozbalit menu Zdrave proteiny"></label>
            <div class="mega" role="region" aria-label="Zdrave proteiny podmenu">
              <div class="mega-col">
                <h3>Typy</h3>
                <ul>
                  <li><a href="/kategorie/proteiny#srvate">Srvatkove (WPC/WPI)</a></li>
                  <li><a href="/kategorie/proteiny#rastlinne">Rastlinne</a></li>
                  <li><a href="/kategorie/proteiny#vegan">Vegan blend</a></li>
                </ul>
              </div>
              <div class="mega-col">
                <h3>Ciele</h3>
                <ul>
                  <li><a href="/kategorie/proteiny#chudnutie">Chudnutie</a></li>
                  <li><a href="/kategorie/proteiny#regeneracia">Regeneracia</a></li>
                  <li><a href="/kategorie/proteiny#rychly-snack">Rychly snack</a></li>
                </ul>
              </div>
              <div class="mega-col">
                <h3>Tipy a clanky</h3>
                <ul>
                  <li><a href="/clanky/">Poradna</a></li>
                  <li><a href="/clanky/#recepty">Recepty s proteinom</a></li>
                  <li><a href="/clanky/#najcastejsie-otazky">FAQ</a></li>
                </ul>
              </div>
            </div>
          </li>
          <li class="has-mega">
            <a href="/kategorie/vyziva" data-mega="vyziva">Zdrava vyziva</a>
            <input type="checkbox" id="mm-vyziva" class="mega-toggle" aria-hidden="true" />
            <label class="mega-caret" for="mm-vyziva" aria-label="Rozbalit menu Zdrava vyziva"></label>
            <div class="mega" role="region" aria-label="Zdrava vyziva podmenu">
              <div class="mega-col">
                <h3>Jedla a snacky</h3>
                <ul>
                  <li><a href="/kategorie/vyziva#granola">Granola a kase</a></li>
                  <li><a href="/kategorie/vyziva#orechy">Orechy a masla</a></li>
                  <li><a href="/kategorie/vyziva#tycinky">Tycinky</a></li>
                </ul>
              </div>
              <div class="mega-col">
                <h3>Specialne</h3>
                <ul>
                  <li><a href="/kategorie/vyziva#bezlepkove">Bezlepkove</a></li>
                  <li><a href="/kategorie/vyziva#bezlaktozy">Bez laktozy</a></li>
                  <li><a href="/kategorie/vyziva#keto">Keto</a></li>
                </ul>
              </div>
              <div class="mega-col">
                <h3>Nastroje</h3>
                <ul>
                  <li><a href="/clanky/#jedalnicky">Jedalnicky</a></li>
                  <li><a href="/clanky/#makra">Vypocet makier</a></li>
                  <li><a href="/clanky/#hydratacia">Hydratacia</a></li>
                </ul>
              </div>
            </div>
          </li>
          <li class="has-mega">
            <a href="/kategorie/mineraly" data-mega="mineraly">Vitaminy a mineraly</a>
            <input type="checkbox" id="mm-mineraly" class="mega-toggle" aria-hidden="true" />
            <label class="mega-caret" for="mm-mineraly" aria-label="Rozbalit menu Vitaminy a mineraly"></label>
            <div class="mega" role="region" aria-label="Vitaminy a mineraly podmenu">
              <div class="mega-col">
                <h3>Vitaminy</h3>
                <ul>
                  <li><a href="/kategorie/mineraly#vitamin-c">Vitamin C</a></li>
                  <li><a href="/kategorie/mineraly#vitamin-d3">Vitamin D3</a></li>
                  <li><a href="/kategorie/mineraly#b-komplex">B-komplex</a></li>
                </ul>
              </div>
              <div class="mega-col">
                <h3>Mineraly</h3>
                <ul>
                  <li><a href="/kategorie/mineraly#zinok">Zinok</a></li>
                  <li><a href="/kategorie/mineraly#horcik">Horcik</a></li>
                  <li><a href="/kategorie/mineraly#zelezo">Zelezo</a></li>
                </ul>
              </div>
              <div class="mega-col">
                <h3>Balicky</h3>
                <ul>
                  <li><a href="/kategorie/mineraly#imunita">Balicek imunita</a></li>
                  <li><a href="/kategorie/mineraly#energia">Balicek energia</a></li>
                  <li><a href="/kategorie/mineraly#wellbeing">Balicek wellbeing</a></li>
                </ul>
              </div>
            </div>
          </li>
          <li><a href="/kategorie/imunita">Imunita</a></li>
          <li><a href="/kategorie/sila">Sila</a></li>
          <li><a href="/kategorie/klby-koza">Klby a koza</a></li>
          <li><a href="/clanky/">Clanky</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <div id="megaTray" class="mega-tray" aria-hidden="true"></div>
  <main id="obsah">