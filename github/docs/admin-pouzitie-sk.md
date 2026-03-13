# Admin pouzitie (SK)

Toto je jednoduchy navod pre beznu pracu s adminom bez technickych detailov.

## Na co admin sluzi
Admin ti ma pomoct robit 4 veci bez rucneho editovania kodu:

1. upravit clanok
2. doplnit obrazky
3. doplnit produkty
4. doplnit Dognet odkazy

Admin nie je WordPress a nie je to plny CMS.
Je to lahka interna vrstva nad tymto webom.

## Kde co najdes
- `Clanky` = text clanku, sekcie, porovnania, odporucane produkty, SEO meta
- `Produkty` = reusable produkty, obrazky produktov, rating, plusy, minusy, affiliate kod
- `Images` = hero obrazky clankov a chybajuce obrazky produktov v konkretnom clanku
- `Affiliate odkazy` = finalne Dognet deeplinky za internymi `/go/` linkami
- `Pomoc / quickstart` = rychla pomoc priamo v admine

## Najjednoduchsie odporucane poradie
Ked robis jeden clanok, chod v tomto poradi:

1. `Clanky`
2. `Images`
3. `Produkty`
4. `Affiliate odkazy`
5. skontrolovat live clanok

To znamena:
- najprv text a struktura
- potom hero obrazok
- potom obrazky produktov
- potom Dognet linky

## Ked chces upravit clanok
1. otvor `Clanky`
2. vyber clanok zo zoznamu
3. uprav:
   - nazov
   - intro
   - meta title
   - meta description
   - sekcie
4. ak je to nakupny clanok, pridaj odporucane produkty
5. klikni `Ulozit clanok`
6. klikni `Live clanok`

## Ked chces doplnit hero obrazok clanku
1. otvor `Images`
2. vyber spravny clanok
3. admin ti ukaze:
   - prompt
   - filename
   - alt text
   - target path
4. skopiruj prompt do Canvy alebo AI nastroja
5. vytvor obrazok
6. exportuj `WebP`
7. nahraj obrazok cez admin
8. otvor live clanok a skontroluj vysledok

## Ked chces doplnit obrazok produktu
Mas 2 moznosti:

### A. Zrkadlit remote
Toto je najrychlejsia moznost.

Pouzi ju, ked admin ukazuje tlacidlo `Zrkadlit remote`.

Postup:
1. otvor `Produkty` alebo `Images`
2. najdi produkt
3. klikni `Zrkadlit remote`
4. admin stiahne schvaleny merchant obrazok do lokalneho assetu
5. otvor live clanok a skontroluj kartu produktu

### B. Nahrat obrazok rucne
Pouzi to vtedy, ked `Zrkadlit remote` nie je k dispozicii alebo chces vlastny obrazok.

Postup:
1. otvor produkt
2. skopiruj `target path`
3. priprav finalny obrazok
4. klikni `Nahrat obrazok`
5. skontroluj live clanok

## Ked chces doplnit Dognet link
1. otvor `Affiliate odkazy`
2. vyber existujuci kod alebo vytvor novy
3. vloz finalny Dognet deeplink
4. klikni `Ulozit affiliate odkaz`
5. otvor `/go/...` link alebo live clanok a skontroluj CTA

## Ked robis novy nakupny clanok
Najjednoduchsi postup je:

1. v `Clanky` vytvor alebo otvor clanok
2. pridaj reusable produkty
3. pouzi:
   - `Money-page scaffold`
   - alebo `Top 3 hotove vybery`
4. dolad text
5. prejdi do `Images`
6. dopln hero obrazok
7. dopln obrazky produktov, kde este chybaju
8. prejdi do `Affiliate odkazy`
9. dopln finalne Dognet deeplinky
10. skontroluj live clanok

## Ako sa rozhodnut, kde zacat
Ak nevies, co otvorit:

- chces menit text clanku -> `Clanky`
- chces menit hlavny obrazok clanku -> `Images`
- chces menit produkt a jeho kartu -> `Produkty`
- chces menit Dognet link -> `Affiliate odkazy`

## Co si kontrolovat pred publikovanim
- clanok ma nazov, intro a meta description
- hero obrazok sedi k teme
- odporucane produkty maju obrazok
- CTA vedu na spravne `/go/` odkazy
- stranka vyzera dobre na mobile aj desktope

## Dolezite pravidlo
Na verejnom webe nechaj ciste a jednoduche informacie pre navstevnika.

Technicke veci ako:
- zdroj obrazka
- vnutorne coverage stavy
- detailna pripravenost produktu

maju ostat hlavne v admine, nie na verejnej stranke.
