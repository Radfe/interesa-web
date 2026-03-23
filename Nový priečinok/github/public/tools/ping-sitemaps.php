<?php
declare(strict_types=1);

/**
 * Ping sitemapy (Google/Bing).
 * Spúšťanie: php tools/ping-sitemaps.php
 * Odporúčané v CRONe krátko po build-e (napr. 09:05).
 */

$base = getenv('SITE_URL') ?: 'https://interessa.sk';
$maps = [
  $base.'/sitemap.xml',
  $base.'/sitemap-articles.xml',
  $base.'/sitemap-categories.xml',
  $base.'/sitemap-pages.xml',
];
$endpoints = [
  'https://www.google.com/ping?sitemap=',
  'https://www.bing.com/ping?sitemap='
];

foreach ($maps as $map) {
  foreach ($endpoints as $ep) {
    $url = $ep . urlencode($map);
    $ctx = stream_context_create(['http'=>['timeout'=>10, 'method'=>'GET']]);
    @file_get_contents($url, false, $ctx);
    echo '['.date('c')."] Ping $url\n";
  }
}
