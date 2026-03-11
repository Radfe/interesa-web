# Obrázky a ikony na webe Interesa

Tento web už má pripravený technický základ pre obrázky v `public/inc/media.php` a registry v `public/content/media`.

## Čo vieme robiť priamo v projekte

- jednotne renderovať hero obrázky článkov a kategórií
- používať fallback placeholder, keď obrázok ešte chýba
- dopĺňať `alt`, `width`, `height`, `loading`, `fetchpriority`
- držať reálne produktové obrázky z merchant feedov alebo vzdialených URL
- robiť jednoduché konzistentné SVG ikony pre menu, sekcie a badge prvky

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
- jeden dominantný objekt alebo téma
- minimum textu priamo v obrázku
- jemná zelená / neutrálna farebnosť ako na webe
- radšej jednoduchý produktový alebo ingredienčný vizuál než generický chaos

### Produktové obrázky

Pri produktoch preferuj:

- reálny packshot z feedu alebo merchant webu
- transparentné alebo čisté svetlé pozadie
- rovnaký pomer strán v rámci jednej sekcie
- neprepisovať produkt zbytočnou grafikou

## Kedy použiť čo

- hero článku: ilustračný obrázok k téme
- top produkty / porovnania: reálny produktový packshot
- menu a mikro UI: jednoduché jednofarebné SVG ikony

## Ako to robiť prakticky

1. Obrázok priprav mimo kódu v grafickom nástroji alebo generátore.
2. Ulož ho do správneho priečinka v `public/assets/img/...`.
3. Ak ide o článok alebo kategóriu, doplň meta záznam do `public/content/media/articles.php` alebo `public/content/media/categories.php`.
4. Ak ide o produkt, doplň obrázok do produktového katalógu alebo cez merchant feed.
5. Potom ho už web vie renderovať jednotne cez media helper.

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