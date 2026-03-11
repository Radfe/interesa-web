# Affiliate / Dognet Guide

## Cieľ architektúry

Affiliate odkazy nesmú byť rozhádzané natvrdo po článkoch. Web používa centralizovaný tok:
- produktový katalóg
- affiliate link registry
- interná route `/go/<code>`
- reusable CTA a produktové komponenty

## Hlavné súbory

- `public/content/products/catalog.php`
- `public/content/affiliates/links.php`
- `public/inc/affiliates.php`
- `public/inc/products.php`
- `public/inc/affiliate-ui.php`
- `public/inc/top-products.php`
- `public/go.php`

## Dátový model produktu

Každý produkt môže niesť aspoň tieto polia:
- `name`
- `slug`
- `merchant`
- `merchant_slug`
- `category`
- `affiliate_code`
- `fallback_url`
- `summary`
- `pros`
- `cons`
- `image`
- `feed_source`
- `merchant_product_id`
- `image_source`

To znamená, že architektúra je pripravená na budúce feedy aj na ručne kurátorované editorial picks.

## Affiliate link workflow

1. Produkt alebo CTA používa `affiliate_code`.
2. Frontend odkazuje na internú route `/go/<code>`.
3. `public/go.php` presmeruje na URL vyriešenú v `public/inc/affiliates.php`.
4. URL môže pochádzať z:
   - `public/content/affiliates/links.php`
   - CSV ako `affiliate_simple_edit.csv`
5. CSV override má prednosť pre reálne Dognet deeplinky.

Tým pádom vieš meniť ciele odkazov bez ručného editovania článkov.

## CTA a reusable prvky

Dostupné helpery:
- `interessa_affiliate_cta_html()`
- `interessa_render_affiliate_disclosure()`
- `interessa_render_product_box()`
- `interessa_render_recommended_product()`
- `interessa_render_comparison_table()`

Špecializovaný renderer pre shortlisty zostáva v `public/inc/top-products.php`, ale už stojí na tej istej centralizovanej affiliate logike.

## Dognet feed ready vrstva

Pripravené miesto pre feedy:
- `public/storage/dognet/`

Pripravený tool skeleton:
- `public/tools/import-dognet-feed.php`

Odporúčaný budúci tok:
1. export feedu uložiť do `public/storage/dognet/raw/`
2. normalizačným skriptom ho previesť do interného tvaru
3. vybrané produkty zapísať do `public/content/products/catalog.php` alebo do samostatnej normalizovanej vrstvy
4. Dognet deeplinky držať v CSV alebo v `links.php`

## Disclosure

Odporúčané pravidlo:
- disclosure zobrazovať pri komerčných boxoch, shortlistoch a porovnaniach
- `/go/` route držať s `noindex, nofollow`
- CTA nerozbíjať agresívnymi widgetmi alebo extra JS

## Použitie v obsahu

Príklad produktu v katalógu:
- produkt má vlastné dáta, obrázok, merchant a affiliate code

Príklad v článku:
- článok používa len editorial shortlist alebo `product_slug`
- renderer si doplní CTA, obrázok a disclosure centralizovane

Týmto sa oddeľuje obsah od affiliate logiky a zjednodušuje sa budúca výmena merchantov, deep linkov aj produktových obrázkov.