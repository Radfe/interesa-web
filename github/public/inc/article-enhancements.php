<?php
declare(strict_types=1);

if (!function_exists('interessa_article_enhancements_registry')) {
    function interessa_article_enhancements_registry(): array {
        return [
            'doplnky-vyzivy' => [
                'audience' => [
                    'Ak si chceš upratať základné doplnky a neísť po každom novom trende.',
                    'Ak riešiš rozumný štartovací stack pre energiu, regeneráciu a každodennú rutinu.',
                    'Ak chceš vedieť, ktoré doplnky dávajú zmysel skôr než začneš riešiť drahé špeciality.',
                ],
                'faq' => [
                    ['q' => 'Musím brať veľa doplnkov naraz?', 'a' => 'Nie. Pre väčšinu ľudí dáva väčší zmysel pár základných doplnkov podľa cieľa než dlhý a drahý stack.'],
                    ['q' => 'Je kreatín vhodný aj mimo silového tréningu?', 'a' => 'Áno, ak riešiš výkon a pravidelný tréning. Nie je však nutný pre každého, kto chce len zlepšiť bežný jedálniček.'],
                    ['q' => 'Ktorý doplnok má najlepší pomer cena a prínos?', 'a' => 'Závisí od cieľa. Často dávajú najväčší zmysel kreatín, vitamín D3 podľa sezóny a horčík vo vhodnej forme.'],
                ],
            ],
            'najlepsie-proteiny-2025' => [
                'audience' => [
                    'Ak chceš rýchly shortlist najlepších proteínov bez dlhého porovnávania e-shopov.',
                    'Ak hľadáš univerzálny proteín po tréningu aj počas dňa.',
                    'Ak sa chceš zorientovať medzi value WPC, čistejším izolátom a športovejším blendom.',
                ],
                'faq' => [
                    ['q' => 'Je lepší koncentrát alebo izolát?', 'a' => 'Pre väčšinu ľudí stačí kvalitný koncentrát. Izolát dáva väčší zmysel pri nižšej tolerancii laktózy alebo pri dôraze na čistejší profil.'],
                    ['q' => 'Má zmysel riešiť proteín, ak viem bielkoviny dojesť?', 'a' => 'Proteín nie je povinný. Je to hlavne praktický spôsob, ako si uľahčiť doplnenie bielkovín v dňoch, keď ich stravou nestíhaš.'],
                    ['q' => 'Kedy sa oplatí platiť viac za drahší proteín?', 'a' => 'Najmä keď riešiš nižší obsah laktózy, vyšší komfort trávenia alebo čistejšie zloženie počas diéty.'],
                ],
            ],
            'protein-na-chudnutie' => [
                'audience' => [
                    'Ak chceš proteín do redukcie bez zbytočne vysokých kalórií navyše.',
                    'Ak sa rozhoduješ medzi WPC, WPI a clear proteínom pri chudnutí.',
                    'Ak chceš vedieť, či sa pri diéte naozaj oplatí platiť viac za izolát.',
                ],
                'faq' => [
                    ['q' => 'Je pri chudnutí vždy najlepší izolát?', 'a' => 'Nie vždy. Izolát je praktický pri nižšej laktóze a čistejšom profile, ale pri dobre nastavenom jedálničku môže stačiť aj kvalitný koncentrát.'],
                    ['q' => 'Pomôže mi proteín schudnúť sám o sebe?', 'a' => 'Nie. Proteín pomáha hlavne so sýtosťou a doplnením bielkovín, ale o chudnutí stále rozhoduje celkový príjem energie a dlhodobý režim.'],
                    ['q' => 'Je clear proteín lepší na leto alebo do diéty?', 'a' => 'Pre niektorých áno, lebo je ľahší na pitie a menej hutný. Nutrične to však nie je automaticky lepšia voľba pre každého.'],
                ],
            ],
            'kreatin-porovnanie' => [
                'audience' => [
                    'Ak chceš zistiť, či ti stačí obyčajný monohydrát alebo riešiť aj HCl a iné formy.',
                    'Ak hľadáš kreatín s najlepším pomerom dôkazy, cena a praktické použitie.',
                    'Ak si nie si istý dávkovaním, nasycovaním alebo tým, kedy kreatín brať.',
                ],
                'faq' => [
                    ['q' => 'Musím robiť nasycovaciu fázu?', 'a' => 'Nie. Pre väčšinu ľudí stačí pravidelných 3 až 5 gramov denne a efekt sa dostaví aj bez nasycovania.'],
                    ['q' => 'Je HCl lepší než monohydrát?', 'a' => 'Nie všeobecne. Monohydrát má najlepšie dáta a cenu. HCl je skôr alternatíva pre ľudí, ktorí chcú menšiu dávku alebo lepšiu rozpustnosť.'],
                    ['q' => 'Kedy je najlepší čas na kreatín?', 'a' => 'Dôležitejšia než presný čas je pravidelnosť. Kreatín môžeš brať kedykoľvek počas dňa, keď sa ti to dá dlhodobo držať.'],
                ],
            ],
            'horcik-ktory-je-najlepsi-a-preco' => [
                'audience' => [
                    'Ak sa strácaš medzi bisglycinátom, citrátom, malátom a oxidom horečnatým.',
                    'Ak chceš vybrať horčík podľa cieľa, nie podľa najhlasnejšieho marketingu.',
                    'Ak riešiš stres, spánok, kŕče alebo bežné denné dopĺňanie minerálov.',
                ],
                'faq' => [
                    ['q' => 'Ktorá forma horčíka je najšetrnejšia?', 'a' => 'Často dobre vychádza bisglycinát, najmä ak riešiš toleranciu a večerné použitie.'],
                    ['q' => 'Je citrát zlý, keď ho má veľa značiek?', 'a' => 'Nie. Citrát je často dobrá univerzálna voľba, len u citlivejších ľudí môže viac rozhýbať trávenie.'],
                    ['q' => 'Oplatí sa kupovať lacný oxid horečnatý?', 'a' => 'Skôr nie ako prvú voľbu. Pri praktickom používaní zvyknú dávať viac zmysel lepšie vstrebateľné formy.'],
                ],
            ],
            'kolagen-recenzia' => [
                'audience' => [
                    'Ak chceš rozlíšiť kolagén na pokožku od kolagénu orientovaného viac na kĺby.',
                    'Ak nevieš, či sledovať typ kolagénu, dávku alebo pridaný vitamín C.',
                    'Ak hľadáš jednoduchý kolagén do každodennej rutiny bez marketingového chaosu.',
                ],
                'faq' => [
                    ['q' => 'Má pri kolagéne zmysel vitamín C?', 'a' => 'Áno, často je to rozumný doplnok, lebo vitamín C súvisí s tvorbou kolagénu. Nie vždy však musí byť priamo v tom istom produkte.'],
                    ['q' => 'Je dôležitejší typ kolagénu alebo dávka?', 'a' => 'V praxi treba sledovať oboje. Typ ti napovie účel produktu a dávka zas to, či má používanie vôbec šancu dávať zmysel.'],
                    ['q' => 'Pomôže kolagén okamžite?', 'a' => 'Nie. Pri kolagéne je dôležitejšia dlhodobosť a pravidelnosť než rýchly efekt po pár dňoch.'],
                ],
            ],
            'kolagen-na-klby-porovnanie' => [
                'audience' => [
                    'Ak riešiš kolagén cielene na kĺby, šľachy alebo dlhodobú podporu pohybového aparátu.',
                    'Ak chceš vedieť, čo má pri kĺboch väčší význam než len pekná etiketa.',
                    'Ak si potrebuješ vybrať medzi jednoduchým kolagénom a komplexnejším kĺbovým produktom.',
                ],
                'faq' => [
                    ['q' => 'Je na kĺby lepší špecifický typ kolagénu?', 'a' => 'Často áno, preto sa pri tejto téme oplatí sledovať typ kolagénu a nie len marketingový názov produktu.'],
                    ['q' => 'Pomáha vyššia dávka automaticky viac?', 'a' => 'Nie vždy. Dôležité je, aby dávala zmysel forma, cieľ použitia a dlhodobá konzistencia.'],
                    ['q' => 'Má zmysel brať kolagén len občas?', 'a' => 'Skôr nie. Pri tejto kategórii býva dôležitejšia pravidelnosť a dlhší horizont používania.'],
                ],
            ],
            'pre-workout-ako-vybrat' => [
                'audience' => [
                    'Ak chceš predtréningovku, ale nevieš odhadnúť vhodnú silu stimulantov.',
                    'Ak si chceš vybrať medzi balanced stim, high-stim a non-stim variantom.',
                    'Ak trénuješ večer alebo si citlivejší na kofeín a nechceš si rozhodit spánok.',
                ],
                'faq' => [
                    ['q' => 'Je silnejší pre-workout automaticky lepší?', 'a' => 'Nie. Pre väčšinu ľudí dáva väčší zmysel balanced stim, ktorý zlepší tréning bez zbytočne prestreleného kofeínu.'],
                    ['q' => 'Má zmysel non-stim pre-workout?', 'a' => 'Áno, najmä pri večernom tréningu alebo ak sa chceš vyhnúť kofeínu a riešiš skôr pumpu a fokus.'],
                    ['q' => 'Na čo sa mám v zložení pozerať ako prvé?', 'a' => 'Najpraktickejšie je sledovať kofeín, citrulín a beta-alanín a nie len počet zložiek na etikete.'],
                ],
            ],
            'probiotika-ako-vybrat' => [
                'audience' => [
                    'Ak si chceš vybrať probiotiká podľa použitia, nie len podľa veľkého čísla CFU na obale.',
                    'Ak riešiš trávenie, dlhodobejšiu rutinu alebo citlivejšie brucho.',
                    'Ak chceš vedieť, čo sledovať pri kmeňoch, dávkovaní a praktickom používaní.',
                ],
                'faq' => [
                    ['q' => 'Je viac CFU automaticky lepšie?', 'a' => 'Nie. Dôležité sú aj konkrétne kmene, cieľ použitia a to, či produkt vieš užívať pravidelne.'],
                    ['q' => 'Má zmysel brať probiotiká len nárazovo?', 'a' => 'Pri veľa situáciách väčší zmysel dáva konzistentné používanie než náhodný jednorazový nákup.'],
                    ['q' => 'Sú dôležité konkrétne kmene?', 'a' => 'Áno. Pri probiotikách je často užitočnejšie sledovať konkrétny kmeň a použitie než len marketingový opis produktu.'],
                ],
            ],
            'veganske-proteiny-top-vyber-2025' => [
                'audience' => [
                    'Ak chceš rastlinný proteín bez zbytočného tápania medzi hrachom, ryžou a blendmi.',
                    'Ak riešiš vegánsku alternatívu ku klasickému srvátkovému proteínu.',
                    'Ak chceš proteín, ktorý sa dá používať aj do smoothie, kaší alebo bežnej rutiny.',
                ],
                'faq' => [
                    ['q' => 'Je vegánsky proteín automaticky slabší než srvátka?', 'a' => 'Nie automaticky. Veľa závisí od kombinácie zdrojov bielkovín, použitia a toho, či ti chutí a vieš ho dlhodobo používať.'],
                    ['q' => 'Je lepší blend alebo čistý hrachový proteín?', 'a' => 'Blend býva univerzálnejší, ale pri niektorých ľuďoch môže dávať zmysel aj jednoduchší produkt s čistejším zložením.'],
                    ['q' => 'Hodí sa vegánsky proteín aj do receptov?', 'a' => 'Áno. Práve pri rastlinných proteínoch býva kuchynské použitie často jedna z najpraktickejších výhod.'],
                ],
            ],
        ];
    }
}

if (!function_exists('interessa_article_enhancements')) {
    function interessa_article_enhancements(string $slug): array {
        $registry = interessa_article_enhancements_registry();
        $canonicalSlug = canonical_article_slug($slug);
        return $registry[$canonicalSlug] ?? $registry[$slug] ?? [];
    }
}

if (!function_exists('interessa_article_audience_items')) {
    function interessa_article_audience_items(string $slug): array {
        $items = interessa_article_enhancements($slug)['audience'] ?? [];
        return array_values(array_filter(is_array($items) ? $items : [], 'is_string'));
    }
}

if (!function_exists('interessa_article_faq_items')) {
    function interessa_article_faq_items(string $slug): array {
        $faq = interessa_article_enhancements($slug)['faq'] ?? [];
        return array_values(array_filter(is_array($faq) ? $faq : [], static function ($item): bool {
            return is_array($item) && trim((string) ($item['q'] ?? '')) !== '' && trim((string) ($item['a'] ?? '')) !== '';
        }));
    }
}

if (!function_exists('interessa_article_faq_schema')) {
    function interessa_article_faq_schema(array $faq): ?array {
        if ($faq === []) {
            return null;
        }

        $items = [];
        foreach ($faq as $qa) {
            $question = trim((string) ($qa['q'] ?? ''));
            $answer = trim((string) ($qa['a'] ?? ''));
            if ($question === '' || $answer === '') {
                continue;
            }

            $items[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        if ($items === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $items,
        ];
    }
}

if (!function_exists('interessa_render_article_audience_box')) {
    function interessa_render_article_audience_box(string $slug): void {
        $items = interessa_article_audience_items($slug);
        if ($items === []) {
            return;
        }

        echo '<section class="article-audience">';
        echo '<div class="section-head">';
        echo '<h2>Pre koho je článok</h2>';
        echo '<p class="meta">Rýchla orientácia, či je tento článok relevantný pre tvoju situáciu.</p>';
        echo '</div>';
        echo '<div class="article-audience-box">';
        echo '<ul class="article-audience-list">';
        foreach ($items as $item) {
            echo '<li>' . esc($item) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '</section>';
    }
}

if (!function_exists('interessa_render_article_faq_box')) {
    function interessa_render_article_faq_box(string $slug): void {
        $faq = interessa_article_faq_items($slug);
        if ($faq === []) {
            return;
        }

        echo '<section class="article-faq">';
        echo '<div class="section-head">';
        echo '<h2>Časté otázky</h2>';
        echo '<p class="meta">Krátke odpovede na veci, ktoré si čitatelia pri tejto téme riešia najčastejšie.</p>';
        echo '</div>';
        echo '<div class="article-faq-list">';
        foreach ($faq as $item) {
            echo '<details class="article-faq-item">';
            echo '<summary>' . esc((string) $item['q']) . '</summary>';
            echo '<div class="article-faq-answer"><p>' . esc((string) $item['a']) . '</p></div>';
            echo '</details>';
        }
        echo '</div>';
        echo '</section>';
    }
}