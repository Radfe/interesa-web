# Admin implementacny stav - Interesa.sk

Tento dokument je spolocny stavovy prehlad pre:
- admin vlakno
- web vlakno

Zdroj pravdy pre obsahovu a produktovu prioritu:
- [WEB_CONTENT_PRODUCT_MAP_SK.md](C:/data/praca/webova_stranka/github/WEB_CONTENT_PRODUCT_MAP_SK.md)
- [CAMPAIGN_ARTICLE_MAP_SK.md](C:/data/praca/webova_stranka/github/CAMPAIGN_ARTICLE_MAP_SK.md)
- [ADMIN_PRODUCT_IMPORT_BRIEF_SK.md](C:/data/praca/webova_stranka/github/ADMIN_PRODUCT_IMPORT_BRIEF_SK.md)

## 1. Pravidlo prace

Admin vrstva neriesi znovu:
- ktore clanky su priorita
- ktore kampane su priorita
- ake typy produktov patria do clankov

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

Vysledok tohto kroku:
- Produkty hovoria ludskejsie a menej technicky
- Affiliate odkazy hovoria ludskejsie a menej technicky
- tlacidlo `Otvorit produkt` z obrazkovych workflowov uz vedie priamo na formular produktu
- diagnosticke technicke udaje pri produkte su schovane do rozbalovacieho bloku

## 4. Co este chyba

### Produkty a klikacie odkazy
Chyba najma:
- jednoduchy workflow typu:
  - vloz produktovy link alebo Dognet link
  - admin pripravi produkt
  - admin pripravi obrazok
  - admin pripravi klikaci odkaz
- jasne prepojenie:
  - produkt -> clanok
  - produkt -> merchant
  - produkt -> typ boxu

### Prioritne naplnenie
Treba sa sustredit najprv na clanky urcene v:
- [WEB_CONTENT_PRODUCT_MAP_SK.md](C:/data/praca/webova_stranka/github/WEB_CONTENT_PRODUCT_MAP_SK.md)
- [CAMPAIGN_ARTICLE_MAP_SK.md](C:/data/praca/webova_stranka/github/CAMPAIGN_ARTICLE_MAP_SK.md)

Najprv riesit:
- P1 a P2 clanky
- kampane fazy 1A a 1B

## 5. Najblizsi admin krok

Najblizsi odporucany krok v admine:
- pripravit jednoduchy polo-rucny system:
  - vlozit konkretny produktovy link alebo Dognet deeplink
  - vytvorit produkt
  - skusit ziskat obrazok
  - pripravit klikaci odkaz
- ciel je, aby bezny pouzivatel nemusel hladat technicke polia a nerozhodoval sa medzi viacerymi podobnymi tlacidlami

Zatial nerobit:
- velky hromadny XML importer pre vsetko
- komplikovane feed mapovanie bez kontroly

## 6. Co potrebuje web vlakno

### Handoff pre web vlakno - aktualny stav

Zatial nie je nutny novy zasah do verejneho webu len kvoli tomuto dokumentu.

Ak bude admin dalej rozsireny o:
- nove typy produktovych vazieb na clanky
- nove stavy odporucana volba / value volba / veganska volba
- samostatne male a velke obrazky produktov alebo tem

potom treba pripravit handoff vo forme:
- co zmenit
- v ktorych suboroch
- preco

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
