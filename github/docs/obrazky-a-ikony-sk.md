# Obrázky a ikony na webe Interesa

Tento web už má pripravený technický základ pre obrázky v `public/inc/media.php` a registry v `public/content/media`.

## Čo vieme robiť priamo v projekte

- jednotne renderovať hero obrázky článkov a kategórií
- používať fallback placeholder, keď obrázok ešte chýba
- dopĺňať `alt`, `width`, `height`, `loading` a `fetchpriority`
- držať reálne produktové obrázky z merchant feedov alebo vzdialených URL
- napájať články na kategóriové fallback vizuály, keď ešte nemajú vlastný hero obrázok

## Kde ukladať obrázky

- články: `public/assets/img/articles/<slug>/`
- kategórie: `public/assets/img/categories/<slug>/`
- produkty: `public/assets/img/products/<merchant>/`
- brand: `public/assets/img/brand/`
- placeholdery: `public/assets/img/placeholders/`
- SVG ikony: `public/assets/img/icons/`

## Názvy súborov

Používaj vždy:

- malé písmená
- bez diakritiky
- slová oddeľované pomlčkami
- ideálne SEO názov podľa článku alebo produktu

Príklady:

- `protein-na-chudnutie-hero.webp`
- `veganske-proteiny-thumb.webp`
- `gymbeam-true-whey.webp`
- `horcik-citrat-kapsuly.webp`

## Odporúčaný štýl obrázkov

### Ilustračné článkové obrázky

Drž jeden konzistentný smer:

- čisté svetlé pozadie
- jeden dominantný objekt alebo tému
- minimum textu priamo v obrázku
- jemná zelená alebo neutrálna farebnosť ako na webe
- radšej jednoduchý produktový alebo ingredienčný vizuál než generický chaos

### Produktové obrázky

Pri produktoch preferuj:

- reálny packshot z feedu alebo merchant webu
- transparentné alebo čisté svetlé pozadie
- rovnaký pomer strán v rámci jednej sekcie
- neprepisovať produkt zbytočnou grafikou

## Ikony a menu

Aktuálny smer webu je textová navigácia bez veľkých ilustračných ikon v hlavnom menu.

To znamená:

- horné menu nechávame čisté a textové
- staré PNG ikonky v `public/assets/img/icons/` ber ako legacy assety
- ak budeme pridávať nové ikony, preferované sú jednoduché SVG pre mikro UI, nie veľké farebné obrázky v navigácii
- kategórie a huby môžu používať jemné inline SVG badge ikony, aby vizuál pôsobil jednotne

## Kedy použiť čo

- hero článku: ilustračný obrázok k téme
- top produkty a porovnania: reálny produktový packshot
- kategória bez vlastného vizuálu: fallback na kategóriový hero obrázok
- menu a mikro UI: jednoduché jednofarebné SVG ikony

## Ako to robiť prakticky

1. Obrázok priprav mimo kódu v grafickom nástroji alebo generátore.
2. Ulož ho do správneho priečinka v `public/assets/img/...`.
3. Ak ide o článok alebo kategóriu, doplň meta záznam do `public/content/media/articles.php` alebo `public/content/media/categories.php`.
4. Ak ide o produkt, doplň obrázok do produktového katalógu alebo cez merchant feed.
5. Potom ho web vie renderovať jednotne cez media helper.

## Dôležitá poznámka

Codex vie veľmi dobre pomôcť s:

- architektúrou assetov
- SVG ikonami
- naming convention
- render helpermi
- napojením obrázkov do šablón

Pri samotných hero ilustráciách je najlepší workflow tento:

- obrázok vytvoriť alebo doladiť v externom nástroji
- potom ho sem len zaradiť, pomenovať a napojiť do existujúceho systému

To je najspoľahlivejšia cesta, ako držať konzistentný vizuál bez chaosu.