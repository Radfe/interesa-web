<?php

declare(strict_types=1);

if (!function_exists('interessa_nav_category_title')) {
    function interessa_nav_category_title(string $slug, string $fallback): string {
        $meta = category_meta($slug);
        $title = trim((string) ($meta['title'] ?? ''));
        return $title !== '' ? $title : $fallback;
    }
}

if (!function_exists('interessa_primary_navigation_items')) {
    function interessa_primary_navigation_items(): array {
        return [
            [
                'href' => '/kategorie/proteiny',
                'label' => interessa_nav_category_title('proteiny', interessa_text('Zdrav&eacute; prote&iacute;ny')),
                'mega_key' => 'proteiny',
                'sections' => [
                    ['title' => interessa_text('Najd&ocirc;ležitejšie'), 'links' => [
                        ['href' => '/clanky/najlepsie-proteiny-2025', 'label' => interessa_text('Najlepšie prote&iacute;ny 2025')],
                        ['href' => '/clanky/protein-na-chudnutie', 'label' => interessa_text('Prote&iacute;n na chudnutie')],
                        ['href' => '/clanky/veganske-proteiny-top-vyber-2025', 'label' => interessa_text('Veg&aacute;nske prote&iacute;ny')],
                    ]],
                    ['title' => 'Porovnania', 'links' => [
                        ['href' => '/clanky/srvatkovy-protein-vs-izolat-vs-hydro', 'label' => 'WPC vs WPI vs hydro'],
                        ['href' => '/clanky/najlepsi-protein-na-chudnutie-wpc-vs-wpi', 'label' => 'WPC vs WPI na chudnutie'],
                        ['href' => '/clanky/clear-protein', 'label' => 'Clear protein'],
                    ]],
                    ['title' => interessa_text('Presk&uacute;maj t&eacute;mu'), 'links' => [
                        ['href' => '/kategorie/proteiny', 'label' => interessa_text('Hub prote&iacute;nov')],
                        ['href' => '/clanky/proteiny', 'label' => interessa_text('Z&aacute;klady prote&iacute;nov')],
                        ['href' => '/clanky/', 'label' => interessa_text('Všetky články')],
                    ]],
                ],
            ],
            [
                'href' => '/kategorie/vyziva',
                'label' => interessa_nav_category_title('vyziva', interessa_text('Zdrav&aacute; v&yacute;živa')),
                'mega_key' => 'vyziva',
                'sections' => [
                    ['title' => 'Strava a rutina', 'links' => [
                        ['href' => '/clanky/doplnky-vyzivy', 'label' => interessa_text('Doplnky v&yacute;živy')],
                        ['href' => '/clanky/chudnutie-tip', 'label' => 'Tipy na chudnutie'],
                        ['href' => '/clanky/spalovace-tukov-realita', 'label' => interessa_text('Spaľovače tukov: realita')],
                    ]],
                    ['title' => interessa_text('Tr&aacute;venie a tolerancia'), 'links' => [
                        ['href' => '/clanky/probiotika-a-travenie', 'label' => interessa_text('Probiotik&aacute; a tr&aacute;venie')],
                        ['href' => '/clanky/probiotika-ako-vybrat', 'label' => interessa_text('Ako vybrať probiotik&aacute;')],
                        ['href' => '/kategorie/probiotika-travenie', 'label' => interessa_text('Hub tr&aacute;venia')],
                    ]],
                    ['title' => 'Ciele', 'links' => [
                        ['href' => '/kategorie/vyziva', 'label' => interessa_text('Zdrav&aacute; v&yacute;živa')],
                        ['href' => '/kategorie/chudnutie', 'label' => 'Chudnutie'],
                        ['href' => '/kategorie/probiotika', 'label' => interessa_text('Probiotik&aacute;')],
                    ]],
                ],
            ],
            [
                'href' => '/kategorie/mineraly',
                'label' => interessa_nav_category_title('mineraly', interessa_text('Vitam&iacute;ny a miner&aacute;ly')),
                'mega_key' => 'mineraly',
                'sections' => [
                    ['title' => interessa_text('Najč&iacute;tanejšie'), 'links' => [
                        ['href' => '/clanky/horcik-ktory-je-najlepsi-a-preco', 'label' => interessa_text('Ak&yacute; horč&iacute;k vybrať')],
                        ['href' => '/clanky/vitamin-d3-a-imunita', 'label' => interessa_text('Vitam&iacute;n D3 a imunita')],
                        ['href' => '/clanky/vitamin-c', 'label' => interessa_text('Vitam&iacute;n C')],
                    ]],
                    ['title' => interessa_text('Miner&aacute;ly'), 'links' => [
                        ['href' => '/clanky/zinek', 'label' => 'Zinok'],
                        ['href' => '/clanky/horcik', 'label' => interessa_text('Horč&iacute;k')],
                        ['href' => '/kategorie/mineraly', 'label' => interessa_text('Hub miner&aacute;lov')],
                    ]],
                    ['title' => interessa_text('S&uacute;visiace t&eacute;my'), 'links' => [
                        ['href' => '/kategorie/imunita', 'label' => 'Imunita'],
                        ['href' => '/kategorie/vitaminy-mineraly', 'label' => interessa_text('Vitam&iacute;ny a miner&aacute;ly')],
                        ['href' => '/clanky/imunita-prirodne-latky-ktore-funguju', 'label' => interessa_text('L&aacute;tky pre imunitu')],
                    ]],
                ],
            ],
            [
                'href' => '/kategorie/imunita',
                'label' => interessa_nav_category_title('imunita', 'Imunita'),
            ],
            [
                'href' => '/kategorie/sila',
                'label' => interessa_nav_category_title('sila', interessa_text('Sila a v&yacute;kon')),
            ],
            [
                'href' => '/kategorie/klby-koza',
                'label' => interessa_nav_category_title('klby-koza', interessa_text('Kĺby a koža')),
            ],
            [
                'href' => '/clanky/',
                'label' => interessa_text('Články'),
            ],
        ];
    }
}

if (!function_exists('interessa_render_primary_navigation')) {
    function interessa_render_primary_navigation(): string {
        $html = '<nav id="hlavne-menu" class="main-nav" aria-label="' . esc(interessa_text('Hlavná navigácia')) . '">';
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
                $html .= '<label class="mega-caret" for="' . esc($toggleId) . '" aria-label="' . esc(interessa_text('Rozbaliť menu')) . ' ' . esc($label) . '"></label>';
                $html .= '<div class="mega" role="region" aria-label="' . esc($label) . ' ' . esc(interessa_text('podmenu')) . '">';

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

        $html .= '</ul></nav>';
        return $html;
    }
}