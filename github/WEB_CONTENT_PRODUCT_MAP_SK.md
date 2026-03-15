# Interesa.sk - mapa clankov, produktov a merchantov

Tento dokument je podklad medzi `web vlaknom` a `admin vlaknom`.

Ciel:
- najprv vo web casti rozhodnut `co` sa ma na webe zobrazovat
- az potom v admin casti riesit `ako` sa to technicky naimportuje, napoji a zobrazi

## 1. Pravidlo rozdelenia

### Web vlakno riesi
- ktore temy su hlavne SEO piliere
- ktore clanky budu money pages
- ktore clanky maju mat comparison table
- ktore clanky maju mat top produktovy box
- ake typy produktov do clanku patria
- aky merchant ma mat prioritu

### Admin vlakno riesi
- import alebo manualne pridanie produktov
- affiliate linky
- packshoty a produktove obrazky
- mapovanie produkt -> clanok
- mapovanie produkt -> merchant
- upload workflow a spravu assetov

## 2. Hlavne kategorie s najvacsim potencialom

Poradie je kombinacia:
- SEO potencial
- affiliate potencial
- zrozumitelnost pre bezneho citatela
- potencial pre comparison table a top vyber

1. Proteiny
2. Sila a vykon
3. Kreatin
4. Zdrava vyziva
5. Vitaminy a mineraly
6. Chudnutie
7. Klby a koza
8. Probiotika a travenie
9. Imunita
10. Pre-workout
11. Aminokyseliny

## 3. Merchant priorita pre verejny web

Zakladne pravidlo:
- `GymBeam` ma byt obchodna priorita tam, kde ma dobry produktovy fit a konkurencieschopnu value volbu
- web ale nesmie posobit jednostranne, preto ostava prirodzeny mix merchantov

Odporucane poradie podla typu stranky:

### Proteiny a sportova suplementacia
1. GymBeam
2. Aktin
3. Myprotein

### Kazdodenne doplnky, vitaminy, horcik
1. GymBeam
2. Aktin
3. Myprotein

### Probiotika a travenie
1. Aktin
2. GymBeam
3. Myprotein

### Kolagen a klby
1. GymBeam
2. Aktin
3. Protein.sk alebo Myprotein podla konkretneho produktu

## 4. Typy boxov na webe

### Comparison table
Pouzit len tam, kde sa citatel rozhoduje medzi 2 az 5 konkretnymi moznostami.

Najvhodnejsie pre:
- top vyber
- porovnanie foriem
- rychly nakupny clanok

### Top produkty
Pouzit na money pages a na clanky, kde chceme rychlu cestu ku kliku do obchodu.

### Odporucena volba
Pouzit tam, kde jedna moznost dava zmysel pre vacsinu ludi a chceme skratit rozhodovanie.

## 5. Prioritna mapa clankov

### P1 - najdolezitejsie money pages

#### najlepsie-proteiny-2026
- Typ clanku: top vyber
- SEO ciel: najlepsie proteiny, protein podla ciela, aky protein vybrat
- Typy produktov:
  - whey koncentrat
  - whey isolate
  - clear protein
  - vegan blend
- Boxy:
  - comparison table
  - top produkty
  - odporucena volba
- Merchant priorita:
  1. GymBeam
  2. Aktin
  3. Myprotein
- Poznamka:
  hlavna vstupna money page pre cely protein cluster

#### protein-na-chudnutie
- Typ clanku: sprievodca + vyber
- SEO ciel: protein na chudnutie, protein do diety
- Typy produktov:
  - whey isolate
  - whey koncentrat value volba
  - clear protein
- Boxy:
  - comparison table
  - top produkty
- Merchant priorita:
  1. GymBeam
  2. Aktin
  3. Myprotein
- Poznamka:
  vysoko konverzny clanok, lebo riesi konkretny ciel a rychly vyber

#### najlepsi-protein-na-chudnutie-wpc-vs-wpi
- Typ clanku: porovnanie
- SEO ciel: WPC vs WPI, protein pri chudnuti
- Typy produktov:
  - WPC value volba
  - WPI redukcia
  - pripadne sportovy isolate blend
- Boxy:
  - comparison table
  - top produkty
- Merchant priorita:
  1. GymBeam
  2. Aktin
  3. Myprotein
- Poznamka:
  porovnavaci clanok s velmi dobrym intentom tesne pred nakupom

#### kreatin-porovnanie
- Typ clanku: porovnanie + top vyber
- SEO ciel: najlepsi kreatin, kreatin porovnanie
- Typy produktov:
  - kreatin monohydrat
  - creatine creapure
  - kreatin HCl ako alternativa
- Boxy:
  - comparison table
  - top produkty
  - odporucena volba
- Merchant priorita:
  1. GymBeam
  2. Aktin
  3. Myprotein
- Poznamka:
  hlavna money page pre vykonovy cluster

#### doplnky-vyzivy
- Typ clanku: zakladny vyber
- SEO ciel: doplnky vyzivy, ake doplnky brat, zakladne doplnky
- Typy produktov:
  - multivitamin
  - kreatin
  - vitamin D3 + K2
  - horcik
  - probiotika
- Boxy:
  - comparison table
  - top produkty
- Merchant priorita:
  1. GymBeam
  2. Aktin
  3. Myprotein
- Poznamka:
  siroka, ale velmi dobra vstupna stranka pre bezneho navstevnika

#### veganske-proteiny-top-vyber-2026
- Typ clanku: top vyber
- SEO ciel: vegansky protein, najlepsi rastlinny protein
- Typy produktov:
  - hrach + ryza blend
  - hrachovy izolat
  - rastlinny mix
- Boxy:
  - comparison table
  - top produkty
- Merchant priorita:
  1. GymBeam
  2. Aktin
  3. Myprotein
- Poznamka:
  dolezita sub-vertikala v proteinoch

### P2 - silne podporne money pages

#### horcik-ktory-je-najlepsi-a-preco
- Typ clanku: vyber podla formy
- SEO ciel: aky horcik je najlepsi, horcik bisglycinat vs citrat
- Typy produktov:
  - magnesium bisglycinate
  - magnesium citrate
  - magnesium malate
- Boxy:
  - top produkty
  - neskor comparison table, ak bude dost dat
- Merchant priorita:
  1. GymBeam
  2. Aktin
  3. Myprotein

#### kolagen-na-klby-porovnanie
- Typ clanku: porovnanie
- SEO ciel: kolagen na klby, aky kolagen na klby
- Typy produktov:
  - kolagen typ II
  - kolagen s vitaminom C
  - hydrolyzovany kolagen
- Boxy:
  - comparison table
  - top produkty
- Merchant priorita:
  1. GymBeam
  2. Aktin
  3. Protein.sk / Myprotein

#### kolagen-recenzia
- Typ clanku: recenzia + shortlist
- SEO ciel: kolagen vyber, najlepsi kolagen
- Typy produktov:
  - typ I a III
  - kolagen + vitamin C
  - cisty hydrolyzovany kolagen
- Boxy:
  - top produkty
  - odporucena volba
- Merchant priorita:
  1. GymBeam
  2. Aktin
  3. Protein.sk

#### pre-workout-ako-vybrat
- Typ clanku: sprievodca + vyber
- SEO ciel: aky pre-workout, najlepsia predtreningovka
- Typy produktov:
  - balanced stim
  - high stim
  - non-stim pump formula
- Boxy:
  - top produkty
  - neskor comparison table
- Merchant priorita:
  1. GymBeam
  2. Aktin
  3. Myprotein

#### probiotika-ako-vybrat
- Typ clanku: top vyber
- SEO ciel: najlepsie probiotika, ako vybrat probiotika
- Typy produktov:
  - multi-strain probiotika
  - everyday digestion probiotika
  - travel / simple capsules
- Boxy:
  - top produkty
  - neskor comparison table
- Merchant priorita:
  1. Aktin
  2. GymBeam
  3. Myprotein

### P3 - supporting content, zatial bez silnej produktovej vrstvy

Tieto clanky zatial pouzivat hlavne na SEO, interlinking a edukaciu:
- srvatkovy-protein-vs-izolat-vs-hydro
- kreatin-monohydrat-vs-hcl
- kedy-brat-kreatin-a-kolko
- kreatin-vedlajsie-ucinky-a-fakty
- probiotika-a-travenie
- vitamin-d3-a-imunita
- vitamin-c
- zinek
- aminokyseliny-bcaa-eaa
- bcaa-vs-eaa
- spalovace-tukov-realita
- chudnutie-tip

Pri tychto strankach staci:
- interlinking na money pages
- eventualne jeden lahky CTA blok
- nie nutne plna comparison table

## 6. Co ma ist do admin vlakna ako dalsi krok

Admin ma dostat zadanie az po tomto dokumente.

Presne ma riesit:
- vytvorenie produktov pre P1 a P2 clanky
- vazbu `clanok -> produkty`
- vazbu `produkt -> merchant`
- affiliate deeplink alebo fallback URL
- packshot / realnu fotku produktu
- oznacenie, ktory produkt je:
  - odporucena volba
  - value volba
  - cista volba
  - veganska volba

## 7. Minimalne launch-ready naplnenie

Ak chceme rychlo spustit monetizacne jadro webu, staci najprv naplnit tieto clanky:

1. najlepsie-proteiny-2026
2. protein-na-chudnutie
3. najlepsi-protein-na-chudnutie-wpc-vs-wpi
4. kreatin-porovnanie
5. doplnky-vyzivy
6. veganske-proteiny-top-vyber-2026
7. horcik-ktory-je-najlepsi-a-preco
8. kolagen-na-klby-porovnanie
9. kolagen-recenzia
10. pre-workout-ako-vybrat
11. probiotika-ako-vybrat

To je jadro, ktore da:
- najlepsiu kombinaciu SEO + affiliate
- najmensi chaos pri admin importe
- rychlu moznost testovat CTR a money flow
