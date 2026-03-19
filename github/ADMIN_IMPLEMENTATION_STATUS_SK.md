# Admin implementacny stav - Interesa.sk

Tento dokument je spolocny stavovy prehlad pre:
- admin vlakno
- web vlakno

Rychly onboarding pre novu AI relaciu:
- [PROJECT_MASTER_STATUS_SK.md](C:/data/praca/webova_stranka/github/PROJECT_MASTER_STATUS_SK.md)
- [AGENTS.md](C:/data/praca/webova_stranka/github/AGENTS.md)

Zdroj pravdy pre obsahovu a produktovu prioritu:
- [WEB_CONTENT_PRODUCT_MAP_SK.md](C:/data/praca/webova_stranka/github/WEB_CONTENT_PRODUCT_MAP_SK.md)
- [CAMPAIGN_ARTICLE_MAP_SK.md](C:/data/praca/webova_stranka/github/CAMPAIGN_ARTICLE_MAP_SK.md)
- [FINAL_CAMPAIGN_SHORTLIST_SK.md](C:/data/praca/webova_stranka/github/FINAL_CAMPAIGN_SHORTLIST_SK.md)
- [ADMIN_PRODUCT_IMPORT_BRIEF_SK.md](C:/data/praca/webova_stranka/github/ADMIN_PRODUCT_IMPORT_BRIEF_SK.md)
- [WEB_IMPLEMENTATION_STATUS_SK.md](C:/data/praca/webova_stranka/github/WEB_IMPLEMENTATION_STATUS_SK.md)
- [COLLABORATION_PROTOCOL_SK.md](C:/data/praca/webova_stranka/github/COLLABORATION_PROTOCOL_SK.md)

Povinna synchronizacia pred vacsim admin krokom:
- precitat `ADMIN_IMPLEMENTATION_STATUS_SK.md`
- precitat `WEB_IMPLEMENTATION_STATUS_SK.md`
- riadit sa `COLLABORATION_PROTOCOL_SK.md`

## 1. Pravidlo prace

Admin vrstva neriesi znovu:
- ktore clanky su priorita
- ktore kampane su priorita
- ake typy produktov patria do clankov
- aky ma byt finalny shortlist kampani a produktov

Admin vrstva riesi:
- ako dostat produkt do systemu
- ako mu priradit obrazok
- ako mu priradit klikaci odkaz
- ako ho napojit na clanok
- ako ho zobrazit v porovnani a odporucanych produktoch

## 2. Co je uz hotove

### Obrazky clankov
- existuje jednoduchy workflow v admine
- admin vie:
  - skopirovat text pre Canvu
  - nahrat hotovy obrazok
  - previest ho na WebP
  - upravit rozmer
  - otvorit clanok na webe po nahrati

### Obrazky tem
- existuje jednoduchy workflow podobny clankom
- admin vie:
  - skopirovat text pre Canvu
  - nahrat hlavny obrazok temy
  - nahrat mensi obrazok temy
  - automaticky upravit rozmer
  - otvorit temu na webe po nahrati

### Produkty - zakladne upravy
- sekcia Produkty je zrozumitelnejsia nez predtym
- produktovy formular je opat funkcny
- admin vie:
  - otvorit miesto na doplnenie produktu
  - ulozit URL konkretneho produktu v obchode
  - skusit nacitat udaje z obchodu
  - ulozit najdeny obrazok z e-shopu
- zjednodusene texty uz hovoria, ze:
  - Produkty = produkt, obrazok, adresa produktu
  - Affiliate odkazy = kam clovek po kliknuti odide

## 3. Co sa menilo naposledy

Najnovsi produktovy krok:
- sekcia `Produkty` uz ide cela v jednstlpcovom pilotnom rezime, nielen pri `candidate=...`
- po importe uz nema ostat viditelny bočný admin panel vedla prazdneho obsahu
- `section=products` a `candidate=...` teraz pouzivaju rovnaky produktovy shell:
  - flash hore
  - pilotny import
  - krok `2. Otvor jeden produkt z posledneho importu`
- tym sa ma odstranit stav, kde bola:
  - zelena hlaska vlavo
  - prazdny admin panel vpravo
  - a dalsi krok posunuty mimo viditelnu cast

- predchadzajuci produktovy krok:
- opravene rozlozenie po importe v `Produkty`
- zelena alebo cervena hlaska po importe uz v `admin-shell` zabera cely riadok a nerozbija grid rozlozenie
- tym padom sa uz nema stat, ze po importe skonci:
  - flash na lavom uzkom stlpci
  - sidebar vpravo
  - a hlavny produktovy krok nizsie mimo viditelnu cast
- ocakavany stav po importe je:
  - flash hore cez celu sirku
  - pod nim standardne `Produkty`
  - a hned viditelny krok `2. Otvor jeden produkt z posledneho importu`

- predchadzajuci produktovy krok:
- po pilotnom importe pre `najlepsie-proteiny-2026` sa dalsi krok uz ukazuje priamo v sekcii `2. Otvor jeden produkt z posledneho importu`
- zoznam na otvorenie sa uz zobrazuje hned v tom istom kroku, nie az nizsie na stranke
- spodny duplicitny zoznam z hlavneho toku zmizol; pri otvorenom produkte ostal len ako pomocny zoznam dalsich produktov z toho isteho batchu
- pilot pre `najlepsie-proteiny-2026` uz v zozname po importe ukazuje len vhodne produkty pre tento clanok
- produkty, ktore do tohto pilotu nepatria, sa uz nemaju miešat do hlavneho zoznamu po importe
- ak sa v poslednom importe nasli len nevhodne produkty, admin to povie priamo a netlaci pouzivatela do dalsieho kroku

- predchadzajuci produktovy krok:
- pilot pre `najlepsie-proteiny-2026` uz neberie "posledny batch" zo vsetkych kandidatov, ale len batchy, ktore maju ulozeny `target_article_slug = najlepsie-proteiny-2026`
- tym sa do pilotneho zoznamu prestavaju miesat stare spinate batchy s tycinkami, porrige a meal replacement produktmi
- import pre pilot teraz vyzaduje aj explicitny typ produktu
- ked sa pre dany clanok, obchod a typ nic nenajde, chybova hlaska uz hovori presne:
  - ktory clanok
  - ktory obchod
  - ktory typ produktu
- chybova hlaska uz tiez vysvetluje, ze pilot pusta len ciste proteiny, nie tycinky, snacky, porridge ani iny balast

- predchadzajuci pilotny krok:
- `Produkty` maju teraz dva striktne oddelene rezimy:
  - `section=products` = import pre pilotny clanok
  - `candidate=...` = jeden otvoreny produkt
- po importe sa uz neotvara prvy produkt automaticky
- po importe sa otvori len zoznam posledneho batchu a dalsi krok je:
  - `Otvorit tento produkt`
- stary rucny blok sa uz nema zobrazit v beznom pilotnom toku, len pri explicitne otvorenom rucnom produkte
- v hlavnej ceste pre pilot nezostali manualne editorialne polia
- hlavny pilot teraz drzi logiku:
  - import pre clanok
  - otvorit jeden produkt
  - pripravit odkaz do obchodu
  - ak obsahovo patri, pridat k clanku
  - ulozit do systemu

Najnovsi produktovy krok predtym:
- hlavna cesta v `Produkty` je viac article-first
- bezny pouzivatel uz nema v hlavnej ceste vidiet:
  - docasne poradie
  - male stitky
  - horny vyber
  - porovnavaciu tabulku
- tieto technicke volby ostali len mimo hlavnej cesty ako rucny fallback
- pri otvorenom produkte admin teraz ukazuje len:
  - stav produktu
  - dalsi technicky krok
  - alebo jasnu vetu, ze produkt do prvych 3 clankov nepatri
- ak produkt do prvych 3 clankov nepatri, admin ho uz netlaci do priradenia
- `Zakladny prehlad webu` sa v sekcii `Produkty` uz bezne nezobrazuje
- `Rucne opravy a starsie nastavenia` sa bezne zobrazia len vtedy, ked je otvoreny explicitny rucny detail produktu
- jazyk v produkte je jednoduchsi:
  - `Produkt je nacitany`
  - `Odkaz do obchodu`
  - `Pridany k clanku`
  - `Ulozeny v systeme`

Posledny vacsi admin krok:
- oprava a zjednodusenie [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php)

Vysledok:
- produktovy formular bol opravny po rozbiti
- texty v sekcii Produkty su jednoduchsie
- tlacidla vedu priamo na doplnenie produktu
- admin uz menej miesa obrazok produktu, URL produktu a klikaci odkaz

Posledne doplnenie:
- dalsie zjednodusenie sekcii `Produkty` a `Affiliate odkazy`
- viac technickych detailov je schovanych mimo hlavneho toku
- clovek ma mat na ociach len:
  - otvorit produkt
  - doplnit adresu alebo klikaci odkaz
  - nacitat udaje z obchodu
  - ulozit obrazok

Najnovsie doplnenie:
- v `Produkty` pribudla jednoducha cesta:
  - vlozit link produktu alebo Dognet link
  - admin sa pokusi sam doplnit:
    - adresu produktu v obchode
    - klikaci odkaz
    - obchod
    - obrazok z obchodu
- produktovy workflow uz nepozera len na rucne vyplnenu adresu produktu
- ak uz existuje klikaci odkaz, admin vie casto pouzit aj jeho cielovu adresu ako zdroj pre nacitanie produktu
- zrozumitelnejsi je aj dalsi krok v `Co kliknut teraz`

Vysledok tohto kroku:
- Produkty hovoria ludskejsie a menej technicky
- Affiliate odkazy hovoria ludskejsie a menej technicky
- tlacidlo `Otvorit produkt` z obrazkovych workflowov uz vedie priamo na formular produktu
- diagnosticke technicke udaje pri produkte su schovane do rozbalovacieho bloku
- v produktoch je novy hlavny vstup `Link produktu alebo Dognet link`
- admin vie pouzit bud:
  - priamu adresu produktu
  - alebo existujuci klikaci odkaz
- queue pri produktoch uz nehovori len `doplni URL`, ale smeruje na vlozenie linku

Najnovsi krok:
- v editore clanku pribudol jednoduchy blok `Produkty v tomto clanku`
- pri kazdom produkte sa da nastavit:
  - poradie
  - rola vo vybere
  - ci sa ma ukazat v odporucanych produktoch
  - ci sa ma ukazat v porovnani
- admin z tohto bloku vie pripravit:
  - `recommended_products`
  - `comparison.rows`
- pokrocile povodne polia ostavaju ako fallback, ale bezny tok ma ist cez tento novy blok
- v hornej casti clanku pribudlo priame tlacidlo `Produkty v clanku`
- zakladanie noveho clanku je schovane do rozbalovacieho bloku, aby nezavadzalo pri beznej praci

Posledne zjednodusenie:
- v `Clanky` sa hlavny produktovy blok posunul vyssie hned za titulok, kategoriu a intro
- pribudla jasna veta, ze ak teraz riesis produkty, ostatne casti mas ignorovat
- texty pri produktoch su ludskejsie:
  - `Typ odporucania`
  - `Hlavny tip`
  - `Vyhodna volba`
  - `Ina moznost`
- pokrocile casti clanku su schovane do rozbalovacieho bloku:
  - meta title
  - meta description
  - text clanku
  - porovnanie
  - pokrocile odporucane produkty
  - kontrola pripravenosti

Najnovsie doplnenie:
- sekcia `Produkty` bola este viac upratana
- na ociach zostala hlavna cesta:
  - vlozit link produktu
  - kliknut dalsi krok
  - ulozit obrazok
  - az potom riesit odkaz do obchodu
- hlucne zoznamy su schovane do rozbalovacich blokov:
  - produkty bez hotoveho obrazka
  - produkty bez odkazu do obchodu
  - co este pri produktoch chyba
  - kde sa produkt pouziva
- pribudla nova samostatna sekcia `Logo a ikonka`
- admin uz vie:
  - nahrat hlavne logo
  - nahrat jeden zdrojovy obrazok pre ikonku
  - sam vytvorit male verzie:
    - favicon-32
    - favicon-48
    - apple-touch-icon
  - nahrat hlavny obrazok pri zdielani
  - pri kazdom z tychto blokov skopirovat hotove zadanie pre Canvu

Najnovsie zjednodusenie:
- v `Clanky` pri produktoch su dalsie kroky pomenovane jednoducho:
  - `Doplnit produkt`
  - `Doplnit odkaz`
  - `Hotovo`
- tlacidla z clanku idu rovno na spravne miesto v produkte
- v `Produkty` uz nevidno tolko technickych poli naraz
- hore zostali len bezne veci:
  - nazov
  - znacka
  - obchod
  - priama adresa produktu v obchode
- technicke a menej dolezite casti su schovane do rozbalovacich blokov
- pri produktoch bez obrazka uz technicka cesta obrazka nezavadzia na ociach

Najnovsie brand spresnenie:
- sekcia `Logo a ikonka` ma dva odlisne vstupy:
  - `Hlavne logo` prijima `svg/png/jpeg/webp`
  - `Ikonka stranky` ocakava stvorcovy raster zdroj a z neho pripravuje:
    - `logo-icon.png`
    - `favicon-32.png`
    - `favicon-48.png`
    - `apple-touch-icon.png`
- do ikonky teda nema ist `logo-icon.svg`, ale `logo-icon.png`
- ak sa po nahrati hlavneho loga nezobrazi zmena na webe, treba overit, ci sa vytvoril aj odvodeny asset `logo-full-web`

Najnovsi upratovaci krok:
- horny prehlad webu je schovany do rozbalovacieho bloku, aby pri beznej praci nezavadzal
- v `Produkty` pribudol novy hlavny blok `Produkty z vybraneho clanku`
- admin tam ukazuje len produkty z jedneho prioritneho clanku a pri kazdom produkte uz len:
  - stav
  - co este chyba
  - jeden dalsi krok
- produkty sa tak uz daju doplnat po clankoch, nie cez chaoticky velky katalog
- blok `Stav tohto produktu` je oznaceny ako miesto, kam ta maju priviest tlacidla zhora

Najnovsi import krok:
- `Krok 1: Nahraj zoznam produktov` uz vie brat aj priamu `URL feedu z Dognetu`
- feed sa uz pri importe nestahuje cely do pamate ako jeden velky text
- admin ho najprv ulozi do docasneho suboru a potom ho cita po castiach
- tym sa znizilo riziko padu pri vacsich XML feedoch
- pribudol aj jednoduchy `Volitelny filter produktov`
- filter hlada slova v nazve a kategorii produktu, napr:
  - `whey`
  - `protein`
  - `creatine`
  - `magnesium`
  - `probiotic`
- ciel je netahat cely feed naraz, ale len mensi prvy balik kandidatov

Najnovsie doladenie kandidatov:
- po importe sa ma admin vratit rovno na blok `Prave nacitane produkty`
- po priprave odkazu, priradeni alebo schvaleni sa ma vratit rovno na blok `Vyber jeden produkt a dokonci ho`
- pri prave nacitanych produktoch je teraz jasnejsie vidno:
  - poradie v zozname
  - typ produktu
  - cenu, ak vo feede prisla
- pri jednom otvorenom kandidatovi je zretelnejsie vidno:
  - obchod
  - typ
  - cenu
  - link produktu
- ciel je, aby po importe bolo hned jasne:
  - co sa nacitalo
  - s ktorym produktom teraz robis

Najnovsia oprava candidate rezimu:
- ked je otvoreny konkretny kandidat cez `?candidate=...`, stary blok `Rucne opravy a starsie nastavenia` sa uz nema renderovat pod nim
- candidate rezim ma ukazovat len aktualneho kandidata a jeho dalsi krok
- tym sa odstranil dojem, ze sa na jednej obrazovke miesaju dva rozdielne admin panely naraz

Najnovsia oprava rozlozenia pri kandidatoch:
- blok `Vyber jeden produkt a dokonci ho` je teraz hlavny a je vyssie
- blok `Produkty z posledneho importu` je presunuty nizsie a po otvoreni jedneho produktu je zatvoreny
- po importe sa ma otvorit priamo prvy produkt v kroku 2, nie pomocny zoznam
- po priprave odkazu a po priradeni sa ma stranka vratit rovno na dalsi spravny krok pri tom istom produkte
- ciel je, aby sa uz nevytvaral pocit rozbiteho laveho a praveho panelu
- pri `candidate-click` a `candidate-assignment` sa uz scrolluje spat na ten isty otvoreny produkt, nie na vedlajsi pomocny blok

Najnovsie posilnenie logiky prvych 3 clankov:
- admin uz prisnejsie rozlisuje, ci kandidat patri do prvych troch clankov
- ak kandidat zjavne nepatri do:
  - `najlepsie-proteiny-2026`
  - `kreatin-porovnanie`
  - `doplnky-vyzivy`
  tak sa formular na priradenie vobec nezobrazi
- namiesto toho admin jasne povie, ze produkt zatial nepatri do prvych troch clankov a nema sa priradovat
- tym sa znizuje riziko, ze sa do clanku dostane obsahovo nespravny kandidat, napr. tycinka alebo kofeinove tablety

## 4. Co este chyba

### Produkty a klikacie odkazy
Chyba najma:
- uplne bezchybny workflow typu:
  - vloz produktovy link alebo Dognet link
  - admin pripravi produkt
  - admin pripravi obrazok
  - admin pripravi klikaci odkaz
- jasne prepojenie:
  - produkt -> clanok
  - produkt -> merchant
  - produkt -> typ boxu
- treba este dotiahnut, aby verejny web cital tieto admin data ako prvu volbu
- este treba dotiahnut:
  - skrytie dalsich menej dolezitych poli
  - este jasnejsie kroky pri produktoch bez klikacieho odkazu
- finalne samostatne tlacidlo pre `vloz link -> priprav produkt`
- dalsie zjednodusenie samotneho formulára produktu, aby hore ostali len uplne bezne polia
- prakticke naviazanie na prve prioritne clanky z dokumentov

### Prioritne naplnenie
Treba sa sustredit najprv na clanky urcene v:
- [WEB_CONTENT_PRODUCT_MAP_SK.md](C:/data/praca/webova_stranka/github/WEB_CONTENT_PRODUCT_MAP_SK.md)
- [CAMPAIGN_ARTICLE_MAP_SK.md](C:/data/praca/webova_stranka/github/CAMPAIGN_ARTICLE_MAP_SK.md)
- [FINAL_CAMPAIGN_SHORTLIST_SK.md](C:/data/praca/webova_stranka/github/FINAL_CAMPAIGN_SHORTLIST_SK.md)

Najprv riesit:
- P1 a P2 clanky
- kampane fazy 1A a 1B
- a v prvej admin faze hlavne tieto stranky:
  - `najlepsie-proteiny-2026`
  - `kreatin-porovnanie`
  - `doplnky-vyzivy`

## 5. Najblizsi admin krok

Najblizsi odporucany krok v admine:
- dotiahnut novy jednoduchy polo-rucny system:
  - vlozit konkretny produktovy link alebo Dognet deeplink
  - vytvorit alebo doplnit produkt
  - skusit ziskat obrazok
  - pripravit klikaci odkaz
- a hned ho aplikovat aspon na:
  - `najlepsie-proteiny-2026`
  - `kreatin-porovnanie`
  - `doplnky-vyzivy`
- pri produktoch riesit len prvu fazu:
  - pridat produkt
  - pridat odkaz do obchodu
  - nahrat realny obrazok produktu
  - priradit produkt ku clanku
  - nastavit, ci ide do porovnania, odporucanych produktov alebo ako hlavne odporucanie
- ciel ostava rovnaky:
  - bezny pouzivatel nema hladat technicke polia
  - nema premyslat medzi viacerymi podobnymi tlacidlami

## 12. Bezpecne prve nastavenie pri importe

Pri kandidatovi produktu je teraz admin nastavovany tak, aby pri prvom importe netlacil na finalne editorialne rozhodnutie.

Aktualne pravidla v admine:
- pri priradeni ku clanku je predvolene docasne poradie `10`
- maly stitok je predvolene `Bez oznacenia`
- v rychlom vybere su na ociach len tieto volby:
  - `Bez oznacenia`
  - `Veganska moznost`
  - `Cista moznost`
- volby pre horny vyber a porovnavaciu tabulku ostavaju pri prvom importe vypnute, pokial ich clovek vyslovene nezaskrtne
- posledny krok sa nevola `Schvalit pre web`, ale zrozumitelnejsie `Ulozit do systemu`

Zmysel:
- najprv dostat do systemu spravnych kandidatov
- az potom nechat web vlakno urobit finalny vyber

Prakticka pomocka:
- pri kandidatovi je nove tlacidlo `Pouzit bezpecne prve nastavenie`
- to jedným klikom ulozi:
  - aktualne vybrany clanok
  - poradie `10`
  - `Bez oznacenia`
  - horny vyber vypnuty
  - porovnavaciu tabulku vypnutu

Zatial nerobit:
- velky hromadny XML importer pre vsetko
- komplikovane feed mapovanie bez kontroly

Najnovsi krok:
- v `Clanky` pribudol novy blok `Co v tomto clanku este chyba`
- hned pri otvoreni clanku uz vidno:
  - kolko produktov je vybranych
  - kolko je uplne hotovych
  - kolkym chyba obrazok
  - kolkym chyba klik do obchodu
- pri kazdom vybranom produkte je uz len jeden dalsi krok:
  - `Doplnit produkt`
  - `Doplnit obrazok`
  - `Doplnit odkaz`
  - `Hotovo`

Najnovsia stabilizacia:
- v `Produkty` uz detail produktu nesmie spadnut len preto, ze sa otvoril slug, ktory este nie je plnohodnotne pripraveny v katalogu
- admin si pri takom stave vytvori bezpecny prazdny medzistav a pouzivatela vrati do jednoducheho toku namiesto chyby 500

Najnovsie doplnenie:
- `Krok 1: Nahraj zoznam produktov` uz nevyzaduje len rucne nahraty subor
- admin uz vie zobrat aj priamu URL feedu z Dognetu
- prakticky postup je teraz:
  - v Dognete kliknes `Kopirovat URL`
  - vlozis to do pola `URL feedu z Dognetu`
  - admin z toho nacita kandidatov na produkty
- suborovy upload zostava ako zalozna moznost
- hore v clanku pribudlo aj tlacidlo `Skontrolovat clanok`

Najnovsie upratanie:
- v `Produkty` pribudol novy blok `Stav tohto produktu`
- hned pri otvoreni produktu je vidno:
  - ci je produkt vytvoreny
  - ci admin pozna stranku produktu v obchode
  - ci je hotovy obrazok
  - ci je hotovy klik do obchodu
- pod tym je uz len jedna veta `Co spravit dalej`, aby nebolo treba rozmyslat medzi viacerymi castami stranky

Najnovsie jazykove upratanie:
- v admine sa dalej obmedzili technicke a anglicke nazvy
- `Affiliate odkazy` su v texte a tlacidlach pomenovane zrozumitelnejsie ako `Odkazy do obchodov`
- v clankoch uz tlacidla hovoria:
  - `Upravit produkt`
  - `Upravit odkaz do obchodu`

Najnovsie zjednodusenie pre clanky a produkty:
- v `Clanky` pri produktoch uz nie su na ociach dva samostatne checkboxy pre odporucane produkty a porovnanie
- nahradilo ich jedno jednoduchsie pole:
  - `Kde sa ma ukazat`
  - moznosti:
    - len medzi odporucanymi produktmi
    - len v porovnani
    - v odporucanych aj v porovnani
    - zatial nikde
- `Typ odporucania` bol premenovany na zrozumitelnejsie `Ako ho oznacit`
- v `Produkty` sa este viac zdoraznila hlavna cesta:
  - vlozit link produktu alebo Dognet link
  - ulozit produkt
  - nacitat udaje z obchodu
  - ulozit obrazok z e-shopu
- texty pri produkte uz nehovoria len o priamej adrese produktu, ale aj o Dognet linku ako beznej moznosti
  - `Doplnit obrazok produktu`
- v produktoch uz blok hovori jednoducho:
  - `Ako na produkt bez chaosu`
  - `Otvorit odkazy do obchodu`
  - `Vlozit link produktu`
- v casti odkazov do obchodov su jednoduchsie nazvy:
  - `Sem vloz finalny odkaz do obchodu`
  - `Interny kod odkazu`
  - `Finalny odkaz do obchodu`
- rucne vytvaranie odkazu je schovane do rozbalovacieho bloku, aby nezavadzalo pri beznej praci

Najnovsie velke zjednodusenie:
- v `Produkty` je ako hlavna cesta postaveny 4-krokovy tok:
  - `Krok 1: Nahraj zoznam produktov`
  - `Krok 2: Pripravit klik do obchodu`
  - `Krok 3: Priradit ku clanku`
  - `Krok 4: Schvalit pre web`
- tato cast je teraz jasne oznacena ako hlavna cesta pre beznu pracu
- povodna rucna sprava jednotlivych produktov ostala zachovana, ale je schovana do rozbalovacieho bloku:
  - `Rucne opravy a starsie nastavenia`
- ciel je, aby bezny pouzivatel najprv isiel cez:
  - nahrat zoznam produktov
  - vybrat jeden produkt
  - pripravit klik
  - priradit ku clanku
  - schvalit pre web
- a az vynimocne otvaral rucne polia pre jeden produkt
- produktove kroky su teraz zamerane len na prvu fazu:
  - `najlepsie-proteiny-2026`
  - `kreatin-porovnanie`
  - `doplnky-vyzivy`
- kandidat aj vyber clanku v produktoch sa uz opieraju hlavne o tieto 3 clanky, aby sa bezny pouzivatel nestracal v prilis sirokom zozname

## 6. Co potrebuje web vlakno

### Handoff pre web vlakno - aktualny stav

Je pripraveny handoff:
- [ADMIN_WEB_HANDOFF_PRODUCT_BINDING_SK.md](C:/data/praca/webova_stranka/github/ADMIN_WEB_HANDOFF_PRODUCT_BINDING_SK.md)

Dovod:
- admin uz vie ukladat poradie a miesto produktu v clanku
- ale verejny web este stale pri money clankoch cita hlavne stary pevny zoznam produktov

## 7. Co sledovat pri dalsom kroku

Pri kazdom dalsom vacsom admin kroku zapisat:
- co je hotove
- ktore subory sa menili
- co este chyba
- co ma nasledne dorobit web vlakno

## 8. Stav k dnesnemu dnu

Datum:
- 2026-03-15

Stav:
- obrazky clankov: pouzitelne
- obrazky tem: pouzitelne
- produkty: ciastocne upratane, ale este nie hotovy jednoduchy system pre odkazy + obrazky + priradenie ku clankom
- produkty: hlavna 4-krokova cesta je pripravena, dalsie ladenie sa ma sustredit na co najmensi pocet klikov a na bezchybne dotiahnutie jedneho produktu od linku az po schvalenie
- produkty: prva faza je zamerana len na 3 hlavne clanky, aby bola praca prehladna
- pri jednom nacitanom produkte v 4-krokovej ceste je teraz na ociach uz len blok `Co kliknut teraz`
- starsie jednotlive kroky a rucne nastavenia su skryte do podrobneho bloku, aby bezny pouzivatel nevidel viac tlacidiel naraz
- jazyk v hlavnej produktovej ceste je opat o trochu jednoduchsi:
  - menej zbytocneho cislovania v textoch
  - `klik do obchodu` je na hlavnych miestach premenovany na zrozumitelnejsi `odkaz do obchodu`
  - `Krok 2 az 4` je premenovany na prirodzenejsie `Vyber jeden produkt a dokonci ho`
  - hlavny blok pri jednom produkte hovori `Co spravit teraz`
- affiliate workflow: funkcny len ciastocne, treba dalej zjednodusit
- logo a ikonka: zakladny jednoduchy upload je hotovy, dalsie drobnosti sa budu ladit podla testu
- produkty: `Krok 1` uz ide priamo cez Dognet feed URL alebo subor, nie cez samostatne rucne pole `Zdroj`
- produkty: pri prepinani nacitaneho kandidata sa uz nema prenasat stary vybrany produkt, aby kandidatova cast nespadla na rucnom detaile produktu
- produkty: nav v admine uz otvara ciste `Produkty` bez natvrdo prilepeneho konkretneho produktu

## 9. Posledny upratovaci krok

Menene subory:
- [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php)

Co sa upravilo:
- z `Krok 1` zmizlo mätúce pole `Zdroj`
- import kandidátov teraz berie nazov obchodu priamo zo zvoleného obchodu
- pri Dognet feede je jasnejsia veta:
  - chod do `Produktove feedy`
  - klikni `Kopirovat URL`
  - vloz link do adminu
- kandidatovy vyber uz neprenasa stary parameter `product`, aby sa hlavna 4-krokova cast nemiesala so starym rucnym detailom produktu

Co este treba doriesit:
- bezchybne dotiahnut import prveho realneho Dognet feedu pre prvych 6 kampani
- dotiahnut `Krok 2`, aby co najviac produktov vedel pripravit odkaz do obchodu bez dalsieho chaosu

## 10. Stabilizacia Krok 1 - kandidati z Dognet feedu

Menene subory:
- [public/inc/admin-feed-import.php](C:/data/praca/webova_stranka/github/public/inc/admin-feed-import.php)
- [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php)

Co sa upravilo:
- `Krok 1` uz netaha cely feed naraz
- z jedneho feedu sa teraz nacita len prvy bezpecny balik kandidatov:
  - 40 produktov
- plati to pre:
  - Dognet feed URL
  - aj rucny XML/CSV/JSON subor
- ciel je, aby import nespadol na velkom feede a aby admin najprv pripravil len mensi vyber kandidatov
- priamo na obrazovke `Produkty` je teraz jasna veta, ze ide len o prvy mensi balik kandidatov, nie o cely feed

Preco:
- podla zadania nechceme velky chaos ani import vsetkeho naraz
- chceme kandidatov pre prvu fazu
- web vlakno potom spravi konecny vyber

Co este treba doriesit:
- overit prvy realny Dognet import po tomto obmedzeni
- potom dotiahnut `Krok 2: Pripravit odkaz do obchodu`

## 11. Prehlad po importe kandidatov

Menene subory:
- [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php)
- [public/inc/admin-store.php](C:/data/praca/webova_stranka/github/public/inc/admin-store.php)

Co sa upravilo:
- po importe kandidatov sa admin vracia rovno na blok s prave nacitanymi produktmi
- pri importe sa zapisuje `batch_id`, aby bolo jasne, co patrilo do posledneho importu
- pribudol blok `Prave nacitane produkty`
- pri kazdom prave nacitanom produkte je hned tlacidlo `Vybrat tento produkt`
- po priprave odkazu, priradeni ku clanku a schvaleni sa admin vracia rovno na krok s vybranym produktom, nie na vrch stranky

Preco:
- po importe to predtym posobilo ako nezmyselny navrat na prazdnu cast adminu
- nebolo jasne, co sa vlastne nacitalo
- teraz je hned vidiet posledny import a dalsi krok

Co este treba doriesit:
- este viac zjednodusit samotne priradenie ku clanku
- pripadne predvyplnit clanok pri kandidatoch importovanych pre prvu fazu

## 12. Ludske vysvetlenie pri priradeni produktu

Menene subory:
- [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php)

Co sa upravilo:
- pri produktoch v clanku aj pri kandidatoch po importe su teraz zrozumitelnejsie texty
- `Ako ho oznacit` je premenovane na `Maly stitok pri produkte`
- `Poradie` je premenovane na `Poradie v zozname (1 je prve)`
- `Ukazat medzi top produktmi` je premenovane na `Ukazat v hornom vybere`
- `Ukazat v porovnani` je premenovane na `Ukazat v porovnavacej tabulke`
- admin priamo pri produkte vysvetluje:
  - co znamena clanok
  - co znamena poradie
  - co znamena maly stitok
  - co znamena horny vyber
  - co znamena porovnavacia tabulka
- pre prve tri clanky admin ukazuje aj kratku vetu, co sa v tom clanku bezne nastavuje

Preco:
- pouzivatel nerozumel, co presne rozhoduje pri produkte
- web vlakno uz rozhodlo obsahovu politiku, admin ma uz len vysvetlit technicke nastavenie ludsky a bez chaosu

Co este treba doriesit:
- este viac zjednodusit samotny vyber jedneho kandidata po importe
- ak bude treba, predvyplnit clanok pri prvych troch clankoch podla fazy importu

## 13. Jasnejsi navrat po importe a vybere kandidata

Menene subory:
- [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php)

Co sa upravilo:
- po importe je blok `Prave nacitane produkty` zrozumitelnejsi
- pri zozname posledneho importu je teraz jasna veta, co ma pouzivatel urobit:
  - kliknut na jeden produkt
  - potom pokracovat nizsie na tom istom produkte
- pri prave otvorenom produkte v zozname sa ukaze stav `Prave otvoreny`
- tlacidlo pri otvorenom produkte sa zmeni na `Tento produkt je otvoreny nizsie`
- v kroku `2. Vyber jeden produkt a dokonci ho` sa teraz explicitne zobrazi:
  - `Prave otvoreny produkt: nazov / obchod`
- po importe a po dalsich krokoch tak uz stranka nevyzera ako nahodny navrat na iny kus adminu

Preco:
- po importe sice kandidati vznikli, ale navrat na stranku stale posobil nejasne
- pouzivatel nevedel, co sa vlastne nacitalo a s ktorym produktom prave pracuje
- tento krok zlepsuje orientaciu bez zavedenia noveho paralelneho systemu

Co este treba doriesit:
- zjednodusit samotny zoznam kandidatov tak, aby bol pre prvy batch este citatelnejsi
- pri prvych troch clankoch casom predvyplnit clanok uz pri importe alebo pri bezpecnom prvom nastaveni

## 14. Posledny import drzi pokope

Menene subory:
- [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php)

Co sa upravilo:
- po importe kandidatov sa admin uz nesnazí otvorit bocny zoznam ako hlavny ciel, ale rovno krok s otvorenym produktom
- pri priprave odkazu do obchodu, priradeni ku clanku a ulozeni do systemu sa zachova `batch` posledneho importu
- v druhom kroku sa pri poslednom importe vybera len z kandidatov z tohto jedneho batchu
- horne pocty v produktoch sa pri poslednom importe prepnu na cisla len z posledneho batchu, nie zo vsetkych starsich kandidatov
- zoznam `Prave nacitane produkty` je uz len pomocny prehlad, nie hlavny ciel navratu

Preco:
- po importe a dalsich krokoch to posobilo, akoby admin skakal na nahodne miesto
- miesali sa nove a starsie kandidaty naraz
- pouzivatel nevidel jasne, s ktorym importom a s ktorym produktom prave pracuje

Co este treba doriesit:
- este viac skratit texty pri samotnom jednom kandidátovi
- pri prvych troch clankoch casom opatrne predvyplnit clanok len vtedy, ked je obsahovy fit jasny

## 15. Sustredeny rezim pri otvorenom kandidatovi

Menene subory:
- [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php)
- [public/assets/css/admin.css](C:/data/praca/webova_stranka/github/public/assets/css/admin.css)

Co sa upravilo:
- ked je v produktoch otvoreny jeden kandidat, admin sa prepne do sustredeneho rezimu
- v tomto rezime sa schova bocny panel, aby nerusil pri dokoncovani jedneho produktu
- hlavna cast sa vycentruje a zostane v sirke jedneho normalneho pracovneho panela
- dlhe linky produktu sa zalamuju a uz nerozbijaju rozlozenie
- karty so stavom, detailom produktu a dalsim krokom maju nastavene `min-width: 0`, aby sa nezužovali do uzkych stlpcov
- pri otvorenom kandidatovi sa dvojstlpcovy prehlad zmeni na jeden stlpec, aby bol citatelnejsi

Preco:
- po importe a po klikoch to posobilo, ako keby bol pravy panel zablokovany a admin rozbity
- v skutocnosti vizualne rusil bocny panel a dlhe URL rozbijali sirku kandidatnych kariet
- cielom je, aby pri jednom otvorenom produkte zostalo na ociach len to, co treba spravit dalej

Co este treba doriesit:
- ak bude treba, este viac potlacit pomocny zoznam posledneho importu
- dotiahnut opatrne predvyplnenie clanku len pri jasnom obsahovom fite pre prve tri clanky

## 16. Jeden otvoreny kandidat bez horneho chaosu

Menene subory:
- [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php)
- [public/assets/css/admin.css](C:/data/praca/webova_stranka/github/public/assets/css/admin.css)

Co sa upravilo:
- ak je v URL otvoreny konkretny kandidat, admin sa prepne do rezimu jedneho produktu aj vtedy, ked sa pouzivatel vratil po medzikroku
- v tomto rezime sa uz neschovava len bocny panel, ale aj horny import a horne pocitadla
- pri otvorenom kandidatovi zostava na obrazovke len krok `2. Vyber jeden produkt a dokonci ho`
- tym padom pouzivatel po `Pripravit odkaz do obchodu` a po `Priradit ako kandidata` nema vidiet zmiesany import hore a produkt dole

Preco:
- screenshoty ukazali, ze navrat po krokoch stale posobil ako rozbity panel: vlavo kandidat, vpravo prazdny admin shell
- skutocny problem bol, ze pri otvorenom kandidatovi zostaval na obrazovke aj horny import a vizualne to rozbijalo tok

Co este treba doriesit:
- urobit import clanok-first, nie feed-first
- pri importe ponuknut len odporucane filtre pre prve 3 clanky

## 17. Otvoreny kandidat ma vlastny cisty rezim

Menene subory:
- [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php)
- [public/assets/css/admin.css](C:/data/praca/webova_stranka/github/public/assets/css/admin.css)

Co sa upravilo:
- rezim otvoreneho kandidata sa teraz zapina len vtedy, ked je v URL explicitne `candidate=...`
- pri otvorenom kandidatovi sa zobrazi len jeden sustredeny pracovny panel bez bocneho menu a bez rozbiteho gridu
- pribudlo jasne tlacidlo `Spat na import produktov`
- layout otvoreneho kandidata uz nie je grid s prazdnym priestorom, ale jeden normalny blok na sirku

Preco:
- screenshoty ukazali, ze po importe a po dalsich krokoch sa stale objavoval mätúci vseobecny admin shell
- pouzivatel potreboval vidiet len jeden otvoreny produkt a jeho dalsi krok, nie cely admin naraz

Co este treba doriesit:
- zjednodusit clanok-first import tak, aby sa neimportovalo feed + lubovolny filter, ale produkty pre konkretny clanok
- zretelnejsie oddelit `kandidati z posledneho importu` od `jeden otvoreny kandidat`

## 3. Co sa menilo naposledy

Dalsie zjednodusenie pilotu pre `najlepsie-proteiny-2026`:
- import uz v hlavnej ceste pyta len:
  - obchod
  - presny typ proteinu
  - Dognet feed URL
- z pilotu zmizli pomocne polia `Sem patri`, `Sem nepatri` a suborovy upload
- ak pouzivatel nevyberie typ rucne, admin pouzije prvy povoleny typ automaticky
- texty pri produktoch a tlacidlach su priamejsie:
  - `Pridat tento produkt k clanku`
  - `Ulozit tento produkt do systemu`

Aktualna oprava pilotneho importu:
- presny typ produktu uz nie je prazdny dropdown
- v pilote je teraz predvoleny rezim `Automaticky pre tento clanok`
- admin si v tomto rezime sam vezme povolene typy pre clanok `najlepsie-proteiny-2026`
- chyba `Najprv vyber presny typ produktu` uz pri pilote nema blokovat import
- manualne volby typu ostali viditelne ako vedlajsia moznost, nie povinny krok
- pilotny import uz nepada ani vtedy, ked sa neodosle skryty slug clanku alebo sa rozbije volba typu
- pre `najlepsie-proteiny-2026` ma importer bezpecny fallback na pevne povolene skupiny produktov
- pilotny import je teraz natvrdo viazany na clanok `najlepsie-proteiny-2026`
- ak by preset zlyhal, importer aj tak pouzije pevny zoznam: whey, concentrate, isolate, clear, vegan

Posledna oprava navratov po importe:
- po `candidate-imported` sa admin uz vracia na krok `2. Otvor jeden produkt z posledneho importu`
- stary anchor `products-imported-list` uz nie je hlavny ciel po importe
- aj tlacidla `Spat na posledny import` vracaju pouzivatela na krok 2, nie na stary spodny helper zoznam

Viditelna verzia v lokalnom prostredi:
- tlacidlo `Obnovit verziu` uz ma vedla seba aj citatelnu build verziu
- build verzia sa rata z poslednej zmeny v lokalnych `php`, `css`, `js` a `json` suboroch v `public`
- ciel je, aby bolo na prvy pohlad jasne, ktoru verziu webu alebo adminu mas prave otvorenu
