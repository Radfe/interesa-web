<?php
declare(strict_types=1);

require_once __DIR__ . '/article-review-details.php';

if (!function_exists('interessa_article_commerce_clean')) {
    function interessa_article_commerce_clean(mixed $value): mixed {
        if (is_string($value)) {
            return function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake($value) : $value;
        }

        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = interessa_article_commerce_clean($item);
        }

        return $value;
    }
}

if (!function_exists('interessa_article_commerce_sections')) {
    function interessa_article_commerce_sections(): array {
        return [
            'najlepsie-proteiny-2025' => [
                'title' => 'Odporúčané produkty',
                'intro' => 'Výber nižšie je pripravený ako nákupný shortcut k článku. Keď budú doplnené finálne Dognet deeplinky, CTA ostanú rovnaké a zmení sa len cieľová URL.',
                'products' => [
                    [
                        'name' => 'Whey Protein / univerzálny WPC',
                        'subtitle' => 'Najlepší pomer cena, chuť a použiteľnosť pre väčšinu ľudí.',
                        'rating' => 4.8,
                        'code' => 'najlepsie-proteiny-2025-aktin',
                        'url' => 'https://www.aktin.sk/',
                        'merchant' => 'Aktin',
                    ],
                    [
                        'name' => 'Whey Isolate / čistejší WPI',
                        'subtitle' => 'Vhodný pri redukcii a nižšej tolerancii laktózy.',
                        'rating' => 4.7,
                        'code' => 'najlepsie-proteiny-2025-gymbeam',
                        'url' => 'https://gymbeam.sk/',
                        'merchant' => 'GymBeam',
                    ],
                    [
                        'name' => 'Performance isolate blend',
                        'subtitle' => 'Dobrý variant pre tréning a vyšší dôraz na bielkoviny na dávku.',
                        'rating' => 4.6,
                        'code' => 'najlepsie-proteiny-2025-myprotein',
                        'url' => 'https://www.myprotein.sk/',
                        'merchant' => 'Myprotein',
                    ],
                ],
            ],
            'srvatkovy-protein-vs-izolat-vs-hydro' => [
                'title' => 'Top voľby podľa typu srvátky',
                'intro' => 'Ak riešiš rozdiel medzi WPC, WPI a hydrolyzátom, nižšie máš rýchly nákupný výber podľa najčastejšieho použitia.',
                'products' => [
                    [
                        'name' => 'WPC koncentrát / value voľba',
                        'subtitle' => 'Najlepšia voľba pre bežné dopĺňanie bielkovín a rozumný rozpočet.',
                        'rating' => 4.7,
                        'code' => 'srvatkovy-protein-vs-izolat-vs-hydro-gymbeam',
                        'url' => 'https://gymbeam.sk/',
                        'merchant' => 'GymBeam',
                    ],
                    [
                        'name' => 'WPI izolát / čistejší profil',
                        'subtitle' => 'Vhodný pri diéte, nižšej tolerancii laktózy a dôraze na čisté zloženie.',
                        'rating' => 4.8,
                        'code' => 'srvatkovy-protein-vs-izolat-vs-hydro-aktin',
                        'url' => 'https://www.aktin.sk/',
                        'merchant' => 'Aktin',
                    ],
                    [
                        'name' => 'Hydrolyzát / rýchle vstrebávanie',
                        'subtitle' => 'Špecifickejšia voľba po tréningu alebo keď chceš čo najrýchlejšie vstrebávanie.',
                        'rating' => 4.4,
                        'code' => 'srvatkovy-protein-vs-izolat-vs-hydro-myprotein',
                        'url' => 'https://www.myprotein.sk/',
                        'merchant' => 'Myprotein',
                    ],
                ],
            ],
            'protein-na-chudnutie' => [
                'title' => 'Proteíny vhodné pri chudnutí',
                'intro' => 'Pri redukcii väčšinou funguje jednoduchý výber: čistejší WPI, rozumný WPC alebo nízkokalorický clear protein podľa preferencie.',
                'products' => [
                    [
                        'name' => 'WPI izolát / nižšie kalórie',
                        'subtitle' => 'Najlepšia voľba, ak chceš menej laktózy a čistejší nutričný profil.',
                        'rating' => 4.8,
                        'code' => 'protein-na-chudnutie-aktin',
                        'url' => 'https://www.aktin.sk/',
                        'merchant' => 'Aktin',
                    ],
                    [
                        'name' => 'WPC koncentrát / value cut',
                        'subtitle' => 'Dobrá voľba, ak chceš udržať cenu nižšie a stále mať praktický proteín na každý deň.',
                        'rating' => 4.6,
                        'code' => 'protein-na-chudnutie-gymbeam',
                        'url' => 'https://gymbeam.sk/',
                        'merchant' => 'GymBeam',
                    ],
                    [
                        'name' => 'Clear protein / ľahší drink',
                        'subtitle' => 'Praktický variant, ak ti nevyhovujú hutné mliečne shake-y.',
                        'rating' => 4.5,
                        'code' => 'protein-na-chudnutie-myprotein',
                        'url' => 'https://www.myprotein.sk/',
                        'merchant' => 'Myprotein',
                    ],
                ],
            ],
            'najlepsi-protein-na-chudnutie-wpc-vs-wpi' => [
                'title' => 'WPC vs WPI: odporúčané voľby',
                'intro' => 'Ak sa rozhoduješ medzi koncentrátom a izolátom, nižšie máš jednoduchý shortlist podľa rozpočtu a citlivosti na laktózu.',
                'products' => [
                    [
                        'name' => 'WPI izolát / redukcia a čistota',
                        'subtitle' => 'Silná voľba do diétnej fázy a pri potrebe nižšej laktózy.',
                        'rating' => 4.8,
                        'code' => 'najlepsi-protein-na-chudnutie-wpc-vs-wpi-aktin',
                        'url' => 'https://www.aktin.sk/',
                        'merchant' => 'Aktin',
                    ],
                    [
                        'name' => 'WPC koncentrát / výhodná cena',
                        'subtitle' => 'Najlepšia voľba, ak chceš držať rozpočet a nepotrebuješ ultra čistý profil.',
                        'rating' => 4.6,
                        'code' => 'najlepsi-protein-na-chudnutie-wpc-vs-wpi-gymbeam',
                        'url' => 'https://gymbeam.sk/',
                        'merchant' => 'GymBeam',
                    ],
                    [
                        'name' => 'WPI blend / športovejší variant',
                        'subtitle' => 'Dobrá voľba pri tréningu a vyššom dôraze na bielkoviny na dávku.',
                        'rating' => 4.5,
                        'code' => 'najlepsi-protein-na-chudnutie-wpc-vs-wpi-myprotein',
                        'url' => 'https://www.myprotein.sk/',
                        'merchant' => 'Myprotein',
                    ],
                ],
            ],
            'kreatin-porovnanie' => [
                'title' => 'Top kreatín produkty',
                'intro' => 'Pre väčšinu ľudí dáva zmysel začať kvalitným monohydrátom. HCl má význam skôr ako špecifická alternatíva.',
                'products' => [
                    [
                        'name' => 'Creatine Monohydrate / Creapure',
                        'subtitle' => 'Najistejšia voľba z pohľadu dôkazov a ceny.',
                        'rating' => 4.9,
                        'code' => 'kreatin-porovnanie-aktin',
                        'url' => 'https://www.aktin.sk/',
                        'merchant' => 'Aktin',
                    ],
                    [
                        'name' => 'Kreatín monohydrát',
                        'subtitle' => 'Dobrá value voľba pre pravidelné dávkovanie.',
                        'rating' => 4.7,
                        'code' => 'kreatin-porovnanie-gymbeam',
                        'url' => 'https://gymbeam.sk/',
                        'merchant' => 'GymBeam',
                    ],
                    [
                        'name' => 'Kreatín HCl',
                        'subtitle' => 'Alternatíva, ak chceš menšie dávky alebo lepšiu rozpustnosť.',
                        'rating' => 4.4,
                        'code' => 'kreatin-monohydrat-vs-hcl-myprotein',
                        'url' => 'https://www.myprotein.sk/',
                        'merchant' => 'Myprotein',
                    ],
                ],
            ],
            'doplnky-vyzivy' => [
                'title' => 'Odporúčané doplnky výživy',
                'intro' => 'Ak chceš rýchly shortlist bez prechádzania celej tabuľky, nižšie máš základné typy doplnkov podľa najčastejšieho cieľa: výkon, imunita, regenerácia, spánok a každodenný základ.',
                'products' => [
                    [
                        'name' => 'Multivitamín pre aktívnych',
                        'subtitle' => 'Praktický základ, ak chceš pokryť mikroživiny bez zbytočne komplikovaného stacku.',
                        'rating' => 4.6,
                        'code' => 'doplnky-vyzivy-aktin',
                        'url' => 'https://www.aktin.sk/',
                        'merchant' => 'Aktin',
                    ],
                    [
                        'name' => 'Kreatín monohydrát / výkon',
                        'subtitle' => 'Najsilnejšia voľba z pohľadu dôkazov, ak riešiš silu, výbušnosť a progres v tréningu.',
                        'rating' => 4.9,
                        'code' => 'doplnky-vyzivy-gymbeam',
                        'url' => 'https://gymbeam.sk/',
                        'merchant' => 'GymBeam',
                    ],
                    [
                        'name' => 'Vitamín D3 + K2 / imunita a kosti',
                        'subtitle' => 'Rozumná voľba najmä mimo leta, keď chceš riešiť základ podpory imunity a kostí.',
                        'rating' => 4.7,
                        'code' => 'doplnky-vyzivy-myprotein',
                        'url' => 'https://www.myprotein.sk/',
                        'merchant' => 'Myprotein',
                    ],
                ],
            ],            'horcik-ktory-je-najlepsi-a-preco' => [
                'title' => 'Najlepšie formy horčíka',
                'intro' => 'Pri horčíku je kľúčová forma. Najčastejšie dáva zmysel bisglycinát na toleranciu, citrát ako univerzál a malát na dennú energiu.',
                'products' => [
                    [
                        'name' => 'Horčík bisglycinát',
                        'subtitle' => 'Najlepšia tolerancia a večerné použitie pri strese alebo spánku.',
                        'rating' => 4.8,
                        'code' => 'horcik-ktory-je-najlepsi-a-preco-aktin',
                        'url' => 'https://www.aktin.sk/',
                        'merchant' => 'Aktin',
                    ],
                    [
                        'name' => 'Horčík citrát',
                        'subtitle' => 'Univerzálna voľba pre väčšinu ľudí, ak ti sedí aj tráviaco.',
                        'rating' => 4.7,
                        'code' => 'horcik-ktory-je-najlepsi-a-preco-gymbeam',
                        'url' => 'https://gymbeam.sk/',
                        'merchant' => 'GymBeam',
                    ],
                    [
                        'name' => 'Horčík malát',
                        'subtitle' => 'Rozumná voľba na deň, ak chceš energickejší profil bez večernej ospalosti.',
                        'rating' => 4.5,
                        'code' => 'horcik-ktory-je-najlepsi-a-preco-myprotein',
                        'url' => 'https://www.myprotein.sk/',
                        'merchant' => 'Myprotein',
                    ],
                ],
            ],
            'kolagen-na-klby-porovnanie' => [
                'title' => 'Kolagény na kĺby: top výber',
                'intro' => 'Pri kĺboch pozeraj typ kolagénu, dávku na porciu a to, či má produkt zmysluplne doplnený vitamín C alebo ďalšie podporné látky.',
                'products' => [
                    [
                        'name' => 'Kolagén typ II / kĺbový fokus',
                        'subtitle' => 'Dobrá voľba, ak cieliš primárne na kĺby, šľachy a pravidelné dlhodobé používanie.',
                        'rating' => 4.7,
                        'code' => 'kolagen-na-klby-porovnanie-aktin',
                        'url' => 'https://www.aktin.sk/',
                        'merchant' => 'Aktin',
                    ],
                    [
                        'name' => 'Kolagén + vitamín C',
                        'subtitle' => 'Praktický variant do každodenného stacku bez potreby ďalšieho kombinovania.',
                        'rating' => 4.6,
                        'code' => 'kolagen-na-klby-porovnanie-gymbeam',
                        'url' => 'https://gymbeam.sk/',
                        'merchant' => 'GymBeam',
                    ],
                    [
                        'name' => 'Hydrolyzovaný kolagén peptides',
                        'subtitle' => 'Čistá voľba, ak chceš jednoducho sledovať dávku a cenu za gram.',
                        'rating' => 4.5,
                        'code' => 'kolagen-na-klby-porovnanie-myprotein',
                        'url' => 'https://www.myprotein.sk/',
                        'merchant' => 'Myprotein',
                    ],
                ],
            ],
            'kolagen-recenzia' => [
                'title' => 'Odporúčané kolagény',
                'intro' => 'Pri kĺboch a koži sleduj typ kolagénu, dávku na porciu a to, či dáva zmysel kombinácia s vitamínom C.',
                'products' => [
                    [
                        'name' => 'Kolagén typ I & III',
                        'subtitle' => 'Rozumný základ pre pokožku, vlasy a univerzálne použitie.',
                        'rating' => 4.7,
                        'code' => 'kolagen-recenzia-gymbeam',
                        'url' => 'https://gymbeam.sk/',
                        'merchant' => 'GymBeam',
                    ],
                    [
                        'name' => 'Kolagén + vitamín C',
                        'subtitle' => 'Vhodný variant, ak chceš mať support stack v jednom produkte.',
                        'rating' => 4.6,
                        'code' => 'kolagen-recenzia-aktin',
                        'url' => 'https://www.aktin.sk/',
                        'merchant' => 'Aktin',
                    ],
                    [
                        'name' => 'Čistý hydrolyzovaný kolagén',
                        'subtitle' => 'Jednoduchá voľba bez zbytočných prísad.',
                        'rating' => 4.5,
                        'code' => 'kolagen-proteinsk',
                        'url' => 'https://www.protein.sk/',
                        'merchant' => 'Protein.sk',
                    ],
                ],
            ],
            'pre-workout-ako-vybrat' => [
                'title' => 'Pre-workout: odporúčané voľby',
                'intro' => 'Pri predtréningovke sleduj hlavne kofeín, citrulín a beta-alanín. Výber nižšie pokrýva balanced stim, silnejší stimulant aj non-stim pumpu.',
                'products' => [
                    [
                        'name' => 'Balanced stim pre-workout',
                        'subtitle' => 'Najlepšia voľba pre väčšinu ľudí, ak chceš výkon bez extrémneho nakopnutia.',
                        'rating' => 4.7,
                        'code' => 'pre-workout-ako-vybrat-aktin',
                        'url' => 'https://www.aktin.sk/',
                        'merchant' => 'Aktin',
                    ],
                    [
                        'name' => 'High-stim pre-workout',
                        'subtitle' => 'Silnejšia voľba do náročných tréningov, ak dobre toleruješ stimulanty.',
                        'rating' => 4.6,
                        'code' => 'pre-workout-ako-vybrat-gymbeam',
                        'url' => 'https://gymbeam.sk/',
                        'merchant' => 'GymBeam',
                    ],
                    [
                        'name' => 'Non-stim pump formula',
                        'subtitle' => 'Rozumná voľba, ak trénuješ večer alebo sa chceš vyhnúť kofeínu.',
                        'rating' => 4.5,
                        'code' => 'pre-workout-ako-vybrat-myprotein',
                        'url' => 'https://www.myprotein.sk/',
                        'merchant' => 'Myprotein',
                    ],
                ],
            ],
            'probiotika-ako-vybrat' => [
                'title' => 'Probiotiká: top výber',
                'intro' => 'Pri probiotikách sleduj konkrétne kmene, počet CFU pri expiracii a jednoduché dávkovanie. Nižšie je shortlist praktických volieb.',
                'products' => [
                    [
                        'name' => 'Multi-strain probiotiká',
                        'subtitle' => 'Dobrá voľba na každodenné užívanie, ak chceš širší kmeňový profil.',
                        'rating' => 4.7,
                        'code' => 'probiotika-ako-vybrat-aktin',
                        'url' => 'https://www.aktin.sk/',
                        'merchant' => 'Aktin',
                    ],
                    [
                        'name' => 'Everyday digestion probiotiká',
                        'subtitle' => 'Praktický variant pri dlhšom pravidelnom užívaní a citlivejšom trávení.',
                        'rating' => 4.6,
                        'code' => 'probiotika-ako-vybrat-gymbeam',
                        'url' => 'https://gymbeam.sk/',
                        'merchant' => 'GymBeam',
                    ],
                    [
                        'name' => 'Capsules / cestovná voľba',
                        'subtitle' => 'Rozumný variant, ak chceš jednoduché dávkovanie a praktické balenie.',
                        'rating' => 4.4,
                        'code' => 'probiotika-ako-vybrat-myprotein',
                        'url' => 'https://www.myprotein.sk/',
                        'merchant' => 'Myprotein',
                    ],
                ],
            ],
            'veganske-proteiny-top-vyber-2025' => [
                'title' => 'Top vegánske proteíny',
                'intro' => 'Najlepší výsledok zvyčajne dávajú blendy hrach + ryža. Pri citlivosti na sóju je to aj praktický kompromis.',
                'products' => [
                    [
                        'name' => 'Blend hrach + ryža',
                        'subtitle' => 'Najvyváženejší aminokyselinový profil pre bežné použitie.',
                        'rating' => 4.8,
                        'code' => 'veganske-proteiny-top-vyber-2025-aktin',
                        'url' => 'https://www.aktin.sk/',
                        'merchant' => 'Aktin',
                    ],
                    [
                        'name' => 'Hrachový izolát',
                        'subtitle' => 'Dobrá voľba, ak chceš čisté zloženie a bez sóje.',
                        'rating' => 4.6,
                        'code' => 'veganske-proteiny-top-vyber-2025-gymbeam',
                        'url' => 'https://gymbeam.sk/',
                        'merchant' => 'GymBeam',
                    ],
                    [
                        'name' => 'Rastlinný protein mix',
                        'subtitle' => 'Praktický variant do kaše, smoothie a pravidelného použitia.',
                        'rating' => 4.5,
                        'code' => 'veganske-proteiny-top-vyber-2025-myprotein',
                        'url' => 'https://www.myprotein.sk/',
                        'merchant' => 'Myprotein',
                    ],
                ],
            ],
        ];
    }
}

if (!function_exists('interessa_article_commerce_canonical_slug')) {
    function interessa_article_commerce_canonical_slug(string $slug): ?string {
        $sections = interessa_article_commerce_sections();
        $aliases = [
            'najlepsie-proteiny-2026' => 'najlepsie-proteiny-2025',
            'proteiny-na-chudnutie' => 'protein-na-chudnutie',
            'veganske-proteiny-top-vyber-2026' => 'veganske-proteiny-top-vyber-2025',
            'veganske-proteiny-top' => 'veganske-proteiny-top-vyber-2025',
        ];

        if (isset($sections[$slug])) {
            return $slug;
        }

        $canonicalSlug = $aliases[$slug] ?? null;
        if ($canonicalSlug === null || !isset($sections[$canonicalSlug])) {
            return null;
        }

        return $canonicalSlug;
    }
}

if (!function_exists('interessa_article_commerce')) {
    function interessa_article_commerce(string $slug): ?array {
        $sections = interessa_article_commerce_sections();
        $canonicalSlug = interessa_article_commerce_canonical_slug($slug);
        if ($canonicalSlug === null) {
            return null;
        }

        $section = $sections[$canonicalSlug];
        $reviewDetails = interessa_article_review_details();
        $sectionDetails = $reviewDetails[$canonicalSlug] ?? [];

        if ($sectionDetails === []) {
            return interessa_article_commerce_clean($section);
        }

        foreach ($section['products'] as $index => $product) {
            $code = trim((string) ($product['code'] ?? ''));
            if ($code === '' || !isset($sectionDetails[$code])) {
                continue;
            }

            $section['products'][$index] = array_merge($product, $sectionDetails[$code]);
        }

        return interessa_article_commerce_clean($section);
    }
}

if (!function_exists('interessa_article_comparison_table_whitelist')) {
    function interessa_article_comparison_table_whitelist(): array {
        return [
            'doplnky-vyzivy',
            'kreatin-porovnanie',
            'kolagen-na-klby-porovnanie',
            'najlepsie-proteiny-2025',
            'najlepsi-protein-na-chudnutie-wpc-vs-wpi',
            'protein-na-chudnutie',
            'veganske-proteiny-top-vyber-2025',
        ];
    }
}

if (!function_exists('interessa_article_comparison_table_payload')) {
    function interessa_article_comparison_table_payload(string $slug, ?array $commerce = null): ?array {
        $canonicalSlug = interessa_article_commerce_canonical_slug($slug);
        if ($canonicalSlug === null || !in_array($canonicalSlug, interessa_article_comparison_table_whitelist(), true)) {
            return null;
        }

        $commerce = $commerce ?? interessa_article_commerce($slug);
        $products = is_array($commerce['products'] ?? null) ? $commerce['products'] : [];
        if ($products === []) {
            return null;
        }

        $rows = [];
        foreach ($products as $product) {
            $resolved = interessa_resolve_product_reference(is_array($product) ? $product : []);
            $name = trim((string) ($resolved['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $benefit = trim((string) (($resolved['best_for'] ?? '') ?: ($resolved['subtitle'] ?? $resolved['summary'] ?? '')));
            $rating = (float) ($resolved['rating'] ?? 0);
            $resolved['main_benefit'] = $benefit !== '' ? $benefit : 'Vyber na zaklade ciela a zlozenia';
            $resolved['rating_label'] = $rating > 0 ? number_format($rating, 1) . '/5' : 'Bez hodnotenia';
            $rows[] = $resolved;
        }

        if ($rows === []) {
            return null;
        }

        return [
            'title' => 'Porovnanie produktov',
            'intro' => 'Rovnaky vyber ako v shortliste nizsie, len v rychlej tabulkovej verzii.',
            'columns' => [
                ['label' => 'Produkt', 'key' => 'name', 'type' => 'product'],
                ['label' => 'Hlavny benefit', 'key' => 'main_benefit', 'type' => 'text'],
                ['label' => 'Hodnotenie', 'key' => 'rating_label', 'type' => 'text'],
                ['label' => 'Akcia', 'key' => 'code', 'type' => 'cta'],
            ],
            'rows' => $rows,
        ];
    }
}

if (!function_exists('interessa_article_commerce_summary')) {
    function interessa_article_commerce_summary(string $slug): ?array {
        $commerce = interessa_article_commerce($slug);
        if ($commerce === null) {
            return null;
        }

        $stats = function_exists('interessa_commerce_shortlist_stats')
            ? interessa_commerce_shortlist_stats($commerce)
            : null;

        if (!is_array($stats)) {
            $products = is_array($commerce['products'] ?? null) ? $commerce['products'] : [];
            $stats = ['count' => count($products)];
        }

        return [
            'label' => trim((string) ($commerce['title'] ?? 'Odporucane produkty')),
            'count' => (int) ($stats['count'] ?? 0),
            'merchant_count' => (int) ($stats['merchant_count'] ?? 0),
            'real_packshots' => (int) ($stats['real_packshots'] ?? 0),
            'editorial_visuals' => (int) ($stats['editorial_visuals'] ?? 0),
        ];
    }
}

if (!function_exists('interessa_render_article_commerce_submeta')) {
    function interessa_render_article_commerce_submeta(string $slug, string $variant = 'full'): string {
        $summary = interessa_article_commerce_summary($slug);
        if ($summary === null || (int) ($summary['count'] ?? 0) <= 0) {
            return '';
        }

        $count = (int) ($summary['count'] ?? 0);
        $merchantCount = (int) ($summary['merchant_count'] ?? 0);
        $coverageState = interessa_shortlist_coverage_state($summary);
        $variant = strtolower(trim($variant));

        $html = '<div class="article-card-submeta">';
        if ($variant === 'compact') {
            if ($coverageState === 'full') {
                $html .= '<span class="article-card-subchip is-coverage is-full">Porovnanie + vyber</span>';
            } else {
                $html .= '<span class="article-card-subchip">Odporucane produkty</span>';
            }
            $html .= '</div>';

            return $html;
        }

        $html .= '<span class="article-card-subchip">Vyber ' . esc((string) $count) . ' produktov</span>';
        if ($merchantCount > 0) {
            $html .= '<span class="article-card-subchip">' . esc((string) $merchantCount) . ' ' . esc(interessa_pluralize_slovak($merchantCount, 'obchod', 'obchody', 'obchodov')) . '</span>';
        }
        if ($coverageState === 'full') {
            $html .= '<span class="article-card-subchip is-coverage is-full">Porovnanie pripravene</span>';
        } elseif ($coverageState === 'partial') {
            $html .= '<span class="article-card-subchip is-coverage is-partial">Shortlist pripraveny</span>';
        }
        $html .= '</div>';

        return $html;
    }
}

if (!function_exists('interessa_article_has_commerce')) {
    function interessa_article_has_commerce(string $slug): bool {
        $summary = interessa_article_commerce_summary($slug);
        return $summary !== null && (int) ($summary['count'] ?? 0) > 0;
    }
}

if (!function_exists('interessa_article_commerce_coverage_state')) {
    function interessa_article_commerce_coverage_state(string $slug): ?string {
        $summary = interessa_article_commerce_summary($slug);
        if ($summary === null || (int) ($summary['count'] ?? 0) <= 0) {
            return null;
        }

        return interessa_shortlist_coverage_state($summary);
    }
}

if (!function_exists('interessa_article_has_full_packshot_coverage')) {
    function interessa_article_has_full_packshot_coverage(string $slug): bool {
        return interessa_article_commerce_coverage_state($slug) === 'full';
    }
}
