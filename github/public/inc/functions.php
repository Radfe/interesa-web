<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/admin-store.php';
require_once __DIR__ . '/media.php';
require_once __DIR__ . '/functions-compat.php';

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
            ?? 'Interesa je obsahovÄ‚â€žĂ˘â‚¬ĹˇÄ‚ËĂ˘â€šÂ¬ÄąÄľĂ„â€šĂ‹ÂÄ‚ËĂ˘â‚¬ĹˇĂ‚Â¬Ă„Ä…Ă‹â€ˇÄ‚â€žĂ˘â‚¬ĹˇÄ‚ËĂ˘â€šÂ¬Ă„â€¦Ä‚â€žĂ„â€¦Ä‚â€žĂ˘â‚¬Ĺľ magazÄ‚â€žĂ˘â‚¬ĹˇÄ‚ËĂ˘â€šÂ¬ÄąÄľĂ„â€šĂ‹ÂÄ‚ËĂ˘â‚¬ĹˇĂ‚Â¬Ă„Ä…Ă‹â€ˇÄ‚â€žĂ˘â‚¬ĹˇÄ‚ËĂ˘â€šÂ¬ÄąË‡Ă„â€šĂ˘â‚¬ĹˇÄ‚â€šĂ‚Â­n o vÄ‚â€žĂ˘â‚¬ĹˇÄ‚ËĂ˘â€šÂ¬ÄąÄľĂ„â€šĂ‹ÂÄ‚ËĂ˘â‚¬ĹˇĂ‚Â¬Ă„Ä…Ă‹â€ˇÄ‚â€žĂ˘â‚¬ĹˇÄ‚ËĂ˘â€šÂ¬Ă„â€¦Ä‚â€žĂ„â€¦Ä‚â€žĂ˘â‚¬ĹľÄ‚â€žĂ˘â‚¬ĹˇÄ‚ËĂ˘â€šÂ¬ÄąÄľĂ„â€šĂ˘â‚¬ĹľÄ‚ËĂ˘â€šÂ¬Ă‚Â¦Ä‚â€žĂ˘â‚¬ĹˇÄ‚ËĂ˘â€šÂ¬ÄąÄľĂ„â€šĂ˘â‚¬ĹľÄ‚â€žĂ„Äľive, doplnkoch a zmysluplnom vÄ‚â€žĂ˘â‚¬ĹˇÄ‚ËĂ˘â€šÂ¬ÄąÄľĂ„â€šĂ‹ÂÄ‚ËĂ˘â‚¬ĹˇĂ‚Â¬Ă„Ä…Ă‹â€ˇÄ‚â€žĂ˘â‚¬ĹˇÄ‚ËĂ˘â€šÂ¬Ă„â€¦Ä‚â€žĂ„â€¦Ä‚â€žĂ˘â‚¬Ĺľbere produktov.');
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


if (!function_exists('page_canonical')) {
    function page_canonical(): string {
        return canonical_url();
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

if (!function_exists('interessa_pluralize_slovak')) {
    function interessa_pluralize_slovak(int $count, string $one, string $few, string $many): string {
        $abs = abs($count);
        if ($abs === 1) {
            return $one;
        }

        $mod100 = $abs % 100;
        $mod10 = $abs % 10;
        if ($mod10 >= 2 && $mod10 <= 4 && !($mod100 >= 12 && $mod100 <= 14)) {
            return $few;
        }

        return $many;
    }
}

if (!function_exists('interessa_article_format_slug')) {
    function interessa_article_format_slug(string $slug, string $title = ''): string {
        $haystack = strtolower(trim($slug . ' ' . $title));

        return match (true) {
            str_contains($haystack, 'porovnanie'),
            str_contains($haystack, 'vs ') => 'porovnanie',
            str_contains($haystack, 'recenzia') => 'recenzia',
            str_contains($haystack, 'top-vyber'),
            str_contains($haystack, 'top vyber'),
            str_contains($haystack, 'najlepsie') => 'top-vyber',
            str_contains($haystack, 'ako-vybrat'),
            str_contains($haystack, 'ako vybrat') => 'nakupny-navod',
            default => 'sprievodca',
        };
    }
}

if (!function_exists('interessa_article_format_map')) {
    function interessa_article_format_map(): array {
        return [
            'porovnanie' => 'Porovnanie',
            'recenzia' => 'Recenzia',
            'top-vyber' => 'Top vyber',
            'nakupny-navod' => 'Nakupny navod',
            'sprievodca' => 'Sprievodca',
        ];
    }
}

if (!function_exists('interessa_article_format_label')) {
    function interessa_article_format_label(string $slug, string $title = ''): string {
        $formatSlug = interessa_article_format_slug($slug, $title);
        $map = interessa_article_format_map();
        return $map[$formatSlug] ?? 'Sprievodca';
    }
}

if (!function_exists('interessa_article_cta_label')) {
    function interessa_article_cta_label(string $slug, string $title = ''): string {
        return match (interessa_article_format_slug($slug, $title)) {
            'porovnanie' => 'Pozriet porovnanie',
            'recenzia' => 'Otvorit recenziu',
            'top-vyber' => 'Pozriet vyber',
            'nakupny-navod' => 'Pozriet navod',
            default => 'Otvorit clanok',
        };
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
            'najlepsie-proteiny-2026' => 'proteiny',
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
            'veganske-proteiny-top-vyber-2026' => 'proteiny',
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
        $title = (string) ($row[0] ?? humanize_slug($slug));
        $description = (string) ($row[1] ?? '');

        if (function_exists('interessa_fix_mojibake')) {
            $title = interessa_fix_mojibake($title);
            $description = interessa_fix_mojibake($description);
        }

        return [
            'slug' => $slug,
            'title' => $title,
            'description' => $description,
            'category' => normalize_category_slug($row[2] ?? ''),
        ];
    }
}

if (!function_exists('interessa_article_seo_overrides')) {
    function interessa_article_seo_overrides(): array {
        return [
            'doplnky-vyzivy' => [
                'meta_title' => 'Doplnky vyzivy 2026 - co ma zmysel kupit a preco',
                'meta_description' => 'Prakticky vyber doplnkov vyzivy: kreatin, vitamin D3, magnezium, kolagen a dalsie zaklady podla ciela a rozpoctu.',
            ],
            'horcik-ktory-je-najlepsi-a-preco' => [
                'meta_title' => 'Horcik - ktory typ je najlepsi? Porovnanie foriem',
                'meta_description' => 'Porovnanie foriem horcika: bisglycinat, citrat, malat a oxid. Kedy sa ktory typ oplati a na co si dat pozor pri vybere.',
            ],
            'kolagen-recenzia' => [
                'meta_title' => 'Kolagen - recenzia a vyber podla typu a pouzitia',
                'meta_description' => 'Ako vybrat kolagen podla typu I, II a III, davky, gramaze a realneho pouzitia na klby, kozu a dlhodobu suplementaciu.',
            ],
            'kreatin-porovnanie' => [
                'meta_title' => 'Kreatin - porovnanie a vyber najlepsich foriem',
                'meta_description' => 'Prakticke porovnanie kreatinu: monohydrat, Creapure a HCl. Zisti, co dava najvacsi zmysel pre silu, vykon a cenu.',
            ],
            'najlepsi-protein-na-chudnutie-wpc-vs-wpi' => [
                'meta_title' => 'Najlepsi protein na chudnutie - WPC vs WPI a vyber',
                'meta_description' => 'WPC vs WPI pri chudnuti: kedy staci koncentrat, kedy sa oplati izolat a ktore proteiny davaju najlepsi zmysel v diete.',
            ],
            'najlepsie-proteiny-2025' => [
                'meta_title' => 'Najlepsie proteiny 2026 - vyber podla ciela a typu',
                'meta_description' => 'Vyber najlepsich proteinov 2026 podla ciela: objem, chudnutie, intolerancia laktozy, veganska alternativa a kazdodenne pouzitie.',
            ],
            'najlepsie-proteiny-2026' => [
                'meta_title' => 'Najlepsie proteiny 2026 - vyber podla ciela a typu',
                'meta_description' => 'Vyber najlepsich proteinov 2026 podla ciela: objem, chudnutie, intolerancia laktozy, veganska alternativa a kazdodenne pouzitie.',
            ],
            'protein-na-chudnutie' => [
                'meta_title' => 'Protein na chudnutie - ako vybrat a co funguje',
                'meta_description' => 'Ako vybrat protein na chudnutie: WPI, WPC alebo clear protein, davkovanie, kaloricky profil a prakticke odporucania.',
            ],
            'srvatkovy-protein-vs-izolat-vs-hydro' => [
                'meta_title' => 'Srvatkovy protein vs izolat vs hydro - co sa oplati',
                'meta_description' => 'WPC vs WPI vs hydrolyzat: rozdiely v laktoze, rychlosti vstrebavania, cene a tom, pre koho sa ktory typ proteinu oplati.',
            ],
            'veganske-proteiny-top-vyber-2025' => [
                'meta_title' => 'Veganske proteiny 2026 - top vyber a porovnanie',
                'meta_description' => 'Top vyber veganskych proteinov 2026: hrach, ryza, soja a blendy. Porovnanie chuti, zlozenia, ceny a pouzitia.',
            ],
            'veganske-proteiny-top-vyber-2026' => [
                'meta_title' => 'Veganske proteiny 2026 - top vyber a porovnanie',
                'meta_description' => 'Top vyber veganskych proteinov 2026: hrach, ryza, soja a blendy. Porovnanie chuti, zlozenia, ceny a pouzitia.',
            ],
        ];
    }
}

if (!function_exists('interessa_article_seo_meta')) {
    function interessa_article_seo_meta(string $slug): array {
        $meta = article_meta($slug);
        $overrides = interessa_article_seo_overrides();
        $override = $overrides[$slug] ?? $overrides[canonical_article_slug($slug)] ?? [];

        $metaTitle = trim((string) ($override['meta_title'] ?? $meta['title'] ?? ''));
        $metaDescription = trim((string) ($override['meta_description'] ?? $meta['description'] ?? ''));

        if ($metaDescription === '') {
            $metaDescription = interessa_article_card_description($slug, '', 28);
        }

        return [
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
        ];
    }
}

if (!function_exists('interessa_category_seo_meta')) {
    function interessa_category_seo_meta(string $slug): array {
        $slug = normalize_category_slug($slug);
        $meta = category_meta($slug) ?? ['title' => humanize_slug($slug), 'description' => ''];

        $overrides = [
            'proteiny' => [
                'meta_title' => 'Proteiny - porovnania, vyber a clanky | Interesa',
                'meta_description' => 'Tematicky hub pre proteiny: najlepsie proteiny, protein na chudnutie, WPC vs WPI a veganske alternativy.',
            ],
            'vyziva' => [
                'meta_title' => 'Zdrava vyziva - doplnky, probiotika a vyber | Interesa',
                'meta_description' => 'Prakticke clanky o zdravej vyzive, doplnkoch vyzivy, probiotikach a kazdodennych zakladoch bez marketingoveho balastu.',
            ],
            'mineraly' => [
                'meta_title' => 'Vitaminy a mineraly - vyber a porovnania | Interesa',
                'meta_description' => 'Hub pre vitaminy a mineraly: horcik, vitamin D3, vitamin C a zinok s fokusom na vyber, davku a realne pouzitie.',
            ],
            'imunita' => [
                'meta_title' => 'Imunita - vitaminy, doplnky a clanky | Interesa',
                'meta_description' => 'Prakticke clanky pre podporu imunity: vitamin D3, vitamin C, zinok, probiotika a dalsie doplnky s realnym zmyslom.',
            ],
            'sila' => [
                'meta_title' => 'Sila a vykon - kreatin, pre-workout a vyber | Interesa',
                'meta_description' => 'Tematicky hub pre silu a vykon: kreatin, pre-workout, regeneracia a clanky pre prakticky vyber doplnkov na trening.',
            ],
            'klby-koza' => [
                'meta_title' => 'Klby a koza - kolagen a doplnky | Interesa',
                'meta_description' => 'Clanky o kolagene, klbovej vyzive a doplnkoch pre pokozku a spojivove tkaniva s dorazom na realne pouzitie.',
            ],
        ];

        return $overrides[$slug] ?? [
            'meta_title' => trim((string) ($meta['title'] ?? humanize_slug($slug))) . ' | Interesa',
            'meta_description' => trim((string) ($meta['description'] ?? '')),
        ];
    }
}

if (!function_exists('interessa_commerce_shortlist_stats')) {
    function interessa_commerce_shortlist_stats(?array $commerce): ?array {
        $products = is_array($commerce['products'] ?? null) ? $commerce['products'] : [];
        if ($products === []) {
            return null;
        }

        if (!function_exists('interessa_resolve_product_reference')) {
            require_once __DIR__ . '/products.php';
        }

        $resolvedProducts = array_map(
            static fn(array $item): array => interessa_resolve_product_reference($item),
            $products
        );

        $merchantNames = array_values(array_unique(array_values(array_filter(array_map(
            static fn(array $item): string => trim((string) ($item['merchant'] ?? '')),
            $resolvedProducts
        )))));

        $realPackshotCount = count(array_filter($resolvedProducts, static function (array $item): bool {
            $mode = trim((string) ($item['image_mode'] ?? (($item['_image']['source_type'] ?? 'placeholder'))));
            return $mode === 'remote' || $mode === 'local';
        }));

        $catalogResolvedCount = count(array_filter($resolvedProducts, static function (array $item): bool {
            return interessa_product_catalog_resolved($item);
        }));

        $count = count($resolvedProducts);

        return [
            'count' => $count,
            'merchant_names' => $merchantNames,
            'merchant_count' => count($merchantNames),
            'real_packshots' => $realPackshotCount,
            'editorial_visuals' => max(0, $count - $realPackshotCount),
            'catalog_resolved' => $catalogResolvedCount,
        ];
    }
}

if (!function_exists('interessa_shortlist_coverage_percent')) {
    function interessa_shortlist_coverage_percent(?array $stats): int {
        $count = (int) ($stats['count'] ?? 0);
        $realPackshots = (int) ($stats['real_packshots'] ?? 0);
        if ($count <= 0) {
            return 0;
        }

        return (int) round(($realPackshots / $count) * 100);
    }
}

if (!function_exists('interessa_shortlist_coverage_state')) {
    function interessa_shortlist_coverage_state(?array $stats): string {
        $count = (int) ($stats['count'] ?? 0);
        $realPackshots = (int) ($stats['real_packshots'] ?? 0);

        if ($count <= 0 || $realPackshots <= 0) {
            return 'none';
        }
        if ($realPackshots >= $count) {
            return 'full';
        }

        return 'partial';
    }
}

if (!function_exists('interessa_shortlist_coverage_label')) {
    function interessa_shortlist_coverage_label(?array $stats): string {
        return match (interessa_shortlist_coverage_state($stats)) {
            'full' => 'kompletny vyber',
            'partial' => 'vyber produktov',
            default => 'obrazky este doplname',
        };
    }
}

if (!function_exists('interessa_article_category_stats')) {
    function interessa_article_category_stats(string $slug, ?string $categorySlug = null): ?array {
        $normalized = normalize_category_slug((string) ($categorySlug ?? ''));
        if ($normalized === '') {
            $meta = article_meta($slug);
            $normalized = normalize_category_slug((string) ($meta['category'] ?? ''));
        }

        if ($normalized === '') {
            return null;
        }

        $items = array_values(category_articles($normalized));
        if ($items === []) {
            return null;
        }

        $recentWindow = strtotime('-60 days');
        $recentCount = 0;
        foreach ($items as $item) {
            $itemSlug = trim((string) ($item['slug'] ?? ''));
            if ($itemSlug === '') {
                continue;
            }

            $articleFile = __DIR__ . '/../content/articles/' . $itemSlug . '.html';
            if (is_file($articleFile) && (int) @filemtime($articleFile) >= $recentWindow) {
                $recentCount++;
            }
        }

        $categoryMeta = category_meta($normalized);

        return [
            'slug' => $normalized,
            'title' => (string) ($categoryMeta['title'] ?? humanize_slug($normalized)),
            'count' => count($items),
            'recent_count' => $recentCount,
            'url' => category_url($normalized),
        ];
    }
}

if (!function_exists('interessa_trim_words')) {
    function interessa_trim_words(string $text, int $limit = 28): string {
        $text = trim(preg_replace('~\s+~u', ' ', strip_tags($text)) ?? $text);
        if ($text === '') {
            return '';
        }

        $words = preg_split('~\s+~u', $text) ?: [];
        if (count($words) <= $limit) {
            return $text;
        }

        $slice = array_slice($words, 0, $limit);
        return trim(implode(' ', $slice)) . '...';
    }
}

if (!function_exists('interessa_article_teaser_description')) {
    function interessa_article_teaser_description(string $slug): string {
        $meta = article_meta($slug);
        $description = trim((string) ($meta['description'] ?? ''));
        if ($description !== '') {
            return interessa_fix_mojibake($description);
        }

        $file = __DIR__ . '/../content/articles/' . $slug . '.html';
        if (!is_file($file)) {
            return '';
        }

        $html = interessa_fix_mojibake((string) file_get_contents($file));
        if (preg_match('~<p[^>]*>(.*?)</p>~isu', $html, $match)) {
            $text = interessa_trim_words(html_entity_decode(strip_tags((string) ($match[1] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($text !== '') {
                return $text;
            }
        }

        $text = interessa_trim_words(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return $text;
    }
}

if (!function_exists('interessa_article_card_description')) {
    function interessa_article_card_description(string $slug, string $fallback = '', int $limit = 22): string {
        $text = trim($fallback);
        if ($text === '') {
            $text = interessa_article_teaser_description($slug);
        }

        $text = interessa_fix_mojibake($text);
        return interessa_trim_words($text, $limit);
    }
}

if (!function_exists('category_meta')) {
    function category_meta(string $slug): ?array {
        $normalized = normalize_category_slug($slug);
        $categories = category_registry();
        if (!isset($categories[$normalized])) {
            return null;
        }

        $title = (string) ($categories[$normalized]['title'] ?? '');
        $description = (string) ($categories[$normalized]['description'] ?? '');
        if (function_exists('interessa_fix_mojibake')) {
            $title = interessa_fix_mojibake($title);
            $description = interessa_fix_mojibake($description);
        }

        return [
            'slug' => $normalized,
            'title' => $title,
            'description' => $description,
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
    function interessa_article_preferred_slug(string $slug): string {
        $slug = trim($slug);
        if ($slug === '') {
            return '';
        }

        $preferred = [
            'doplnky-vyzivy-top-vyber-2025' => 'doplnky-vyzivy',
            'najlepsie-proteiny-2025' => 'najlepsie-proteiny-2026',
            'najlepsie-proteiny-2026' => 'najlepsie-proteiny-2026',
            'veganske-proteiny-top' => 'veganske-proteiny-top-vyber-2026',
            'veganske-proteiny-top-vyber-2025' => 'veganske-proteiny-top-vyber-2026',
            'veganske-proteiny-top-vyber-2026' => 'veganske-proteiny-top-vyber-2026',
        ];

        return $preferred[$slug] ?? $slug;
    }
}

if (!function_exists('interessa_article_source_slug')) {
    function interessa_article_source_slug(string $slug): string {
        $slug = trim($slug);
        if ($slug === '') {
            return '';
        }

        $source = [
            'doplnky-vyzivy-top-vyber-2025' => 'doplnky-vyzivy',
            'najlepsie-proteiny-2026' => 'najlepsie-proteiny-2025',
            'veganske-proteiny-top-vyber-2026' => 'veganske-proteiny-top-vyber-2025',
        ];

        return $source[$slug] ?? canonical_article_slug($slug);
    }
}

if (!function_exists('article_url')) {
    function article_url(string $slug): string {
        return '/clanky/' . rawurlencode(interessa_article_preferred_slug($slug));
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
