# Projektovy master stav - Interesa.sk

Tento dokument je hlavny rychly onboarding pre:
- nove web vlakno
- nove admin vlakno
- novy Codex ucet alebo novu AI relaciu po pripojeni repozitara

Ak sa AI nacita od nuly, ma najprv precitat tento subor a az potom otvorit dalsie stavove a strategicke dokumenty.

## 1. Ciel projektu

Interesa.sk je slovensky obsahovy affiliate web o:
- doplnkoch vyzivy
- proteinoch
- kreatine
- vitaminoch a mineraloch
- imunite
- klboch a kolagene

Nie je to e-shop.

Ciel je:
- doveryhodny verejny web
- silny SEO obsah
- rozumny vyber produktov
- jednoducha admin sprava kandidatov, produktov, obrazkov a klikov do obchodov
- finalne nasadenie na hosting az po dokonceni klucovych launch krokov

## 2. Rozdelenie zodpovednosti

### Web vlakno rozhoduje
- ktore clanky su priorita
- ktore kampane a obchody su priorita
- ake typy produktov patria do clankov
- ktore konkretne produkty budu schvalene na verejny web
- co bude:
  - hlavna volba
  - vyhodna volba
  - ina moznost
  - produkt v porovnani

### Admin vlakno rozhoduje
- ako sa kandidat produktu dostane do systemu
- ako sa importuje feed
- ako sa pripravi klik do obchodu
- ako sa uklada obrazok produktu
- ako sa kandidat priradi ku clanku
- ako sa kandidat neskor schvali pre web

## 3. Hlavne zdroje pravdy

Najprv citat:
- [AGENTS.md](C:/data/praca/webova_stranka/github/AGENTS.md)
- [PROJECT_MASTER_STATUS_SK.md](C:/data/praca/webova_stranka/github/PROJECT_MASTER_STATUS_SK.md)
- [NEW_CODEX_QUICKSTART_SK.md](C:/data/praca/webova_stranka/github/NEW_CODEX_QUICKSTART_SK.md)
- [COLLABORATION_PROTOCOL_SK.md](C:/data/praca/webova_stranka/github/COLLABORATION_PROTOCOL_SK.md)
- [WEB_IMPLEMENTATION_STATUS_SK.md](C:/data/praca/webova_stranka/github/WEB_IMPLEMENTATION_STATUS_SK.md)
- [ADMIN_IMPLEMENTATION_STATUS_SK.md](C:/data/praca/webova_stranka/github/ADMIN_IMPLEMENTATION_STATUS_SK.md)

Potom podla potreby:
- [WEB_CONTENT_PRODUCT_MAP_SK.md](C:/data/praca/webova_stranka/github/WEB_CONTENT_PRODUCT_MAP_SK.md)
- [CAMPAIGN_ARTICLE_MAP_SK.md](C:/data/praca/webova_stranka/github/CAMPAIGN_ARTICLE_MAP_SK.md)
- [FINAL_CAMPAIGN_SHORTLIST_SK.md](C:/data/praca/webova_stranka/github/FINAL_CAMPAIGN_SHORTLIST_SK.md)
- [PRODUCT_SELECTION_POLICY_SK.md](C:/data/praca/webova_stranka/github/PRODUCT_SELECTION_POLICY_SK.md)
- [FIRST_PRODUCT_ROLLOUT_SK.md](C:/data/praca/webova_stranka/github/FIRST_PRODUCT_ROLLOUT_SK.md)
- [FIRST_3_ARTICLES_PRODUCT_SHORTLIST_SK.md](C:/data/praca/webova_stranka/github/FIRST_3_ARTICLES_PRODUCT_SHORTLIST_SK.md)
- [ADMIN_IMPORT_INPUT_GUIDE_SK.md](C:/data/praca/webova_stranka/github/ADMIN_IMPORT_INPUT_GUIDE_SK.md)
- [WEB_TO_ADMIN_IMPORT_HANDOFF_2026-03-17_SK.md](C:/data/praca/webova_stranka/github/WEB_TO_ADMIN_IMPORT_HANDOFF_2026-03-17_SK.md)
- [WEB_AUDIT_2026-03-17_SK.md](C:/data/praca/webova_stranka/github/WEB_AUDIT_2026-03-17_SK.md)

## 4. Aktualny stav

### Verejny web
- verejny web je obsahovo silny a blizko launch-ready
- top a semitop clanky boli dorovnane do decision-first stylu
- robots a sitemap boli opravene
- logo, favicon a finalne OG assety este nie su finalne

### Admin
- admin ma 4-krokovy produktovy workflow:
  - nahrat zoznam produktov
  - pripravit klik do obchodu
  - priradit ku clanku
  - schvalit pre web
- import z Dognet feed URL uz funguje
- prvy import je zuzeny na kandidatov, nie finalny vyber
- prve 3 clanky pre rollout su:
  - `najlepsie-proteiny-2026`
  - `kreatin-porovnanie`
  - `doplnky-vyzivy`

## 5. Dolezite pravidlo pri produktoch

Prvy import neznamena finalne zobrazenie na webe.

Najprv:
- kandidat
- klik do obchodu
- priradenie ku clanku

Az potom web vlakno rozhodne:
- co ponechat
- co vyradit
- co bude hlavna volba
- co bude vo vrchnom vybere
- co bude v porovnani

## 6. Aktualny najblizsi krok

Najvyssia priorita teraz je:
- admin doda prvy cisty batch kandidatov pre prve 3 clanky
- web vlakno spravi finalny vyber produktov kus po kuse

Potom:
- finalny brand pass
- logo a favicon
- launch QA
- hosting deployment

## 7. Co novy Codex nevie automaticky

Novy Codex ucet alebo nova AI relacia si automaticky neprenesie stare chat vlakna.

Vie sa vsak velmi rychlo zorientovat, ak:
- cita tento master subor
- cita AGENTS.md
- cita web a admin status dokumenty

Prakticky ciel je:
- nie automaticky prenos chatu
- ale automaticky onboarding zo suborov v repozitari
