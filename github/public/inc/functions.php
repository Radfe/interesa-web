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
            'proteiny' => ['title' => 'Zdrave proteiny', 'description' => 'Srvatkove WPC/WPI, veganske a clear proteiny. Ako vybrat, davkovanie a najlepsie tipy.'],
            'vyziva' => ['title' => 'Zdrava vyziva', 'description' => 'Snacky, ranajky, zmysluplne zlozenie a prakticke tipy pre zdravu vyzivu.'],
            'mineraly' => ['title' => 'Vitaminy a mineraly', 'description' => 'Horcik, zinok, vitamin D3/C a dalsie mikro-ziviny. Ako sa zorientovat a co funguje.'],
            'imunita' => ['title' => 'Imunita', 'description' => 'Zaklady aj prakticke tipy pre podporu imunity: D3, C, zinok a probiotika.'],
            'sila' => ['title' => 'Sila a vykon', 'description' => 'Kreatin, pre-workout a regeneracia. Ako ich pouzivat a co vybrat.'],
            'klby-koza' => ['title' => 'Klby a koza', 'description' => 'Kolagen a klbova vyziva. Porovnania, recenzie a ako vybrat co funguje.'],
            'aminokyseliny' => ['title' => 'Aminokyseliny', 'description' => 'BCAA, EAA a aminokyseliny pre regeneraciu, vykon a trening.'],
            'chudnutie' => ['title' => 'Chudnutie', 'description' => 'Tipy na redukciu tuku, proteiny na chudnutie a realita okolo spalovacov.'],
            'doplnkove-prislusenstvo' => ['title' => 'Doplnkove prislusenstvo', 'description' => 'Pomocne doplnky, vybava a prakticke odporucania.'],
            'kreatin' => ['title' => 'Kreatin', 'description' => 'Monohydrat, HCl, davkovanie, nasycovanie a porovnanie kreatinov.'],
            'pre-workout' => ['title' => 'Pre-workout', 'description' => 'Ako vybrat predtreningovku, kedy ju brat a co sledovat v zlozeni.'],
            'probiotika-travenie' => ['title' => 'Probiotika a travenie', 'description' => 'Probiotika, travenie a ako si vybrat vhodny produkt.'],
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
        $dir = __DIR__ . '/../assets/img/articles/';
        foreach (['.webp', '.jpg', '.jpeg', '.png', '.svg'] as $ext) {
            if (is_file($dir . $slug . $ext)) {
                return asset('img/articles/' . $slug . $ext);
            }
        }

        return asset('img/placeholder-16x9.svg');
    }
}