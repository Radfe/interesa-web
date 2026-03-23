# Jednoduchy test produktoveho importu - Interesa.sk

Tento dokument je pre bezny test bez chaosu.

Ciel:
- pouzivatel nema hladat, co robit
- admin nema miesat viac workflowov naraz
- testuje sa len jeden jednoduchy smer

## 1. Co teraz netestovat

Netestovat:
- siroky filter `protein`
- produkty, ktore su zjavne tycinky alebo snacky
- rucne lepenie nespravnych kandidatov do clankov
- homepage obchodu ako URL produktu
- kategorie ako URL produktu

## 2. Co testovat ako prvy cisty scenar

Testovat len:
- obchod: `GymBeam.sk`
- clanok: `najlepsie-proteiny-2026`
- filter: `whey`

Ak admin este nema clanok-first import, tak aspon:
- obchod: `GymBeam.sk`
- filter: `whey`

## 3. Jediny spravny test krok po kroku

1. otvor `Produkty`
2. zadaj Dognet feed URL
3. pouzi filter `whey`
4. klikni `Nahraj zoznam produktov`
5. ak sa ukazu:
   - whey protein
   - isolate
   - clear protein
   - vegan protein
   tak je to pouzitelny smer
6. ak sa ukazu:
   - tycinky
   - snacky
   - kofein
   - pre-workout
   tak je import stale zly

## 4. Ked otvoris jedneho kandidata

Ak kandidat vyzera obsahovo spravne:
- najprv priprav klik do obchodu
- potom ho prirad ku clanku
- s bezpecnymi hodnotami:
  - poradie `10`
  - `Bez oznacenia`
  - top vypnute
  - porovnanie vypnute

Ak kandidat vyzera obsahovo zle:
- nic nepriraduj
- vrat sa spat
- import je stale zle nastaveny

## 5. Pri URL produktu

Do pola URL produktu patri len:
- priama URL konkretneho produktu

Nepatri tam:
- homepage obchodu
- kategoria
- vseobecna landing page

## 6. Co je teraz hlavny ciel

Nie je ciel:
- dokoncit kazdy produkt rucne

Ciel je:
- dostat prvy cisty batch spravnych kandidatov

Az potom:
- web vlakno vyberie, co ma ist na web

## 7. Kratka sprava pre admin vlakno

```text
Pouzivatel potrebuje jeden jednoduchy test bez chaosu.

Preto teraz testujeme len:
- GymBeam.sk
- filter whey
- clanok najlepsie-proteiny-2026

Ak sa natiahnu tycinky alebo snacky, import je stale zly.
Ak sa natiahnu realne whey / isolate / clear / vegan kandidati, je to spravny smer.

Pouzivatel nema teraz rucne zachranovat zly batch.
Najprv treba dostat cisty batch kandidatov, az potom pokracovat dalej.
```
