# Web -> Admin handoff: one-article pilot pre produkty

Tento dokument je zamerne uzky.

Neriesi univerzalny importer pre vsetky clanky.
Riesi jeden funkcny end-to-end pilot pre jeden clanok.

Ak tento pilot nebude fungovat, nema zmysel dalej rozsirivat admin workflow na dalsie clanky.

## 1. Hlavny strategicky zaver

Spravna strategia je:
- najprv clanok
- potom presne sloty a povolene typy produktov
- potom import kandidatov iba pre tento clanok
- potom obrazok produktu
- potom klik do obchodu / Dognet stopa
- potom technicke priradenie ku clanku
- az nakoniec finalny vyber vo web vlakne

Nespravna strategia je:
- natiahnut produkty zo shop feedu vo velkom
- potom skusat zistovat, do coho patria
- a az dodatocne prisposobovat clanok importu

To je dovod, preco dnes v kandidat batchoch koncia:
- proteinove tycinky
- meal replacement produkty
- kofeinove tablety
- dalsi obsahovy balast mimo intentu clanku

## 2. Jeden pilotny clanok

Pilotny clanok:
- `najlepsie-proteiny-2026`

Preco prave tento:
- ma najjasnejsi intent
- ma najjasnejsie sloty
- ma najmensi priestor na obsahovy omyl
- ma najsilnejsiu obchodnu hodnotu
- vie rychlo ukazat, ci admin workflow realne funguje

Tento pilot ma dokazat, ze admin vie dodat:
- spravne kandidatov
- realne obrazky produktov
- realne kliky do obchodu
- technicke priradenie ku clanku

Az po tomto sa ma ist na:
- `kreatin-porovnanie`
- `doplnky-vyzivy`

## 3. Povoleny rozsah pre pilot

Clanok:
- `najlepsie-proteiny-2026`

Merchants v prvom pilotnom kole:
- `GymBeam`
- `Protein.sk`

Volitelne neskor:
- `IronAesthetics`

Import kandidatov v prvom pilotnom kole:
- spolu max `8` kandidatov

Finalny ciel pre web:
- `5 az 6` pouzitelnych produktov

## 4. Presne povolene produktove typy

Admin ma v pilotnom kole importovat len tieto typy:
- whey protein
- whey concentrate
- whey isolate
- clear whey
- vegan protein blend

Volitelne neskor:
- jedna sportova alternativa, ak bude obsahovo sediet

## 5. Co sem vyslovene nepatri

Do tohto clanku nesmu ist:
- proteinove tycinky
- snacky
- meal replacement
- gainer
- aminokyseliny
- kolagen
- pre-workout
- spalovace
- stimulanty
- performance mixy bez jasneho proteinoveho intentu

Ak produkt spada do niektorej z tychto skupin, admin ho nema ani pustit do pilotneho shortlistu.

## 6. Ako ma vyzerat admin workflow v tomto pilote

### Krok 1 - vyber clanku

Admin najprv vyberie:
- `najlepsie-proteiny-2026`

Bez vyberu clanku sa import pre pilot nema spustit.

### Krok 2 - article-specific import

Po vybere clanku ma admin ponuknut len odporucane filtre pre tento clanok:
- `whey`
- `isolate`
- `clear`
- `vegan`

Nema ponukat siroky filter:
- `protein`

Nema pustat import bez explicitneho clanku a bez explicitneho filtra.

### Krok 3 - cisty kandidat batch

Kazdy kandidat musi po importe mat minimalne:
- nazov
- merchant
- produktovu URL
- remote obrazok alebo image status
- cenu, ak prisla z feedu
- batch id
- article slug = pilotny clanok alebo prazdny navrh

Kazdy kandidat ma mat aj interny fit status:
- `allowed`
- `blocked`
- `needs_review`

Pre pilot:
- `allowed` iba pre povolene proteinove typy
- `blocked` pre tycinky, snacky, meal replacement a iny balast

### Krok 4 - obrazok produktu

Pred dalsim krokom musi admin vediet:
- ci ma kandidat realny produktovy obrazok
- ci je obrazok len remote URL
- ci sa obrazok uz realne ulozil do systemu

Pre pilot treba realne ulozit obrazok aspon pri finalnych kandidatoch.

### Krok 5 - klik do obchodu / Dognet stopa

Tu je dolezite rozlisit 2 technicke stavy:

#### Stav A - priama produktova URL

Admin ma:
- produktovu URL z feedu alebo e-shopu
- vie pripravit technicky zaznam produktu

To este nie je finalny affiliate klik.

#### Stav B - Dognet deeplink

Dognet deeplink nevznika automaticky len tym, ze kandidat ma produktovu URL.

Pre pilot je spravna polo-rucna logika:
- admin pripravi kandidata s `product_url`
- admin alebo helper doplni `deeplink_url`
- system prepne klik na affiliate / Dognet stav

Pri GymBeam uz na to v projekte existuje realna poloautomatika:
- `dognet-helper`
- CSV template
- import deeplinkov

Admin workflow preto nema predstierat, ze Dognet deeplink vie vzdy vytvorit sam.

Spravny technicky ciel pre pilot:
- pri kazdom finalnom kandidatovi musi byt jasne:
  - `product_url`
  - `click_status`
  - ci je klik:
    - `direct`
    - `dognet`
    - `missing`

Pilot moze prejst v dvoch fazach:

1. kandidat + produktovy obrazok + direct click
2. doplneny Dognet deeplink cez helper / affiliate workflow

### Krok 6 - technicke priradenie ku clanku

Az po:
- spravnom fit stave
- realnej produktovej URL
- obrazku
- a aspon zakladnom click stave

ma admin kandidata priradit ku clanku.

V tejto faze sa este nema robit:
- finalna editorialna rola
- top box
- comparison rows

To je rozhodnutie web vlakna az po cistom pilote.

## 7. Co presne ma admin dorucit po skonceni pilotu

Pre `najlepsie-proteiny-2026` ma admin dodat:

### A. shortlist kandidatov
- 6 az 8 kandidatov
- bez tyciniek a balastu

### B. pri kazdom kandidatovi
- merchant
- nazov
- produktovu URL
- image status
- click status
- navrh slotu:
  - universal whey
  - value whey
  - isolate
  - clear
  - vegan
  - optional sport alternative

### C. technicky stav
- kolko kandidatov ma realny obrazok
- kolko kandidatov ma priamy click
- kolko kandidatov ma Dognet deeplink
- kolko kandidatov je stale nepouzitelnych

## 8. Co je definicia uspechu

Pilot je uspesny len vtedy, ak admin doda pre `najlepsie-proteiny-2026`:
- cisty batch bez obsahoveho balastu
- realne obrazky finalnych kandidatov
- funkcny click stav pri finalnych kandidatoch
- technicky priradene produkty ku clanku

Pilot nie je uspesny, ak:
- importer dalej taha tycinky a nahrady stravy
- Dognet klik je len predstierany stav bez realneho deeplinku
- obrazky ostanu len ako remote URL bez realneho ulozenia
- system sa dalej sprava ako univerzalny feed importer bez clanku-first logiky

## 9. Kratke zadanie pre admin vlakno

```text
Neries teraz univerzalny importer pre vsetko.

Ries jeden funkcny pilot pre:
- najlepsie-proteiny-2026

Spravna strategia je:
1. najprv clanok
2. potom odporucane filtre pre ten clanok
3. potom cisty kandidat batch bez tyciniek a balastu
4. potom obrazky produktov
5. potom click status / Dognet deeplink
6. potom technicke priradenie ku clanku

Pre tento pilot su povolene len:
- whey
- concentrate
- isolate
- clear
- vegan blend

Zakazane:
- tycinky
- snacky
- meal replacement
- gainer
- kolagen
- pre-workout
- stimulanty

Dognet krok nema byt predstierany ako plna automatika.
Pouzi existujuci helper / affiliate workflow a jasne ukaz:
- product_url
- click_status = direct / dognet / missing

Ciel:
- jeden funkcny end-to-end clanok
- az potom rozsirovanie na dalsie clanky
```
