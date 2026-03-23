<?php
declare(strict_types=1);

/**
 * Build sitemaps bez zmeny URL.
 * Spúšťanie: php tools/build-sitemaps.php
 */

$root = dirname(__DIR__);
$base = getenv('SITE_URL') ?: 'https://interessa.sk';

function esc($s) { return htmlspecialchars($s, ENT_XML1); }
function urlset($items): string {
    $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($items as $u) {
        $out .= '  <url><loc>' . esc($u['loc']) . '</loc>';
        if (!empty($u['lastmod'])) $out .= '<lastmod>' . esc($u['lastmod']) . '</lastmod>';
        $out .= "</url>\n";
    }
    $out .= '</urlset>';
    return $out;
}

// Pages
$pages = ['/', '/kontakt', '/o-nas', '/affiliate', '/zasady-ochrany-osobnych-udajov'];
$pages_items = array_map(fn($p) => ['loc' => $base . $p, 'lastmod' => date('c')], $pages);
file_put_contents($root . '/sitemap-pages.xml', urlset($pages_items));

// Categories (/kategorie/*.php)
$cat_items = [];
$catDir = $root . '/kategorie';
if (is_dir($catDir)) {
    foreach (glob($catDir . '/*.php') as $f) {
        $bn = basename($f);
        if ($bn === 'router.php' || $bn === 'index.php') continue;
        $slug = pathinfo($f, PATHINFO_FILENAME);
        $cat_items[] = ['loc' => $base . '/kategorie/' . $slug, 'lastmod' => date('c', filemtime($f))];
    }
}
file_put_contents($root . '/sitemap-categories.xml', urlset($cat_items));

// Articles z /clanky/*.php + /content/articles/*.html (ako /clanky/slug)
$art_items = [];
foreach (glob($root . '/clanky/*.php') as $f) {
    $bn = basename($f);
    if ($bn === 'index.php' || $bn === 'router.php') continue;
    $slug = pathinfo($f, PATHINFO_FILENAME);
    $art_items[] = ['loc' => $base . '/clanky/' . $slug, 'lastmod' => date('c', filemtime($f))];
}
foreach (glob($root . '/content/articles/*.html') as $f) {
    $slug = basename($f, '.html');
    $art_items[] = ['loc' => $base . '/clanky/' . $slug, 'lastmod' => date('c', filemtime($f))];
}
file_put_contents($root . '/sitemap-articles.xml', urlset($art_items));

// Sitemap index
$index = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$index .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach (['sitemap-pages.xml','sitemap-categories.xml','sitemap-articles.xml'] as $sf) {
    $index .= '  <sitemap><loc>' . esc($base . '/' . $sf) . '</loc><lastmod>' . esc(date('c', filemtime($root . '/' . $sf))) . "</lastmod></sitemap>\n";
}
$index .= '</sitemapindex>';
file_put_contents($root . '/sitemap.xml', $index);

echo "Sitemaps rebuilt.\n";
