# Web -> Admin brief: naplnenie webu produktmi, obrazkami a klikmi

Tento dokument je presny pracovny brief z web vlakna pre admin vlakno.

Neriesi univerzalny importer.
Neriesi finalny editorialny vyber.
Riesi len to, co admin musi technicky dodat, aby web mohol byt naplneny realnymi produktmi.

## 1. Hlavny strategicky zaver

Spravny smer je:
- `article-first`
- `article-by-article`

Nie:
- najprv natiahnut vela produktov z feedov
- potom hadat, do coho patria

Ano:
- najprv vybrat jeden konkretny clanok
- potom pre ten clanok dostat cisty batch kandidatov
- potom pri kandidatovi doriesit:
  - produktovu URL
  - obrazok
  - click status
  - priradenie ku clanku

## 2. Rozhodnutie pre aktualnu fazu

Admin nema teraz riesit vsetky 3 clanky naraz v jednej chaotickej ceste.

Admin ma ist v tomto poradi:
1. `najlepsie-proteiny-2026`
2. `kreatin-porovnanie`
3. `doplnky-vyzivy`

Dovod:
- prvy clanok musi dokazat, ze pipeline realne funguje
- az potom sa ma workflow rozsirovat na dalsie clanky

## 3. Co presne ma admin dodat pri jednom clanku

Pri jednom clanku sa za technicky hotovy vysledok povazuje len kandidat, ktory ma:
- `article_slug`
- `product_url`
- `image_status`
- `click_status`

Minimalna technicka definicia:

### A. article_slug
- kandidat je ulozeny pre konkretny clanok
- nie je to len volny import bez ciela

### B. product_url
- je to konkretna produktova URL
- nie homepage shopu
- nie kategoriova stranka

### C. image_status
- jeden z tychto stavov:
  - `missing`
  - `remote_only`
  - `saved_local`

Pre web sa za dostatocny stav povazuje:
- pri finalnych kandidatoch `saved_local`

### D. click_status
- jeden z tychto stavov:
  - `missing`
  - `direct`
  - `dognet`

Vysvetlenie:
- `direct` = kandidat ma realnu produktovu URL, ale este nema Dognet deeplink
- `dognet` = kandidat uz ma realny affiliate klik / deeplink

## 4. Co presne ma admin urobit pri jednom clanku

### Krok 1
Vyber clanok.

### Krok 2
Importuj len kandidatov, ktori patria do tohto clanku.

### Krok 3
Pri kazdom kandidatovi priprav alebo over:
- produktovu URL
- obrazok produktu
- click status

### Krok 4
Technicky prirad kandidata ku clanku.

### Krok 5
Odovzdaj web vlaknu len ciste kandidatov, nie spinavy feed dump.

## 5. Pilotny clanok: najlepsie-proteiny-2026

Pre tento clanok admin importuje len:
- whey
- concentrate
- isolate
- clear
- vegan

Sem nepatri:
- tycinky
- snacky
- porridge
- meal replacement
- gainer
- kolagen
- pre-workout
- stimulanty

Technicky ciel pre tento pilot:
- `6 az 8` cistych kandidatov
- pri kazdom:
  - produktova URL
  - local obrazok alebo aspon jasny image status
  - click status
  - priradenie ku clanku

Pilot nie je hotovy, ak:
- batch stale miesa tycinky a balast
- kandidat nema jasnu produktovu URL
- obrazok ostava len ako nejasny remote stav bez rozlisenia
- Dognet je len domnienka bez realneho deeplinku alebo bez jasneho `direct` fallbacku

## 6. Dognet logika pre admin

Najdolezitejsia technicka vec:
- import produktu nie je Dognet klik

Spravna logika:
1. feed alebo shop doda `product_url`
2. admin ulozi produktovy zaznam
3. admin pripravi click vrstvu
4. ak existuje realny Dognet deeplink, stav je `dognet`
5. ak zatial existuje len priama produktova URL, stav je `direct`

Admin nema predstierat automatiku, ak realny deeplink este nevznikol.

Web vlakno potrebuje len pravdivy technicky stav:
- `missing`
- `direct`
- `dognet`

## 7. Co ma admin odovzdat spat web vlaknu

Pre kazdy clanok ma admin dodat jednoduchy zoznam kandidatov v tomto tvare:

- `nazov`
- `merchant`
- `produktovy typ`
- `product_url`
- `image_status`
- `click_status`
- `article_slug`

Idealne aj s internym slot navrhom:
- `main`
- `value`
- `clean`
- `alternative`
- `comparison`

Ale slot navrh este nie je finalne editorialne rozhodnutie.

## 8. Co admin teraz nema riesit

Admin teraz nema rozhodovat:
- finalne poradie pre web
- hlavnu volbu
- vyhodnu volbu
- comparison rows
- finalne CTA bloky

To je az dalsi krok vo web vlakne po dodani technicky pripravenych kandidatov.

## 9. Prakticke pravidlo pre aktualnu fazu

Namiesto otazky:
- `ako naraz naplnit cely web produktmi`

sa ma admin pytat:
- `viem pre tento jeden clanok dodat cisty batch s URL, obrazkom a click stavom`

Ak odpoved nie je `ano`, admin workflow este nie je hotovy.

## 10. Kratke zadanie pre admin vlakno

```text
Neries teraz univerzalny importer ani vsetky clanky naraz.

Ries article-first, article-by-article pipeline.

Poradie:
1. najlepsie-proteiny-2026
2. kreatin-porovnanie
3. doplnky-vyzivy

Pri jednom clanku sa za hotovy technicky vysledok povazuje len kandidat, ktory ma:
- article_slug
- product_url
- image_status
- click_status

Click status musi byt pravdivy:
- missing
- direct
- dognet

Pre pilot najlepsie-proteiny-2026 importuj len:
- whey
- concentrate
- isolate
- clear
- vegan

Nevhodne produkty:
- tycinky
- snacky
- porridge
- meal replacement
- gainer
- kolagen
- pre-workout
- stimulanty

Web vlakno nechce feed dump.
Web vlakno chce cisty zoznam kandidatov, pri ktorych je jasne:
- co to je
- kam patri
- ci ma obrazok
- ci ma klik
```
