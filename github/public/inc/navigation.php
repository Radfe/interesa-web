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
                    ['title' => 'Typy', 'links' => [
                        ['href' => '/kategorie/proteiny#srvate', 'label' => 'Srvátkové (WPC/WPI)'],
                        ['href' => '/kategorie/proteiny#rastlinne', 'label' => 'Rastlinné'],
                        ['href' => '/kategorie/proteiny#vegan', 'label' => 'Vegan blend'],
                    ]],
                    ['title' => 'Ciele', 'links' => [
                        ['href' => '/kategorie/proteiny#chudnutie', 'label' => 'Chudnutie'],
                        ['href' => '/kategorie/proteiny#regeneracia', 'label' => 'Regenerácia'],
                        ['href' => '/kategorie/proteiny#rychly-snack', 'label' => 'Rýchly snack'],
                    ]],
                    ['title' => 'Tipy a články', 'links' => [
                        ['href' => '/clanky/', 'label' => 'Poradňa'],
                        ['href' => '/clanky/#recepty', 'label' => 'Recepty s proteínom'],
                        ['href' => '/clanky/#najcastejsie-otazky', 'label' => 'FAQ'],
                    ]],
                ],
            ],
            [
                'href' => '/kategorie/vyziva',
                'label' => interessa_nav_category_title('vyziva', 'Zdravá výživa'),
                'mega_key' => 'vyziva',
                'sections' => [
                    ['title' => 'Jedlá a snacky', 'links' => [
                        ['href' => '/kategorie/vyziva#granola', 'label' => 'Granola a kaše'],
                        ['href' => '/kategorie/vyziva#orechy', 'label' => 'Orechy a maslá'],
                        ['href' => '/kategorie/vyziva#tycinky', 'label' => 'Tyčinky'],
                    ]],
                    ['title' => 'Špeciálne', 'links' => [
                        ['href' => '/kategorie/vyziva#bezlepkove', 'label' => 'Bezlepkové'],
                        ['href' => '/kategorie/vyziva#bezlaktozy', 'label' => 'Bez laktózy'],
                        ['href' => '/kategorie/vyziva#keto', 'label' => 'Keto'],
                    ]],
                    ['title' => 'Nástroje', 'links' => [
                        ['href' => '/clanky/#jedalnicky', 'label' => 'Jedálničky'],
                        ['href' => '/clanky/#makra', 'label' => 'Výpočet makier'],
                        ['href' => '/clanky/#hydratacia', 'label' => 'Hydratácia'],
                    ]],
                ],
            ],
            [
                'href' => '/kategorie/mineraly',
                'label' => interessa_nav_category_title('mineraly', 'Vitamíny a minerály'),
                'mega_key' => 'mineraly',
                'sections' => [
                    ['title' => 'Vitamíny', 'links' => [
                        ['href' => '/kategorie/mineraly#vitamin-c', 'label' => 'Vitamín C'],
                        ['href' => '/kategorie/mineraly#vitamin-d3', 'label' => 'Vitamín D3'],
                        ['href' => '/kategorie/mineraly#b-komplex', 'label' => 'B-komplex'],
                    ]],
                    ['title' => 'Minerály', 'links' => [
                        ['href' => '/kategorie/mineraly#zinok', 'label' => 'Zinok'],
                        ['href' => '/kategorie/mineraly#horcik', 'label' => 'Horčík'],
                        ['href' => '/kategorie/mineraly#zelezo', 'label' => 'Železo'],
                    ]],
                    ['title' => 'Balíčky', 'links' => [
                        ['href' => '/kategorie/mineraly#imunita', 'label' => 'Balíček imunita'],
                        ['href' => '/kategorie/mineraly#energia', 'label' => 'Balíček energia'],
                        ['href' => '/kategorie/mineraly#wellbeing', 'label' => 'Balíček wellbeing'],
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