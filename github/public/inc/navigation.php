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
                'label' => interessa_nav_category_title('proteiny', 'Zdravé proteíny'),
                'mega_key' => 'proteiny',
                'sections' => [
                    ['title' => 'Najdôležitejšie', 'links' => [
                        ['href' => '/clanky/najlepsie-proteiny-2025', 'label' => 'Najlepšie proteíny 2025'],
                        ['href' => '/clanky/protein-na-chudnutie', 'label' => 'Proteín na chudnutie'],
                        ['href' => '/clanky/veganske-proteiny-top-vyber-2025', 'label' => 'Vegánske proteíny'],
                    ]],
                    ['title' => 'Porovnania', 'links' => [
                        ['href' => '/clanky/srvatkovy-protein-vs-izolat-vs-hydro', 'label' => 'WPC vs WPI vs hydro'],
                        ['href' => '/clanky/najlepsi-protein-na-chudnutie-wpc-vs-wpi', 'label' => 'WPC vs WPI na chudnutie'],
                        ['href' => '/clanky/clear-protein', 'label' => 'Clear protein'],
                    ]],
                    ['title' => 'Preskúmaj tému', 'links' => [
                        ['href' => '/kategorie/proteiny', 'label' => 'Hub proteínov'],
                        ['href' => '/clanky/proteiny', 'label' => 'Základy proteínov'],
                        ['href' => '/clanky/', 'label' => 'Všetky články'],
                    ]],
                ],
            ],
            [
                'href' => '/kategorie/vyziva',
                'label' => interessa_nav_category_title('vyziva', 'Zdravá výživa'),
                'mega_key' => 'vyziva',
                'sections' => [
                    ['title' => 'Strava a rutina', 'links' => [
                        ['href' => '/clanky/doplnky-vyzivy', 'label' => 'Doplnky výživy'],
                        ['href' => '/clanky/chudnutie-tip', 'label' => 'Tipy na chudnutie'],
                        ['href' => '/clanky/spalovace-tukov-realita', 'label' => 'Spaľovače tukov: realita'],
                    ]],
                    ['title' => 'Trávenie a tolerancia', 'links' => [
                        ['href' => '/clanky/probiotika-a-travenie', 'label' => 'Probiotiká a trávenie'],
                        ['href' => '/clanky/probiotika-ako-vybrat', 'label' => 'Ako vybrať probiotiká'],
                        ['href' => '/kategorie/probiotika-travenie', 'label' => 'Hub trávenia'],
                    ]],
                    ['title' => 'Ciele', 'links' => [
                        ['href' => '/kategorie/vyziva', 'label' => 'Zdravá výživa'],
                        ['href' => '/kategorie/chudnutie', 'label' => 'Chudnutie'],
                        ['href' => '/kategorie/probiotika', 'label' => 'Probiotiká'],
                    ]],
                ],
            ],
            [
                'href' => '/kategorie/mineraly',
                'label' => interessa_nav_category_title('mineraly', 'Vitamíny a minerály'),
                'mega_key' => 'mineraly',
                'sections' => [
                    ['title' => 'Najčítanejšie', 'links' => [
                        ['href' => '/clanky/horcik-ktory-je-najlepsi-a-preco', 'label' => 'Aký horčík vybrať'],
                        ['href' => '/clanky/vitamin-d3-a-imunita', 'label' => 'Vitamín D3 a imunita'],
                        ['href' => '/clanky/vitamin-c', 'label' => 'Vitamín C'],
                    ]],
                    ['title' => 'Minerály', 'links' => [
                        ['href' => '/clanky/zinek', 'label' => 'Zinok'],
                        ['href' => '/clanky/horcik', 'label' => 'Horčík'],
                        ['href' => '/kategorie/mineraly', 'label' => 'Hub minerálov'],
                    ]],
                    ['title' => 'Súvisiace témy', 'links' => [
                        ['href' => '/kategorie/imunita', 'label' => 'Imunita'],
                        ['href' => '/kategorie/vitaminy-mineraly', 'label' => 'Vitamíny a minerály'],
                        ['href' => '/clanky/imunita-prirodne-latky-ktore-funguju', 'label' => 'Látky pre imunitu'],
                    ]],
                ],
            ],
            [
                'href' => '/kategorie/imunita',
                'label' => interessa_nav_category_title('imunita', 'Imunita'),
            ],
            [
                'href' => '/kategorie/sila',
                'label' => interessa_nav_category_title('sila', 'Sila a výkon'),
            ],
            [
                'href' => '/kategorie/klby-koza',
                'label' => interessa_nav_category_title('klby-koza', 'Kĺby a koža'),
            ],
            [
                'href' => '/clanky/',
                'label' => 'Články',
            ],
        ];
    }
}

if (!function_exists('interessa_render_primary_navigation')) {
    function interessa_render_primary_navigation(): string {
        $html = '<nav id="hlavne-menu" class="main-nav" aria-label="Hlavná navigácia">';
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
                $html .= '<label class="mega-caret" for="' . esc($toggleId) . '" aria-label="Rozbaliť menu ' . esc($label) . '"></label>';
                $html .= '<div class="mega" role="region" aria-label="' . esc($label) . ' podmenu">';

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