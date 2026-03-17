# Web -> Admin handoff - UI importu kandidatov - 2026-03-17

Tento handoff dokument vznikol po realnom teste obrazoviek importu v admine.

Ciel:
- zjednodusit import tak, aby pouzivatel nemusel hadat filter
- neimportovat obsahovo nespravne produkty
- dostat sa rychlo k prvemu cistemu batchu kandidatov pre prve 3 clanky

## 1. Hlavny problem zo screenshotov

Aktualny tok je stale prilis chaoticky:
- admin pyta `obchod + feed URL + volitelny filter`
- pouzivatel nevie, co presne napisat do filtra
- pri filtre `protein` sa natiahnu aj proteinove tycinky a iny balast
- po importe sa da sice klikat dalej, ale tok stale nie je dost clanok-first

Prakticky dosledok:
- z feedu sa natiahli produkty, ktore sice obsahuju slovo `protein`, ale nepatria do clanku `najlepsie-proteiny-2026`
- napriklad proteinove tycinky nemaju byt kandidat pre hlavny vyber proteinov

Z toho plynie:
- problem uz nie je len technicky import
- problem je, ze UI stale pyta pouzivatela pojem, ktory nie je redakcne dost presny

## 2. Co ma byt hlavna zmena

Import uz nema byt:
- `feed URL + volny filter`

Import ma byt:
- `Najprv vyber cielovy clanok`
- `Potom admin ponukne odporucane filtre a typy produktov`
- `Az potom sa spusti import`

Teda:
1. vyber clanok
2. admin ukaze odporucany importny preset
3. pouzivatel len potvrdi alebo jemne upravi
4. import sa spusti

## 3. Presne importne presety pre prve 3 clanky

### A. najlepsie-proteiny-2026

Povolit kandidatov len pre:
- whey
- whey concentrate
- isolate
- clear protein
- vegan protein

Nepovolit alebo aktivne odfiltrovat:
- proteinove tycinky
- gainery
- BCAA
- EAA
- kolagen
- pre-workout
- kofein
- snacky

Odporucane filtre:
- `whey`
- `isolate`
- `clear`
- `vegan`

Zakazane alebo nevhodne prilis siroke filtre:
- `protein`

### B. kreatin-porovnanie

Povolit kandidatov len pre:
- kreatin
- creatine
- monohydrate
- creapure
- hcl

Nepovolit:
- kofein
- pre-workout
- aminokyseliny
- snacky
- spalovace

Odporucane filtre:
- `creatine`
- `kreatin`
- `monohydrate`
- `creapure`
- `hcl`

### C. doplnky-vyzivy

Povolit kandidatov len pre:
- multivitamin
- horcik
- magnesium
- vitamin d
- d3
- k2
- zinc
- zinok
- probiotic
- probiotika

Nepovolit:
- snacky
- gainery
- pre-workout
- uzke performance doplnky

Odporucane filtre:
- `multivitamin`
- `magnesium`
- `vitamin d`
- `d3`
- `zinc`
- `probiotic`

## 4. Co treba zmenit v UI

### Namiesto volneho pola ako hlavneho vstupu

Nedavat ako hlavne rozhodnutie:
- `Volitelny filter produktov`

Lebo pouzivatel nevie, co je redakcne spravne slovo.

### Nove hlavne poradie

Krok 1 ma vyzerat takto:
1. vyber cielovy clanok
2. vyber obchod
3. admin ponukne odporucane filtry ako tlacidla alebo predvolene hodnoty
4. az potom `Nahraj zoznam produktov`

### Konkretny navrh pre UI

Pre prve 3 clanky staci:
- dropdown `Pre ktory clanok importujes kandidatov?`
- po vybere clanku sa ukaze:
  - `Odporucany typ importu`
  - `Odporucane filtre`
  - `Co sem nepatri`

Priklad pre `najlepsie-proteiny-2026`:
- odporucane filtre:
  - `whey`
  - `isolate`
  - `clear`
  - `vegan`
- upozornenie:
  - `Nepouzivaj siroky filter protein, taha aj tycinky a snacky.`

## 5. Co treba skryt pocas importu

Ked prebieha importny kandidatovy tok, nema sa na tej istej obrazovke miesat:
- stary produktovy backlog
- starsie produkty mimo batchu
- ine produktove workflowy, ktore nesuvisia s aktualnym importom

Pouzivatel ma pocas importu vidiet len:
- aktualny batch kandidatov
- aktualne otvoreny kandidat
- dalsi spravny krok

## 6. Stav odkazu do obchodu

Text `Stav odkazu do obchodu: Chyba` je matuci, ked uz pri produkte existuje normalny produktovy link.

Ludsky vhodnejsie docasne stavy:
- `Klik do obchodu este nie je pripraveny`
- `Pouzij produktovy link alebo Dognet link`
- `Klik do obchodu je pripraveny`

## 7. Co ma admin urobit teraz

Najblizsi krok nie je dalsie drobne posuvanie anchorov.

Najblizsi krok je:
1. prerobit Krok 1 na clanok-first import
2. ponuknut odporucane filtre podla clanku
3. prestat pouzivat siroky filter `protein` ako bezny priklad pre hlavny proteinovy clanok
4. udrzat import v uzkom, cistom batchi

## 8. Kratka sprava pre admin vlakno

```text
Zo screenshotov je vidno, ze hlavny problem uz nie je len technicky import, ale samotne poradie krokov.

Aktualny tok `obchod + feed URL + volitelny filter` je stale prilis chaoticky, lebo pouzivatel nevie, co ma do filtra napisat.

Pre prve 3 clanky potrebujeme clanok-first import:
1. najprv vybrat cielovy clanok
2. admin ma podla clanku ponuknut odporucane filtre
3. az potom spustit import

Najdolezitejsie:
- pre `najlepsie-proteiny-2026` nepouzivat siroky filter `protein`
- taha aj proteinove tycinky a iny balast
- tam maju ist len filtre typu `whey`, `isolate`, `clear`, `vegan`

Pocas importu nech uzivatel vidi len:
- aktualny kandidat batch
- aktualne otvoreny kandidat
- dalsi spravny krok

Nemiesat mu do toho starsie produkty a iny backlog.

Riad sa prosim podla:
- FIRST_3_ARTICLES_PRODUCT_SHORTLIST_SK.md
- ADMIN_IMPORT_INPUT_GUIDE_SK.md
- WEB_TO_ADMIN_IMPORT_HANDOFF_2026-03-17_SK.md
- WEB_TO_ADMIN_IMPORT_UI_HANDOFF_2026-03-17_SK.md
```
