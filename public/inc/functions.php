<?php
declare(strict_types=1);

/** Guard – ak sa includne 2×, skonči bez redeclare. */
if (defined('INTERESA_FUNCS_V3')) { return; }
define('INTERESA_FUNCS_V3', 1);

/** HTML escape */
if (!function_exists('esc')) {
    function esc(null|string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/** Asset URL s cache-busterom */
if (!function_exists('asset')) {
    function asset(string $path): string {
        $path = ltrim($path, '/');
        $fs = __DIR__ . '/../assets/' . $path;
        $url = '/assets/' . $path;
        if (is_file($fs)) {
            $ver = (string)filemtime($fs);
            $sep = (str_contains($url, '?') ? '&' : '?');
            return $url . $sep . 'v=' . $ver;
        }
        return $url;
    }
}

/** SEO meta */
if (!function_exists('page_title')) {
    function page_title(): string {
        global $page_title;
        return esc($page_title ?? 'Interesa');
    }
}
if (!function_exists('page_description')) {
    function page_description(): string {
        global $page_description;
        $d = $page_description ?? 'Interesa – magazín o zdravej výžive. Testy, porovnania a návody.';
        return esc($d);
    }
}

/** CTA cez /go/<kod> */
if (!function_exists('cta_href')) { function cta_href(string $code): string { return '/go/' . rawurlencode($code); } }
if (!function_exists('cta_attrs')) { function cta_attrs(): string { return 'rel="nofollow sponsored" target="_blank"'; } }

/** Slug -> pekný titul (pomocné) */
if (!function_exists('humanize_slug')) {
    function humanize_slug(string $slug): string {
        $t = str_replace(['-', '_'], ' ', $slug);
        $t = preg_replace('~\s+~', ' ', $t);
        return mb_convert_case(trim($t), MB_CASE_TITLE, 'UTF-8');
    }
}
