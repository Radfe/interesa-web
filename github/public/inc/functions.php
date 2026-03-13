<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/media.php';

if (defined('INTERESA_FUNCS_V5')) { return; }
define('INTERESA_FUNCS_V5', 1);

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

if (!function_exists('raw_page_title')) {
    function raw_page_title(): string {
        global $page_title, $PAGE_TITLE, $page;
        return (string) ($page_title ?? $PAGE_TITLE ?? ($page['title'] ?? null) ?? 'Interesa');
    }
}

if (!function_exists('page_title')) {
    function page_title(): string {
        return esc(raw_page_title());
    }
}

if (!function_exists('raw_page_description')) {
    function raw_page_description(): string {
        global $page_description, $PAGE_DESCRIPTION, $page;
        return (string) ($page_description
            ?? $PAGE_DESCRIPTION
            ?? ($page['description'] ?? null)
            ?? 'Interesa je obsahový magazín o výžive, doplnkoch a zmysluplnom výbere produktov.');
    }
}

if (!function_exists('page_description')) {
    function page_description(): string {
        return esc(raw_page_description());
    }
}

if (!function_exists('request_scheme')) {
    function request_scheme(): string {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
            || (string) ($_SERVER['REQUEST_SCHEME'] ?? '') === 'https'
            || (int) ($_SERVER['SERVER_PORT'] ?? 80) === 443;

        return $https ? 'https' : 'http';
    }
}

if (!function_exists('base_url')) {
    function base_url(): string {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:5000');
        return request_scheme() . '://' . $host;
    }
}

if (!function_exists('absolute_url')) {
    function absolute_url(string $path = '/'): string {
        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        return rtrim(base_url(), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('current_path')) {
    function current_path(): string {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if ($path === '') {
            return '/';
        }

        return $path;
    }
}

if (!function_exists('canonical_url')) {
    function canonical_url(): string {
        global $page_canonical, $page;
        $candidate = $page_canonical ?? ($page['canonical'] ?? null) ?? current_path();
        return absolute_url(site_url((string) $candidate));
    }
}

if (!function_exists('page_robots')) {
    function page_robots(): string {
        global $page_robots, $page;
        return (string) ($page_robots ?? ($page['robots'] ?? null) ?? 'index,follow,max-image-preview:large');
    }
}

if (!function_exists('page_type')) {
    function page_type(): string {
        global $page_type;
        if (is_string($page_type) && $page_type !== '') {
            return $page_type;
        }

        $path = current_path();
        if ($path === '/') {
            return 'WebSite';
        }
        if (str_starts_with($path, '/clanky/')) {
            return 'Article';
        }
        if (str_starts_with($path, '/kategorie/')) {
            return 'CollectionPage';
        }

        return 'WebPage';
    }
}

if (!function_exists('page_image')) {
    function page_image(): string {
        global $page_image, $page;
        $candidate = (string) ($page_image ?? ($page['image'] ?? null) ?? asset('img/og-default.jpg'));
        return absolute_url($candidate);
    }
}

if (!function_exists('breadcrumb_items')) {
    function breadcrumb_items(): array {
        $path = trim(current_path(), '/');
        if ($path === '') {
            return [
                ['name' => 'Domov', 'url' => absolute_url('/')],
            ];
        }

        $items = [
            ['name' => 'Domov', 'url' => absolute_url('/')],
        ];

        $segments = explode('/', $path);
        $accumulator = '';
        foreach ($segments as $segment) {
            $accumulator .= '/' . $segment;
            $items[] = [
                'name' => humanize_slug($segment),
                'url' => absolute_url($accumulator),
            ];
        }

        return $items;
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
            'proteiny' => ['title' => 'Zdravé proteíny', 'description' => 'Srvátkové WPC/WPI, clear proteíny aj rastlinné varianty. Výber, dávkovanie a praktické odporúčania.'],
            'vyziva' => ['title' => 'Zdravá výživa', 'description' => 'Snacky, raňajky, orechy, kaše a funkčné potraviny pre lepší jedálniček bez balastu.'],
            'mineraly' => ['title' => 'Vitamíny a minerály', 'description' => 'Horčík, zinok, vitamín D3, vitamín C a ďalšie mikroživiny s dôrazom na použiteľné rady.'],
            'imunita' => ['title' => 'Imunita', 'description' => 'D3, vitamín C, zinok, probiotiká a ďalšie základy podpory imunity zrozumiteľne a vecne.'],
            'sila' => ['title' => 'Sila a výkon', 'description' => 'Kreatín, pre-workout, regenerácia a doplnky pre lepší výkon bez marketingového nánosu.'],
            'klby-koza' => ['title' => 'Kĺby a koža', 'description' => 'Kolagén, kĺbová výživa a doplnky pre pohybový aparát aj lepší vzhľad pokožky.'],
            'aminokyseliny' => ['title' => 'Aminokyseliny', 'description' => 'BCAA, EAA a aminokyseliny pre regeneráciu, tréning a orientáciu v zložení.'],
            'chudnutie' => ['title' => 'Chudnutie', 'description' => 'Proteíny na chudnutie, spaľovače tukov a realistické tipy pre redukciu tuku.'],
            'doplnkove-prislusenstvo' => ['title' => 'Doplnkové príslušenstvo', 'description' => 'Praktické príslušenstvo a pomocné doplnky, ktoré dávajú zmysel pri každodennom používaní.'],
            'kreatin' => ['title' => 'Kreatín', 'description' => 'Monohydrát, HCl, dávkovanie, nasýtenie a výber kreatínu bez mýtov.'],
            'pre-workout' => ['title' => 'Pre-workout', 'description' => 'Ako vybrať predtréningovku, kedy ju brať a na čo si dať pozor v zložení.'],
            'probiotika-travenie' => ['title' => 'Probiotiká a trávenie', 'description' => 'Probiotiká, trávenie a výber produktov podľa zloženia a reálnych potrieb.'],
        ];

        return $categories;
    }
}

if (!function_exists('category_icon')) {
    function category_icon(string $slug): string {
        $map = [
            'proteiny' => 'img/icons/proteiny.png',
            'vyziva' => 'img/icons/vyziva.png',
            'mineraly' => 'img/icons/vitaminy.png',
            'imunita' => 'img/icons/imunita.png',
            'sila' => 'img/icons/sila.png',
            'klby-koza' => 'img/icons/klby-koza.png',
        ];

        $normalized = normalize_category_slug($slug);
        return asset($map[$normalized] ?? 'img/og-default.jpg');
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
        $row = $articles[$slug] ?? [humanize_slug($slug), '', ''];

        return [
            'slug' => $slug,
            'title' => $row[0] ?? humanize_slug($slug),
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
        return article_media($slug)['card_image'];
    }
}
