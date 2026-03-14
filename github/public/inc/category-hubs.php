<?php
declare(strict_types=1);

if (!function_exists('interessa_category_hubs')) {
    function interessa_category_hubs(): array {
        return [
            'proteiny' => [
                'title' => 'Zdrave proteiny',
                'description' => 'Srvatkove WPC/WPI, veganske a clear proteiny. Ako vybrat podla ciela, chuti a rozpoctu.',
                'intro' => 'Tato kategoria zdruzuje hlavne nakupne aj edukacne clanky o proteinoch. Zacni podla ciela: univerzalne pouzitie, chudnutie, veganska alternativa alebo detailne porovnanie WPC vs WPI.',
                'focus_points' => [
                    'Najprv si ujasni ciel: bezne doplnanie bielkovin, redukcia, tolerancia laktozy alebo rastlinna alternativa.',
                    'Sleduj cenu na davku, nie len cenu za balenie.',
                    'Pri diete sleduj cukry, tuky a typ proteinu na porciu.',
                ],
                'featured_guides' => [
                    ['slug' => 'najlepsie-proteiny-2026', 'label' => 'Top vyber', 'description' => 'Rychly shortlist najlepsich proteinov podla typu pouzitia.'],
                    ['slug' => 'protein-na-chudnutie', 'label' => 'Chudnutie', 'description' => 'Ako vybrat protein do diety bez zbytocnych kalorii navyse.'],
                    ['slug' => 'srvatkovy-protein-vs-izolat-vs-hydro', 'label' => 'Porovnanie', 'description' => 'Jasny rozdiel medzi WPC, WPI a hydrolyzatom.'],
                    ['slug' => 'veganske-proteiny-top-vyber-2026', 'label' => 'Veganske', 'description' => 'Najlepsie rastlinne proteiny a co sledovat pri vybere.'],
                ],
            ],
            'vyziva' => [
                'title' => 'Zdrava vyziva',
                'description' => 'Snacky, ranajky, travenie a zmysluplne doplnky pre kazdodennu rutinu.',
                'intro' => 'Kategoria pre citatela, ktory nechce len jeden produkt, ale chce sa zorientovat v zakladoch kazdodennej vyzivy. Najdes tu vyber doplnkov, probiotik aj prakticke sprievodcovske clanky.',
                'focus_points' => [
                    'Zacni tym, co riesis najcastejsie: travenie, energia, mikroziviny alebo lahke doplnanie bielkovin.',
                    'Preferuj jednoduche stacky pred zbytocne sirokou kombinaciou doplnkov.',
                    'Kontroluj davku, formu a to, ci produkt riesi realny problem.',
                ],
                'featured_guides' => [
                    ['slug' => 'doplnky-vyzivy', 'label' => 'Zaklad', 'description' => 'Zakladny shortlist doplnkov, ktore davaju zmysel pre vacsinu ludi.'],
                    ['slug' => 'probiotika-ako-vybrat', 'label' => 'Travenie', 'description' => 'Ako citat kmene, CFU a vyberat probiotika podla pouzitia.'],
                    ['slug' => 'probiotika-a-travenie', 'label' => 'Sprievodca', 'description' => 'Prakticky uvod do vztahu probiotik a travenia.'],
                ],
            ],
            'mineraly' => [
                'title' => 'Vitaminy a mineraly',
                'description' => 'Horcik, vitamin D3, vitamin C, zinok a dalsie mikroziviny v praktickom kontexte.',
                'intro' => 'Tu riesime zakladne mikroziviny, ktore maju najvacsi prakticky vyznam. Namiesto marketingovych slubov sa orientujeme podla formy, davky a toho, kedy ktory mineral alebo vitamin dava zmysel.',
                'focus_points' => [
                    'Pri horciku sleduj formu, nie len vysoke cislo na etikete.',
                    'Pri D3 a zinku ma zmysel kontext: sezona, strava a realny deficit.',
                    'Menej je casto viac nez siroke megadavkove kombinacie.',
                ],
                'featured_guides' => [
                    ['slug' => 'horcik-ktory-je-najlepsi-a-preco', 'label' => 'Top vyber', 'description' => 'Najlepsie formy horcika a ako sa rozhodnut medzi nimi.'],
                    ['slug' => 'vitamin-d3-a-imunita', 'label' => 'D3', 'description' => 'Kedy dava vitamin D3 zmysel a ako suvisi s imunitou.'],
                    ['slug' => 'zinek', 'label' => 'Zinok', 'description' => 'Zinok v praxi: kedy ho zaradit a co od neho cakat.'],
                    ['slug' => 'vitamin-c', 'label' => 'Vitamin C', 'description' => 'Kedy je vitamin C uzitocny a kedy je to skor rutina bez vacsieho efektu.'],
                ],
            ],
            'imunita' => [
                'title' => 'Imunita',
                'description' => 'D3, vitamin C, zinok, probiotika a navyky, ktore podporuju obranyschopnost.',
                'intro' => 'Imunita nie je o jednom zazracnom produkte. Najvacsi zmysel maju zakladne ziviny, spanok, pravidelnost a rozumny vyber podla sezony a zivotneho stylu.',
                'focus_points' => [
                    'Zacni spankom, stravou a zakladnymi navykmi, az potom ries konkretne doplnky.',
                    'D3, zinok a probiotika davaju zmysel v roznych situaciach, nie ako univerzalny balik pre kazdeho.',
                    'Pri dlhom uzivani sa oplati sledovat konzistentnost viac nez kratkodobe megadavky.',
                ],
                'featured_guides' => [
                    ['slug' => 'vitamin-d3-a-imunita', 'label' => 'D3', 'description' => 'Najpraktickejsi startovaci clanok pre podporu imunity.'],
                    ['slug' => 'vitamin-c', 'label' => 'Vitamin C', 'description' => 'Co od vitaminu C realne cakat a kde ma hranice.'],
                    ['slug' => 'zinek', 'label' => 'Zinok', 'description' => 'Kedy moze mat zinok vyznam a ako ho zaradit rozumne.'],
                    ['slug' => 'probiotika-a-travenie', 'label' => 'Probiotika', 'description' => 'Vztah travenia, mikrobiomu a imunity v praktickom prehlade.'],
                ],
            ],
            'sila' => [
                'title' => 'Sila a vykon',
                'description' => 'Kreatin, pre-workout a dalsie doplnky, ktore davaju zmysel pri treningovom vykone.',
                'intro' => 'Tato kategoria je postavena okolo doplnkov, ktore maju najvyssi prakticky prinos pre vykon. Jadrom je kreatin, okolo neho pre-workout a rozumne nacasovanie podla treningu.',
                'focus_points' => [
                    'Ak riesis vykon, zacni kreatinom skor nez zlozitejsimi stackmi.',
                    'Pre-workout vyberaj podla tolerancie kofeinu a casu treningu.',
                    'Pri vykonovych doplnkoch je pravidelnost dolezitejsia nez marketingove nazvy foriem.',
                ],
                'featured_guides' => [
                    ['slug' => 'kreatin-porovnanie', 'label' => 'Top vyber', 'description' => 'Najdolezitejsi clanok pre vyber kreatinu bez zbytocneho chaosu.'],
                    ['slug' => 'kreatin-monohydrat-vs-hcl', 'label' => 'Monohydrat vs HCl', 'description' => 'Kedy ma zmysel ostat pri monohydrate a kedy riesit alternativu.'],
                    ['slug' => 'pre-workout-ako-vybrat', 'label' => 'Pre-workout', 'description' => 'Ako si vybrat predtreningovku podla stimu, pumpy a casu treningu.'],
                    ['slug' => 'kedy-brat-kreatin-a-kolko', 'label' => 'Davkovanie', 'description' => 'Kolko kreatinu brat a ci riesit nacasovanie.'],
                ],
            ],
            'klby-koza' => [
                'title' => 'Klby a koza',
                'description' => 'Kolagen, klbova vyziva a dlhodoba podpora pokozky a spojivovych tkaniv.',
                'intro' => 'Klby a koza su typicka kategoria, kde sa ludia stracaju medzi formou kolagenu, davkou a marketingom. Tu je zameranie na to, co sa oplati sledovat a aky clanok si otvorit ako prvy.',
                'focus_points' => [
                    'Sleduj typ kolagenu a davku na porciu, nie len velky napis kolagen na obale.',
                    'Pri klboch ma zmysel dlhodobost a konzistencia viac nez rychly efekt.',
                    'Prakticka kombinacia s vitaminom C dava casto vacsi zmysel nez zlozite blendy bez jasnej davky.',
                ],
                'featured_guides' => [
                    ['slug' => 'kolagen-na-klby-porovnanie', 'label' => 'Klby', 'description' => 'Prvy clanok, ak riesis kolagen cielene na klby.'],
                    ['slug' => 'kolagen-recenzia', 'label' => 'Recenzia', 'description' => 'Komercny shortlist kolagenov podla pouzitia a zlozenia.'],
                    ['slug' => 'kolagen', 'label' => 'Zaklad', 'description' => 'Zakladny sprievodca tym, co kolagen je a ako sa v nom zorientovat.'],
                ],
            ],
            'aminokyseliny' => [
                'title' => 'Aminokyseliny',
                'description' => 'BCAA, EAA a aminokyseliny pre regeneraciu, vykon a trening v praktickom kontexte.',
                'intro' => 'Aminokyseliny byvaju casto preplnene marketingom. Tato sekcia oddeluje, kedy ma zmysel riesit BCAA, kedy EAA a kedy ti v skutocnosti viac pomoze obycajny protein a celkovy prijem bielkovin.',
                'focus_points' => [
                    'Najprv sleduj celkovy prijem bielkovin, az potom ries samostatne aminokyseliny.',
                    'EAA byvaju praktickejsie pri nizkom prijme bielkovin alebo treningu nalacno.',
                    'BCAA same o sebe zriedka porazia kvalitny protein alebo vyvazene EAA.',
                ],
                'featured_guides' => [
                    ['slug' => 'aminokyseliny-bcaa-eaa', 'label' => 'Zaklad', 'description' => 'Prakticky uvod do aminokyselin, BCAA a EAA.'],
                    ['slug' => 'bcaa-vs-eaa', 'label' => 'Porovnanie', 'description' => 'Kedy davaju zmysel BCAA a kedy je lepsia volba EAA.'],
                ],
            ],
            'chudnutie' => [
                'title' => 'Chudnutie',
                'description' => 'Redukcia tuku, proteiny do diety a triezvy pohlad na spalovace tukov.',
                'intro' => 'Tato kategoria spaja clanky pre realnu redukciu tuku bez zbytocnych skratiek. Zakladom je deficit, dostatok bielkovin, rozumne navyky a opatrnost pri marketingu okolo spalovacov.',
                'focus_points' => [
                    'Pri chudnuti rozhoduje hlavne kaloricky deficit a udrzatelny rezim.',
                    'Protein pomaha so sytostou a zachovanim svalov, nie je to magicky spalovac.',
                    'Spalovace tukov su skor doplnok marketingu nez zakladny nastroj redukcie.',
                ],
                'featured_guides' => [
                    ['slug' => 'chudnutie-tip', 'label' => 'Zaklad', 'description' => 'Rychle tipy na chudnutie, ktore maju realny zaklad.'],
                    ['slug' => 'protein-na-chudnutie', 'label' => 'Protein', 'description' => 'Ako si vybrat protein, ktory dava zmysel pri diete.'],
                    ['slug' => 'najlepsi-protein-na-chudnutie-wpc-vs-wpi', 'label' => 'WPC vs WPI', 'description' => 'Co je pri redukcii praktickejsie: koncentrat alebo izolat.'],
                    ['slug' => 'spalovace-tukov-realita', 'label' => 'Spalovace', 'description' => 'Co je pri spalovacoch realita a co len marketing.'],
                ],
            ],
            'doplnkove-prislusenstvo' => [
                'title' => 'Doplnkove prislusenstvo',
                'description' => 'Prakticke doplnky, rutina a pomocne veci okolo suplementacie a treningu.',
                'intro' => 'Tato kategoria je zatial skor podporny hub nez plnohodnotna obsahova sekcia. Zameriava sa na prakticku stranku suplementacie: co ma zmysel riesit v rutine, davkovani a organizacii doplnkov.',
                'focus_points' => [
                    'Najprv si uprac rutinu a davkovanie, az potom kupuj dalsie produkty navyse.',
                    'Praktickost pouzivania casto rozhoduje o tom, ci doplnok budes realne uzivat dlhodobo.',
                    'Pomocne prislusenstvo ma zmysel len vtedy, ked zjednodusuje konzistentnost.',
                ],
                'featured_guides' => [
                    ['slug' => 'doplnky-vyzivy', 'label' => 'Rutina', 'description' => 'Zakladny shortlist doplnkov, ktory pomoze upratat kazdodennu suplementaciu.'],
                    ['slug' => 'kedy-brat-kreatin-a-kolko', 'label' => 'Davkovanie', 'description' => 'Prakticky clanok o nacasovani a jednoduchosti davkovania kreatinu.'],
                    ['slug' => 'pre-workout-ako-vybrat', 'label' => 'Trening', 'description' => 'Ako si vybrat predtreningovku bez zbytocneho chaosu a prestreleneho stimu.'],
                ],
            ],
            'kreatin' => [
                'title' => 'Kreatin',
                'description' => 'Monohydrat, HCl, davkovanie, nasycovanie a rozdiely medzi najcastejsimi formami kreatinu.',
                'intro' => 'Ak riesis kreatin detailne, toto je tvoj specializovany hub. Najdes tu porovnanie foriem, davkovanie, nacasovanie aj najcastejsie myty a obavy okolo vedlajsich ucinkov.',
                'focus_points' => [
                    'Pre vacsinu ludi ma najvacsi zmysel kvalitny monohydrat.',
                    'Nacasovanie nie je tak dolezite ako pravidelnost denneho uzivania.',
                    'Mnohe obavy z kreatinu vychadzaju skor z mytov nez zo studii.',
                ],
                'featured_guides' => [
                    ['slug' => 'kreatin-porovnanie', 'label' => 'Top vyber', 'description' => 'Najlepsi startovaci clanok pre vyber kreatinu.'],
                    ['slug' => 'kedy-brat-kreatin-a-kolko', 'label' => 'Davkovanie', 'description' => 'Kolko kreatinu brat, ci robit pauzy a ako riesit nacasovanie.'],
                    ['slug' => 'kreatin-monohydrat-vs-hcl', 'label' => 'Porovnanie', 'description' => 'Monohydrat vs HCl v praktickom porovnani.'],
                    ['slug' => 'kreatin-vedlajsie-ucinky-a-fakty', 'label' => 'Fakty', 'description' => 'Vedlajsie ucinky kreatinu a co hovori vyskum.'],
                ],
            ],
            'pre-workout' => [
                'title' => 'Pre-workout',
                'description' => 'Predtreningovky, stimulanty, pumpa a ako vyberat podla casu treningu a tolerancie kofeinu.',
                'intro' => 'Predtreningovka nie je len o tom, kolko kofeinu citis po prvej odmerke. Tato sekcia pomaha rozlisit balanced stim, high-stim a non-stim pristup podla realneho pouzitia.',
                'focus_points' => [
                    'Najdolezitejsie je sledovat kofein, citrulin a beta-alanin v realnych davkach.',
                    'Silnejsia predtreningovka nie je automaticky lepsia volba.',
                    'Vecerny trening si casto pyta non-stim alebo miernejsi stim profil.',
                ],
                'featured_guides' => [
                    ['slug' => 'pre-workout-ako-vybrat', 'label' => 'Vyber', 'description' => 'Ako si vybrat pre-workout podla stimu, pumpy a casu treningu.'],
                    ['slug' => 'pre-workout', 'label' => 'Zaklad', 'description' => 'Zakladny sprievodca tym, co predtreningovka robi a kedy ju zaradit.'],
                ],
            ],
            'probiotika-travenie' => [
                'title' => 'Probiotika a travenie',
                'description' => 'Probiotika, kmene, CFU a prakticky vyber podla travenia a dlhodobeho pouzitia.',
                'intro' => 'Tato kategoria je postavena na praktickom vybere probiotik. Namiesto pocitania co najvyssich CFU sa sustredujeme na konkretne kmene, pouzitie a to, ako produkt zapada do realneho rezimu.',
                'focus_points' => [
                    'Dolezitejsie nez velke cislo CFU byvaju konkretne kmene a sposob pouzitia.',
                    'Probiotika maju vacsi zmysel pri konzistentnom pouzivani nez ako nahodny jednorazovy nakup.',
                    'Pri traveni sa oplati riesit aj stravu, vlakninu a celkovy rezim, nie len kapsuly.',
                ],
                'featured_guides' => [
                    ['slug' => 'probiotika-ako-vybrat', 'label' => 'Vyber', 'description' => 'Ako citat probiotika, CFU a kmene bez marketingoveho chaosu.'],
                    ['slug' => 'probiotika-a-travenie', 'label' => 'Sprievodca', 'description' => 'Prakticky uvod do probiotik a travenia.'],
                    ['slug' => 'imunita-prirodne-latky-ktore-funguju', 'label' => 'Suvislosti', 'description' => 'Sirsi kontext latok, ktore podporuju imunitu vratane ulohy travenia.'],
                ],
            ],
        ];
    }
}

if (!function_exists('interessa_category_hub')) {
    function interessa_category_hub(string $slug): ?array {
        $hubs = interessa_category_hubs();
        return $hubs[$slug] ?? null;
    }
}

if (!function_exists('interessa_cross_theme_map')) {
    function interessa_cross_theme_map(): array {
        return [
            'proteiny' => [
                ['type' => 'category', 'slug' => 'chudnutie', 'label' => 'Chudnutie', 'description' => 'Ak protein riesis hlavne do diety a redukcie tuku.'],
                ['type' => 'article', 'slug' => 'veganske-proteiny-top-vyber-2026', 'label' => 'Veganske', 'description' => 'Ak hladas rastlinnu alternativu bez mliecnej zlozky.'],
                ['type' => 'category', 'slug' => 'sila', 'label' => 'Sila a vykon', 'description' => 'Ak ta po proteine zaujimaju aj kreatin, regeneracia a treningovy progres.'],
            ],
            'chudnutie' => [
                ['type' => 'category', 'slug' => 'proteiny', 'label' => 'Proteiny', 'description' => 'Ak si chces najprv upratat typy proteinov a ich pouzitie.'],
                ['type' => 'category', 'slug' => 'vyziva', 'label' => 'Zdrava vyziva', 'description' => 'Ak okrem proteinu riesis aj kazdodennu rutinu a zakladne doplnky.'],
                ['type' => 'article', 'slug' => 'spalovace-tukov-realita', 'label' => 'Spalovace tukov', 'description' => 'Ak chces oddelit realitu od marketingu pri redukcnych doplnkoch.'],
            ],
            'sila' => [
                ['type' => 'category', 'slug' => 'kreatin', 'label' => 'Kreatin', 'description' => 'Najkratsia cesta ku kreatinu, davkovaniu a porovnaniu foriem.'],
                ['type' => 'category', 'slug' => 'pre-workout', 'label' => 'Pre-workout', 'description' => 'Ak po kreatine riesis aj predtreningovku, stim a pumpu.'],
                ['type' => 'category', 'slug' => 'proteiny', 'label' => 'Proteiny', 'description' => 'Ak chces doriesit aj regeneraciu a doplnanie bielkovin po treningu.'],
            ],
            'kreatin' => [
                ['type' => 'category', 'slug' => 'sila', 'label' => 'Sila a vykon', 'description' => 'Sirsi kontext doplnkov na vykon, nie len samotny kreatin.'],
                ['type' => 'category', 'slug' => 'pre-workout', 'label' => 'Pre-workout', 'description' => 'Ak popri kreatine riesis aj akutny treningovy boost.'],
                ['type' => 'category', 'slug' => 'proteiny', 'label' => 'Proteiny', 'description' => 'Ak si po vykone chces upratat aj regeneraciu a prijem bielkovin.'],
            ],
            'pre-workout' => [
                ['type' => 'category', 'slug' => 'sila', 'label' => 'Sila a vykon', 'description' => 'Sirsi prehlad doplnkov, ktore davaju zmysel pri treningu.'],
                ['type' => 'category', 'slug' => 'kreatin', 'label' => 'Kreatin', 'description' => 'Ak chces zacat doplnkom s najsilnejsou dokazovou zakladnou.'],
                ['type' => 'category', 'slug' => 'proteiny', 'label' => 'Proteiny', 'description' => 'Ak po pre-workoute riesis aj regeneraciu a bielkoviny.'],
            ],
            'mineraly' => [
                ['type' => 'category', 'slug' => 'imunita', 'label' => 'Imunita', 'description' => 'Ak ta pri vitaminach a mineraloch zaujima hlavne obranyschopnost.'],
                ['type' => 'category', 'slug' => 'vyziva', 'label' => 'Zdrava vyziva', 'description' => 'Ak chces mikro ziviny zasadit do sirsej kazdodennej rutiny.'],
                ['type' => 'article', 'slug' => 'horcik-ktory-je-najlepsi-a-preco', 'label' => 'Horcik', 'description' => 'Jedna z najpraktickejsich vstupnych tem v tejto oblasti.'],
            ],
            'imunita' => [
                ['type' => 'category', 'slug' => 'mineraly', 'label' => 'Vitaminy a mineraly', 'description' => 'Ak si chces rozobrat D3, zinok a dalsie latky detailnejsie.'],
                ['type' => 'category', 'slug' => 'probiotika-travenie', 'label' => 'Probiotika a travenie', 'description' => 'Ak ta zaujima vztah travenia, mikrobiomu a imunity.'],
                ['type' => 'category', 'slug' => 'vyziva', 'label' => 'Zdrava vyziva', 'description' => 'Ak chces podporu imunity zasadit do sirsej rutiny a stravy.'],
            ],
            'klby-koza' => [
                ['type' => 'article', 'slug' => 'kolagen-recenzia', 'label' => 'Kolagen', 'description' => 'Najrychlejsia cesta ku konkretnemu vyberu kolagenu podla pouzitia.'],
                ['type' => 'category', 'slug' => 'vyziva', 'label' => 'Zdrava vyziva', 'description' => 'Ak chces klby a kozu riesit aj cez sirsi kazdodenny zaklad.'],
                ['type' => 'category', 'slug' => 'mineraly', 'label' => 'Vitaminy a mineraly', 'description' => 'Ak ta popri kolagene zaujima aj vitamin C a dalsie podporne latky.'],
            ],
            'vyziva' => [
                ['type' => 'category', 'slug' => 'mineraly', 'label' => 'Vitaminy a mineraly', 'description' => 'Ak si chces z kazdodennej rutiny odskocit ku konkretnym mikro zivinam.'],
                ['type' => 'category', 'slug' => 'probiotika-travenie', 'label' => 'Probiotika a travenie', 'description' => 'Ak ta v ramci vyzivy zaujima hlavne travenie a mikrobiom.'],
                ['type' => 'category', 'slug' => 'klby-koza', 'label' => 'Klby a koza', 'description' => 'Ak hladas temy, kde vyziva prechadza do cielenej suplementacie.'],
            ],
        ];
    }
}

if (!function_exists('interessa_cross_theme_paths')) {
    function interessa_cross_theme_paths(string $slug): array {
        $slug = normalize_category_slug($slug);
        $map = interessa_cross_theme_map();
        $items = $map[$slug] ?? [];
        $resolved = [];

        foreach ($items as $item) {
            $type = trim((string) ($item['type'] ?? 'category'));
            $targetSlug = trim((string) ($item['slug'] ?? ''));
            if ($targetSlug === '') {
                continue;
            }

            if ($type === 'article') {
                $meta = article_meta($targetSlug);
                $title = trim((string) ($meta['title'] ?? humanize_slug($targetSlug)));
                $resolved[] = [
                    'href' => article_url($targetSlug),
                    'title' => trim((string) ($item['label'] ?? $title)),
                    'description' => trim((string) ($item['description'] ?? '')),
                    'cta' => interessa_article_cta_label($targetSlug, $title),
                ];
                continue;
            }

            $meta = category_meta($targetSlug);
            if ($meta === null) {
                continue;
            }

            $resolved[] = [
                'href' => category_url($targetSlug),
                'title' => trim((string) ($item['label'] ?? $meta['title'])),
                'description' => trim((string) ($item['description'] ?? $meta['description'] ?? '')),
                'cta' => 'Otvorit temu',
            ];
        }

        return $resolved;
    }
}
