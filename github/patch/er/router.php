<?php
declare(strict_types=1);
/** Interessa.sk – central router (hotfix: normalize .php, add aliases) */
$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim($uri, '/');

$normalize = function(string $slug): string {
  $slug = trim($slug, '/');
  if (substr($slug, -4) === '.php') $slug = substr($slug, 0, -4);
  return $slug;
};

if (preg_match('~^/(sitemap(\-[a-z]+)?\.xml|robots\.txt)$~i', $path)) {
  $file = __DIR__ . $path;
  if (is_file($file)) {
    header('Content-Type: ' . (substr($path, -4) === '.xml' ? 'application/xml; charset=utf-8' : 'text/plain; charset=utf-8'));
    readfile($file); exit;
  }
}

if ($path === '/go' || strpos($path, '/go/') === 0) {
  if (!isset($_GET['c'])) {
    $parts = array_values(array_filter(explode('/', $path)));
    if ((count($parts) >= 2) && $parts[0] === 'go') $_GET['c'] = $parts[1];
  }
  require __DIR__ . '/go.php'; exit;
}

if ($path === '/' || $path === '/index.php') { require __DIR__ . '/index.php'; exit; }

if ($path === '/kategorie' || strpos($path, '/kategorie/') === 0) {
  $slug = $normalize(substr($path, strlen('/kategorie')));
  if ($slug === '') { require __DIR__ . '/kategorie/index.php'; exit; }
  $slug = trim($slug, '/');
  $aliases = ['probiotika' => 'probiotika-travenie'];
  if (isset($aliases[$slug])) $slug = $aliases[$slug];
  $php = __DIR__ . "/kategorie/{$slug}.php";
  if (is_file($php)) { require $php; exit; }
  $CATEGORY_SLUG = $slug;
  require __DIR__ . '/category-template.php'; exit;
}

if ($path === '/clanky' || strpos($path, '/clanky/') === 0) {
  $slug = $normalize(substr($path, strlen('/clanky')));
  if ($slug === '') { require __DIR__ . '/clanky/index.php'; exit; }
  $slug = trim($slug, '/');
  $php = __DIR__ . "/clanky/{$slug}.php";
  if (is_file($php)) { require $php; exit; }
  $html = __DIR__ . "/content/articles/{$slug}.html";
  if (is_file($html)) { $GLOBALS['__ARTICLE_CONTENT_FILE'] = $html; require __DIR__ . '/article-template.php'; exit; }
  http_response_code(404); require __DIR__ . '/404.php'; exit;
}

$short = ['affiliate','kontakt','o-nas','zasady-ochrany-osobnych-udajov'];
if (in_array(trim($path,'/'), $short, true)) {
  $php = __DIR__ . '/stranky/' . trim($path,'/') . '.php';
  if (is_file($php)) { require $php; exit; }
}

$direct = __DIR__ . $path;
if (is_file($direct) && preg_match('/\.php$/i', $direct)) { require $direct; exit; }

http_response_code(404); require __DIR__ . '/404.php';
