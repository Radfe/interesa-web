# Interesa.sk - jednoduchy navod pre import kandidatov z adminu

Tento dokument je urceny pre:
- teba ako pouzivatela adminu
- admin vlakno
- web vlakno

Ciel:
- aby si pri importe nemusel hadat poradie, top produkt alebo comparison
- aby admin najprv dodal spravne kandidatov
- aby finalny vyber produktov robilo web vlakno az potom

## 1. Hlavne pravidlo

Pri prvom importe NERIES:
- poradie finalnych produktov
- hlavnu volbu
- vyhodnu volbu
- ci sa to ma ukazat v top produktoch
- ci sa to ma ukazat v porovnani

To sa nema rozhodovat pri importe.

Pri prvom importe ries len:
1. dostat spravnych kandidatov do systemu
2. pripravit im odkaz do obchodu
3. priradit ich ku spravnemu clanku

Az POTOM web vlakno rozhodne:
- co nechat
- co vyhodit
- co bude hlavna volba
- co pojde do comparison table
- co pojde do top produktov

## 2. Co mas robit ty v admine

Tvoj ciel pri importe je len tento:

### Krok 1
Vyber obchod a feed.

### Krok 2
Pouzi jednoduchy filter, aby si nenatahoval cely feed.

### Krok 3
Natiahni len kandidatov pre jeden konkretny clanok.

### Krok 4
Priprav im klik do obchodu.

### Krok 5
Prirad ich ku clanku.

### Krok 6
Nerob este finalne editorialne rozhodnutie.

## 3. Co sa ma importovat ako prve

Najprv ries len tieto 3 clanky:
- `najlepsie-proteiny-2026`
- `kreatin-porovnanie`
- `doplnky-vyzivy`

Podklad:
- [FIRST_3_ARTICLES_PRODUCT_SHORTLIST_SK.md](C:/data/praca/webova_stranka/github/FIRST_3_ARTICLES_PRODUCT_SHORTLIST_SK.md)

## 4. Presne co importovat pre kazdy clanok

### A. najlepsie-proteiny-2026

Importuj kandidatov len z:
- GymBeam
- Protein.sk
- IronAesthetics

Filter pouzi približne:
- `whey, isolate, clear, vegan, protein`

Sem patria len kandidati typu:
- whey koncentrat
- whey isolate
- clear protein
- vegan protein
- univerzalny whey protein

Sem NEPATRI:
- gainer
- BCAA / EAA
- kolagen
- pre-workout

Odporucany pocet kandidatov:
- 8 az 10

### B. kreatin-porovnanie

Importuj kandidatov len z:
- GymBeam
- Protein.sk
- IronAesthetics

Filter pouzi približne:
- `creatine, kreatin, monohydrate, creapure, hcl`

Sem patria len kandidati typu:
- kreatin monohydrat
- premium monohydrat
- HCl

Sem NEPATRI:
- pre-workout
- aminokyseliny
- spalovace

Odporucany pocet kandidatov:
- 6 az 8

### C. doplnky-vyzivy

Importuj kandidatov len z:
- GymBeam
- Imunoklub
- Symprove
- Protein.sk len doplnkovo pre kreatin

Filter pouzi približne:
- GymBeam:
  - `multivitamin, magnesium, vitamin d, d3, k2, creatine`
- Imunoklub:
  - `vitamin d, d3, c, zinc, zinok`
- Symprove:
  - `probiotic, probiotika`
- Protein.sk:
  - `creatine, kreatin`

Sem patria len kandidati typu:
- multivitamin
- horcik
- vitamin D3 / D3 + K2
- probiotika
- kreatin
- pripadne vitamin C alebo zinok

Sem NEPATRI:
- uzke specialitky
- beauty doplnky
- silne sportove performance veci

Odporucany pocet kandidatov:
- 8 az 10

## 5. Co nastavovat pri importe a co nie

### Nastavovat hned
- obchod
- feed URL
- filter
- clanok, ku ktoremu kandidat patri
- klik do obchodu, ak sa da pripravit

### Zatial nenastavovat natvrdo, ak to nie je nutne
- konecne poradie
- finalna hlavna volba
- finalna vyhodna volba
- finalne comparison row
- finalne top produkty

Ak system pyta tieto polia povinne, pouzi len docasne neutralne nastavenie.

Odporucane docasne:
- poradie: nechaj zakladne alebo automaticke
- top produkty: zatial nie
- porovnanie: zatial nie
- hlavna volba: zatial nie

## 6. Co ma admin vlakno dorucit spat do web vlakna

Web vlakno potrebuje od adminu len toto:

### Pre kazdy z 3 clankov
- zoznam kandidatov
- nazov produktu
- obchod
- typ produktu
- URL produktu
- ci ma obrazok
- ci ma klik do obchodu

Idealne v jednoduchom tvare:
- clanok
- produkt
- obchod
- typ
- stav

## 7. Jednoducha veta pre admin vlakno

Pri importe nechceme finalne rozhodovat, co bude top 1.
Chceme len dostat do systemu spravnych kandidatov pre prve 3 clanky, aby web vlakno potom urobilo finalny vyber.

## 8. Copy-paste sprava pre admin vlakno

```text
Potrebujem zjednodusit import tak, aby som pri nom nemusel rozhodovat finalne poradie ani top produkty.

Pri importe chcem riesit len:
1. natiahnut spravnych kandidatov
2. pripravit klik do obchodu
3. priradit ich ku spravnemu clanku

Finalne rozhodnutie o tom:
- co bude hlavna volba
- co pojde do top produktov
- co pojde do comparison table
- v akom bude poradi
robi az web vlakno.

Prvu fazu ries len pre:
- najlepsie-proteiny-2026
- kreatin-porovnanie
- doplnky-vyzivy

Riad sa podla:
- FIRST_3_ARTICLES_PRODUCT_SHORTLIST_SK.md
- ADMIN_IMPORT_INPUT_GUIDE_SK.md

Pri importe nechcem tahat cely feed.
Chcem tahat len mensi kandidatovy balik cez filter.
```
