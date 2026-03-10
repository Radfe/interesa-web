<?php
declare(strict_types=1);

/** Guard - ak sa includne 2x, skonci bez redeclare. */
if (defined('INTERESA_FUNCS_V3')) { return; }
define('INTERESA_FUNCS_V3', 1);

/** HTML escape */
if (!function_exists('esc')) {
    function esc(null|string $s): string {
        return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/** Asset URL s cache-busterom */
if (!function_exists('asset')) {
    function asset(string $path): string {
        $path = ltrim($path, '/');
        $fs = __DIR__ . '/../assets/' . $path;
        $url = '/assets/' . $path;
        if (is_file($fs)) {
            $ver = (string) filemtime($fs);
            $sep = str_contains($url, '?') ? '&' : '?';
            return $url . $sep . 'v=' . $ver;
        }
        return $url;
    }
}

/** SEO meta */
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

/** CTA cez /go/<kod> */
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

/** Absolutna URL v ramci webu */
if (!function_exists('site_url')) {
    function site_url(string $path = '/'): string {
        if ($path === '') {
            return '/';
        }
        return ($path[0] ?? '') === '/' ? $path : '/' . $path;
    }
}

/** Slug -> pekny titul */
if (!function_exists('humanize_slug')) {
    function humanize_slug(string $slug): string {
        $title = str_replace(['-', '_'], ' ', $slug);
        $title = preg_replace('~\s+~', ' ', $title);
        return mb_convert_case(trim((string) $title), MB_CASE_TITLE, 'UTF-8');
    }
}

/** Nahladovy obrazok clanku */
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

/** Zakladne meta pre clanok podla slug-u */
if (!function_exists('article_meta')) {
    function article_meta(string $slug): array {
        static $meta = null;
        if (!is_array($meta)) {
            $meta = [];
            foreach ([__DIR__ . '/articles.php', __DIR__ . '/articles_ext.php'] as $file) {
                if (!is_file($file)) {
                    continue;
                }
                $loaded = require $file;
                if (is_array($loaded)) {
                    $meta = array_replace($meta, $loaded);
                }
            }
        }

        return [
            'title' => $meta[$slug][0] ?? humanize_slug($slug),
            'description' => $meta[$slug][1] ?? '',
        ];
    }
}
