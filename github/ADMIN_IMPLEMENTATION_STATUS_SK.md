# Admin implementacny stav - Interesa.sk

Tento dokument je spolocny stavovy prehlad pre:
- admin vlakno
- web vlakno

Zdroj pravdy pre obsahovu a produktovu prioritu:
- [WEB_CONTENT_PRODUCT_MAP_SK.md](C:/data/praca/webova_stranka/github/WEB_CONTENT_PRODUCT_MAP_SK.md)
- [CAMPAIGN_ARTICLE_MAP_SK.md](C:/data/praca/webova_stranka/github/CAMPAIGN_ARTICLE_MAP_SK.md)
- [FINAL_CAMPAIGN_SHORTLIST_SK.md](C:/data/praca/webova_stranka/github/FINAL_CAMPAIGN_SHORTLIST_SK.md)
- [ADMIN_PRODUCT_IMPORT_BRIEF_SK.md](C:/data/praca/webova_stranka/github/ADMIN_PRODUCT_IMPORT_BRIEF_SK.md)

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

Zatial nerobit:
- velky hromadny XML importer pre vsetko
- komplikovane feed mapovanie bez kontroly

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
- affiliate workflow: funkcny len ciastocne, treba dalej zjednodusit
