# Web implementacny stav - Interesa.sk

Tento dokument je spolocny stavovy prehlad pre:
- web vlakno
- admin vlakno

Rychly onboarding pre novu AI relaciu:
- [PROJECT_MASTER_STATUS_SK.md](C:/data/praca/webova_stranka/github/PROJECT_MASTER_STATUS_SK.md)
- [AGENTS.md](C:/data/praca/webova_stranka/github/AGENTS.md)

Zdroj pravdy pre obsahovu a produktovu prioritu:
- [WEB_CONTENT_PRODUCT_MAP_SK.md](C:/data/praca/webova_stranka/github/WEB_CONTENT_PRODUCT_MAP_SK.md)
- [CAMPAIGN_ARTICLE_MAP_SK.md](C:/data/praca/webova_stranka/github/CAMPAIGN_ARTICLE_MAP_SK.md)
- [FINAL_CAMPAIGN_SHORTLIST_SK.md](C:/data/praca/webova_stranka/github/FINAL_CAMPAIGN_SHORTLIST_SK.md)
- [PRODUCT_SELECTION_POLICY_SK.md](C:/data/praca/webova_stranka/github/PRODUCT_SELECTION_POLICY_SK.md)
- [FIRST_PRODUCT_ROLLOUT_SK.md](C:/data/praca/webova_stranka/github/FIRST_PRODUCT_ROLLOUT_SK.md)

## 1. Pravidlo prace

Web vrstva rozhoduje:
- ktore clanky su priorita
- ktore kampane sa maju pouzivat
- ake typy produktov patria do ktorych clankov
- ktore konkretne produkty budu schvalene na verejny web
- aky produkt bude:
  - hlavna volba
  - vyhodna volba
  - ina moznost
  - produkt do porovnania

Web vrstva neriesi:
- technicky import feedov
- generovanie klikacich Dognet odkazov
- upload obrazkov produktov
- internu admin spravu produktov

## 2. Co je uz hotove na verejnom webe

### Verejny web a launch smer
- homepage, kategorie a clankovy layout boli vyrazne vycistene
- wording bol upraveny tak, aby web posobil profesionalnejsie a menej interne-technicky
- discovery, trust boxy, sidebar a decision flow su silnejsie ako na zaciatku
- verejny web je velmi blizko launch-ready stavu po obsahovej a UX stranke

### Obsahove clustre
- jadro webu uz ma silny obsahovy zaklad
- top a semitop clanky boli priebezne prepisane do decision-first stylu
- velka cast starsich rozbitych alebo slabych clankov bola vycistena

### Produkty a kampane - strategia
- finalny shortlist kampani je pripraveny
- produktova politika je pripravena
- rollout pre prve produkty je pripraveny
- prve prioritne clanky pre produktove naplnenie su urcene

## 3. Co sa menilo naposledy

Posledny vacsi webovy krok:
- velky kvalitativny pass na starsich jadrovych clankoch, aby boli na rovnakej urovni ako top money pages

Posledny technicky webovy krok:
- web audit pre launch
- opravene:
  - [robots.txt](C:/data/praca/webova_stranka/github/public/robots.txt)
  - [sitemap.xml](C:/data/praca/webova_stranka/github/public/sitemap.xml)
- audit zapisany do:
  - [WEB_AUDIT_2026-03-17_SK.md](C:/data/praca/webova_stranka/github/WEB_AUDIT_2026-03-17_SK.md)

Najnovsi UX krok:
- audit kategorii a listingov po realnej kontrole dojmu rozbiteho webu
- opravene:
  - [public/inc/category-landing.php](C:/data/praca/webova_stranka/github/public/inc/category-landing.php)
  - [public/assets/css/main.css](C:/data/praca/webova_stranka/github/public/assets/css/main.css)
- audit zapisany do:
  - [CATEGORY_ARCHIVE_AUDIT_2026-03-17_SK.md](C:/data/praca/webova_stranka/github/CATEGORY_ARCHIVE_AUDIT_2026-03-17_SK.md)

Najnovsi obrazkovy zaver:
- admin workflow pre obrazky clankov existuje, ale jeho pocty su zatial zavadzajuce
- admin dnes miesa:
  - vlastne article obrazky
  - fallback obrazky temy
  - placeholder logiku
- preto mohol pouzivatel nadobudnut dojem, ze obrazky su hotove, hoci vacsina clankov stale nema vlastny article hero obrazok
- handoff zapisany do:
  - [WEB_TO_ADMIN_IMAGE_STATUS_HANDOFF_2026-03-17_SK.md](C:/data/praca/webova_stranka/github/WEB_TO_ADMIN_IMAGE_STATUS_HANDOFF_2026-03-17_SK.md)

Najnovsi brand/Canva zaver:
- admin uz ma sekciu `Logo a ikonka`, prompty pre Canvu aj automaticku pripravu icon bundle
- netreba novy brand system od nuly
- treba len preverit a opravit, preco realny upload loga nefunguje v pouzivatelskom toku
- handoff zapisany do:
  - [WEB_TO_ADMIN_BRAND_CANVA_HANDOFF_2026-03-17_SK.md](C:/data/praca/webova_stranka/github/WEB_TO_ADMIN_BRAND_CANVA_HANDOFF_2026-03-17_SK.md)

Najnovsi admin audit z web vlakna:
- admin uz ma viacero technickych stavebnych blokov pre kandidatov, obrazky a kliky
- hlavny problem uz nie je len rozbity formular, ale strategia:
  - importer sa pokusa byt univerzalny priskoro
  - feed filtracia je stale prilis volna
  - Dognet deeplink krok nie je dostatocne jasne oddeleny od samotneho importu
- webovy zaver je preto jednoznacny:
  - najprv treba dokazat jeden funkcny `article-first` pilot
  - az potom rozsirovat workflow na dalsie clanky
- pilotny handoff zapisany do:
  - [WEB_TO_ADMIN_ONE_ARTICLE_PILOT_SK.md](C:/data/praca/webova_stranka/github/WEB_TO_ADMIN_ONE_ARTICLE_PILOT_SK.md)

Najnovsie spresnenie z web vlakna:
- pre admin bol doplneny novy fulfillment brief, ktory presne oddeluje:
  - co je technicky hotovy kandidat
  - co musi mat vyplnene pri jednom clanku
  - v akom poradi maju ist prve 3 clanky
- webovy zaver je:
  - admin ma ist `article-first` a `article-by-article`
  - nie tri clanky naraz jednym univerzalnym importerom
  - pri jednom clanku sa za hotovy stav povazuje kandidat az vtedy, ked ma:
    - `article_slug`
    - `product_url`
    - `image_status`
    - `click_status`
- brief zapisany do:
  - [WEB_TO_ADMIN_PRODUCT_FULFILLMENT_BRIEF_2026-03-19_SK.md](C:/data/praca/webova_stranka/github/WEB_TO_ADMIN_PRODUCT_FULFILLMENT_BRIEF_2026-03-19_SK.md)

Dotiahnute temy:
- kreatin
- proteiny
- kolagen
- vitamin D
- B vitaminy

Aktualny kvalitativny standard pre dolezite clanky:
- `Rychly zaver`
- decision-first struktura
- `Ako si zuzit vyber bez chaosu`
- `Kedy zbytocne nepriplacat`
- silnejsi prakticky ton

## 4. Co este chyba na webe

### Produkty
- admin musi dodat prve realne kandidatske produkty
- po ich dodani treba vo web vlakne urobit finalny vyber kus po kuse
- az potom sa dotiahnu:
  - comparison rows
  - top produkty
  - hlavne volby
  - finalne CTA pre web

### Brand a assety
- logo este nie je finalne
- favicon este nie je finalna
- OG assety este nie su finalne
- vacsina clankov stale nema vlastny article obrazok a treba to jasnejsie odlisit od fallbackov temy v admine

### Launch dokoncenie
- posledny pass na top 10 az 20 strankach po napojeni produktov
- posledna kontrola po nasadeni loga a brand assetov
- finalny hosting deployment az po tychto krokoch

## 5. Najblizsi webovy krok

Najblizsi odporucany krok vo web vlakne:
- pripravit presny shortlist slotov a rol pre prve 3 clanky
- dat adminu jasny rozsah kandidatov pre:
  - `najlepsie-proteiny-2026`
  - `kreatin-porovnanie`
  - `doplnky-vyzivy`
- po dodani kandidatov urobit finalny vyber kus po kuse

K tomu uz je pripraveny aj:
- [FIRST_3_ARTICLES_PRODUCT_SHORTLIST_SK.md](C:/data/praca/webova_stranka/github/FIRST_3_ARTICLES_PRODUCT_SHORTLIST_SK.md)
- [WEB_TO_ADMIN_IMPORT_HANDOFF_2026-03-17_SK.md](C:/data/praca/webova_stranka/github/WEB_TO_ADMIN_IMPORT_HANDOFF_2026-03-17_SK.md)
- [WEB_TO_ADMIN_IMPORT_UI_HANDOFF_2026-03-17_SK.md](C:/data/praca/webova_stranka/github/WEB_TO_ADMIN_IMPORT_UI_HANDOFF_2026-03-17_SK.md)
- [WEB_ADMIN_PRODUCT_PIPELINE_SK.md](C:/data/praca/webova_stranka/github/WEB_ADMIN_PRODUCT_PIPELINE_SK.md)
- [WEB_TO_ADMIN_BUG_HANDOFF_2026-03-17_SK.md](C:/data/praca/webova_stranka/github/WEB_TO_ADMIN_BUG_HANDOFF_2026-03-17_SK.md)
- [WEB_TO_ADMIN_IMAGE_STATUS_HANDOFF_2026-03-17_SK.md](C:/data/praca/webova_stranka/github/WEB_TO_ADMIN_IMAGE_STATUS_HANDOFF_2026-03-17_SK.md)
- [WEB_TO_ADMIN_BRAND_CANVA_HANDOFF_2026-03-17_SK.md](C:/data/praca/webova_stranka/github/WEB_TO_ADMIN_BRAND_CANVA_HANDOFF_2026-03-17_SK.md)
- [SIMPLE_PRODUCT_TEST_FLOW_SK.md](C:/data/praca/webova_stranka/github/SIMPLE_PRODUCT_TEST_FLOW_SK.md)
- [WEB_TO_ADMIN_PRODUCT_FULFILLMENT_BRIEF_2026-03-19_SK.md](C:/data/praca/webova_stranka/github/WEB_TO_ADMIN_PRODUCT_FULFILLMENT_BRIEF_2026-03-19_SK.md)

Do toho momentu web vrstva:
- dalej jemne dorovnava kvalitu top stranok
- nedoplnuje masovo dalsie nove clanky
- drzi fokus na launch, nie na dalsie nafukovanie poctu textov

## 6. Mimoriadna oprava homepage CSS

Datum:
- `19.03.2026`

Problem:
- homepage na lokalnom serveri `127.0.0.1:5001` sa zobrazovala ako takmer neostylovany HTML vystup
- realny problem nebol v HTML sablone homepage, ale v nacitanych CSS suboroch
- vo viacerych hlavnych stylesheetoch boli poskodene komentare a rozbite kodovanie, co vedelo narusit parsovanie stylov v prehliadaci

Opravene subory:
- [public/assets/css/main.css](C:/data/praca/webova_stranka/github/public/assets/css/main.css)
- [public/assets/css/compat.css](C:/data/praca/webova_stranka/github/public/assets/css/compat.css)
- [public/assets/css/patch.css](C:/data/praca/webova_stranka/github/public/assets/css/patch.css)
- [public/assets/css/home-b12.css](C:/data/praca/webova_stranka/github/public/assets/css/home-b12.css)

Co sa spravilo:
- odstranili sa poskodene komentare
- subory sa ulozili znova ako ciste UTF-8 bez BOM
- homepage znova vracia normalne HTML aj ciste stylesheety

Poznamka pre dalsie vlakna:
- ak sa po tejto oprave stale zobrazi stara rozbita verzia, najprv spravit tvrdy refresh v prehliadaci
- toto nebola obsahova chyba homepage, ale chyba v nacitanych CSS suboroch
