# Obrazky a ikony na webe Interesa

Tento web uz ma pripraveny technicky zaklad pre obrazky v `public/inc/media.php` a registry v `public/content/media`.

## Co vieme robit priamo v projekte

- jednotne renderovat hero obrazky clankov a kategorii
- pouzivat fallback placeholder, ked obrazok este chyba
- doplnat `alt`, `width`, `height`, `loading` a `fetchpriority`
- drzat realne produktove obrazky z merchant feedov alebo vzdialenych URL
- napajat clanky na kategoricke fallback vizualy, ked este nemaju vlastny hero obrazok

## Kde ukladat obrazky

- clanky: `public/assets/img/articles/<slug>/`
- kategorie: `public/assets/img/categories/<slug>/`
- produkty: `public/assets/img/products/<merchant>/`
- brand: `public/assets/img/brand/`
- placeholdery: `public/assets/img/placeholders/`
- SVG ikony: `public/assets/img/icons/`

## Nazvy suborov

Pouzivaj vzdy:

- male pismena
- bez diakritiky
- slova oddelovane pomlckami
- idealne SEO nazov podla clanku alebo produktu

Priklady:

- `protein-na-chudnutie-hero.webp`
- `veganske-proteiny-thumb.webp`
- `gymbeam-true-whey.webp`
- `horcik-citrat-kapsuly.webp`

## Odporucany styl obrazkov

### Ilustracne clankove obrazky

Drz jeden konzistentny smer:

- ciste svetle pozadie
- jeden dominantny objekt alebo temu
- minimum textu priamo v obrazku
- jemna zelena alebo neutralna farebnost ako na webe
- radsej jednoduchy produktovy alebo ingrediencny vizual nez genericky chaos

### Produktove obrazky

Pri produktoch preferuj:

- realny packshot z feedu alebo merchant webu
- transparentne alebo ciste svetle pozadie
- rovnaky pomer stran v ramci jednej sekcie
- neprepisovat produkt zbytocnou grafikou

## Ikony a menu

Aktualny smer webu je textova navigacia bez velkych ilustracnych ikon v hlavnom menu.

To znamena:

- horne menu nechavame ciste a textove
- stare PNG ikonky v `public/assets/img/icons/` ber ako legacy assety
- ak budeme pridavat nove ikony, preferovane su jednoduche SVG pre mikro UI, nie velke farebne obrazky v navigacii
- kategorie a huby mozu pouzivat jemne inline SVG badge ikony, aby vizual posobil jednotne

## Kedy pouzit co

- hero clanku: ilustracny obrazok k teme
- top produkty a porovnania: realny produktovy packshot
- kategoria bez vlastneho vizualu: fallback na kategoricky hero obrazok
- menu a mikro UI: jednoduche jednofarebne SVG ikony

## Ako to robit prakticky

1. Obrazok priprav mimo kodu v grafickom nastroji alebo generatori.
2. Uloz ho do spravneho priecinka v `public/assets/img/...`.
3. Ak ide o clanok alebo kategoriu, dopln meta zaznam do `public/content/media/articles.php` alebo `public/content/media/categories.php`.
4. Ak ide o produkt, dopln obrazok do produktoveho katalogu alebo cez merchant feed.
5. Potom ho web vie renderovat jednotne cez media helper.

## Dolezita poznamka

Codex vie velmi dobre pomoct s:

- architekturou assetov
- SVG ikonami
- naming convention
- render helpermi
- napojenim obrazkov do sablon

Pri samotnych hero ilustraciach je najlepsi workflow tento:

- obrazok vytvorit alebo doladit v externom nastroji
- potom ho sem len zaradit, pomenovat a napojit do existujuceho systemu

To je najspolahlivejsia cesta, ako drzat konzistentny vizual bez chaosu.