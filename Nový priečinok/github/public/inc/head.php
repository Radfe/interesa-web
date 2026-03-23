<?php declare(strict_types=1); ?>
<?php
$interessaHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$interessaIsLocalDev = $interessaHost === ''
    || str_contains($interessaHost, '127.0.0.1')
    || str_contains($interessaHost, 'localhost');
if ($interessaIsLocalDev && !headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}
$brandIconImage = interessa_brand_image_meta('logo-icon', true);
$brandLogoImage = interessa_brand_image_meta('logo-full', false);
$brandIconSrc = (string) ($brandIconImage['src'] ?? asset('img/brand/logo-icon.svg'));
$brandIconExt = strtolower((string) pathinfo((string) (parse_url($brandIconSrc, PHP_URL_PATH) ?? ''), PATHINFO_EXTENSION));
$brandIconType = $brandIconExt === 'svg' ? 'image/svg+xml' : 'image/png';
$brandFaviconHref = is_file(dirname(__DIR__) . '/assets/img/brand/favicon-32.png') ? asset('img/brand/favicon-32.png') : $brandIconSrc;
$brandAppleTouchHref = is_file(dirname(__DIR__) . '/assets/img/brand/apple-touch-icon.png') ? asset('img/brand/apple-touch-icon.png') : $brandIconSrc;
?>
<?php require_once __DIR__ . '/navigation.php'; ?>
<!doctype html>
<html lang="sk">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= page_title() ?></title>
  <meta name="description" content="<?= page_description() ?>" />
  <meta name="robots" content="<?= esc(page_robots()) ?>" />
  <meta name="theme-color" content="#11a96d" />
  <meta name="color-scheme" content="light" />
  <link rel="canonical" href="<?= esc(page_canonical()) ?>" />

  <meta property="og:type" content="<?= esc(page_og_type()) ?>" />
  <meta property="og:site_name" content="Interesa" />
  <meta property="og:locale" content="sk_SK" />
  <meta property="og:url" content="<?= esc(page_canonical()) ?>" />
  <meta property="og:title" content="<?= page_title() ?>" />
  <meta property="og:description" content="<?= page_description() ?>" />
  <meta property="og:image" content="<?= esc(page_image_url()) ?>" />
  <meta property="og:image:alt" content="<?= page_title() ?>" />

  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?= page_title() ?>" />
  <meta name="twitter:description" content="<?= page_description() ?>" />
  <meta name="twitter:image" content="<?= esc(page_image_url()) ?>" />
  <meta name="twitter:image:alt" content="<?= page_title() ?>" />

  <link rel="shortcut icon" href="<?= esc($brandFaviconHref) ?>" type="image/png" />
  <link rel="icon" href="<?= esc($brandIconSrc) ?>" type="<?= esc($brandIconType) ?>" />
  <link rel="icon" href="<?= esc($brandFaviconHref) ?>" type="image/png" sizes="32x32" />
  <link rel="apple-touch-icon" href="<?= esc($brandAppleTouchHref) ?>" />
  <link rel="stylesheet" href="<?= asset('css/main.css') ?>" />
  <link rel="stylesheet" href="<?= asset('css/compat.css') ?>" />
  <link rel="stylesheet" href="<?= asset('css/sidebar.css') ?>" />
  <link rel="stylesheet" href="<?= asset('css/patch.css') ?>" />
  <?= stylesheet_tags(page_style_urls()) ?>
  <?= schema_script_tags() ?>
</head>
<body>
  <a class="skip-link" href="#obsah">Preskocit na obsah</a>

  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="/" aria-label="Domov">
        <?php if (is_array($brandLogoImage) && trim((string) ($brandLogoImage['src'] ?? '')) !== ''): ?>
          <?= interessa_render_image($brandLogoImage, ['alt' => 'Interesa.sk', 'class' => 'brand-logo']) ?>
        <?php else: ?>
          <?= interessa_render_image($brandIconImage, ['alt' => 'Interesa symbol', 'width' => '28', 'height' => '28', 'class' => 'brand-mark']) ?>
          <span class="brand-copy">
            <strong>Interesa.sk</strong>
            <span>Prakticke porovnania a navody pre vyzivu</span>
          </span>
        <?php endif; ?>
      </a>

      <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-hidden="true" />
      <label for="nav-toggle" class="nav-toggle-btn" aria-label="Zobrazit menu" aria-controls="hlavne-menu" aria-expanded="false">
        <span class="bar"></span><span class="bar"></span><span class="bar"></span>
      </label>

      <?= interessa_render_primary_navigation() ?>
    </div>
  </header>

  <div id="megaTray" class="mega-tray" aria-hidden="true"></div>
  <main id="obsah">
