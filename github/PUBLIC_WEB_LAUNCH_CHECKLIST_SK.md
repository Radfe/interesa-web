# Public Web Launch Checklist - Interesa.sk

Tento dokument je urceny len pre verejny web projektu Interesa.sk.

## Aktualne poradie riesenia

1. Opravit a overit sekciu `Logo a ikonka` v admine.
2. Nahrat finalne brand assety:
   - hlavne logo
   - ikonku / favicon bundle
   - OG obrazok
3. Dokazat jeden cisty `article-first` pilot pre:
   - `najlepsie-proteiny-2026`
4. Ziskat prve technicky hotove kandidatne produkty pre prve 3 prioritne clanky.
5. Spravit finalny vyber produktov vo web vlakne.
6. Napojit realne produktove bloky, obrazky a kliky do obchodov na verejnom webe.
7. Dorobit posledny pass na homepage, kategoriach a top money clankoch.
8. Spravit launch QA.
9. Az potom hosting deployment.

## Brand upload: technicke pravidla

- Ak je sekcia `Logo a ikonka` pokazena alebo neuklada nove assety, je to aktualne rozumne dat na prve miesto.
- Bez funkcnej brand sekcie sa zbytocne tocia dalsie pokusy dokola a web stale zostava na starom logu.
- `Hlavne logo` sa ma nahravat ako:
  - `logo-full.svg`
  - fallback: `logo-full.png`
- `Ikonka stranky` sa nema nahravat ako SVG.
- Zdroj pre favicon a male ikony ma byt:
  - `logo-icon.png`
  - alebo iny stvorcovy PNG/JPG/WEBP zdroj
- `Obrazok pri zdielani` odporucany upload:
  - `og-default.png`
- Frontend preferuje odvodeny asset `logo-full-web`, ak sa po nahrati korektne vytvori.

## Co je hotove na webe

- Desktop menu funguje stabilne a uz neschovava obsah pri beznom pouziti.
- Verejne obrazky a hlavne assety su opat nacitane a zobrazovane korektne.
- Homepage ma jasnejsi funnel:
  - hero
  - rychly start
  - hlavne temy
  - clanky na prvy klik
  - porovnania
  - trust vrstvu
- Kategorie funguju viac ako landing pages a menej ako obycajny archiv.
- Clanky maju silnejsi decision-first flow:
  - rychly prehlad
  - prehladnu tabulku produktov
  - odporucane produkty
  - trust box
  - FAQ
  - suvisiace clanky
- Sidebar, latest a related uz tlacia viac na uzitocny obsah a menej na interny system.
- Verejny wording bol vycisteny od vacsiny internych fraz a technickych oznaceni.
- Header a footer posobia cistejsie a brandovo konzistentnejsie.
- Meta vrstva je lepsie pripravena pre share:
  - canonical
  - og tags
  - twitter image
  - favicon linky
  - apple-touch-icon
- Legacy category vrstva bola odstavena cez redirect na novu verejnu kategoriu.

## Co este ma zmysel doladit vo web vlakne

- Dorobit posledny mobilny UX audit:
  - header
  - homepage sekcie
  - theme cards
  - article quick actions
  - CTA v clankoch
- Este raz prejst top money clanky ako clovek:
  - najlepsie-proteiny-2026
  - doplnky-vyzivy
  - kreatin-porovnanie
  - veganske-proteiny-top-vyber-2026
- Doladit posledne mikrocopy tam, kde text este posobi prilis systemovo.
- Znovu skontrolovat homepage po realnych grafikach z admin vrstvy.
- Po dodani novych brand assetov z adminu dorobit finalnu brand prezentaciu vo fronte.

## Co patri do admin vlakna

- Logo refresh:
  - logo horizontal
  - logo icon
- Favicon a icon sady:
  - favicon-32
  - favicon-48
  - apple-touch-icon
- OG assety:
  - default OG
  - homepage OG
  - article OG template
  - category OG template
- Tematicke grafiky pre hlavne temy.
- Upload workflow pre brand assety.
- Canva prompt kniznica a copy workflow.
- Aktivacia a prepinanie brand assetov v admine.

## Pred publikovanim este skontrolovat

- Homepage:
  - hero text
  - CTA
  - hlavne temy
  - top clanky
  - porovnania
- Kategoria:
  - proteiny
  - vyziva
  - mineraly
  - sila
  - klby-koza
  - imunita
- Top money clanky:
  - prehladna tabulka funguje
  - CTA vedu spravne
  - obrazky su korektne
  - trust texty posobia prirodzene
- Search:
  - filtre
  - karty
  - prazdne stavy
- Header a footer:
  - logo
  - favicon
  - mobilne menu
  - navigacia

## Stav brand vrstvy

- Aktualne logo je pouzitelne, ale nie finalne.
- Favicon a OG vrstva su pripravene technicky, ale stale potrebuju kvalitne finalne assety.
- Web uz vie finalne brand assety prijat bez velkeho rozbitia layoutu.

## Prakticky zaver

Verejny web je po tychto upravach velmi blizko launch-ready stavu.

Najvacsi zostavajuci rozdiel medzi "funkcny web" a "spickovy web" uz nie je v strukture alebo routingu, ale v:

- finalnej grafike
- logu
- favicone
- OG assetoch
- tematickych vizualoch

To je dalsia prirodzena faza, ktoru treba doriesit v admin vlakne a potom uz len jemne doladit vo verejnom fronte.
