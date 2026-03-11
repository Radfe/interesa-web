<?php
declare(strict_types=1);

if (!function_exists('interessa_category_hubs')) {
    function interessa_category_hubs(): array {
        return [
            'proteiny' => [
                'title' => 'Zdravé proteíny',
                'description' => 'Srvátkové WPC/WPI, vegánske a clear proteíny. Ako vybrať podľa cieľa, chuti a rozpočtu.',
                'intro' => 'Táto kategória zhromažďuje hlavné nákupné aj edukačné články o proteínoch. Začni podľa cieľa: univerzálne použitie, chudnutie, vegánska alternatíva alebo detailné porovnanie WPC vs WPI.',
                'focus_points' => [
                    'Najprv si ujasni cieľ: bežné dopĺňanie bielkovín, redukcia, tolerancia laktózy alebo rastlinná alternatíva.',
                    'Sleduj cenu na dávku, nie len cenu za balenie.',
                    'Pri diéte sleduj cukry, tuky a typ proteínu na porciu.',
                ],
                'featured_guides' => [
                    ['slug' => 'najlepsie-proteiny-2025', 'label' => 'Top výber', 'description' => 'Rýchly shortlist najlepších proteínov podľa typu použitia.'],
                    ['slug' => 'protein-na-chudnutie', 'label' => 'Chudnutie', 'description' => 'Ako vybrať proteín do diéty bez zbytočných kalórií navyše.'],
                    ['slug' => 'srvatkovy-protein-vs-izolat-vs-hydro', 'label' => 'Porovnanie', 'description' => 'Jasný rozdiel medzi WPC, WPI a hydrolyzátom.'],
                    ['slug' => 'veganske-proteiny-top-vyber-2025', 'label' => 'Vegánske', 'description' => 'Najlepšie rastlinné proteíny a čo sledovať pri výbere.'],
                ],
            ],
            'vyziva' => [
                'title' => 'Zdravá výživa',
                'description' => 'Snacky, raňajky, trávenie a zmysluplné doplnky pre každodennú rutinu.',
                'intro' => 'Kategória pre čitateľa, ktorý nechce len jeden produkt, ale chce sa zorientovať v základoch každodennej výživy. Nájdeš tu výber doplnkov, probiotík aj praktické sprievodcovské články.',
                'focus_points' => [
                    'Začni tým, čo riešiš najčastejšie: trávenie, energia, mikroživiny alebo ľahké dopĺňanie bielkovín.',
                    'Preferuj jednoduché stacky pred zbytočne širokou kombináciou doplnkov.',
                    'Kontroluj dávku, formu a to, či produkt rieši reálny problém.',
                ],
                'featured_guides' => [
                    ['slug' => 'doplnky-vyzivy', 'label' => 'Základ', 'description' => 'Základný shortlist doplnkov, ktoré dávajú zmysel pre väčšinu ľudí.'],
                    ['slug' => 'probiotika-ako-vybrat', 'label' => 'Trávenie', 'description' => 'Ako čítať kmene, CFU a vyberať probiotiká podľa použitia.'],
                    ['slug' => 'probiotika-a-travenie', 'label' => 'Sprievodca', 'description' => 'Praktický úvod do vzťahu probiotík a trávenia.'],
                ],
            ],
            'mineraly' => [
                'title' => 'Vitamíny a minerály',
                'description' => 'Horčík, vitamín D3, vitamín C, zinok a ďalšie mikroživiny v praktickom kontexte.',
                'intro' => 'Tu riešime základné mikroživiny, ktoré majú najväčší praktický význam. Namiesto marketingových sľubov sa orientujeme podľa formy, dávky a toho, kedy ktorý minerál alebo vitamín dáva zmysel.',
                'focus_points' => [
                    'Pri horčíku sleduj formu, nie len vysoké číslo na etikete.',
                    'Pri D3 a zinku má zmysel kontext: sezóna, strava a reálny deficit.',
                    'Menej je často viac než široké megadávkové kombinácie.',
                ],
                'featured_guides' => [
                    ['slug' => 'horcik-ktory-je-najlepsi-a-preco', 'label' => 'Top výber', 'description' => 'Najlepšie formy horčíka a ako sa rozhodnúť medzi nimi.'],
                    ['slug' => 'vitamin-d3-a-imunita', 'label' => 'D3', 'description' => 'Kedy dáva vitamín D3 zmysel a ako súvisí s imunitou.'],
                    ['slug' => 'zinek', 'label' => 'Zinok', 'description' => 'Zinok v praxi: kedy ho zaradiť a čo od neho čakať.'],
                    ['slug' => 'vitamin-c', 'label' => 'Vitamín C', 'description' => 'Kedy je vitamín C užitočný a kedy je to skôr rutina bez väčšieho efektu.'],
                ],
            ],
            'imunita' => [
                'title' => 'Imunita',
                'description' => 'D3, vitamín C, zinok, probiotiká a návyky, ktoré podporujú obranyschopnosť.',
                'intro' => 'Imunita nie je o jednom zázračnom produkte. Najväčší zmysel majú základné živiny, spánok, pravidelnosť a rozumný výber podľa sezóny a životného štýlu.',
                'focus_points' => [
                    'Začni spánkom, stravou a základnými návykmi, až potom rieš konkrétne doplnky.',
                    'D3, zinok a probiotiká dávajú zmysel v rôznych situáciách, nie ako univerzálny balík pre každého.',
                    'Pri dlhšom užívaní sa oplatí sledovať konzistentnosť viac než krátkodobé megadávky.',
                ],
                'featured_guides' => [
                    ['slug' => 'vitamin-d3-a-imunita', 'label' => 'D3', 'description' => 'Najpraktickejší štartovací článok pre podporu imunity.'],
                    ['slug' => 'vitamin-c', 'label' => 'Vitamín C', 'description' => 'Čo od vitamínu C reálne čakať a kde má hranice.'],
                    ['slug' => 'zinek', 'label' => 'Zinok', 'description' => 'Kedy môže mať zinok význam a ako ho zaradiť rozumne.'],
                    ['slug' => 'probiotika-a-travenie', 'label' => 'Probiotiká', 'description' => 'Vzťah trávenia, mikrobiomu a imunity v praktickom prehľade.'],
                ],
            ],
            'sila' => [
                'title' => 'Sila a výkon',
                'description' => 'Kreatín, pre-workout a ďalšie doplnky, ktoré dávajú zmysel pri tréningovom výkone.',
                'intro' => 'Táto kategória je postavená okolo doplnkov, ktoré majú najvyšší praktický prínos pre výkon. Jadrom je kreatín, okolo neho pre-workout a rozumné načasovanie podľa tréningu.',
                'focus_points' => [
                    'Ak riešiš výkon, začni kreatínom skôr než zložitejšími stackmi.',
                    'Pre-workout vyberaj podľa tolerancie kofeínu a času tréningu.',
                    'Pri výkonových doplnkoch je pravidelnosť dôležitejšia než marketingové názvy foriem.',
                ],
                'featured_guides' => [
                    ['slug' => 'kreatin-porovnanie', 'label' => 'Top výber', 'description' => 'Najdôležitejší článok pre výber kreatínu bez zbytočného chaosu.'],
                    ['slug' => 'kreatin-monohydrat-vs-hcl', 'label' => 'Monohydrát vs HCl', 'description' => 'Kedy má zmysel ostať pri monohydráte a kedy riešiť alternatívu.'],
                    ['slug' => 'pre-workout-ako-vybrat', 'label' => 'Pre-workout', 'description' => 'Ako si vybrať predtréningovku podľa stimu, pumpy a času tréningu.'],
                    ['slug' => 'kedy-brat-kreatin-a-kolko', 'label' => 'Dávkovanie', 'description' => 'Koľko kreatínu brať a či riešiť načasovanie.'],
                ],
            ],
            'klby-koza' => [
                'title' => 'Kĺby a koža',
                'description' => 'Kolagén, kĺbová výživa a dlhodobá podpora pokožky a spojivových tkanív.',
                'intro' => 'Kĺby a koža sú typická kategória, kde sa ľudia strácajú medzi formou kolagénu, dávkou a marketingom. Tu je zameranie na to, čo sa oplatí sledovať a aký článok si otvoriť ako prvý.',
                'focus_points' => [
                    'Sleduj typ kolagénu a dávku na porciu, nie len veľký nápis „kolagén“ na obale.',
                    'Pri kĺboch má zmysel dlhodobosť a konzistencia viac než rýchly efekt.',
                    'Praktická kombinácia s vitamínom C dáva často väčší zmysel než zložité blendy bez jasnej dávky.',
                ],
                'featured_guides' => [
                    ['slug' => 'kolagen-na-klby-porovnanie', 'label' => 'Kĺby', 'description' => 'Prvý článok, ak riešiš kolagén cielene na kĺby.'],
                    ['slug' => 'kolagen-recenzia', 'label' => 'Recenzia', 'description' => 'Komerčný shortlist kolagénov podľa použitia a zloženia.'],
                    ['slug' => 'kolagen', 'label' => 'Základ', 'description' => 'Základný sprievodca tým, čo kolagén je a ako sa v ňom zorientovať.'],
                ],
            ],
            'aminokyseliny' => [
                'title' => 'Aminokyseliny',
                'description' => 'BCAA, EAA a aminokyseliny pre regeneráciu, výkon a tréning v praktickom kontexte.',
                'intro' => 'Aminokyseliny bývajú často preplnené marketingom. Táto sekcia oddeľuje, kedy má zmysel riešiť BCAA, kedy EAA a kedy ti v skutočnosti viac pomôže obyčajný proteín a celkový príjem bielkovín.',
                'focus_points' => [
                    'Najprv sleduj celkový príjem bielkovín, až potom rieš samostatné aminokyseliny.',
                    'EAA bývajú praktickejšie pri nízkom príjme bielkovín alebo tréningu nalačno.',
                    'BCAA samé o sebe zriedka porazia kvalitný proteín alebo vyvážené EAA.',
                ],
                'featured_guides' => [
                    ['slug' => 'aminokyseliny-bcaa-eaa', 'label' => 'Základ', 'description' => 'Praktický úvod do aminokyselín, BCAA a EAA.'],
                    ['slug' => 'bcaa-vs-eaa', 'label' => 'Porovnanie', 'description' => 'Kedy dávajú zmysel BCAA a kedy je lepšia voľba EAA.'],
                ],
            ],
            'chudnutie' => [
                'title' => 'Chudnutie',
                'description' => 'Redukcia tuku, proteíny do diéty a triezvy pohľad na spaľovače tukov.',
                'intro' => 'Táto kategória spája články pre reálnu redukciu tuku bez zbytočných skratiek. Základom je deficit, dostatok bielkovín, rozumné návyky a opatrnosť pri marketingu okolo spaľovačov.',
                'focus_points' => [
                    'Pri chudnutí rozhoduje hlavne kalorický deficit a udržateľný režim.',
                    'Proteín pomáha so sýtosťou a zachovaním svalov, nie je to magický spaľovač.',
                    'Spaľovače tukov sú skôr doplnok marketingu než základný nástroj redukcie.',
                ],
                'featured_guides' => [
                    ['slug' => 'chudnutie-tip', 'label' => 'Základ', 'description' => 'Rýchle tipy na chudnutie, ktoré majú reálny základ.'],
                    ['slug' => 'protein-na-chudnutie', 'label' => 'Proteín', 'description' => 'Ako si vybrať proteín, ktorý dáva zmysel pri diéte.'],
                    ['slug' => 'najlepsi-protein-na-chudnutie-wpc-vs-wpi', 'label' => 'WPC vs WPI', 'description' => 'Čo je pri redukcii praktickejšie: koncentrát alebo izolát.'],
                    ['slug' => 'spalovace-tukov-realita', 'label' => 'Spaľovače', 'description' => 'Čo je pri spaľovačoch realita a čo len marketing.'],
                ],
            ],
            'doplnkove-prislusenstvo' => [
                'title' => 'Doplnkové príslušenstvo',
                'description' => 'Praktické doplnky, rutina a pomocné veci okolo suplementácie a tréningu.',
                'intro' => 'Táto kategória je zatiaľ skôr podporný hub než plnohodnotná obsahová sekcia. Zameriava sa na praktickú stránku suplementácie: čo má zmysel riešiť v rutine, dávkovaní a organizácii doplnkov.',
                'focus_points' => [
                    'Najprv si uprac rutinu a dávkovanie, až potom kupuj ďalšie produkty navyše.',
                    'Praktickosť používania často rozhoduje o tom, či doplnok budeš reálne užívať dlhodobo.',
                    'Pomocné príslušenstvo má zmysel len vtedy, keď zjednodušuje konzistentnosť.',
                ],
                'featured_guides' => [
                    ['slug' => 'doplnky-vyzivy', 'label' => 'Rutina', 'description' => 'Základný shortlist doplnkov, ktorý pomôže upratať každodennú suplementáciu.'],
                    ['slug' => 'kedy-brat-kreatin-a-kolko', 'label' => 'Dávkovanie', 'description' => 'Praktický článok o načasovaní a jednoduchosti dávkovania kreatínu.'],
                    ['slug' => 'pre-workout-ako-vybrat', 'label' => 'Tréning', 'description' => 'Ako si vybrať predtréningovku bez zbytočného chaosu a prestreleného stimu.'],
                ],
            ],
            'kreatin' => [
                'title' => 'Kreatín',
                'description' => 'Monohydrát, HCl, dávkovanie, nasycovanie a rozdiely medzi najčastejšími formami kreatínu.',
                'intro' => 'Ak riešiš kreatín detailne, toto je tvoj špecializovaný hub. Nájdeš tu porovnanie foriem, dávkovanie, načasovanie aj najčastejšie mýty a obavy okolo vedľajších účinkov.',
                'focus_points' => [
                    'Pre väčšinu ľudí má najväčší zmysel kvalitný monohydrát.',
                    'Načasovanie nie je tak dôležité ako pravidelnosť denného užívania.',
                    'Mnohé obavy z kreatínu vychádzajú skôr z mýtov než zo štúdií.',
                ],
                'featured_guides' => [
                    ['slug' => 'kreatin-porovnanie', 'label' => 'Top výber', 'description' => 'Najlepší štartovací článok pre výber kreatínu.'],
                    ['slug' => 'kedy-brat-kreatin-a-kolko', 'label' => 'Dávkovanie', 'description' => 'Koľko kreatínu brať, či robiť pauzy a ako riešiť načasovanie.'],
                    ['slug' => 'kreatin-monohydrat-vs-hcl', 'label' => 'Porovnanie', 'description' => 'Monohydrát vs HCl v praktickom porovnaní.'],
                    ['slug' => 'kreatin-vedlajsie-ucinky-a-fakty', 'label' => 'Fakty', 'description' => 'Vedľajšie účinky kreatínu a čo hovorí výskum.'],
                ],
            ],
            'pre-workout' => [
                'title' => 'Pre-workout',
                'description' => 'Predtréningovky, stimulanty, pumpa a ako vyberať podľa času tréningu a tolerancie kofeínu.',
                'intro' => 'Predtréningovka nie je len o tom, koľko kofeínu cítiš po prvej odmerke. Táto sekcia pomáha rozlíšiť balanced stim, high-stim a non-stim prístup podľa reálneho použitia.',
                'focus_points' => [
                    'Najdôležitejšie je sledovať kofeín, citrulín a beta-alanín v reálnych dávkach.',
                    'Silnejšia predtréningovka nie je automaticky lepšia voľba.',
                    'Večerný tréning si často pýta non-stim alebo miernejší stim profil.',
                ],
                'featured_guides' => [
                    ['slug' => 'pre-workout-ako-vybrat', 'label' => 'Výber', 'description' => 'Ako si vybrať pre-workout podľa stimu, pumpy a času tréningu.'],
                    ['slug' => 'pre-workout', 'label' => 'Základ', 'description' => 'Základný sprievodca tým, čo predtréningovka robí a kedy ju zaradiť.'],
                ],
            ],
            'probiotika-travenie' => [
                'title' => 'Probiotiká a trávenie',
                'description' => 'Probiotiká, kmene, CFU a praktický výber podľa trávenia a dlhodobého použitia.',
                'intro' => 'Táto kategória je postavená na praktickom výbere probiotík. Namiesto počítania čo najvyšších CFU sa sústreďujeme na konkrétne kmene, použitie a to, ako produkt zapadá do reálneho režimu.',
                'focus_points' => [
                    'Dôležitejšie než veľké číslo CFU bývajú konkrétne kmene a spôsob použitia.',
                    'Probiotiká majú väčší zmysel pri konzistentnom používaní než ako náhodný jednorazový nákup.',
                    'Pri trávení sa oplatí riešiť aj stravu, vlákninu a celkový režim, nie len kapsuly.',
                ],
                'featured_guides' => [
                    ['slug' => 'probiotika-ako-vybrat', 'label' => 'Výber', 'description' => 'Ako čítať probiotiká, CFU a kmene bez marketingového chaosu.'],
                    ['slug' => 'probiotika-a-travenie', 'label' => 'Sprievodca', 'description' => 'Praktický úvod do probiotík a trávenia.'],
                    ['slug' => 'imunita-prirodne-latky-ktore-funguju', 'label' => 'Súvislosti', 'description' => 'Širší kontext látok, ktoré podporujú imunitu vrátane úlohy trávenia.'],
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