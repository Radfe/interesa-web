<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="sk">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= page_title() ?></title>
  <meta name="description" content="<?= page_description() ?>" />

  <!-- Open Graph -->
  <meta property="og:type" content="website" />
  <meta property="og:title" content="<?= page_title() ?>" />
  <meta property="og:description" content="<?= page_description() ?>" />
  <meta property="og:image" content="<?= asset('img/og-default.jpg') ?>" />

  <link rel="icon" href="<?= asset('img/logo-full.svg') ?>" type="image/svg+xml" />
  <link rel="stylesheet" href="<?= asset('css/main.css') ?>" />
  <link rel="stylesheet" href="<?= asset('css/home-b12.css') ?>" />
</head>
<body>
  <a class="skip-link" href="#obsah">Preskočiť na obsah</a>

  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="/" aria-label="Domov">
        <img src="<?= asset('img/logo-full.svg') ?>" alt="Interesa.sk logo" width="148" height="32" />
      </a>

      <!-- Mobile nav toggle -->
      <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-hidden="true" />
      <label for="nav-toggle" class="nav-toggle-btn" aria-label="Zobraziť menu" aria-controls="hlavne-menu" aria-expanded="false">
        <span class="bar"></span><span class="bar"></span><span class="bar"></span>
      </label>

      <nav id="hlavne-menu" class="main-nav" aria-label="Hlavná navigácia">
        <ul class="menu-root">
          <!-- 1) Zdravé proteíny -->
          <li class="has-mega">
            <a href="/kategorie/proteiny" data-mega="proteiny">Zdravé proteíny</a>
            <!-- Obsah pre mobilný akordeón (na desktope sa skryje; obsah si berie globálny tray) -->
            <input type="checkbox" id="mm-proteiny" class="mega-toggle" aria-hidden="true" />
            <label class="mega-caret" for="mm-proteiny" aria-label="Rozbaliť menu Zdravé proteíny"></label>
            <div class="mega" role="region" aria-label="Zdravé proteíny podmenu">
              <div class="mega-col">
                <h3>Typy</h3>
                <ul>
                  <li><a href="/kategorie/proteiny#srvate">Srvátkové (WPC/WPI)</a></li>
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
                <h3>Tipy & články</h3>
                <ul>
                  <li><a href="/clanky/">Poradňa</a></li>
                  <li><a href="/clanky/#recepty">Recepty s proteínom</a></li>
                  <li><a href="/clanky/#najcastejsie-otazky">FAQ</a></li>
                </ul>
              </div>
            </div>
          </li>

          <!-- 2) Zdravá výživa -->
          <li class="has-mega">
            <a href="/kategorie/vyziva" data-mega="vyziva">Zdravá výživa</a>
            <input type="checkbox" id="mm-vyziva" class="mega-toggle" aria-hidden="true" />
            <label class="mega-caret" for="mm-vyziva" aria-label="Rozbaliť menu Zdravá výživa"></label>
            <div class="mega" role="region" aria-label="Zdravá výživa podmenu">
              <div class="mega-col">
                <h3>Jedlá & snacky</h3>
                <ul>
                  <li><a href="/kategorie/vyziva#granola">Granola & kaše</a></li>
                  <li><a href="/kategorie/vyziva#orechy">Orechy & maslá</a></li>
                  <li><a href="/kategorie/vyziva#tycinky">Tyčinky</a></li>
                </ul>
              </div>
              <div class="mega-col">
                <h3>Špeciálne</h3>
                <ul>
                  <li><a href="/kategorie/vyziva#bezlepkove">Bezlepkové</a></li>
                  <li><a href="/kategorie/vyziva#bezlaktozy">Bez laktózy</a></li>
                  <li><a href="/kategorie/vyziva#keto">Keto</a></li>
                </ul>
              </div>
              <div class="mega-col">
                <h3>Nástroje</h3>
                <ul>
                  <li><a href="/clanky/#jedalnicky">Jedálničky</a></li>
                  <li><a href="/clanky/#makra">Výpočet makier</a></li>
                  <li><a href="/clanky/#hydratacia">Hydratácia</a></li>
                </ul>
              </div>
            </div>
          </li>

          <!-- 3) Vitamíny & minerály -->
          <li class="has-mega">
            <a href="/kategorie/mineraly" data-mega="mineraly">Vitamíny &amp; minerály</a>
            <input type="checkbox" id="mm-mineraly" class="mega-toggle" aria-hidden="true" />
            <label class="mega-caret" for="mm-mineraly" aria-label="Rozbaliť menu Vitamíny & minerály"></label>
            <div class="mega" role="region" aria-label="Vitamíny a minerály podmenu">
              <div class="mega-col">
                <h3>Vitamíny</h3>
                <ul>
                  <li><a href="/kategorie/mineraly#vitamin-c">Vitamín C</a></li>
                  <li><a href="/kategorie/mineraly#vitamin-d3">Vitamín D3</a></li>
                  <li><a href="/kategorie/mineraly#b-komplex">B-komplex</a></li>
                </ul>
              </div>
              <div class="mega-col">
                <h3>Minerály</h3>
                <ul>
                  <li><a href="/kategorie/mineraly#zinok">Zinok</a></li>
                  <li><a href="/kategorie/mineraly#horcik">Horčík</a></li>
                  <li><a href="/kategorie/mineraly#zelezo">Železo</a></li>
                </ul>
              </div>
              <div class="mega-col">
                <h3>Balíčky</h3>
                <ul>
                  <li><a href="/kategorie/mineraly#imunita">Balíček imunita</a></li>
                  <li><a href="/kategorie/mineraly#energia">Balíček energia</a></li>
                  <li><a href="/kategorie/mineraly#wellbeing">Balíček wellbeing</a></li>
                </ul>
              </div>
            </div>
          </li>

          <!-- Simple links -->
          <li><a href="/kategorie/imunita">Imunita</a></li>
          <li><a href="/kategorie/sila">Sila</a></li>
          <li><a href="/kategorie/klby-koza">Kĺby &amp; koža</a></li>
          <li><a href="/clanky/">Články</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <!-- JEDINÝ desktop mega-panel (tray). Napĺňa sa dynamicky JS-om obsahom z .mega v príslušnej položke. -->
  <div id="megaTray" class="mega-tray" aria-hidden="true"></div>

  <main id="obsah">
