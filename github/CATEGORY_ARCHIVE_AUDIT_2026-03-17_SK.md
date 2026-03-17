# Audit kategorii a clankovych listingov - 2026-03-17

Tento audit vznikol po kontrole verejneho webu, hlavne kategorie:
- [mineraly](/C:/data/praca/webova_stranka/github/public/kategorie)

## Hlavny problem

Pouzivatelsky dojem `vyzera to ako rozbity alebo neprofesionalny web` je opodstatneny.

Najviac sa to ukazalo na kategorii `mineraly`.

## Zistenia

### 1. Privela clankov v jednej kategorii

`mineraly` ma aktualne:
- `88` clankov

To samo o sebe este nemusi byt problem, ale bez kuratorstva a limitu na listing stranka posobi ako obsahovy dump, nie ako editorialny hub.

### 2. Skoro vsetky karty pouzivali rovnaky fallback obrazok

Pri `mineraly`:
- len `6` clankov malo vlastny clanokovy obrazok
- az `82` clankov padalo na fallback

Prakticky dosledok:
- listing posobil, akoby boli vsetky clanky vizualne tie iste
- kategoria nevyzerala ako profesionalne kuratorovany prehlad

### 3. Prilis vela drobnych variacii za sebou

Najma v `mineraly` je velke mnozstvo velmi podobnych practical-use clankov:
- horcik rano / vecer
- horcik s jedlom / nalacno
- horcik ked zabudas
- horcik v hektickom dni

Bez limitu a bez diverzity to posobi:
- spamovo
- monotonne
- ako strojovo nafuknuty archiv

### 4. Zoradenie nie je dost editorialne

Kategoria berie clanky zo systemu vo vseobecnom poradi, nie v silne kuratorovanom vybere pre listing.

To sposobuje:
- zhluky podobnych tem
- slabsie first impression
- pocit, ze sa clovek topi v tom istom dookola

## Co som uz opravil

Na verejnom webe som upravil:
- [public/inc/category-landing.php](/C:/data/praca/webova_stranka/github/public/inc/category-landing.php)
- [public/assets/css/main.css](/C:/data/praca/webova_stranka/github/public/assets/css/main.css)

Konkretny dopad:
- `Dalsie clanky v teme` uz nezobrazuju neobmedzene vela podobnych clankov naraz
- listing teraz zobrazuje len vyber, nie cely zahlceny dump
- ak clanok nema vlastny obrazok, karta uz nepouziva rovnaky velky fallback obrazok ako ostatne
- namiesto toho sa zobrazi cistejsi textovo-ikonovy fallback

## Co este odporucam

### Kratkodobo pred launchom

1. Nechaj listingy kuratorovane, nie kompletne otvorene.
2. Pri velkych kategoriach zobraz len vyber a link na kompletny archiv.
3. Neinvestuj teraz do manualneho tvorenia desiatok dalsich obrazkov pre slabe supporting clanky.

### Strednodobo

1. Zaviesť silnejsie tematicke rodiny alebo subtopic groupings.
2. Pri velkych kategoriach zvazit:
- `Top clanky`
- `Najpraktickejsie odpovede`
- `Dalsie clanky`
3. Postupne znizit vizualnu zavislost od fallback obrazkov.

## Zaver

Problem nebol v tom, ze je na webe vela clankov sam o sebe.

Problem bol:
- ako boli vypisane
- ako malo boli kuratorovane
- a ako fallback obrazky vytvarali dojem, ze je vsetko to iste

Opraveny smer:
- menej zahltenia
- menej rovnakeho vizualu
- viac editorialneho vyberu
