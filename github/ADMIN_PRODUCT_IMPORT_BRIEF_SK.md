# Zadanie pre admin vlakno - produkty, affiliate linky a packshoty

Toto zadanie nadvazuje na dokument:
- `WEB_CONTENT_PRODUCT_MAP_SK.md`

Ciel:
- vo web casti uz je rozhodnute, ktore clanky budu hlavne money pages
- teraz treba v admin casti vyriesit, ako sa produkty, affiliate linky a realne packshoty dostanu do systemu

## 1. Co uz je rozhodnute vo web casti

Vo web casti je uz urcene:
- ktore clanky su priorita
- ake typy produktov do nich patria
- kde ma byt comparison table
- kde ma byt top produktovy box
- aki merchanti maju mat prioritu

Admin uz nema rozhodovat `co` sa ma na webe zobrazit, ale ma vyriesit `ako` to dostat do systemu.

## 2. Hlavny ciel admin implementacie

Potrebujem system, ktory umozni:
- pridat alebo importovat produkt
- priradit mu merchant a affiliate link
- nahrat realnu fotku produktu
- priradit produkt ku konkretnemu clanku
- urcit, ci patri do:
  - comparison table
  - top produkty
  - odporucena volba

Nechcem zatial bezhlavy hromadny XML import.

Preferovany smer:
- jednoduchy polo-manualny workflow
- produkt pridam cielene
- priradim ho ku konkretnym clankom
- nahram spravny packshot
- nastavim affiliate odkaz

## 3. Prioritne clanky na naplnenie

Najprv naplnit tieto clanky:

1. `najlepsie-proteiny-2026`
2. `protein-na-chudnutie`
3. `najlepsi-protein-na-chudnutie-wpc-vs-wpi`
4. `kreatin-porovnanie`
5. `doplnky-vyzivy`
6. `veganske-proteiny-top-vyber-2026`
7. `horcik-ktory-je-najlepsi-a-preco`
8. `kolagen-na-klby-porovnanie`
9. `kolagen-recenzia`
10. `pre-workout-ako-vybrat`
11. `probiotika-ako-vybrat`

Tieto stranky maju najvacsi pomer:
- SEO potencial
- affiliate potencial
- rychly prakticky prinos po naplneni

## 4. Co ma admin pri kazdom clanku vediet

Pri kazdom clanku chcem vediet:
- zoznam produktov v poradi
- ktory produkt je:
  - odporucena volba
  - value volba
  - cista volba
  - veganska volba
  - alternativa
- do ktoreho boxu patri:
  - comparison table
  - top produkty
  - oboje

## 5. Minimalne polia produktu

Navrhujem, aby kazdy produkt vedel mat minimalne tieto polia:

- `slug`
- `name`
- `merchant`
- `merchant_slug`
- `category`
- `product_type`
- `short_label`
- `benefit`
- `rating`
- `affiliate_code`
- `affiliate_url` alebo fallback URL
- `image_source`
- `image_asset`
- `packshot_status`
- `best_for`
- `notes`

Volitelne:
- `price_band`
- `flavour_note`
- `form`
- `dosage_note`

## 6. Co ma admin workflow vediet

### A. Pridanie produktu
Workflow:
1. vyberiem clanok alebo kategoriu
2. pridam produkt
3. nastavim merchant
4. vlozim affiliate link alebo kod
5. nahram packshot
6. oznacim typ pozicie vo vybere
7. ulozim

### B. Priradenie ku clanku
Produkt sa musi dat priradit k:
- jednemu clanku
- viacerym clankom

Priklad:
- jeden kreatin moze byt v `kreatin-porovnanie`
- aj v `doplnky-vyzivy`
- aj v `sila a vykon`, ak tam neskor bude money blok

### C. Box typ
Pri vazbe `produkt -> clanok` chcem vediet nastavit:
- `comparison`
- `top_products`
- `featured`

### D. Priorita poradia
Pri produkte v clanku chcem vediet nastavit:
- poradie
- ci je odporucany ako `top 1`

## 7. Merchant priorita

Zakladne pravidlo verejneho webu:
- GymBeam je obchodna priorita
- ale nie vsade nasilu
- web ma ostat doveryhodny

Admin nech teda podporuje prirodzene poradie merchantov:

### Proteiny, vykon, kreatin, pre-workout
1. GymBeam
2. Aktin
3. Myprotein

### Horcik, vitaminy, zakladne doplnky
1. GymBeam
2. Aktin
3. Myprotein

### Probiotika
1. Aktin
2. GymBeam
3. Myprotein

### Kolagen
1. GymBeam
2. Aktin
3. Protein.sk alebo Myprotein

## 8. Packshoty a obrazky

Chcem, aby admin vedel:
- nahrat realny packshot produktu
- ulozit ho k spravnemu merchantovi a produktu
- zobrazit preview
- pripadne neskor vymenit starsi packshot za novsi

Minimalne stavy obrazka:
- realna fotka produktu
- ilustracny vizual
- bez obrazka

Packshot ma byt naviazany na:
- produkt
- merchant

## 9. Co nechcem robit v prvej faze

Zatial nechcem:
- plne automaticky XML import bez kontroly
- masivne feedy a hromadne mapovanie bez obsahovej logiky
- komplikovane interné workflowy navyse

Prva faza ma byt:
- jednoducha
- rucne kontrolovana
- prakticka

## 10. Co ma byt vystup admin vlakna

Chcem:

1. kratku analyzu, ako to najlepsie doplnit do existujucej admin architektury
2. navrh konkretneho workflowu
3. zoznam admin suborov, ktore bude treba menit
4. implementaciu po malych krokoch
5. testovanie na prvych prioritnych clankoch

## 11. Prvy prakticky ciel

Ak ma admin zacat co najpraktickejsie, nech ako prve vie:

- pridat produkt
- nahrat packshot
- priradit produkt ku clanku
- nastavit affiliate link
- zobrazit produkt na webe v:
  - top produktoch
  - comparison table

Ak toto bude fungovat pre:
- `najlepsie-proteiny-2026`
- `kreatin-porovnanie`
- `doplnky-vyzivy`

tak potom sa da bez chaosu rozsirovat dalej.
