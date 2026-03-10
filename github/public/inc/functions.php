<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (defined('INTERESA_FUNCS_V7')) { return; }
define('INTERESA_FUNCS_V7', 1);

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
            'vitaminy-a-mineraly' => 'mineraly',
            'klby-a-kolagen' => 'klby-koza',
            'klby-a-koza' => 'klby-koza',
            'vykon' => 'sila',
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

if (!function_exists('breadcrumb_label')) {
    function breadcrumb_label(array $segments, int $index): string {
        $segment = $segments[$index] ?? '';

        if ($index === 0) {
            return match ($segment) {
                'clanky' => 'Články',
                'kategorie' => 'Kategórie',
                default => humanize_slug($segment),
            };
        }

        $parent = $segments[$index - 1] ?? '';
        if ($parent === 'kategorie') {
            return category_meta($segment)['title'] ?? humanize_slug($segment);
        }

        if ($parent === 'clanky') {
            return article_meta($segment)['title'] ?? humanize_slug($segment);
        }

        return humanize_slug($segment);
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

        $segments = array_values(array_filter(explode('/', $path)));
        $accumulator = '';
        foreach ($segments as $index => $segment) {
            $accumulator .= '/' . $segment;
            $items[] = [
                'name' => breadcrumb_label($segments, $index),
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

if (!function_exists('article_file')) {
    function article_file(string $slug): string {
        return __DIR__ . '/../content/articles/' . $slug . '.html';
    }
}

if (!function_exists('clean_excerpt_text')) {
    function clean_excerpt_text(string $html): string {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\xc2\xa0", '&nbsp;'], ' ', $text);
        return trim(preg_replace('~\s+~u', ' ', $text) ?? $text);
    }
}

if (!function_exists('trim_excerpt')) {
    function trim_excerpt(string $text, int $limit = 170): string {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') <= $limit) {
                return $text;
            }

            $snippet = mb_substr($text, 0, $limit + 1, 'UTF-8');
            $snippet = preg_replace('~\s+\S*$~u', '', $snippet) ?? $snippet;
            return rtrim($snippet, " \t\n\r\0\x0B,.;:-") . '…';
        }

        if (strlen($text) <= $limit) {
            return $text;
        }

        $snippet = substr($text, 0, $limit + 1);
        $snippet = preg_replace('~\s+\S*$~', '', $snippet) ?? $snippet;
        return rtrim($snippet, " \t\n\r\0\x0B,.;:-") . '...';
    }
}

if (!function_exists('extract_article_title')) {
    function extract_article_title(string $file, string $slug): string {
        $html = @file_get_contents($file);
        if ($html !== false && preg_match('/<h1[^>]*>(.*?)<\/h1>/isu', $html, $match)) {
            $title = clean_excerpt_text($match[1]);
            if ($title !== '') {
                return $title;
            }
        }

        return humanize_slug($slug);
    }
}

if (!function_exists('extract_article_description')) {
    function extract_article_description(string $file): string {
        $html = @file_get_contents($file);
        if ($html === false || trim($html) === '') {
            return '';
        }

        $patterns = [
            '/<p[^>]*class=(?:"[^"]*\bperex\b[^"]*"|\'[^\']*\bperex\b[^\']*\')[^>]*>(.*?)<\/p>/isu',
            '/<p[^>]*>(.*?)<\/p>/isu',
        ];

        foreach ($patterns as $pattern) {
            if (!preg_match_all($pattern, $html, $matches)) {
                continue;
            }

            foreach ($matches[1] as $fragment) {
                $text = clean_excerpt_text($fragment);
                if ($text === '') {
                    continue;
                }

                $lower = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
                if (str_contains($lower, 'informácie slúžia') || str_contains($lower, 'nenahrádzajú odporúčanie')) {
                    continue;
                }

                return trim_excerpt($text, 170);
            }
        }

        return '';
    }
}

if (!function_exists('go_link_map')) {
    function go_link_map(): array {
        static $map = null;
        if (is_array($map)) {
            return $map;
        }

        $map = [];
        $file = __DIR__ . '/go-links.php';
        if (!is_file($file)) {
            return $map;
        }

        require_once $file;
        if (function_exists('go_links')) {
            $loaded = go_links();
            if (is_array($loaded)) {
                foreach ($loaded as $code => $url) {
                    if (is_string($url) && trim($url) !== '') {
                        $map[(string) $code] = $url;
                    }
                }
            }
        }

        return $map;
    }
}

if (!function_exists('go_link_available')) {
    function go_link_available(string $code): bool {
        $map = go_link_map();
        return isset($map[$code]);
    }
}

if (!function_exists('article_body_html')) {
    function article_body_html(string $slug): string {
        $file = article_file($slug);
        if (!is_file($file)) {
            return '<p>Obsah pre túto tému ešte pripravujeme.</p>';
        }

        $html = (string) file_get_contents($file);
        if (trim($html) === '') {
            return '<p>Obsah pre túto tému ešte pripravujeme.</p>';
        }

        $html = preg_replace('/^\s*<h1\b[^>]*>.*?<\/h1>\s*/isu', '', $html, 1) ?? $html;
        $html = preg_replace('~href=(["\'])/(clanky|kategorie)/([a-z0-9-]+)\.php((?:[#?][^"\']*)?)\1~i', 'href=$1/$2/$3$4$1', $html) ?? $html;
        $html = preg_replace('~href=(["\'])/(kontakt|affiliate|o-nas|zasady-ochrany-osobnych-udajov)\.php((?:[#?][^"\']*)?)\1~i', 'href=$1/$2$3$1', $html) ?? $html;
        $html = preg_replace_callback(
            '~<a\b([^>]*?)href=(["\'])/go/([A-Za-z0-9_-]+)\2([^>]*)>(.*?)<\/a>~isu',
            static function (array $match): string {
                $code = (string) ($match[3] ?? '');
                if ($code !== '' && go_link_available($code)) {
                    return $match[0];
                }

                return '<span class="pending-affiliate-link" title="Affiliate odkaz doplníme po overení ponuky">' . ($match[5] ?? '') . '</span>';
            },
            $html
        ) ?? $html;

        return trim($html);
    }
}
if (!function_exists('article_mtime')) {
    function article_mtime(string $slug): int {
        $file = article_file($slug);
        if (!is_file($file)) {
            return 0;
        }

        return (int) (filemtime($file) ?: 0);
    }
}

if (!function_exists('article_word_count')) {
    function article_word_count(string $slug): int {
        $text = clean_excerpt_text(article_body_html($slug));
        if ($text === '') {
            return 0;
        }

        if (preg_match_all('/[\p{L}\p{N}]+(?:[-\/][\p{L}\p{N}]+)*/u', $text, $matches)) {
            return count($matches[0]);
        }

        return str_word_count($text);
    }
}

if (!function_exists('article_reading_time')) {
    function article_reading_time(string $slug): int {
        $words = article_word_count($slug);
        if ($words <= 0) {
            return 1;
        }

        return max(1, (int) ceil($words / 180));
    }
}

if (!function_exists('article_outline')) {
    function article_outline(string $slug, int $limit = 6): array {
        $html = article_body_html($slug);
        if ($html === '') {
            return [];
        }

        preg_match_all('/<(h2|h3)\b([^>]*)>(.*?)<\/\\1>/isu', $html, $matches, PREG_SET_ORDER);
        $items = [];
        foreach ($matches as $match) {
            $text = clean_excerpt_text($match[3] ?? '');
            if ($text === '') {
                continue;
            }

            $lower = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
            if (in_array($lower, ['súvisiace', 'súvisiace články', 'často kladené otázky', 'faq'], true)) {
                continue;
            }

            $id = '';
            if (preg_match('/\bid=(["\'])(.*?)\\1/isu', $match[2] ?? '', $idMatch)) {
                $id = trim((string) ($idMatch[2] ?? ''));
            }

            $items[] = [
                'level' => strtolower((string) ($match[1] ?? 'h2')),
                'text' => $text,
                'id' => $id,
            ];

            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }
}

if (!function_exists('article_faq_items')) {
    function article_faq_items(string $slug): array {
        $html = article_body_html($slug);
        if ($html === '') {
            return [];
        }

        preg_match_all('/<details\b[^>]*>\s*<summary[^>]*>(.*?)<\/summary>(.*?)<\/details>/isu', $html, $matches, PREG_SET_ORDER);
        $items = [];
        foreach ($matches as $match) {
            $question = clean_excerpt_text($match[1] ?? '');
            $answer = clean_excerpt_text($match[2] ?? '');
            if ($question === '' || $answer === '') {
                continue;
            }

            $items[] = [
                'question' => $question,
                'answer' => trim_excerpt($answer, 320),
            ];
        }

        return array_slice($items, 0, 8);
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
            $title = trim((string) ($row[0] ?? ''));
            $description = trim((string) ($row[1] ?? ''));
            $category = normalize_category_slug((string) ($row[2] ?? guess_article_category($slug)));

            if ($title === '') {
                $title = extract_article_title($file, $slug);
            }

            if ($description === '') {
                $description = extract_article_description($file);
            }

            if ($category === '') {
                $category = guess_article_category($slug);
            }

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
        $description = trim((string) ($row[1] ?? ''));
        if ($description === '') {
            $description = extract_article_description(article_file($slug));
        }

        return [
            'slug' => $slug,
            'title' => trim((string) ($row[0] ?? humanize_slug($slug))) ?: humanize_slug($slug),
            'description' => $description,
            'category' => normalize_category_slug((string) ($row[2] ?? '')),
            'url' => article_url($slug),
            'image' => article_img($slug),
            'mtime' => article_mtime($slug),
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
            $articleCategory = normalize_category_slug((string) ($row[2] ?? ''));
            if ($articleCategory !== $normalized) {
                continue;
            }

            $description = trim((string) ($row[1] ?? ''));
            if ($description === '') {
                $description = extract_article_description(article_file($articleSlug));
            }

            $items[] = [
                'slug' => $articleSlug,
                'title' => trim((string) ($row[0] ?? humanize_slug($articleSlug))) ?: humanize_slug($articleSlug),
                'description' => $description,
                'category' => $articleCategory,
                'url' => article_url($articleSlug),
                'image' => article_img($articleSlug),
                'mtime' => article_mtime($articleSlug),
            ];
        }

        usort($items, static function (array $a, array $b): int {
            $timeCompare = ($b['mtime'] ?? 0) <=> ($a['mtime'] ?? 0);
            if ($timeCompare !== 0) {
                return $timeCompare;
            }

            return strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
        });

        return $items;
    }
}

if (!function_exists('category_hub_registry')) {
    function category_hub_registry(): array {
        return [
            'proteiny' => [
                'eyebrow' => 'Obsahový hub',
                'intro' => 'Ak riešiš proteín prvýkrát, najskôr si ujasni cieľ: chudnutie, doplnenie bielkovín, citlivejšie trávenie alebo rastlinná alternatíva. Až potom má zmysel porovnávať konkrétne typy a značky.',
                'questions' => [
                    'Kedy stačí obyčajný WPC a kedy sa oplatí izolát?',
                    'Aký proteín sa hodí pri chudnutí a ktorý skôr na bežné doplnenie bielkovín?',
                    'Má clear protein alebo rastlinný proteín reálne výhody, alebo je to len preferencia?',
                ],
                'featured' => ['najlepsie-proteiny-2025', 'srvatkovy-protein-vs-izolat-vs-hydro', 'protein-na-chudnutie'],
                'related' => ['chudnutie', 'vyziva', 'aminokyseliny'],
            ],
            'vyziva' => [
                'eyebrow' => 'Začni základom',
                'intro' => 'Táto kategória je pre ľudí, ktorí nechcú len jeden doplnok, ale chcú pochopiť, čo z celého sveta výživy naozaj dáva zmysel v bežnom živote.',
                'questions' => [
                    'Ktoré doplnky majú zmysel ako rozumný základ a ktoré nie?',
                    'Ako odlíšiť praktický produkt od marketingového balastu?',
                    'Kde sa oplatí začať, ak nechceš nakúpiť desať vecí naraz?',
                ],
                'featured' => ['doplnky-vyzivy'],
                'related' => ['mineraly', 'imunita', 'proteiny'],
            ],
            'mineraly' => [
                'eyebrow' => 'Mikroživiny bez chaosu',
                'intro' => 'Minerály a vitamíny sú typická téma, kde obal a čísla často prehlušia to podstatné. V tejto kategórii nájdeš praktické články, ktoré vysvetľujú formy, dávkovanie a typické chyby pri výbere.',
                'questions' => [
                    'Ktorá forma horčíka je vhodná na spánok a ktorá skôr na deň?',
                    'Koľko D3 alebo zinku býva praktické a kedy netreba preháňať?',
                    'Na ktoré formy a zloženie sa oplatí pozerať skôr než na veľké čísla na etikete?',
                ],
                'featured' => ['horcik-ktory-je-najlepsi-a-preco', 'vitamin-d3', 'zinek'],
                'related' => ['imunita', 'vyziva', 'klby-koza'],
            ],
            'imunita' => [
                'eyebrow' => 'Dlhodobý základ',
                'intro' => 'Pri imunite dáva väčší zmysel dlhodobý základ a rozumný výber látok než rýchle „boostre“. Táto kategória prepája D3, zinok, vitamín C aj probiotiká do jedného zrozumiteľného celku.',
                'questions' => [
                    'Čo má pri imunite reálny zmysel a čo je skôr reklama?',
                    'Kedy riešiť D3, zinok alebo probiotiká a kedy stačí upraviť základy?',
                    'Ako sa pozerať na doplnky pre obranyschopnosť bez prehnaných očakávaní?',
                ],
                'featured' => ['imunita-prirodne-latky-ktore-funguju', 'vitamin-d3', 'zinek'],
                'related' => ['mineraly', 'probiotika-travenie', 'vyziva'],
            ],
            'klby-koza' => [
                'eyebrow' => 'Kĺby, pokožka a regenerácia',
                'intro' => 'Ak riešiš kolagén, kĺby alebo pokožku, dôležité je najprv rozlíšiť cieľ. Iné produkty sa hodia na kĺby a iné na vzhľad pokožky či vlasov.',
                'questions' => [
                    'Aký je rozdiel medzi typmi kolagénu I, II a III?',
                    'Kedy má zmysel produkt na kĺby a kedy skôr hydrolyzovaný kolagén?',
                    'Na ktoré dávky a typy sa oplatí pozerať pred kúpou?',
                ],
                'featured' => ['kolagen-recenzia', 'kolagen-na-klby-porovnanie', 'klby-a-kolagen'],
                'related' => ['mineraly', 'vyziva', 'imunita'],
            ],
            'kreatin' => [
                'eyebrow' => 'Výkon bez mýtov',
                'intro' => 'Kreatín je jedna z najpraktickejších tém na webe, lebo práve tu sa dá veľmi ľahko preplatiť. Stačí vedieť, kedy sa oplatí monohydrát, kedy HCl a prečo nie je nutné komplikovať dávkovanie.',
                'questions' => [
                    'Kedy stačí obyčajný monohydrát a kedy uvažovať o HCl?',
                    'Koľko kreatínu denne stačí a je potrebné nasycovanie?',
                    'Na čo sa pozerať pri kúpe, aby si neplatil za marketing?',
                ],
                'featured' => ['kreatin-porovnanie', 'kedy-brat-kreatin-a-kolko', 'kreatin-monohydrat-vs-hcl'],
                'related' => ['sila', 'pre-workout', 'vyziva'],
            ],
            'probiotika-travenie' => [
                'eyebrow' => 'Črevo a praktický výber',
                'intro' => 'Probiotiká sú typická téma, kde nestačí pozrieť len na počet CFU. Tu nájdeš praktický pohľad na kmene, skladovanie, dĺžku užívania a to, čo od probiotík realisticky čakať.',
                'questions' => [
                    'Ako vyberať probiotiká podľa kmeňov, CFU a stability?',
                    'Kedy majú probiotiká zmysel a po akom čase ich hodnotiť?',
                    'Ako súvisí trávenie s imunitou a prečo nestačí riešiť len kapsulu?',
                ],
                'featured' => ['probiotika-ako-vybrat', 'probiotika-a-travenie'],
                'related' => ['imunita', 'vyziva', 'mineraly'],
            ],
            'chudnutie' => [
                'eyebrow' => 'Redukcia bez skratiek',
                'intro' => 'Táto kategória prepája témy, ktoré ľudia riešia pri redukcii najčastejšie: proteín, sýtosť, doplnky a realistické očakávania od produktov označených ako „na chudnutie“.',
                'questions' => [
                    'Ktorý proteín sa hodí do redukcie a kedy je izolát zbytočný?',
                    'Majú spaľovače tukov reálny efekt alebo len marketingovú úlohu?',
                    'Čo pomáha pri chudnutí viac než „dietne“ názvy na obale?',
                ],
                'featured' => ['protein-na-chudnutie', 'proteiny-na-chudnutie', 'spalovace-tukov-realita'],
                'related' => ['proteiny', 'vyziva', 'mineraly'],
            ],
            'aminokyseliny' => [
                'eyebrow' => 'BCAA a EAA zrozumiteľne',
                'intro' => 'Aminokyseliny pôsobia zložito hlavne na etikete. V tejto kategórii si vieš rýchlo ujasniť, čo znamená BCAA, EAA a kedy ich vôbec riešiť.',
                'questions' => [
                    'Aký je rozdiel medzi BCAA a EAA?',
                    'Kedy aminokyseliny dávajú zmysel a kedy skôr nie?',
                ],
                'featured' => ['aminokyseliny-bcaa-eaa', 'bcaa-vs-eaa'],
                'related' => ['proteiny', 'sila', 'kreatin'],
            ],
            'pre-workout' => [
                'eyebrow' => 'Predtréningovky bez preháňania',
                'intro' => 'Predtréningovky vedia byť užitočné, ale ľahko sa predávajú cez silné názvy a menej cez obsah. Cieľom tejto kategórie je oddeliť funkčný výber od lacného hype-u.',
                'questions' => [
                    'Kedy má pre-workout zmysel a kedy nie?',
                    'Na ktoré zložky a dávky sa oplatí pozerať?',
                ],
                'featured' => ['pre-workout', 'pre-workout-ako-vybrat'],
                'related' => ['kreatin', 'sila', 'aminokyseliny'],
            ],
            'sila' => [
                'eyebrow' => 'Výkon a regenerácia',
                'intro' => 'Táto téma prepája výkonové články z kreatínu, predtréningoviek a aminokyselín. Je vhodná najmä vtedy, keď nehľadáš jeden produkt, ale širší prehľad toho, čo má pri výkone reálny zmysel.',
                'questions' => [
                    'Ktoré doplnky majú najlepší pomer cena/výkon pri sile a výkone?',
                    'Kde začať, ak chceš zlepšiť výkon bez zbytočného stacku?',
                ],
                'featured' => ['kreatin-porovnanie', 'kedy-brat-kreatin-a-kolko', 'pre-workout-ako-vybrat'],
                'related' => ['kreatin', 'pre-workout', 'aminokyseliny'],
            ],
        ];
    }
}

if (!function_exists('category_hub_data')) {
    function category_hub_data(string $slug): array {
        $normalized = normalize_category_slug($slug);
        $category = category_meta($normalized);
        if ($category === null) {
            return [];
        }

        $registry = category_hub_registry();
        $row = $registry[$normalized] ?? [];
        $allArticles = category_articles($normalized);
        $featured = [];
        $featuredSlugs = [];

        foreach (($row['featured'] ?? []) as $articleSlug) {
            $file = article_file((string) $articleSlug);
            if (!is_file($file)) {
                continue;
            }

            $item = article_meta((string) $articleSlug);
            $item['url'] = article_url((string) $articleSlug);
            $item['image'] = article_img((string) $articleSlug);
            $item['category_title'] = $category['title'];
            $item['category_url'] = category_url($category['slug']);
            $featured[] = $item;
            $featuredSlugs[$item['slug']] = true;
        }

        if ($featured === []) {
            $featured = array_slice($allArticles, 0, 3);
            foreach ($featured as $item) {
                $featuredSlugs[$item['slug']] = true;
            }
        }

        $remainingArticles = array_values(array_filter(
            $allArticles,
            static fn(array $item): bool => !isset($featuredSlugs[$item['slug'] ?? ''])
        ));

        $relatedCategories = [];
        foreach (($row['related'] ?? []) as $relatedSlug) {
            $relatedMeta = category_meta((string) $relatedSlug);
            if ($relatedMeta === null) {
                continue;
            }

            $relatedCategories[] = [
                'slug' => $relatedMeta['slug'],
                'title' => $relatedMeta['title'],
                'description' => $relatedMeta['description'],
                'url' => category_url($relatedMeta['slug']),
                'count' => count(category_articles($relatedMeta['slug'])),
            ];
        }

        return [
            'eyebrow' => (string) ($row['eyebrow'] ?? 'Obsahový hub'),
            'intro' => (string) ($row['intro'] ?? $category['description']),
            'questions' => array_values(array_filter(array_map('strval', (array) ($row['questions'] ?? [])))),
            'featured_articles' => $featured,
            'articles' => $remainingArticles,
            'related_categories' => $relatedCategories,
            'article_count' => count($allArticles),
        ];
    }
}
if (!function_exists('latest_article_items')) {
    function latest_article_items(int $limit = 6, ?string $excludeSlug = null, ?string $category = null): array {
        $items = [];
        $normalizedCategory = normalize_category_slug($category ?? '');

        foreach (article_registry() as $slug => $row) {
            if ($excludeSlug !== null && $slug === $excludeSlug) {
                continue;
            }

            $articleCategory = normalize_category_slug((string) ($row[2] ?? ''));
            if ($normalizedCategory !== '' && $articleCategory !== $normalizedCategory) {
                continue;
            }

            $description = trim((string) ($row[1] ?? ''));
            if ($description === '') {
                $description = extract_article_description(article_file($slug));
            }

            $items[] = [
                'slug' => $slug,
                'title' => trim((string) ($row[0] ?? humanize_slug($slug))) ?: humanize_slug($slug),
                'description' => $description,
                'category' => $articleCategory,
                'category_title' => category_meta($articleCategory)['title'] ?? 'Článok',
                'category_url' => $articleCategory !== '' ? category_url($articleCategory) : '/kategorie/',
                'url' => article_url($slug),
                'image' => article_img($slug),
                'mtime' => article_mtime($slug),
            ];
        }

        usort($items, static function (array $a, array $b): int {
            $timeCompare = ($b['mtime'] ?? 0) <=> ($a['mtime'] ?? 0);
            if ($timeCompare !== 0) {
                return $timeCompare;
            }

            return strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
        });

        return array_slice($items, 0, max(0, $limit));
    }
}

if (!function_exists('related_articles')) {
    function related_articles(string $slug, int $limit = 3): array {
        $meta = article_meta($slug);
        $items = [];
        $seen = [$slug => true];

        foreach (latest_article_items($limit, $slug, $meta['category']) as $item) {
            $items[] = $item;
            $seen[$item['slug']] = true;
        }

        if (count($items) < $limit) {
            foreach (latest_article_items($limit * 3, $slug) as $item) {
                if (isset($seen[$item['slug']])) {
                    continue;
                }

                $items[] = $item;
                $seen[$item['slug']] = true;
                if (count($items) >= $limit) {
                    break;
                }
            }
        }

        return array_slice($items, 0, $limit);
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
