<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (defined('INTERESA_FUNCS_V4')) { return; }
define('INTERESA_FUNCS_V4', 1);

if (!function_exists('esc')) {
    function esc(null|string $value): string {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('site_url')) {
    function site_url(string $path = '/'): string {
        if ($path === '') {
            return '/';
        }

        $path = '/' . ltrim($path, '/');
        $path = preg_replace('~^/(clanky|kategorie)/([a-z0-9-]+)\.php$~i', '/$1/$2', $path) ?? $path;
        return $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string {
        $path = ltrim($path, '/');
        $file = __DIR__ . '/../assets/' . $path;
        $url = '/assets/' . $path;

        if (is_file($file)) {
            $sep = str_contains($url, '?') ? '&' : '?';
            return $url . $sep . 'v=' . filemtime($file);
        }

        return $url;
    }
}

if (!function_exists('page_title')) {
    function page_title(): string {
        global $page_title, $PAGE_TITLE, $page;
        $title = $page_title ?? $PAGE_TITLE ?? ($page['title'] ?? null) ?? 'Interesa';
        return esc($title);
    }
}

if (!function_exists('page_description')) {
    function page_description(): string {
        global $page_description, $PAGE_DESCRIPTION, $page;
        $description = $page_description
            ?? $PAGE_DESCRIPTION
            ?? ($page['description'] ?? null)
            ?? 'Interesa - magazin o zdravej vyzive. Testy, porovnania a navody.';
        return esc($description);
    }
}

if (!function_exists('cta_href')) {
    function cta_href(string $code): string {
        return '/go/' . rawurlencode($code);
    }
}

if (!function_exists('cta_attrs')) {
    function cta_attrs(): string {
        return 'rel="nofollow sponsored" target="_blank"';
    }
}

if (!function_exists('humanize_slug')) {
    function humanize_slug(string $slug): string {
        $title = str_replace(['-', '_'], ' ', trim($slug));
        $title = preg_replace('~\s+~', ' ', $title) ?? $title;

        if (function_exists('mb_convert_case')) {
            return mb_convert_case($title, MB_CASE_TITLE, 'UTF-8');
        }

        return ucwords($title);
    }
}

if (!function_exists('normalize_category_slug')) {
    function normalize_category_slug(?string $slug): string {
        $slug = strtolower(trim((string) $slug));
        if ($slug === '') {
            return '';
        }

        if (str_starts_with($slug, 'klby-koz')) {
            return 'klby-koza';
        }

        $aliases = [
            'probiotika' => 'probiotika-travenie',
            'vitaminy-mineraly' => 'mineraly',
            'klby-a-kolagen' => 'klby-koza',
        ];

        return $aliases[$slug] ?? $slug;
    }
}

if (!function_exists('category_registry')) {
    function category_registry(): array {
        static $categories = null;
        if (is_array($categories)) {
            return $categories;
        }

        $categories = [
            'proteiny' => ['title' => 'Zdravé proteíny', 'description' => 'Srvátkové WPC/WPI, vegánske aj clear proteíny. Ako vybrať, dávkovanie a najlepšie tipy.'],
            'vyziva' => ['title' => 'Zdravá výživa', 'description' => 'Snacky, raňajky, zmysluplné zloženie a praktické tipy pre zdravšiu výživu.'],
            'mineraly' => ['title' => 'Vitamíny a minerály', 'description' => 'Horčík, zinok, vitamín D3/C a ďalšie mikroživiny. Ako sa zorientovať a čo funguje.'],
            'imunita' => ['title' => 'Imunita', 'description' => 'Základy aj praktické tipy pre podporu imunity: D3, C, zinok a probiotiká.'],
            'sila' => ['title' => 'Sila a výkon', 'description' => 'Kreatín, pre-workout a regenerácia. Ako ich používať a čo vybrať.'],
            'klby-koza' => ['title' => 'Kĺby a koža', 'description' => 'Kolagén a kĺbová výživa. Porovnania, recenzie a ako vybrať to, čo funguje.'],
            'aminokyseliny' => ['title' => 'Aminokyseliny', 'description' => 'BCAA, EAA a aminokyseliny pre regeneráciu, výkon a tréning.'],
            'chudnutie' => ['title' => 'Chudnutie', 'description' => 'Tipy na redukciu tuku, proteíny na chudnutie a realita okolo spaľovačov.'],
            'doplnkove-prislusenstvo' => ['title' => 'Doplnkové príslušenstvo', 'description' => 'Pomocné doplnky, výbava a praktické odporúčania.'],
            'kreatin' => ['title' => 'Kreatín', 'description' => 'Monohydrát, HCl, dávkovanie, nasýcovanie a porovnanie kreatínov.'],
            'pre-workout' => ['title' => 'Pre-workout', 'description' => 'Ako vybrať predtréningovku, kedy ju brať a čo sledovať v zložení.'],
            'probiotika-travenie' => ['title' => 'Probiotiká a trávenie', 'description' => 'Probiotiká, trávenie a ako si vybrať vhodný produkt.'],
        ];

        return $categories;
    }
}

if (!function_exists('include_article_data_file')) {
    function include_article_data_file(string $file): array {
        if (!is_file($file)) {
            return [];
        }

        return (static function (string $includeFile): array {
            $ART = [];
            $result = include $includeFile;

            if (is_array($result)) {
                $ART = array_replace($ART, $result);
            }

            return is_array($ART) ? $ART : [];
        })($file);
    }
}

if (!function_exists('guess_article_category')) {
    function guess_article_category(string $slug): string {
        $map = [
            'aminokyseliny-bcaa-eaa' => 'aminokyseliny',
            'bcaa-vs-eaa' => 'aminokyseliny',
            'chudnutie-tip' => 'chudnutie',
            'clear-protein' => 'proteiny',
            'doplnky-vyzivy' => 'vyziva',
            'doplnky-vyzivy-top-vyber-2025' => 'vyziva',
            'horcik' => 'mineraly',
            'horcik-ktory-je-najlepsi-a-preco' => 'mineraly',
            'imunita-prirodne-latky-ktore-funguju' => 'imunita',
            'kedy-brat-kreatin-a-kolko' => 'kreatin',
            'klby-a-kolagen' => 'klby-koza',
            'kolagen' => 'klby-koza',
            'kolagen-na-klby-porovnanie' => 'klby-koza',
            'kolagen-recenzia' => 'klby-koza',
            'kreatin-monohydrat-vs-hcl' => 'kreatin',
            'kreatin-porovnanie' => 'kreatin',
            'kreatin-vedlajsie-ucinky-a-fakty' => 'kreatin',
            'najlepsi-protein-na-chudnutie-wpc-vs-wpi' => 'proteiny',
            'najlepsie-proteiny-2025' => 'proteiny',
            'pre-workout' => 'pre-workout',
            'pre-workout-ako-vybrat' => 'pre-workout',
            'probiotika-a-travenie' => 'probiotika-travenie',
            'probiotika-ako-vybrat' => 'probiotika-travenie',
            'protein-na-chudnutie' => 'proteiny',
            'proteiny' => 'proteiny',
            'proteiny-na-chudnutie' => 'proteiny',
            'spalovace-tukov-realita' => 'chudnutie',
            'srvatkovy-protein-vs-izolat-vs-hydro' => 'proteiny',
            'veganske-proteiny-top' => 'proteiny',
            'veganske-proteiny-top-vyber-2025' => 'proteiny',
            'vitamin-c' => 'mineraly',
            'vitamin-d3' => 'mineraly',
            'vitamin-d3-a-imunita' => 'mineraly',
            'zinek' => 'mineraly',
        ];

        return normalize_category_slug($map[$slug] ?? '');
    }
}

if (!function_exists('extract_article_title')) {
    function extract_article_title(string $file, string $slug): string {
        $html = @file_get_contents($file);
        if ($html !== false && preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $match)) {
            return trim(strip_tags($match[1]));
        }

        return humanize_slug($slug);
    }
}

if (!function_exists('article_registry')) {
    function article_registry(): array {
        static $articles = null;
        if (is_array($articles)) {
            return $articles;
        }

        $articles = [];
        foreach ([__DIR__ . '/articles.php', __DIR__ . '/articles_ext.php'] as $file) {
            $articles = array_replace($articles, include_article_data_file($file));
        }

        foreach (glob(__DIR__ . '/../content/articles/*.html') ?: [] as $file) {
            $slug = basename($file, '.html');
            $row = $articles[$slug] ?? [];
            $title = $row[0] ?? extract_article_title($file, $slug);
            $description = $row[1] ?? '';
            $category = normalize_category_slug($row[2] ?? guess_article_category($slug));
            $articles[$slug] = [$title, $description, $category];
        }

        ksort($articles);
        return $articles;
    }
}

if (!function_exists('article_meta')) {
    function article_meta(string $slug): array {
        $articles = article_registry();
        $aliases = [
            'proteiny-na-chudnutie' => 'protein-na-chudnutie',
            'veganske-proteiny-top' => 'veganske-proteiny-top-vyber-2025',
        ];
        $canonicalSlug = $aliases[$slug] ?? $slug;
        $row = $articles[$canonicalSlug] ?? $articles[$slug] ?? [humanize_slug($canonicalSlug), '', ''];

        return [
            'slug' => $slug,
            'title' => $row[0] ?? humanize_slug($canonicalSlug),
            'description' => $row[1] ?? '',
            'category' => normalize_category_slug($row[2] ?? ''),
        ];
    }
}

if (!function_exists('category_meta')) {
    function category_meta(string $slug): ?array {
        $normalized = normalize_category_slug($slug);
        $categories = category_registry();
        if (!isset($categories[$normalized])) {
            return null;
        }

        return [
            'slug' => $normalized,
            'title' => $categories[$normalized]['title'],
            'description' => $categories[$normalized]['description'],
        ];
    }
}

if (!function_exists('category_articles')) {
    function category_articles(string $slug): array {
        $normalized = normalize_category_slug($slug);
        $items = [];

        foreach (article_registry() as $articleSlug => $row) {
            $articleCategory = normalize_category_slug($row[2] ?? '');
            if ($articleCategory !== $normalized) {
                continue;
            }

            $items[$articleSlug] = [
                'slug' => $articleSlug,
                'title' => $row[0] ?? humanize_slug($articleSlug),
                'description' => $row[1] ?? '',
                'category' => $articleCategory,
            ];
        }

        return $items;
    }
}

if (!function_exists('article_url')) {
    function article_url(string $slug): string {
        return '/clanky/' . rawurlencode($slug);
    }
}

if (!function_exists('category_url')) {
    function category_url(string $slug): string {
        return '/kategorie/' . rawurlencode(normalize_category_slug($slug));
    }
}

if (!function_exists('article_img')) {
    function article_img(string $slug): string {
        $dir = __DIR__ . '/../assets/img/articles/';
        foreach (['.webp', '.jpg', '.jpeg', '.png', '.svg'] as $ext) {
            if (is_file($dir . $slug . $ext)) {
                return asset('img/articles/' . $slug . $ext);
            }
        }

        $image = interessa_article_image_meta($slug, 'thumb', true);
        return $image['src'] ?? asset('img/placeholders/article-16x9.svg');
    }
}
if (!function_exists('request_path')) {
    function request_path(): string {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        return $path === '' ? '/' : $path;
    }
}

if (!function_exists('site_base_url')) {
    function site_base_url(): string {
        $scheme = 'http';
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $scheme = 'https';
        } elseif ((string) ($_SERVER['REQUEST_SCHEME'] ?? '') === 'https') {
            $scheme = 'https';
        } elseif ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            $scheme = 'https';
        }

        $host = (string) ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:5000');
        return $scheme . '://' . $host;
    }
}

if (!function_exists('absolute_url')) {
    function absolute_url(string $path = '/'): string {
        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        return rtrim(site_base_url(), '/') . site_url($path);
    }
}

if (!function_exists('page_canonical')) {
    function page_canonical(): string {
        global $page_canonical, $PAGE_CANONICAL;
        $path = (string) ($page_canonical ?? $PAGE_CANONICAL ?? request_path());
        return absolute_url($path);
    }
}

if (!function_exists('page_image_url')) {
    function page_image_url(): string {
        global $page_image, $PAGE_IMAGE;
        $image = (string) ($page_image ?? $PAGE_IMAGE ?? asset('img/brand/og-default.svg'));
        return absolute_url($image);
    }
}

if (!function_exists('page_robots')) {
    function page_robots(): string {
        global $page_robots, $PAGE_ROBOTS;
        return (string) ($page_robots ?? $PAGE_ROBOTS ?? 'index,follow');
    }
}

if (!function_exists('page_og_type')) {
    function page_og_type(): string {
        global $page_og_type, $PAGE_OG_TYPE;
        return (string) ($page_og_type ?? $PAGE_OG_TYPE ?? 'website');
    }
}

if (!function_exists('page_schema_nodes')) {
    function page_schema_nodes(): array {
        global $page_schema, $PAGE_SCHEMA;
        $schema = $page_schema ?? $PAGE_SCHEMA ?? [];
        if ($schema === []) {
            return [];
        }

        if (isset($schema['@type'])) {
            return [$schema];
        }

        return array_values(array_filter(is_array($schema) ? $schema : [], 'is_array'));
    }
}

if (!function_exists('schema_script_tags')) {
    function schema_script_tags(): string {
        $chunks = [];
        foreach (page_schema_nodes() as $node) {
            if (!isset($node['@context'])) {
                $node = ['@context' => 'https://schema.org'] + $node;
            }

            $json = json_encode($node, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($json)) {
                $chunks[] = '<script type="application/ld+json">' . $json . '</script>';
            }
        }

        return implode("\n", $chunks);
    }
}

if (!function_exists('breadcrumb_schema')) {
    function breadcrumb_schema(array $items): array {
        $list = [];
        $position = 1;

        foreach ($items as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            if ($name === '' || $url === '') {
                continue;
            }

            $list[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $name,
                'item' => absolute_url($url),
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }
}
if (!function_exists('interesa_lowercase')) {
    function interessa_lowercase(string $value): string {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }

        return strtolower($value);
    }
}

if (!function_exists('interesa_contains')) {
    function interessa_contains(string $haystack, string $needle): bool {
        if ($needle === '') {
            return false;
        }

        return strpos(interessa_lowercase($haystack), interessa_lowercase($needle)) !== false;
    }
}
if (!function_exists('page_style_urls')) {
    function page_style_urls(): array {
        global $page_styles, $PAGE_STYLES;
        $styles = $page_styles ?? $PAGE_STYLES ?? [];
        return array_values(array_filter(is_array($styles) ? $styles : [], 'is_string'));
    }
}

if (!function_exists('page_script_urls')) {
    function page_script_urls(): array {
        global $page_scripts, $PAGE_SCRIPTS;
        $scripts = $page_scripts ?? $PAGE_SCRIPTS ?? [];
        return array_values(array_filter(is_array($scripts) ? $scripts : [], 'is_string'));
    }
}

if (!function_exists('stylesheet_tags')) {
    function stylesheet_tags(array $styles): string {
        $tags = [];
        foreach ($styles as $href) {
            $href = trim($href);
            if ($href === '') {
                continue;
            }

            $tags[] = '<link rel="stylesheet" href="' . esc($href) . '" />';
        }

        return implode("\n  ", $tags);
    }
}

if (!function_exists('script_tags')) {
    function script_tags(array $scripts): string {
        $tags = [];
        foreach ($scripts as $src) {
            $src = trim($src);
            if ($src === '') {
                continue;
            }

            $tags[] = '<script src="' . esc($src) . '" defer></script>';
        }

        return implode("\n", $tags);
    }
}
require_once __DIR__ . '/media.php';