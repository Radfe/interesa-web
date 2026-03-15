<?php

declare(strict_types=1);

if (!function_exists('interessa_nav_category_title')) {
    function interessa_nav_category_title(string $slug, string $fallback): string {
        $meta = category_meta($slug);
        $title = trim((string) ($meta['title'] ?? ''));
        return $title !== '' ? $title : $fallback;
    }
}

if (!function_exists('interessa_is_local_dev')) {
    function interessa_is_local_dev(): bool {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        return $host === ''
            || str_contains($host, '127.0.0.1')
            || str_contains($host, 'localhost');
    }
}

if (!function_exists('interessa_primary_navigation_items')) {
    function interessa_primary_navigation_items(): array {
        return [
            [
                'href' => '/kategorie/proteiny',
                'label' => interessa_nav_category_title('proteiny', 'Zdrave proteiny'),
                'mega_key' => 'proteiny',
                'sections' => [
                    ['title' => 'Najdolezitejsie', 'links' => [
                        ['href' => '/clanky/najlepsie-proteiny-2026', 'label' => 'Najlepsie proteiny 2026'],
                        ['href' => '/clanky/protein-na-chudnutie', 'label' => 'Protein na chudnutie'],
                        ['href' => '/clanky/veganske-proteiny-top-vyber-2026', 'label' => 'Veganske proteiny'],
                    ]],
                    ['title' => 'Porovnania', 'links' => [
                        ['href' => '/clanky/srvatkovy-protein-vs-izolat-vs-hydro', 'label' => 'WPC vs WPI vs hydro'],
                        ['href' => '/clanky/najlepsi-protein-na-chudnutie-wpc-vs-wpi', 'label' => 'WPC vs WPI na chudnutie'],
                        ['href' => '/clanky/clear-protein', 'label' => 'Clear protein'],
                    ]],
                    ['title' => 'Preskumaj temu', 'links' => [
                        ['href' => '/kategorie/proteiny', 'label' => 'Prehlad proteinov'],
                        ['href' => '/clanky/proteiny', 'label' => 'Zaklady proteinov'],
                        ['href' => '/clanky/', 'label' => 'Vsetky clanky'],
                    ]],
                ],
            ],
            [
                'href' => '/kategorie/vyziva',
                'label' => interessa_nav_category_title('vyziva', 'Zdrava vyziva'),
                'mega_key' => 'vyziva',
                'sections' => [
                    ['title' => 'Strava a rutina', 'links' => [
                        ['href' => '/clanky/doplnky-vyzivy', 'label' => 'Doplnky vyzivy'],
                        ['href' => '/clanky/chudnutie-tip', 'label' => 'Tipy na chudnutie'],
                        ['href' => '/clanky/spalovace-tukov-realita', 'label' => 'Spalovace tukov: realita'],
                    ]],
                    ['title' => 'Travenie a tolerancia', 'links' => [
                        ['href' => '/clanky/probiotika-a-travenie', 'label' => 'Probiotika a travenie'],
                        ['href' => '/clanky/probiotika-ako-vybrat', 'label' => 'Ako vybrat probiotika'],
                        ['href' => '/kategorie/probiotika-travenie', 'label' => 'Prehlad travenia'],
                    ]],
                    ['title' => 'Ciele', 'links' => [
                        ['href' => '/kategorie/vyziva', 'label' => 'Zdrava vyziva'],
                        ['href' => '/kategorie/chudnutie', 'label' => 'Chudnutie'],
                        ['href' => '/kategorie/probiotika', 'label' => 'Probiotika'],
                    ]],
                ],
            ],
            [
                'href' => '/kategorie/mineraly',
                'label' => interessa_nav_category_title('mineraly', 'Vitaminy a mineraly'),
                'mega_key' => 'mineraly',
                'sections' => [
                    ['title' => 'Najcitanejsie', 'links' => [
                        ['href' => '/clanky/horcik-ktory-je-najlepsi-a-preco', 'label' => 'Aky horcik vybrat'],
                        ['href' => '/clanky/vitamin-d3-a-imunita', 'label' => 'Vitamin D3 a imunita'],
                        ['href' => '/clanky/vitamin-c', 'label' => 'Vitamin C'],
                    ]],
                    ['title' => 'Mineraly', 'links' => [
                        ['href' => '/clanky/zinek', 'label' => 'Zinok'],
                        ['href' => '/clanky/horcik', 'label' => 'Horcik'],
                        ['href' => '/kategorie/mineraly', 'label' => 'Prehlad mineralov'],
                    ]],
                    ['title' => 'Suvisiace temy', 'links' => [
                        ['href' => '/kategorie/imunita', 'label' => 'Imunita'],
                        ['href' => '/kategorie/vitaminy-mineraly', 'label' => 'Vitaminy a mineraly'],
                        ['href' => '/clanky/imunita-prirodne-latky-ktore-funguju', 'label' => 'Latky pre imunitu'],
                    ]],
                ],
            ],
            ['href' => '/kategorie/imunita', 'label' => interessa_nav_category_title('imunita', 'Imunita')],
            ['href' => '/kategorie/sila', 'label' => interessa_nav_category_title('sila', 'Sila a vykon')],
            ['href' => '/kategorie/klby-koza', 'label' => interessa_nav_category_title('klby-koza', 'Klby a koza')],
            ['href' => '/clanky/', 'label' => 'Clanky'],
        ];
    }
}

if (!function_exists('interessa_render_primary_navigation')) {
    function interessa_render_primary_navigation(): string {
        $html = '<nav id="hlavne-menu" class="main-nav" aria-label="' . esc('Hlavna navigacia') . '">';
        $html .= '<ul class="menu-root">';

        foreach (interessa_primary_navigation_items() as $item) {
            $href = (string) ($item['href'] ?? '/');
            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $megaKey = trim((string) ($item['mega_key'] ?? ''));
            $sections = is_array($item['sections'] ?? null) ? $item['sections'] : [];
            $hasMega = $megaKey !== '' && $sections !== [];

            $html .= '<li class="menu-item' . ($hasMega ? ' has-mega' : '') . '">';
            $html .= '<a class="main-nav__link" href="' . esc($href) . '"';
            if ($hasMega) {
                $html .= ' data-mega="' . esc($megaKey) . '" aria-haspopup="true"';
            }
            $html .= '>' . esc($label) . '</a>';

            if ($hasMega) {
                $toggleId = 'mm-' . $megaKey;
                $html .= '<input type="checkbox" id="' . esc($toggleId) . '" class="mega-toggle" aria-hidden="true" />';
                $html .= '<label class="mega-caret" for="' . esc($toggleId) . '" aria-label="' . esc('Rozbalit menu') . ' ' . esc($label) . '"></label>';
                $html .= '<div class="mega" role="region" aria-label="' . esc($label) . ' ' . esc('podmenu') . '">';

                foreach ($sections as $section) {
                    $sectionTitle = trim((string) ($section['title'] ?? ''));
                    $links = is_array($section['links'] ?? null) ? $section['links'] : [];
                    if ($sectionTitle === '' || $links === []) {
                        continue;
                    }

                    $html .= '<div class="mega-col">';
                    $html .= '<h3>' . esc($sectionTitle) . '</h3>';
                    $html .= '<ul>';

                    foreach ($links as $link) {
                        $sectionHref = (string) ($link['href'] ?? '#');
                        $sectionLabel = trim((string) ($link['label'] ?? ''));
                        if ($sectionLabel === '') {
                            continue;
                        }
                        $html .= '<li><a class="mega-link" href="' . esc($sectionHref) . '">' . esc($sectionLabel) . '</a></li>';
                    }

                    $html .= '</ul></div>';
                }

                $html .= '</div>';
            }

            $html .= '</li>';
        }

        if (interessa_is_local_dev()) {
            $html .= '<li class="menu-item menu-item--dev">';
            $html .= '<button class="main-nav__link main-nav__button main-nav__button--dev" type="button" data-dev-reload>Obnovit verziu</button>';
            $html .= '</li>';
        }

        $html .= '</ul></nav>';
        return $html;
    }
}
