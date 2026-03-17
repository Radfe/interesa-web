# Interesa.sk repository instructions

Tento repozitar ma dva hlavne pracovne smery:
- `web vlakno`
- `admin vlakno`

Ak sa novy Codex alebo nova AI relacia nacita od nuly, ma sa najprv zorientovat cez subory v repozitari, nie cez historiu chatu.

## Povinne onboarding citanie

Precitaj v tomto poradi:
1. [PROJECT_MASTER_STATUS_SK.md](C:/data/praca/webova_stranka/github/PROJECT_MASTER_STATUS_SK.md)
2. [NEW_CODEX_QUICKSTART_SK.md](C:/data/praca/webova_stranka/github/NEW_CODEX_QUICKSTART_SK.md)
3. [COLLABORATION_PROTOCOL_SK.md](C:/data/praca/webova_stranka/github/COLLABORATION_PROTOCOL_SK.md)
4. [WEB_IMPLEMENTATION_STATUS_SK.md](C:/data/praca/webova_stranka/github/WEB_IMPLEMENTATION_STATUS_SK.md)
5. [ADMIN_IMPLEMENTATION_STATUS_SK.md](C:/data/praca/webova_stranka/github/ADMIN_IMPLEMENTATION_STATUS_SK.md)

Potom otvor len tie strategicke dokumenty, ktore su potrebne pre aktualnu ulohu.

## Rozdelenie rozhodovania

### Web vlakno
Rozhoduje:
- ktore clanky su priorita
- ktore kampane a obchody su priorita
- ake typy produktov patria do clankov
- ktore konkretne produkty budu schvalene na verejny web
- co bude hlavna volba, vyhodna volba, ina moznost a porovnanie

Neriesi:
- technicky importer feedov
- generovanie Dognet klikov
- admin workflow a internu spravu assetov

### Admin vlakno
Rozhoduje:
- ako dostat kandidatov do systemu
- ako pripravit klik do obchodu
- ako ulozit produktovy obrazok
- ako produkt alebo kandidata priradit ku clanku
- ako schvalit produkt pre verejny web

Neriesi znovu:
- obsahovu strategiu
- kampanovu strategiu
- finalny vyber produktov pre verejny web

## Povinne pravidlo synchronizacie

Pred vacsim krokom treba precitat:
- [PROJECT_MASTER_STATUS_SK.md](C:/data/praca/webova_stranka/github/PROJECT_MASTER_STATUS_SK.md)
- [WEB_IMPLEMENTATION_STATUS_SK.md](C:/data/praca/webova_stranka/github/WEB_IMPLEMENTATION_STATUS_SK.md)
- [ADMIN_IMPLEMENTATION_STATUS_SK.md](C:/data/praca/webova_stranka/github/ADMIN_IMPLEMENTATION_STATUS_SK.md)

Ak sa zmeni smer webu, zapisat to do web statusu.
Ak sa zmeni admin workflow, zapisat to do admin statusu.

## Aktualna launch priorita

Teraz nie je priorita masovo pridavat dalsie clanky.

Teraz je priorita:
1. prvy cisty batch kandidatov pre prve 3 clanky
2. finalny vyber produktov vo web vlakne
3. finalne logo, favicon a OG assety
4. launch QA
5. hosting deployment

## Prve 3 prioritne clanky pre produkty

- `najlepsie-proteiny-2026`
- `kreatin-porovnanie`
- `doplnky-vyzivy`

Pravidla pre ne su v:
- [FIRST_3_ARTICLES_PRODUCT_SHORTLIST_SK.md](C:/data/praca/webova_stranka/github/FIRST_3_ARTICLES_PRODUCT_SHORTLIST_SK.md)
- [ADMIN_IMPORT_INPUT_GUIDE_SK.md](C:/data/praca/webova_stranka/github/ADMIN_IMPORT_INPUT_GUIDE_SK.md)
- [WEB_TO_ADMIN_IMPORT_HANDOFF_2026-03-17_SK.md](C:/data/praca/webova_stranka/github/WEB_TO_ADMIN_IMPORT_HANDOFF_2026-03-17_SK.md)

## Dolezita zasada pri produktoch

Prvy import = kandidati.

Neznamena to finalny vyber pre web.
Pri prvom importe sa nemaju robit finalne redakcne rozhodnutia, ak to nie je vyslovene potvrdene vo web dokumentoch.
