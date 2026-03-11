<?php
declare(strict_types=1);

if (!function_exists('interessa_heading_slug')) {
    function interessa_heading_slug(string $text): string {
        $text = strip_tags($text);
        $text = interessa_fix_mojibake(trim($text));
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $ascii = is_string($ascii) && $ascii !== '' ? $ascii : $text;
        $ascii = strtolower($ascii);
        $ascii = preg_replace('~[^a-z0-9]+~', '-', $ascii) ?? $ascii;
        return trim($ascii, '-') ?: 'sekcia';
    }
}

if (!function_exists('interessa_article_prepare_body')) {
    function interessa_article_prepare_body(string $html): array {
        $html = interessa_fix_mojibake($html);
        $headings = [];
        $used = [];

        $processed = preg_replace_callback(
            '~<h([23])([^>]*)>(.*?)</h\1>~isu',
            static function (array $match) use (&$headings, &$used): string {
                $level = (int) $match[1];
                $attrs = (string) $match[2];
                $inner = (string) $match[3];
                $text = trim(strip_tags($inner));
                if ($text === '') {
                    return $match[0];
                }

                $id = '';
                if (preg_match('~\sid=("|\')(.*?)\1~isu', $attrs, $idMatch)) {
                    $id = trim((string) $idMatch[2]);
                }
                if ($id === '') {
                    $base = interessa_heading_slug($text);
                    $id = $base;
                    $suffix = 2;
                    while (isset($used[$id])) {
                        $id = $base . '-' . $suffix++;
                    }
                    $attrs .= ' id="' . esc($id) . '"';
                }

                $used[$id] = true;
                $headings[] = [
                    'level' => $level,
                    'id' => $id,
                    'text' => $text,
                ];

                return '<h' . $level . $attrs . '>' . $inner . '</h' . $level . '>';
            },
            $html
        );

        $wordCount = preg_match_all('~\p{L}[\p{L}\p{N}\-]*~u', strip_tags($html), $m);
        $readingTime = max(1, (int) ceil(((int) $wordCount) / 220));

        return [
            'html' => is_string($processed) ? $processed : $html,
            'headings' => $headings,
            'reading_time' => $readingTime,
        ];
    }
}

if (!function_exists('interessa_render_article_outline')) {
    function interessa_render_article_outline(array $headings, int $readingTime): void {
        if ($headings === [] && $readingTime <= 0) {
            return;
        }

        echo '<section class="article-outline">';
        echo '<div class="section-head">';
        echo '<h2>Obsah clanku</h2>';
        echo '<p class="meta">Odhad citania: ' . esc((string) $readingTime) . ' min.</p>';
        echo '</div>';

        if ($headings !== []) {
            echo '<ol class="article-outline-list">';
            foreach ($headings as $heading) {
                $level = (int) ($heading['level'] ?? 2);
                $text = (string) ($heading['text'] ?? '');
                $id = (string) ($heading['id'] ?? '');
                if ($text === '' || $id === '') {
                    continue;
                }
                echo '<li class="level-' . $level . '"><a href="#' . esc($id) . '">' . esc($text) . '</a></li>';
            }
            echo '</ol>';
        }

        echo '</section>';
    }
}