<?php
declare(strict_types=1);

/**
 * SEO helper: meta, OG, Twitter, canonical + JSON-LD (Organization, WebSite, BreadcrumbList, Article, FAQPage)
 * Použitie (napr. v inc/head.php):
 *   require_once __DIR__ . '/seo.php';
 *   echo interessa_seo_head($SEO ?? []);
 */

if (!function_exists('interessa_site_url')) {
    function interessa_site_url(): string {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'interessa.sk';
        return $scheme . '://' . $host;
    }
}
if (!function_exists('interessa_canonical')) {
    function interessa_canonical(): string {
        $url = interessa_site_url() . (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        return rtrim($url, '/');
    }
}
if (!function_exists('interessa_detect_type')) {
    function interessa_detect_type(): string {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (strpos($path, '/clanky/') === 0) return 'Article';
        if (strpos($path, '/kategorie/') === 0) return 'CollectionPage';
        if ($path === '/' || $path === '/index.php') return 'WebSite';
        return 'WebPage';
    }
}

if (!function_exists('interessa_seo_head')) {
    function interessa_seo_head(array $ctx = []): string {
        $site = 'Interessa.sk';
        $title = $ctx['title'] ?? ($site);
        $desc  = $ctx['description'] ?? 'Interessa.sk – články o zdravej výžive, doplnkoch a výbere produktov.';
        $image = $ctx['image'] ?? (interessa_site_url() . '/assets/img/og-default.jpg');
        $canonical = $ctx['canonical'] ?? interessa_canonical();
        $type = $ctx['type'] ?? interessa_detect_type();

        $parts = [];
        $parts[] = '<title>' . htmlspecialchars($title) . '</title>';
        $parts[] = '<meta name="description" content="' . htmlspecialchars($desc) . '">';
        $parts[] = '<link rel="canonical" href="' . htmlspecialchars($canonical) . '">';

        // Open Graph
        $parts[] = '<meta property="og:type" content="' . ($type === 'Article' ? 'article' : 'website') . '">';
        $parts[] = '<meta property="og:title" content="' . htmlspecialchars($title) . '">';
        $parts[] = '<meta property="og:description" content="' . htmlspecialchars($desc) . '">';
        $parts[] = '<meta property="og:url" content="' . htmlspecialchars($canonical) . '">';
        $parts[] = '<meta property="og:image" content="' . htmlspecialchars($image) . '">';

        // Twitter
        $parts[] = '<meta name="twitter:card" content="summary_large_image">';
        $parts[] = '<meta name="twitter:title" content="' . htmlspecialchars($title) . '">';
        $parts[] = '<meta name="twitter:description" content="' . htmlspecialchars($desc) . '">';
        $parts[] = '<meta name="twitter:image" content="' . htmlspecialchars($image) . '">';

        // JSON-LD bloky
        $json = [];

        // Organization
        $json[] = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $site,
            'url' => interessa_site_url(),
            'logo' => interessa_site_url() . '/assets/img/logo-full.svg',
        ];

        // WebSite
        $json[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $site,
            'url' => interessa_site_url(),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => interessa_site_url() . '/search?q={q}',
                'query-input' => 'required name=q'
            ]
        ];

        // BreadcrumbList
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $segments = array_values(array_filter(explode('/', $path)));
        $itemList = [];
        $accum = '';
        foreach ($segments as $i => $seg) {
            $accum .= '/' . $seg;
            $itemList[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => ucfirst(str_replace('-', ' ', $seg)),
                'item' => interessa_site_url() . $accum
            ];
        }
        if ($itemList) {
            $json[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $itemList
            ];
        }

        // Article (ak sme na článku)
        if ($type === 'Article') {
            $json[] = [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $title,
                'description' => $desc,
                'image' => [$image],
                'mainEntityOfPage' => $canonical,
                'author' => ['@type' => 'Person', 'name' => 'Redakcia Interessa'],
                'publisher' => ['@type' => 'Organization', 'name' => $site, 'logo' => ['@type' => 'ImageObject', 'url' => interessa_site_url() . '/assets/img/logo-full.svg']],
                'datePublished' => $ctx['datePublished'] ?? null,
                'dateModified' => $ctx['dateModified'] ?? null,
            ];
        }

        // FAQPage (ak dodáš $ctx['faq'])
        if (!empty($ctx['faq']) && is_array($ctx['faq'])) {
            $items = [];
            foreach ($ctx['faq'] as $qa) {
                if (!isset($qa['q'], $qa['a'])) continue;
                $items[] = [
                    '@type' => 'Question',
                    'name' => $qa['q'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $qa['a']
                    ]
                ];
            }
            if ($items) {
                $json[] = [
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => $items
                ];
            }
        }

        $parts[] = '<script type="application/ld+json" nonce="' . htmlspecialchars($_SERVER["__CSP_NONCE"] ?? "") . '">' .
                   json_encode($json, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) .
                   '</script>';

        return implode("\n", $parts) . "\n";
    }
}
