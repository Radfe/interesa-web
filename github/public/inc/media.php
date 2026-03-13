<?php
declare(strict_types=1);

if (defined('INTERESA_MEDIA_HELPERS')) { return; }
define('INTERESA_MEDIA_HELPERS', 1);

if (!function_exists('interesa_media_registry')) {
    function interesa_media_registry(): array {
        static $registry = null;
        if (is_array($registry)) {
            return $registry;
        }

        $file = __DIR__ . '/../storage/media.json';
        if (!is_file($file)) {
            $registry = ['articles' => [], 'categories' => [], 'products' => []];
            return $registry;
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        $registry = is_array($decoded) ? $decoded : [];
        $registry['articles'] = is_array($registry['articles'] ?? null) ? $registry['articles'] : [];
        $registry['categories'] = is_array($registry['categories'] ?? null) ? $registry['categories'] : [];
        $registry['products'] = is_array($registry['products'] ?? null) ? $registry['products'] : [];

        return $registry;
    }
}

if (!function_exists('interesa_media_item')) {
    function interesa_media_item(string $group, string $key): array {
        $registry = interesa_media_registry();
        $groupItems = $registry[$group] ?? [];
        $item = $groupItems[$key] ?? [];

        return is_array($item) ? $item : [];
    }
}

if (!function_exists('interesa_media_public_path')) {
    function interesa_media_public_path(string $url): ?string {
        if ($url === '' || $url[0] !== '/') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return null;
        }

        return __DIR__ . '/..' . str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}

if (!function_exists('interesa_media_url_exists')) {
    function interesa_media_url_exists(string $url): bool {
        if ($url === '') {
            return false;
        }

        if (preg_match('~^https?://~i', $url)) {
            return true;
        }

        $path = interesa_media_public_path($url);
        return $path !== null && is_file($path);
    }
}

if (!function_exists('interesa_media_resolve_url')) {
    function interesa_media_resolve_url(?string $candidate): string {
        $candidate = trim((string) $candidate);
        if ($candidate === '') {
            return '';
        }

        if (preg_match('~^https?://~i', $candidate)) {
            return $candidate;
        }

        if ($candidate[0] !== '/') {
            $candidate = '/' . ltrim($candidate, '/');
        }

        if (!interesa_media_url_exists($candidate)) {
            return '';
        }

        $path = parse_url($candidate, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return $candidate;
        }

        $file = interesa_media_public_path($path);
        if ($file === null || !is_file($file)) {
            return $path;
        }

        $separator = str_contains($path, '?') ? '&' : '?';
        return $path . $separator . 'v=' . filemtime($file);
    }
}

if (!function_exists('interesa_media_article_asset_candidates')) {
    function interesa_media_article_asset_candidates(string $slug): array {
        static $cache = [];
        if (isset($cache[$slug])) {
            return $cache[$slug];
        }

        $dir = __DIR__ . '/../assets/img/articles';
        $matches = [];
        foreach (glob($dir . '/' . $slug . '*') ?: [] as $file) {
            if (!is_file($file)) {
                continue;
            }

            $base = basename($file);
            if (!preg_match('~^' . preg_quote($slug, '~') . '(?:-(\d+))?\.(webp|jpe?g|png|svg)$~i', $base, $match)) {
                continue;
            }

            $width = isset($match[1]) ? (int) $match[1] : 0;
            $ext = strtolower($match[2]);
            $extScore = ['webp' => 5, 'jpg' => 4, 'jpeg' => 4, 'png' => 3, 'svg' => 2][$ext] ?? 1;
            $matches[] = [
                'file' => $file,
                'url' => asset('img/articles/' . $base),
                'width' => $width,
                'score' => $extScore,
            ];
        }

        usort($matches, static function (array $a, array $b): int {
            if ($a['width'] === $b['width']) {
                return $b['score'] <=> $a['score'];
            }

            return $b['width'] <=> $a['width'];
        });

        $cache[$slug] = $matches;
        return $matches;
    }
}

if (!function_exists('interesa_media_article_asset_url')) {
    function interesa_media_article_asset_url(string $slug, string $size = 'hero'): string {
        $matches = interesa_media_article_asset_candidates($slug);
        if ($matches === []) {
            return '';
        }

        if ($size === 'card') {
            $best = $matches[0];
            foreach (array_reverse($matches) as $match) {
                if (($match['width'] ?? 0) >= 600 && ($match['width'] ?? 0) <= 1200) {
                    $best = $match;
                }
            }

            return $best['url'];
        }

        return $matches[0]['url'];
    }
}

if (!function_exists('interesa_media_palette')) {
    function interesa_media_palette(string $slug, string $type = 'article'): array {
        $palettes = [
            'proteiny' => ['#0f766e', '#14b8a6', '#ccfbf1'],
            'vyziva' => ['#166534', '#65a30d', '#ecfccb'],
            'mineraly' => ['#1d4ed8', '#60a5fa', '#dbeafe'],
            'imunita' => ['#be123c', '#fb7185', '#ffe4e6'],
            'sila' => ['#7c2d12', '#f97316', '#ffedd5'],
            'klby-koza' => ['#7c3aed', '#c084fc', '#f3e8ff'],
            'kreatin' => ['#0f766e', '#2dd4bf', '#ccfbf1'],
            'pre-workout' => ['#9a3412', '#fb923c', '#ffedd5'],
            'aminokyseliny' => ['#4338ca', '#818cf8', '#e0e7ff'],
            'probiotika-travenie' => ['#065f46', '#34d399', '#d1fae5'],
            'chudnutie' => ['#0369a1', '#38bdf8', '#e0f2fe'],
        ];

        if ($type === 'category' && isset($palettes[$slug])) {
            return $palettes[$slug];
        }

        $category = '';
        if ($type === 'article' && function_exists('article_meta')) {
            $category = (string) (article_meta($slug)['category'] ?? '');
        }

        if ($category !== '' && isset($palettes[$category])) {
            return $palettes[$category];
        }

        $hash = substr(md5($type . ':' . $slug), 0, 6);
        $hue = hexdec(substr($hash, 0, 2)) % 360;
        $accent = ($hue + 34) % 360;
        return [
            "hsl($hue 70% 34%)",
            "hsl($accent 80% 58%)",
            "hsl($hue 100% 96%)",
        ];
    }
}

if (!function_exists('interesa_media_text_lines')) {
    function interesa_media_text_lines(string $text, int $maxLines = 3, int $lineLength = 26): array {
        $length = static function (string $value): int {
            return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        };

        $text = trim(preg_replace('~\s+~', ' ', $text) ?? $text);
        if ($text === '') {
            return ['Interesa'];
        }

        $words = preg_split('~\s+~', $text) ?: [$text];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if ($length($candidate) > $lineLength && $current !== '') {
                $lines[] = $current;
                $current = $word;
                if (count($lines) === $maxLines - 1) {
                    break;
                }
                continue;
            }

            $current = $candidate;
        }

        $usedWords = array_sum(array_map(
            static fn(string $line): int => count(array_filter(preg_split('~\s+~', $line) ?: [])),
            $lines
        ));
        if ($current !== '') {
            $lines[] = $current;
            $usedWords += count(array_filter(preg_split('~\s+~', $current) ?: []));
        }

        $remaining = array_slice($words, $usedWords);
        if ($remaining !== []) {
            $tail = trim(implode(' ', $remaining));
            if ($tail !== '') {
                $lines[count($lines) - 1] = rtrim($lines[count($lines) - 1], '. ') . '...';
            }
        }

        return array_slice($lines, 0, $maxLines);
    }
}

if (!function_exists('interesa_media_svg')) {
    function interesa_media_svg(string $type, string $slug, string $title, string $eyebrow, string $context = 'card'): string {
        $palette = interesa_media_palette($slug, $type);
        $lines = interesa_media_text_lines($title, $context === 'hero' ? 3 : 2, $context === 'hero' ? 24 : 28);
        $width = $context === 'hero' ? 1600 : 1200;
        $height = $context === 'hero' ? 900 : 800;
        $fontSize = $context === 'hero' ? 90 : 78;
        $lineHeight = $fontSize + 12;
        $startY = $context === 'hero' ? 470 : 460;

        $textNodes = [];
        foreach ($lines as $index => $line) {
            $y = $startY + ($index * $lineHeight);
            $textNodes[] = '<text x="92" y="' . $y . '" fill="#ffffff" font-size="' . $fontSize . '" font-family="Arial, Helvetica, sans-serif" font-weight="700">' . htmlspecialchars($line, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</text>';
        }

        $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $safeEyebrow = htmlspecialchars($eyebrow, ENT_QUOTES | ENT_XML1, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}" role="img" aria-labelledby="title desc">
  <title id="title">{$safeTitle}</title>
  <desc id="desc">Interesa {$type} visual</desc>
  <defs>
    <linearGradient id="bg" x1="0%" x2="100%" y1="0%" y2="100%">
      <stop offset="0%" stop-color="{$palette[0]}"/>
      <stop offset="55%" stop-color="{$palette[1]}"/>
      <stop offset="100%" stop-color="#0f172a"/>
    </linearGradient>
    <linearGradient id="panel" x1="0%" x2="100%" y1="0%" y2="0%">
      <stop offset="0%" stop-color="rgba(255,255,255,.16)"/>
      <stop offset="100%" stop-color="rgba(255,255,255,.04)"/>
    </linearGradient>
  </defs>
  <rect width="{$width}" height="{$height}" fill="url(#bg)"/>
  <circle cx="1320" cy="160" r="220" fill="{$palette[2]}" opacity=".18"/>
  <circle cx="1180" cy="700" r="280" fill="{$palette[2]}" opacity=".12"/>
  <path d="M0 700 C240 620 420 760 630 670 C850 575 1000 620 1200 560 C1380 505 1490 430 1600 470 L1600 900 L0 900 Z" fill="rgba(15,23,42,.34)"/>
  <rect x="70" y="78" rx="28" ry="28" width="220" height="56" fill="rgba(255,255,255,.14)" stroke="rgba(255,255,255,.18)"/>
  <text x="104" y="115" fill="#ffffff" font-size="28" font-family="Arial, Helvetica, sans-serif" font-weight="700" letter-spacing="2">{$safeEyebrow}</text>
  <rect x="70" y="350" rx="42" ry="42" width="920" height="330" fill="rgba(15,23,42,.22)" stroke="rgba(255,255,255,.16)"/>
  <rect x="1040" y="150" rx="36" ry="36" width="380" height="480" fill="rgba(255,255,255,.08)" stroke="rgba(255,255,255,.14)"/>
  <circle cx="1230" cy="280" r="92" fill="rgba(255,255,255,.12)"/>
  <circle cx="1230" cy="280" r="54" fill="rgba(255,255,255,.24)"/>
  <rect x="1115" y="440" rx="18" ry="18" width="236" height="26" fill="rgba(255,255,255,.18)"/>
  <rect x="1115" y="486" rx="18" ry="18" width="174" height="26" fill="rgba(255,255,255,.12)"/>
  <rect x="1115" y="532" rx="18" ry="18" width="206" height="26" fill="rgba(255,255,255,.12)"/>
  <text x="92" y="414" fill="rgba(255,255,255,.82)" font-size="30" font-family="Arial, Helvetica, sans-serif" font-weight="700" letter-spacing="2">INTERESA</text>
  %TEXT_NODES%
</svg>
SVG;

        return str_replace('%TEXT_NODES%', implode('', $textNodes), $svg);
    }
}

if (!function_exists('interesa_media_fallback_url')) {
    function interesa_media_fallback_url(string $type, string $slug, string $context = 'card'): string {
        return '/tools/media-fallback.php?type=' . rawurlencode($type)
            . '&slug=' . rawurlencode($slug)
            . '&context=' . rawurlencode($context);
    }
}

if (!function_exists('article_media')) {
    function article_media(string $slug): array {
        $meta = article_meta($slug);
        $media = interesa_media_item('articles', $slug);

        $hero = interesa_media_resolve_url($media['hero_image'] ?? '');
        if ($hero === '') {
            $hero = interesa_media_article_asset_url($slug, 'hero');
        }
        if ($hero === '') {
            $hero = interesa_media_fallback_url('article', $slug, 'hero');
        }

        $card = interesa_media_resolve_url($media['card_image'] ?? '');
        if ($card === '') {
            $card = interesa_media_article_asset_url($slug, 'card');
        }
        if ($card === '') {
            $card = interesa_media_fallback_url('article', $slug, 'card');
        }

        return [
            'slug' => $slug,
            'title' => $meta['title'],
            'category' => $meta['category'],
            'hero_image' => $hero,
            'card_image' => $card,
            'brief' => (string) ($media['brief'] ?? ''),
            'canva_prompt' => (string) ($media['canva_prompt'] ?? ''),
            'hero_alt' => (string) ($media['hero_alt'] ?? $meta['title']),
            'card_alt' => (string) ($media['card_alt'] ?? $meta['title']),
        ];
    }
}

if (!function_exists('article_hero_img')) {
    function article_hero_img(string $slug): string {
        return article_media($slug)['hero_image'];
    }
}

if (!function_exists('category_visual')) {
    function category_visual(string $slug, string $context = 'card'): string {
        $normalized = normalize_category_slug($slug);
        $media = interesa_media_item('categories', $normalized);

        $candidate = interesa_media_resolve_url($media['image'] ?? '');
        if ($candidate !== '') {
            return $candidate;
        }

        return interesa_media_fallback_url('category', $normalized, $context);
    }
}
