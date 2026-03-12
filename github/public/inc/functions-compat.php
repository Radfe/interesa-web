<?php
declare(strict_types=1);

if (!function_exists('canonical_article_slug')) {
    function canonical_article_slug(string $slug): string {
        $slug = trim($slug);
        if ($slug === '') {
            return '';
        }

        $aliases = [
            'proteiny-na-chudnutie' => 'protein-na-chudnutie',
            'veganske-proteiny-top' => 'veganske-proteiny-top-vyber-2025',
        ];

        return $aliases[$slug] ?? $slug;
    }
}

if (!function_exists('canonical_category_slug')) {
    function canonical_category_slug(string $slug): string {
        return normalize_category_slug($slug);
    }
}

if (!function_exists('page_canonical')) {
    function page_canonical(): string {
        return canonical_url();
    }
}

if (!function_exists('page_og_type')) {
    function page_og_type(): string {
        global $page_og_type, $page;

        $candidate = (string) ($page_og_type ?? ($page['og_type'] ?? null) ?? '');
        if ($candidate !== '') {
            return $candidate;
        }

        return match (page_type()) {
            'Article' => 'article',
            default => 'website',
        };
    }
}

if (!function_exists('page_image_url')) {
    function page_image_url(): string {
        return page_image();
    }
}

if (!function_exists('page_style_urls')) {
    function page_style_urls(): array {
        global $page_styles, $page;

        $styles = [];
        foreach ([$page_styles ?? null, $page['styles'] ?? null] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $styles[] = trim($candidate);
                continue;
            }

            if (!is_array($candidate)) {
                continue;
            }

            foreach ($candidate as $item) {
                if (is_string($item) && trim($item) !== '') {
                    $styles[] = trim($item);
                }
            }
        }

        return array_values(array_unique($styles));
    }
}

if (!function_exists('stylesheet_tags')) {
    function stylesheet_tags(array $urls): string {
        if ($urls === []) {
            return '';
        }

        $lines = [];
        foreach ($urls as $url) {
            $href = trim((string) $url);
            if ($href === '') {
                continue;
            }

            if (!preg_match('~^(?:https?:)?//|^/~i', $href)) {
                $href = str_starts_with($href, 'assets/')
                    ? '/' . ltrim($href, '/')
                    : asset($href);
            }

            $lines[] = '<link rel="stylesheet" href="' . esc($href) . '" />';
        }

        return implode("\n  ", $lines);
    }
}

if (!function_exists('schema_script_tags')) {
    function schema_script_tags(): string {
        global $page_schema;

        if ($page_schema === null || $page_schema === []) {
            return '';
        }

        $items = is_array($page_schema) && array_is_list($page_schema) ? $page_schema : [$page_schema];
        $scripts = [];
        foreach ($items as $item) {
            if (!is_array($item) || $item === []) {
                continue;
            }

            if (!isset($item['@context'])) {
                $item['@context'] = 'https://schema.org';
            }

            $scripts[] = '<script type="application/ld+json">' . json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
        }

        return implode("\n  ", $scripts);
    }
}

if (!function_exists('breadcrumb_schema')) {
    function breadcrumb_schema(array $items): array {
        $elements = [];
        foreach (array_values($items) as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $url = (string) ($item['url'] ?? '/');
            $elements[] = [
                '@type' => 'ListItem',
                'position' => count($elements) + 1,
                'name' => $name,
                'item' => absolute_url(site_url($url)),
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }
}

if (!function_exists('page_script_urls')) {
    function page_script_urls(): array {
        global $page_scripts, $page;

        $scripts = [];
        foreach ([$page_scripts ?? null, $page['scripts'] ?? null] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $scripts[] = trim($candidate);
                continue;
            }

            if (!is_array($candidate)) {
                continue;
            }

            foreach ($candidate as $item) {
                if (is_string($item) && trim($item) !== '') {
                    $scripts[] = trim($item);
                }
            }
        }

        return array_values(array_unique($scripts));
    }
}

if (!function_exists('script_tags')) {
    function script_tags(array $urls): string {
        if ($urls === []) {
            return '';
        }

        $lines = [];
        foreach ($urls as $url) {
            $src = trim((string) $url);
            if ($src === '') {
                continue;
            }

            if (!preg_match('~^(?:https?:)?//|^/~i', $src)) {
                $src = str_starts_with($src, 'assets/')
                    ? '/' . ltrim($src, '/')
                    : asset($src);
            }

            $lines[] = '<script src="' . esc($src) . '" defer></script>';
        }

        return implode("\n", $lines);
    }
}
if (!function_exists('indexed_articles')) {
    function indexed_articles(): array {
        $items = [];
        foreach (article_registry() as $slug => $row) {
            $canonicalSlug = canonical_article_slug((string) $slug);
            $meta = article_meta($canonicalSlug);
            $items[$canonicalSlug] = [
                'slug' => $canonicalSlug,
                'title' => (string) ($meta['title'] ?? humanize_slug($canonicalSlug)),
                'description' => (string) ($meta['description'] ?? ''),
                'category' => (string) ($meta['category'] ?? ''),
                'url' => article_url($canonicalSlug),
            ];
        }

        ksort($items);
        return $items;
    }
}
if (!function_exists('interessa_fix_mojibake')) {
    function interessa_fix_mojibake(string $text): string {
        if ($text === '') {
            return '';
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $text);
        $roundTrip = @iconv('Windows-1252', 'UTF-8//IGNORE', @iconv('UTF-8', 'Windows-1252//IGNORE', $normalized) ?: '');
        if (is_string($roundTrip) && $roundTrip !== '' && substr_count($roundTrip, 'Ă') < substr_count($normalized, 'Ă')) {
            $normalized = $roundTrip;
        }

        return $normalized;
    }
}
if (!function_exists('interessa_text')) {
    function interessa_text(string $text): string {
        return interessa_fix_mojibake($text);
    }
}