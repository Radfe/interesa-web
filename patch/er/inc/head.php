<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/seo.php';
interessa_security_headers();

$uriNoQuery = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
$SEO = [
  'title'       => $page['title']       ?? 'Interessa.sk – nezávislé porovnania a testy',
  'description' => $page['description'] ?? 'Praktické porovnania doplnkov, návody a recenzie.',
  'canonical'   => site_url($uriNoQuery),
  'image'       => site_url('/assets/img/og-default.jpg'),
  'datePublished' => $page['datePublished'] ?? null,
  'dateModified'  => $page['dateModified']  ?? null,
  'faq'           => $page['faq'] ?? null
];
$noindex = !empty($page['noindex']);
?><!doctype html>
<html lang="sk">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?= interessa_seo_head($SEO) ?>
  <?php if ($noindex): ?><meta name="robots" content="noindex,nofollow"><?php endif; ?>
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32.png">
  <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/compat.css">
  <link rel="stylesheet" href="/assets/css/patch.css">
  <meta name="theme-color" content="#0ea5e9">
</head>
<body>
  <a class="skip-link" href="#obsah">Preskočiť na obsah</a>
  <header class="site-header">
    <div class="wrap header-inner">
      <a class="brand" href="/" aria-label="Domov – Interessa">
        <img src="/assets/img/logo-full.svg" alt="Interessa.sk" width="160" height="40" loading="eager" decoding="async">
      </a>
      <nav class="nav-inline" aria-label="Hlavná navigácia (desktop)">
        <a href="/">Domov</a>
        <a href="/kategorie/">Kategórie</a>
        <a href="/clanky/">Články</a>
        <a href="/kontakt.php">Kontakt</a>
      </nav>
      <button class="menu-toggle" data-nav-toggle aria-controls="site-nav" aria-expanded="false" aria-label="Menu">
        <span class="bar"></span><span class="bar"></span><span class="bar"></span>
      </button>
    </div>
  </header>
  <nav id="site-nav" class="nav" aria-label="Hlavná navigácia (mobil)">
    <a href="/">Domov</a>
    <a href="/kategorie/">Kategórie</a>
    <a href="/clanky/">Články</a>
    <a href="/kontakt.php">Kontakt</a>
    <form action="/search.php" method="get" class="search-form">
      <input type="search" name="q" placeholder="Hľadať články…" aria-label="Hľadať">
      <button type="submit">Hľadať</button>
    </form>
  </nav>
  <main id="obsah" class="site-main">
