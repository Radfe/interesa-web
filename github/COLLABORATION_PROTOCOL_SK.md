# Protokol spoluprace - web vlakno a admin vlakno

Tento dokument urcuje, ako maju web vlakno a admin vlakno spolupracovat bez chaosu.

## 1. Zakladne pravidlo

Obe vlakna maju spolupracovat cez subory v projekte, nie len cez chat.

To znamena:
- ak web vlakno nieco rozhodne, ma to byt zapisane v dokumente v projekte
- ak admin vlakno nieco doruci alebo zmeni workflow, ma to byt zapisane v dokumente v projekte
- chat sluzi len ako upozornenie, nie ako jediny zdroj pravdy

## 2. Kto o com rozhoduje

### Web vlakno rozhoduje
- ktore clanky su priorita
- ktore kampane sa maju pouzivat
- ake typy produktov patria do clankov
- ktore konkretne produkty budu schvalene na web
- co bude hlavna volba, vyhodna volba, ina moznost a porovnanie

### Admin vlakno rozhoduje
- ako sa produkt dostane do systemu
- ako sa importuje kandidat produktu
- ako sa pripravi klik do obchodu
- ako sa ulozi obrazok produktu
- ako sa produkt priradi ku clanku
- ako sa produkt schvali pre web

## 3. Hlavne spolocne dokumenty

Rychly onboarding pre novu AI relaciu:
- [PROJECT_MASTER_STATUS_SK.md](C:/data/praca/webova_stranka/github/PROJECT_MASTER_STATUS_SK.md)
- [AGENTS.md](C:/data/praca/webova_stranka/github/AGENTS.md)

Obsahova a obchodna priorita:
- [WEB_CONTENT_PRODUCT_MAP_SK.md](C:/data/praca/webova_stranka/github/WEB_CONTENT_PRODUCT_MAP_SK.md)
- [CAMPAIGN_ARTICLE_MAP_SK.md](C:/data/praca/webova_stranka/github/CAMPAIGN_ARTICLE_MAP_SK.md)
- [FINAL_CAMPAIGN_SHORTLIST_SK.md](C:/data/praca/webova_stranka/github/FINAL_CAMPAIGN_SHORTLIST_SK.md)
- [PRODUCT_SELECTION_POLICY_SK.md](C:/data/praca/webova_stranka/github/PRODUCT_SELECTION_POLICY_SK.md)
- [FIRST_PRODUCT_ROLLOUT_SK.md](C:/data/praca/webova_stranka/github/FIRST_PRODUCT_ROLLOUT_SK.md)

Admin stav:
- [ADMIN_IMPLEMENTATION_STATUS_SK.md](C:/data/praca/webova_stranka/github/ADMIN_IMPLEMENTATION_STATUS_SK.md)

Web stav:
- [WEB_IMPLEMENTATION_STATUS_SK.md](C:/data/praca/webova_stranka/github/WEB_IMPLEMENTATION_STATUS_SK.md)

## 4. Povinna synchronizacia

Pred kazdym vacsim krokom ma:

### Web vlakno
- precitat:
  - `ADMIN_IMPLEMENTATION_STATUS_SK.md`
  - `WEB_IMPLEMENTATION_STATUS_SK.md`

### Admin vlakno
- precitat:
  - `ADMIN_IMPLEMENTATION_STATUS_SK.md`
  - `WEB_IMPLEMENTATION_STATUS_SK.md`
  - hlavne obsahove a rollout dokumenty

## 5. Ako sa odovzdava zmena

Ak admin spravi vacsi krok:
- zapise co sa zmenilo do `ADMIN_IMPLEMENTATION_STATUS_SK.md`
- ak treba zasah na verejnom webe, dopise kratky handoff:
  - co sa zmenilo
  - co treba urobit na webe
  - kde sa to tyka suborov alebo workflowu

Ak web spravi vacsi krok:
- zapise co sa zmenilo do `WEB_IMPLEMENTATION_STATUS_SK.md`
- ak to meni ocakavania pre admin, dopise kratky handoff:
  - co je nova priorita
  - ktore clanky alebo produkty su teraz prve na rade

## 6. Co sa nema robit

- nepredpokladat, ze druhe vlakno "to vie z chatu"
- nerobit novu strategiu v admine
- nerobit technicky import vo web vlakne
- nezakladat dalsie paralelne stavove dokumenty bez dovodu

## 7. Prakticky ciel

Ciel nie je, aby sa vlakna rozpravali "automaticky".

Ciel je:
- aby obe vlakna po otvoreni repozitara okamzite videli ten isty aktualny stav
- a aby sa nemuselo vsetko vysvetlovat znova od nuly
- a aby sa novy Codex vedel zorientovat aj bez starej chat historie
