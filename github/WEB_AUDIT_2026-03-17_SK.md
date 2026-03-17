# Web audit - Interesa.sk - 2026-03-17

Tento audit je urceny pre web vlakno.

Ciel:
- zistit, co este chyba pred launchom
- oddelit kriticke veci od veci, ktore mozu pockat
- vyuzit cas, kym admin vlakno dotahuje produktovy import

## 1. Zhrnutie

Verejny web je obsahovo a UX velmi daleko.
Najvacsi zostavajuci rozdiel medzi "funguje" a "je pripraveny na hosting" uz nie je v pocte clankov.

Najdolezitejsie oblasti su:
- technicka SEO vrstva
- finalne napojenie produktov
- finalne brand assety
- posledny launch pass na top strankach

## 2. Kriticke zistenia

### A. robots.txt bol pokazeny
Stav:
- subor [robots.txt](/C:/data/praca/webova_stranka/github/public/robots.txt) nebol textovy robots subor
- obsahoval PHP / legacy sablonovy obsah

Riziko:
- vyhladavace mohli dostavat neplatny robots obsah
- zly signal pre indexing a crawling

Stav po audite:
- opravene

### B. hlavny sitemap.xml nefungoval ako sitemap index
Stav:
- [sitemap.xml](/C:/data/praca/webova_stranka/github/public/sitemap.xml) obsahoval len maly manualny zoznam URL
- pritom projekt uz ma:
  - [sitemap-pages.xml](/C:/data/praca/webova_stranka/github/public/sitemap-pages.xml)
  - [sitemap-categories.xml](/C:/data/praca/webova_stranka/github/public/sitemap-categories.xml)
  - [sitemap-articles.xml](/C:/data/praca/webova_stranka/github/public/sitemap-articles.xml)

Riziko:
- hlavna sitemap vrstva neposielala crawlerom plny obraz webu

Stav po audite:
- opravene na sitemap index

## 3. Vysoka priorita pred hostingom

### A. Dokoncit produktovu vrstvu pre prve 3 clanky
Najvyssia launch priorita:
- `najlepsie-proteiny-2026`
- `kreatin-porovnanie`
- `doplnky-vyzivy`

Potrebne:
- admin nech doda kandidatov
- web vrstva potvrdi finalny shortlist kus po kuse
- potom sa dorobi:
  - comparison
  - top produkty
  - hlavna volba
  - CTA

Podklad:
- [FIRST_3_ARTICLES_PRODUCT_SHORTLIST_SK.md](/C:/data/praca/webova_stranka/github/FIRST_3_ARTICLES_PRODUCT_SHORTLIST_SK.md)

### B. Dorobit finalne brand assety
Stav:
- technicka vrstva vie prijat logo a ikonky
- finalny brand este nie je uzavrety

Potrebne:
- finalne logo
- favicon-32
- favicon-48
- apple-touch-icon
- finalny OG default asset

### C. Spravit finalny pass na top 10 az 20 stranok po napojeni produktov
Aktualne:
- vela top stranok uz je kvalitativne dorovnanych

Este treba:
- posledny realny prechod uz s produktami a CTA
- skontrolovat, ze stranka posobi prirodzene aj po commerce napojeni

## 4. Stredna priorita

### A. Zjednotit brand / OG fallback cesty
V kode su stale zmiesane dve vrstvy assetov:
- novsia `img/brand/...`
- starsie fallback odkazy typu:
  - `img/og-default.jpg`
  - `img/logo-full.svg`

Neznamena to hned rozbitie webu, ale pred launchom to ma zmysel zjednotit.

Subory na kontrolu:
- [functions.php](/C:/data/praca/webova_stranka/github/public/inc/functions.php)
- [seo.php](/C:/data/praca/webova_stranka/github/public/inc/seo.php)

### B. Prejst legacy sablony a starsie subory
V repozitari su stale starsie alebo legacy subory, kde je viditelny encoding chaos alebo starsi pristup.

Priklad:
- [article-template.php](/C:/data/praca/webova_stranka/github/public/article-template.php)

Toto nie je momentalne najvyssia launch priorita, ale pred finalnym upratovanim ma zmysel odlisit:
- co web realne pouziva
- co je uz len stary helper alebo rezerva

### C. Mobilny finalny UX pass
Stale dava zmysel spravit posledny manualny pass na:
- header
- mobile menu
- homepage sekcie
- top clanky
- CTA bloky
- footer

## 5. Nizka priorita / netreba teraz nafukovat

### A. Masove dorabanie dalsich 200 supporting clankov
To teraz nedava najlepsi pomer hodnota / cas.

Lepsie je:
- finish launch veci
- produkty
- brand
- top stranky

### B. Dalsie nove clanky bez priameho launch dopadu
Novy obsah uz teraz nie je hlavne uzke miesto projektu.

## 6. Co odporucam robit teraz vo web vlakne

Poradie:
1. pockat na prve kandidatske produkty z adminu
2. urobit finalny produktovy vyber pre prve 3 clanky
3. uzavriet logo a brand assety
4. spravit finalny launch pass na top strankach
5. potom hosting

## 7. Co je uz po tomto audite opravene

- [robots.txt](/C:/data/praca/webova_stranka/github/public/robots.txt)
- [sitemap.xml](/C:/data/praca/webova_stranka/github/public/sitemap.xml)

## 8. Prakticky zaver

Web uz nie je vo faze "treba pridat este viac clankov".

Web je teraz vo faze:
- produkty
- brand
- launch QA
- hosting

To je najrozumnejsi dalsi smer.
