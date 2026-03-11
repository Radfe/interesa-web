<?php
declare(strict_types=1);

if (!function_exists('interessa_article_enhancements_registry')) {
    function interessa_article_enhancements_registry(): array {
        return [
            'doplnky-vyzivy' => [
                'audience' => [
                    'Ak si chces upratat zakladne doplnky a neist po kazdom novom trende.',
                    'Ak riesis rozumny startovaci stack pre energiu, regeneraciu a kazdodennu rutinu.',
                    'Ak chces vediet, ktore doplnky davaju zmysel skor nez zacnes riesit drahe speciality.',
                ],
                'faq' => [
                    ['q' => 'Musim brat vela doplnkov naraz?', 'a' => 'Nie. Pre vacsinu ludi dava vacsi zmysel par zakladnych doplnkov podla ciela nez dlhy a drahy stack.'],
                    ['q' => 'Je kreatin vhodny aj mimo siloveho treningu?', 'a' => 'Ano, ak riesis vykon a pravidelny trening. Nie je vsak nutny pre kazdeho, kto chce len zlepsit bezny jedalnicek.'],
                    ['q' => 'Ktory doplnok ma najlepsi pomer cena a prinos?', 'a' => 'Zavisi od ciela. Casto davaju najvacsi zmysel kreatin, vitamin D3 podla sezony a horcik vo vhodnej forme.'],
                ],
            ],
            'najlepsie-proteiny-2025' => [
                'audience' => [
                    'Ak chces rychly shortlist najlepsich proteinov bez dlheho porovnavania e-shopov.',
                    'Ak hladis univerzalny protein po treningu aj pocas dna.',
                    'Ak sa chces zorientovat medzi value WPC, cistejsim izolatom a sportovejsim blendom.',
                ],
                'faq' => [
                    ['q' => 'Je lepsi koncentrat alebo izolat?', 'a' => 'Pre vacsinu ludi staci kvalitny koncentrat. Izolat dava vacsi zmysel pri nizsej tolerancii laktozy alebo pri doraze na cistejsi profil.'],
                    ['q' => 'Ma zmysel riesit protein, ak viem bielkoviny dojest?', 'a' => 'Protein nie je povinny. Je to hlavne prakticky sposob, ako si ulahcit doplnenie bielkovin v dnoch, ked ich stravou nestihas.'],
                    ['q' => 'Kedy sa oplati platit viac za drahsi protein?', 'a' => 'Najma ked riesis nizsi obsah laktozy, vyssi komfort travenia alebo cistejsie zlozenie pocas diety.'],
                ],
            ],
            'protein-na-chudnutie' => [
                'audience' => [
                    'Ak chces protein do redukcie bez zbytocne vysokych kalorii navyse.',
                    'Ak sa rozhodujes medzi WPC, WPI a clear proteinom pri chudnuti.',
                    'Ak chces vediet, ci sa pri diete naozaj oplati platit viac za izolat.',
                ],
                'faq' => [
                    ['q' => 'Je pri chudnuti vzdy najlepsi izolat?', 'a' => 'Nie vzdy. Izolat je prakticky pri nizsej laktoze a cistejsom profile, ale pri dobre nastavenom jedalnicku moze stacit aj kvalitny koncentrat.'],
                    ['q' => 'Pomoze mi protein schudnut sam o sebe?', 'a' => 'Nie. Protein pomaha hlavne so sytostou a doplnenim bielkovin, ale o chudnuti stale rozhoduje celkovy prijem energie a dlhodoby rezim.'],
                    ['q' => 'Je clear protein lepsi na leto alebo do diety?', 'a' => 'Pre niektorych ano, lebo je lahsi na pitie a menej hutny. Nutricne to vsak nie je automaticky lepsia volba pre kazdeho.'],
                ],
            ],
            'kreatin-porovnanie' => [
                'audience' => [
                    'Ak chces zistit, ci ti staci obycajny monohydrat alebo riesit aj HCl a ine formy.',
                    'Ak hladis kreatin s najlepsim pomerom dokazy, cena a prakticke pouzitie.',
                    'Ak si nie si isty davkovanim, nasycovanim alebo tym, kedy kreatin brat.',
                ],
                'faq' => [
                    ['q' => 'Musim robit nasycovaciu fazu?', 'a' => 'Nie. Pre vacsinu ludi staci pravidelnych 3 az 5 gramov denne a efekt sa dostavi aj bez nasycovania.'],
                    ['q' => 'Je HCl lepsi nez monohydrat?', 'a' => 'Nie vseobecne. Monohydrat ma najlepsie data a cenu. HCl je skor alternativa pre ludi, ktori chcu mensiu davku alebo lepsiu rozpustnost.'],
                    ['q' => 'Kedy je najlepsi cas na kreatin?', 'a' => 'Dolezitejsia nez presny cas je pravidelnost. Kreatin mozes brat kedykolvek pocas dna, ked sa ti to da dlhodobo drzat.'],
                ],
            ],
            'horcik-ktory-je-najlepsi-a-preco' => [
                'audience' => [
                    'Ak sa stracas medzi bisglycinatom, citratom, malatom a oxidom horecnatym.',
                    'Ak chces vybrat horcik podla ciela, nie podla najhlasnejsieho marketingu.',
                    'Ak riesis stres, spanok, krce alebo bezne denne doplnanie mineralov.',
                ],
                'faq' => [
                    ['q' => 'Ktora forma horcika je najsetrnejsia?', 'a' => 'Casto dobre vychadza bisglycinat, najma ak riesis toleranciu a vecerne pouzitie.'],
                    ['q' => 'Je citrat zly, ked ho ma vela znaciek?', 'a' => 'Nie. Citrat je casto dobra univerzalna volba, len u citlivejsich ludi moze viac rozhybat travenie.'],
                    ['q' => 'Oplati sa kupovat lacny oxid horecnaty?', 'a' => 'Skor nie ako prvu volbu. Pri praktickom pouzivani zvyknu davat viac zmysel lepsie vstrebatelne formy.'],
                ],
            ],
            'kolagen-recenzia' => [
                'audience' => [
                    'Ak chces rozlisit kolagen na pokozku od kolagenu orientovaneho viac na klby.',
                    'Ak nevies, ci sledovat typ kolagenu, davku alebo pridany vitamin C.',
                    'Ak hladis jednoduchy kolagen do kazdodennej rutiny bez marketingoveho chaosu.',
                ],
                'faq' => [
                    ['q' => 'Ma pri kolagene zmysel vitamin C?', 'a' => 'Ano, casto je to rozumny doplnok, lebo vitamin C suvisi s tvorbou kolagenu. Nie vzdy vsak musi byt priamo v tom istom produkte.'],
                    ['q' => 'Je dolezitejsi typ kolagenu alebo davka?', 'a' => 'V praxi treba sledovat oboje. Typ ti napovie ucel produktu a davka zas to, ci ma pouzivanie vobec sancu davat zmysel.'],
                    ['q' => 'Pomoze kolagen okamzite?', 'a' => 'Nie. Pri kolagene je dolezitejsia dlhodobost a pravidelnost nez rychly efekt po par dnoch.'],
                ],
            ],
            'kolagen-na-klby-porovnanie' => [
                'audience' => [
                    'Ak riesis kolagen cielene na klby, slachy alebo dlhodobu podporu pohyboveho aparatu.',
                    'Ak chces vediet, co ma pri klboch vacsi vyznam nez len pekna etiketa.',
                    'Ak si potrebujes vybrat medzi jednoduchym kolagenom a komplexnejsim klbovym produktom.',
                ],
                'faq' => [
                    ['q' => 'Je na klby lepsi specificky typ kolagenu?', 'a' => 'Casto ano, preto sa pri tejto teme oplati sledovat typ kolagenu a nie len marketingovy nazov produktu.'],
                    ['q' => 'Pomaha vyssia davka automaticky viac?', 'a' => 'Nie vzdy. Dolezite je, aby davala zmysel forma, ciel pouzitia a dlhodoba konzistencia.'],
                    ['q' => 'Ma zmysel brat kolagen len obcas?', 'a' => 'Skor nie. Pri tejto kategorii byva dolezitejsia pravidelnost a dlhsi horizont pouzivania.'],
                ],
            ],
            'pre-workout-ako-vybrat' => [
                'audience' => [
                    'Ak chces predtreningovku, ale nevies odhadnut vhodnu silu stimulantov.',
                    'Ak si chces vybrat medzi balanced stim, high-stim a non-stim variantom.',
                    'Ak trenujes vecer alebo si citlivejsi na kofein a nechces si rozhodit spanok.',
                ],
                'faq' => [
                    ['q' => 'Je silnejsi pre-workout automaticky lepsi?', 'a' => 'Nie. Pre vacsinu ludi dava vacsi zmysel balanced stim, ktory zlepsi trening bez zbytocne prestreleneho kofeinu.'],
                    ['q' => 'Ma zmysel non-stim pre-workout?', 'a' => 'Ano, najma pri vecernom treningu alebo ak sa chces vyhnut kofeinu a riesis skor pumpu a fokus.'],
                    ['q' => 'Na co sa mam v zlozeni pozerat ako prve?', 'a' => 'Najpraktickejsie je sledovat kofein, citrulin a beta-alanin a nie len pocet zloziek na etikete.'],
                ],
            ],
            'probiotika-ako-vybrat' => [
                'audience' => [
                    'Ak si chces vybrat probiotika podla pouzitia, nie len podla velkeho cisla CFU na obale.',
                    'Ak riesis travenie, dlhodobejsiu rutinu alebo citlivejsie brucho.',
                    'Ak chces vediet, co sledovat pri kmenoch, davkovani a praktickom pouzivani.',
                ],
                'faq' => [
                    ['q' => 'Je viac CFU automaticky lepsie?', 'a' => 'Nie. Dolezite su aj konkretne kmene, ciel pouzitia a to, ci produkt vies uzivat pravidelne.'],
                    ['q' => 'Ma zmysel brat probiotika len narazovo?', 'a' => 'Pri vela situaciach vacsi zmysel dava konzistentne pouzivanie nez nahodny jednorazovy nakup.'],
                    ['q' => 'Su dolezite konkretne kmene?', 'a' => 'Ano. Pri probiotikach je casto uzitocnejsie sledovat konkretny kmen a pouzitie nez len marketingovy opis produktu.'],
                ],
            ],
            'veganske-proteiny-top-vyber-2025' => [
                'audience' => [
                    'Ak chces rastlinny protein bez zbytocneho tapania medzi hrachom, ryzou a blendmi.',
                    'Ak riesis vegansku alternativu ku klasickemu srvatkovemu proteinu.',
                    'Ak chces protein, ktory sa da pouzivat aj do smoothie, kasi alebo beznej rutiny.',
                ],
                'faq' => [
                    ['q' => 'Je vegansky protein automaticky slabsi nez srvatka?', 'a' => 'Nie automaticky. Vela zavisi od kombinacie zdrojov bielkovin, pouzitia a toho, ci ti chuti a vies ho dlhodobo pouzivat.'],
                    ['q' => 'Je lepsi blend alebo cisty hrachovy protein?', 'a' => 'Blend byva univerzalnejsi, ale pri niektorych ludoch moze davat zmysel aj jednoduchsi produkt s cistejsim zlozenim.'],
                    ['q' => 'Hodi sa vegansky protein aj do receptov?', 'a' => 'Ano. Prave pri rastlinnych proteinoch byva kuchynske pouzitie casto jedna z najpraktickejsich vyhod.'],
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
        echo '<h2>Pre koho je clanok</h2>';
        echo '<p class="meta">Rychla orientacia, ci je tento clanok relevantny pre tvoju situaciu.</p>';
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
        echo '<h2>Caste otazky</h2>';
        echo '<p class="meta">Kratke odpovede na veci, ktore si citatelia pri tejto teme riesia najcastejsie.</p>';
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