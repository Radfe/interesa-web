# Web + Admin pipeline pre produkty - Interesa.sk

Tento dokument zjednodusuje jednu klucovu vec:
- co presne potrebuje web vlakno
- co presne ma dodat admin vlakno
- a v ktorom kroku sa pripravi Dognet klik do obchodu

## 1. Zakladne pravidlo

Web vlakno neimportuje produkty.

Web vlakno rozhoduje:
- ktore clanky treba naplnit
- ake typy produktov tam patria
- ktore konkretne produkty z kandidatov prejdu na web

Admin vlakno nevybera finalne produkty pre web.

Admin vlakno robi technicky pipeline:
1. import kandidatov
2. priprava kliku do obchodu
3. priradenie ku clanku
4. schvalenie do systemu

## 2. Co je vstup z web vlakna

Web vlakno doda adminu:
- ktore clanky su prve na rade
- ake typy produktov patria do tych clankov
- ake filtre sa maju pouzit pri importe
- co do tych clankov urcite nepatri

Pre prvu fazu je to uz urcene v:
- [FIRST_3_ARTICLES_PRODUCT_SHORTLIST_SK.md](C:/data/praca/webova_stranka/github/FIRST_3_ARTICLES_PRODUCT_SHORTLIST_SK.md)
- [ADMIN_IMPORT_INPUT_GUIDE_SK.md](C:/data/praca/webova_stranka/github/ADMIN_IMPORT_INPUT_GUIDE_SK.md)
- [WEB_TO_ADMIN_IMPORT_HANDOFF_2026-03-17_SK.md](C:/data/praca/webova_stranka/github/WEB_TO_ADMIN_IMPORT_HANDOFF_2026-03-17_SK.md)
- [WEB_TO_ADMIN_IMPORT_UI_HANDOFF_2026-03-17_SK.md](C:/data/praca/webova_stranka/github/WEB_TO_ADMIN_IMPORT_UI_HANDOFF_2026-03-17_SK.md)

## 3. Co ma urobit admin vlakno

### Krok 1 - import kandidatov

Admin ma natiahnut len kandidatov pre prve 3 clanky:
- `najlepsie-proteiny-2026`
- `kreatin-porovnanie`
- `doplnky-vyzivy`

To znamena:
- nie cely feed bez rozdielu
- nie lubovolny siroky filter
- nie produkty mimo intentu clanku

Spravne je:
- najprv vediet, pre ktory clanok importujem
- potom pouzit uzky filter pre dany clanok

### Krok 2 - priprava kliku do obchodu

Toto je presne miesto, kde ma vzniknut Dognet klik.

Logika:
- kandidat uz ma produktovy link z feedu alebo z e-shopu
- admin pozna obchod alebo kampan
- admin z tohto produktoveho linku pripravi klik do obchodu
- ak je to mozne, klik ma byt Dognet deeplink alebo Dognet odkaz

Teda:
- import kandidatov este nie je Dognet klik
- Dognet klik sa pripravi az v samostatnom druhom kroku

### Krok 3 - priradenie ku clanku

Az ked ma kandidat:
- spravny obsahovy fit
- produktovy link
- klik do obchodu

potom ho admin priradi ku konkretnemu clanku.

V prvej faze:
- s docasnym poriadim
- bez finalnych editorialnych rol
- bez top boxu
- bez comparison boxu

### Krok 4 - schvalenie do systemu

Toto este nema byt finalne redakcne schvalenie pre web.

Znamena to len:
- kandidat je technicky pripraveny
- web vlakno ho moze posudit

## 4. Co potom urobi web vlakno

Az po krokoch 1 az 4 web vlakno rozhodne:
- tento produkt ano
- tento produkt nie
- tento bude hlavna volba
- tento bude vyhodna volba
- tento pojde do porovnania
- tento pojde do vrchneho vyberu

## 5. Najjednoduchsia verzia pipeline

Prakticky to ma vyzerat takto:

1. web povie:
- pre tento clanok chcem tieto typy produktov

2. admin importuje len kandidatov pre tento clanok

3. admin pripravi pri kandidatoch kliky do obchodu

4. admin ich technicky priradi ku clanku

5. web vyberie finalne produkty

6. admin alebo web ich zobrazi na verejnom webe podla finalneho vyberu

## 6. Co je teraz najblizsi spravny ciel

Nie je ciel:
- mat dokonaly univerzalny importer na vsetko

Ciel je:
- dostat prvy cisty kandidat batch
- s pripravenymi klikmi do obchodu
- pre prve 3 clanky

## 7. Kratka sprava pre admin vlakno

```text
Pre prve produkty potrebujeme jednoduchy a cisty pipeline:

1. web urci clanok a typy produktov
2. admin importuje len kandidatov pre ten clanok
3. admin pripravi klik do obchodu
4. admin kandidatov priradi ku clanku
5. az potom web vlakno urobi finalny vyber

Dolezite:
- import kandidatov nie je finalny vyber
- Dognet klik nema vznikat pri editorialnom vybere, ale v samostatnom technickom kroku po importe
- pre prvu fazu riesime len prve 3 clanky

Riad sa prosim podla:
- FIRST_3_ARTICLES_PRODUCT_SHORTLIST_SK.md
- ADMIN_IMPORT_INPUT_GUIDE_SK.md
- WEB_TO_ADMIN_IMPORT_HANDOFF_2026-03-17_SK.md
- WEB_TO_ADMIN_IMPORT_UI_HANDOFF_2026-03-17_SK.md
- WEB_ADMIN_PRODUCT_PIPELINE_SK.md
```
