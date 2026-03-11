# Images and Assets Guide

## Vizuálny smer

Aktuálne použité obrázky ukazujú smer, ktorý sa oplatí držať:
- jemné pastelové zelené a neutrálne pozadia
- čisté kompozície s veľkým množstvom voľného priestoru
- minimalistické still-life alebo ilustračné produktové vizuály
- mäkké svetlo a nízky vizuálny šum

Tento štýl je vhodný pre dlhodobý obsahový affiliate web, pretože pôsobí čisto, dôveryhodne a ľahko sa opakuje.

Poznámka: pôvodný `og-default.jpg` pôsobil štýlovo nekonzistentne. Nový kanonický brand fallback je `public/assets/img/brand/og-default.svg`.

## Kanonická štruktúra

Web používa existujúci asset root `public/assets/`, aby sa nerozbila kompatibilita.

Odporúčaná štruktúra:
- `public/assets/img/brand/`
- `public/assets/img/articles/`
- `public/assets/img/categories/`
- `public/assets/img/products/`
- `public/assets/img/placeholders/`
- `public/content/media/articles.php`
- `public/content/media/categories.php`

Legacy priečinky ako `cards/`, `hero/` alebo `icons/` zostávajú podporované ako fallback, ale nové assety by mali ísť do kanonickej štruktúry vyššie.

## Naming convention

Pravidlá:
- bez diakritiky
- malé písmená
- slová oddelené pomlčkami
- názov orientovaný na obsah a SEO

Príklady:
- `najlepsie-proteiny-2025/hero-1600.webp`
- `najlepsie-proteiny-2025/thumb-800.webp`
- `protein-na-chudnutie/detail-01-1200.webp`
- `aktin-whey-isolate-cut/main-1200.webp`
- `proteiny/hero-1600.webp`

## Odporúčané rozmery

### Články
- hero: `1600x900`
- thumb/card: `800x450`
- inline detail: `1200x675`

### Kategórie
- hero: `1600x900`
- thumbnail/icon: podľa potreby, ideálne `512x512`

### Produkty
- main: `1200x1200`
- gallery: `1200x1200`
- lightweight card fallback: `800x800`

## Formát a výkon

Preferovaný formát:
- WebP ako primárny formát pre nové assety

Zásady:
- explicitné `width` a `height`
- `loading="lazy"` mimo critical above-the-fold prvkov
- `decoding="async"` pre väčšinu obrázkov
- `fetchpriority="high"` len pre hlavný hero obrázok stránky
- pre produkty a články použiť varianty s width suffixom, napr. `hero-800.webp`, `hero-1600.webp`

## Alt texty

Zásady:
- opisuj, čo je na obrázku a prečo je na stránke relevantný
- neopakuj presne len názov článku bez významu
- pri produktových obrázkoch uveď typ produktu alebo merchant kontext, ak je relevantný
- pri čisto dekoratívnych prvkoch zvažuj prázdny alt, ale len tam, kde obrázok naozaj nenesie obsah

Príklady:
- `Proteínový shaker a odmerka s proteínom na mätozelenom pozadí`
- `Produktový vizuál pre whey isolate od Aktinu`
- `Ikona kategórie vitamíny a minerály`

## Ilustračné vs produktové obrázky

Ilustračné obrázky:
- používaj pre hero sekcie, kategórie a edukatívne články
- majú držať vizuálny štýl webu

Produktové obrázky:
- používaj pre recenzie, porovnania a affiliate boxy
- môžu pochádzať z merchant feedov alebo Dognet dát
- udržuj jasný názov produktu, merchant a zdroj

## Technický workflow

Každý článok môže mať v `public/content/media/articles.php` definované:
- `hero`
- `thumb`
- `gallery`

Každá kategória môže mať v `public/content/media/categories.php` definované:
- `hero`
- `thumb`

Renderovanie prechádza cez helpery v `public/inc/media.php`, ktoré:
- hľadajú nový kanonický asset
- podporujú responsive varianty s width suffixom
- doplnia fallback placeholder
- doplnia rozmery a lazy loading