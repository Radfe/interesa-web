<?php declare(strict_types=1); ?>
<?php require_once __DIR__ . '/navigation.php'; ?>
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

  <link rel="icon" href="<?= asset('img/brand/logo-icon.svg') ?>" type="image/svg+xml" />
  <link rel="stylesheet" href="<?= asset('css/main.css') ?>" />
  <link rel="stylesheet" href="<?= asset('css/compat.css') ?>" />
  <link rel="stylesheet" href="<?= asset('css/sidebar.css') ?>" />
  <link rel="stylesheet" href="<?= asset('css/patch.css') ?>" />
  <?= stylesheet_tags(page_style_urls()) ?>
  <?= schema_script_tags() ?>
</head>
<body>
  <a class="skip-link" href="#obsah">Preskočiť na obsah</a>

  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="/" aria-label="Domov">
        <?= interessa_render_image(interessa_brand_image_meta('logo-full'), ['alt' => 'Interesa.sk logo', 'width' => '148', 'height' => '32']) ?>
      </a>

      <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-hidden="true" />
      <label for="nav-toggle" class="nav-toggle-btn" aria-label="Zobraziť menu" aria-controls="hlavne-menu" aria-expanded="false">
        <span class="bar"></span><span class="bar"></span><span class="bar"></span>
      </label>

      <?= interessa_render_primary_navigation() ?>
    </div>
  </header>

  <div id="megaTray" class="mega-tray" aria-hidden="true"></div>
  <main id="obsah">