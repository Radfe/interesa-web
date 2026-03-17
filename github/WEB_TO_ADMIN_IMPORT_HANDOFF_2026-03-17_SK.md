# Web -> Admin handoff - import kandidatskych produktov - 2026-03-17

Tento handoff dokument vysvetluje, co ma admin nastavovat pri importe kandidatov a co este nema nastavovat.

Ciel:
- aby import neziadal finalne editorialne rozhodnutie prilis skoro
- aby sa do systemu dostali spravni kandidati
- aby finálne rozhodnutie o zobrazeni robilo web vlakno az po importe

## 1. Problem, ktory je teraz vidno

Zo screenshotov je zrejme:
- admin uz vie:
  - natiahnut kandidatov
  - pripravit klik do obchodu
  - priradit produkt ku clanku
- ale v jednom kroku sa uz pyta aj na:
  - poradie
  - ako ho oznacit
  - ukazat medzi top produktmi
  - ukazat v porovnani

To je pre prvy import prilis skoro.

Okrem toho je vidno aj obsahovy problem:
- kandidat `ActivLab Caffeine Power 60 tab.` sa ocitol pri clanku `Kreatin - porovnanie`
- to nie je vhodny kandidat pre kreatinovy clanok

Z toho plynie:
- admin uz ma workflow
- ale este treba jasne oddelit:
  - import kandidatov
  - finalne editorialne rozhodnutie

## 2. Pravidlo pre prvu fazu

Pri importe kandidatov sa zatial nema rozhodovat:
- co bude hlavny tip
- co bude vyhodna volba
- co bude ina moznost
- co sa finalne ukaze v top produktoch
- co sa finalne ukaze v porovnani

Pri importe sa ma rozhodovat len:
1. patri tento produkt do daneho clanku alebo nie
2. ma alebo nema klik do obchodu
3. ma alebo nema obrazok
4. patri do prveho kandidatoveho batchu alebo nie

## 3. Presne pravidla pre admin pri aktualnom formulare

### Pole: Clanok
Pouzivat len tieto 3 clanky:
- `najlepsie-proteiny-2026`
- `kreatin-porovnanie`
- `doplnky-vyzivy`

Ak kandidat realne nepatri ani do jedneho z nich:
- nepriradovat ho
- alebo ho nechat ako kandidat na vyradenie

### Pole: Poradie
Toto zatial nema byt finalne poradie na webe.

Docasne pravidlo:
- pouzivat len technicke pomocne poradie:
  - 10
  - 20
  - 30
  - 40
  - 50

Teda:
- nie jemne poradie 1,2,3,4 podla editorialnej sily
- ale len pomocne blokove poradie, aby sa to dalo neskor jednoducho preusporiadat

### Pole: Ako ho oznacit
Docasne pravidlo:
- defaultne `Bez oznacenia`

Povolene v prvej faze len vtedy, ked je to uplne zrejme zo samotneho typu produktu:
- `Veganska moznost` len pre vegan protein
- `Cista moznost` len pre isolate alebo podobne cistejsi protein

V prvej faze NEPOUZIVAT:
- `Hlavny tip`
- `Vyhodna volba`
- `Ina moznost`

To je finalne editorialne rozhodnutie pre web vlakno az po importe.

### Checkbox: Ukazat medzi top produktmi
V prvej faze:
- nechat vypnute

### Checkbox: Ukazat v porovnani
V prvej faze:
- nechat vypnute

## 4. Co patri a nepatri do prvych 3 clankov

### A. najlepsie-proteiny-2026

Patri:
- whey koncentrat
- whey isolate
- clear protein
- vegan protein
- univerzalny whey protein

Nepatri:
- gainer
- BCAA / EAA
- kolagen
- pre-workout
- kofeinove tablety
- spalovace

### B. kreatin-porovnanie

Patri:
- kreatin monohydrat
- premium monohydrat
- Creapure
- HCl

Nepatri:
- pre-workout
- aminokyseliny
- kofeinove tablety
- spalovace
- nahodne performance produkty bez kreatinu

### C. doplnky-vyzivy

Patri:
- multivitamin
- horcik
- vitamin D3
- D3 + K2
- probiotika
- kreatin ako doplnkova nadstavba
- vitamin C alebo zinok ako doplnkovy kandidat

Nepatri:
- specializovane uzke doplnky bez sirokeho zmyslu
- beauty doplnky
- agresivne sportove performance produkty
- nahodne tabletky mimo zakladneho intentu clanku

## 5. Co ma admin dorucit web vlaknu

Pre kazdy z 3 clankov ma admin vratit len kandidatov v tejto logike:

- nazov produktu
- obchod
- typ produktu
- link produktu
- ma / nema klik do obchodu
- ma / nema obrazok
- docasne priradeny clanok

Web vlakno potom rozhodne:
- nechat / vyhodit
- top produkty ano / nie
- comparison ano / nie
- hlavny tip / vyhodna volba / ina moznost
- finalne poradie

## 6. Co treba zjednodusit v admine

Ak sa to da, admin ma upravit UI takto:

### V kroku po priprave odkazu
Neukazovat na rovnakej urovni ako povinne:
- `Ukazat medzi top produktmi`
- `Ukazat v porovnani`
- `Hlavny tip`
- `Vyhodna volba`

Lebo pri prvom importe to zbytocne tlaci pouzivatela do finalneho rozhodnutia.

Lepší docasny text by bol:
- `Toto je len kandidat. Finalne zobrazenie pre web sa urci az po vybere vo web vlakne.`

## 7. Prakticke pravidlo pre tento tyzden

Admin ma teraz robit len toto:
1. natiahnut spravnych kandidatov
2. odfiltrovat obsahovy balast
3. pripravit klik do obchodu
4. priradit ku spravnemu clanku
5. neriesit este finalne top / comparison / hlavny tip

## 8. Kratka sprava pre admin vlakno

```text
Pri aktualnom importe prosim neberte polia poradie, top produkty, porovnanie a oznacenie ako finalne editorialne rozhodnutie.

Pre prvu fazu plati:
- Clanok: priradit len k 3 prioritnym clankom
- Poradie: len docasne technicke 10,20,30...
- Ako ho oznacit: defaultne Bez oznacenia
- Veganska moznost a Cista moznost pouzit len ked je to uplne jasne z typu produktu
- Hlavny tip, Vyhodna volba a Ina moznost teraz este nepouzivat
- Ukazat medzi top produktmi: zatial vypnute
- Ukazat v porovnani: zatial vypnute

Najprv chceme len spravnych kandidatov.
Finalny vyber a finalne zobrazenie na webe urobi az web vlakno po importe.

Riadte sa prosim podla:
- FIRST_3_ARTICLES_PRODUCT_SHORTLIST_SK.md
- ADMIN_IMPORT_INPUT_GUIDE_SK.md
- WEB_TO_ADMIN_IMPORT_HANDOFF_2026-03-17_SK.md
```
