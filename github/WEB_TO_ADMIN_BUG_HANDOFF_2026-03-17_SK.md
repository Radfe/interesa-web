# Web -> Admin handoff - konkretne bugy po teste importu - 2026-03-17

Tento handoff vznikol po dalsom realnom teste admin importu.

## 1. Co je teraz potvrdene

Zo spravania a URL je vidno dva konkretne problemy:

### Problem A - stale sa taha obsahovo zly kandidat

URL kandidata:
- `candidate=gymbeam-nutrend-excelent-protein-bar-limetka-pap-aja`

To znamena:
- system stale taha proteinovu tycinku ako kandidata pre hlavny proteinovy tok
- to je stale zly kandidat pre `najlepsie-proteiny-2026`

Zaver:
- siroky importny smer je stale zly
- `protein` je stale prilis siroky pojem pre tento clanok

### Problem B - krok s linkom produktu pada na nepriamej URL

Error URL:
- `error=Toto zatial nie je priama URL produktu. Do pola URL produktu vloz konkretnu stranku`

To znamena:
- admin v kroku `Vloz link produktu` ocakava priamu produktovu URL
- ale aktualny tok pouzivatela k tomu stale nedostane dost jasne
- pouzivatel sa vie lahko dostat na nespravny alebo prilis vseobecny link

Zaver:
- tok je stale matuci
- system ma jasnejsie povedat, aku URL teraz potrebuje

## 2. Co z toho plynie

Aktualne stale neplati, ze by import pipeline bol hotovy.

Hotove nie je hlavne:
- clanok-first import bez balastu
- jasne oddelenie:
  - kandidat z feedu
  - priama produktova URL
  - Dognet klik

## 3. Co treba opravit

### A. Pre prvy clanok zakazat siroky protein import

Pre `najlepsie-proteiny-2026` nepouzivat:
- `protein`

Povolit alebo odporucat len:
- `whey`
- `isolate`
- `clear`
- `vegan`

Ak sa da, admin ma:
- pri tomto clanku uplne schovat navrh `protein`
- alebo ho oznacit ako nevhodny pre hlavny vyber proteinov

### B. Pri kandidatoch z feedu zretelne rozlisit typ kandidata

Pri kazdom kandidátovi ma byt jasne vidno:
- je to proteinovy prasok?
- je to tycinka alebo snack?
- je to tabletovy doplnok?
- je to iny format?

Ak je to tycinka alebo snack:
- nema sa navrhovat pre `najlepsie-proteiny-2026`

### C. Krok s URL produktu spravit ludsky jasny

Ked admin pyta `Vloz link produktu`, ma tam byt velmi jasne:
- treba vlozit konkretnu stranku produktu
- nie homepage obchodu
- nie vseobecnu kategoriu

Lepsi text:
- `Sem vloz priamu URL konkretneho produktu v obchode, nie domovsku stranku ani kategoriu.`

### D. Pri chybe s URL zostat na tom istom produkte a na tom istom kroku

Ak je URL zla:
- nema to posobit ako rozbitie workflowu
- ma sa zobrazit jasna chyba pri tom istom produkte
- a pouzivatel ma vediet, co presne opravit

## 4. Co ma byt dalsi ciel

Nie dalsia kozmetika.

Dalsi ciel:
1. zastavit import balastu do hlavneho proteinoveho clanku
2. spravit jasnejsi krok pre priamu produktovu URL
3. dotiahnut clanok-first import tak, aby uz pri hlavnom clanku nepadali tycinky a snacky do rovnakeho toku

## 5. Kratka sprava pre admin vlakno

```text
Po dalsom teste su potvrdene 2 problemy:

1. stale sa taha zly kandidat:
- gymbeam-nutrend-excelent-protein-bar...
- proteinova tycinka nema byt kandidat pre hlavny clanok najlepsie-proteiny-2026

2. krok s URL produktu je stale matuci:
- system hlasi, ze to nie je priama URL produktu
- treba jasnejsie povedat, ze sem patri konkretna stranka produktu, nie homepage ani kategoria

Dalsi krok uz nema byt dalsie kozmeticke ladenie, ale:
- zastavit siroky `protein` import pre hlavny proteinovy clanok
- pre tento clanok pouzivat len:
  - whey
  - isolate
  - clear
  - vegan
- jasne rozlisit snacky a tycinky od realnych proteinovych kandidatov
- pri chybe s URL zostat na tom istom produkte a na tom istom kroku s ludskou hlaskou

Riad sa prosim podla:
- WEB_TO_ADMIN_IMPORT_UI_HANDOFF_2026-03-17_SK.md
- WEB_ADMIN_PRODUCT_PIPELINE_SK.md
- WEB_TO_ADMIN_BUG_HANDOFF_2026-03-17_SK.md
```
