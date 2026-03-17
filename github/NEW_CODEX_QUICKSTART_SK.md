# Novy Codex - rychly start

Tento navod je pre pripad, ked:
- otvoris novy Codex ucet
- otvoris novu AI relaciu
- znovu pripojis GitHub repo

## Dolezite obmedzenie

Codex si automaticky neprenesie stare chat vlakna.

Da sa vsak velmi dobre obnovit kontinuita cez subory v repozitari.

## Najjednoduchsi prakticky postup

1. Otvor repo.
2. Vytvor 2 vlakna:
- `web vlakno`
- `admin vlakno`
3. Do web vlakna vloz prompt z:
- [WEB_THREAD_BOOT_PROMPT_SK.md](C:/data/praca/webova_stranka/github/WEB_THREAD_BOOT_PROMPT_SK.md)
4. Do admin vlakna vloz prompt z:
- [ADMIN_THREAD_BOOT_PROMPT_SK.md](C:/data/praca/webova_stranka/github/ADMIN_THREAD_BOOT_PROMPT_SK.md)

## Co tym ziskas

Web vlakno bude vediet:
- co je ciel projektu
- co je aktualna launch priorita
- co ma riesit a co nema riesit

Admin vlakno bude vediet:
- co je jeho technicka uloha
- z coho ma citat prioritu
- ze nema robit finalne produktove rozhodnutia za web vlakno

## Co sa neda uplne zautomatizovat

Automaticke vytvorenie dvoch vlakien len po pripojeni repa bez tvojej akcie nie je spolahlivo k dispozicii.

Najblizsie k automatizacii je:
- mat onboarding zapisany v repozitari
- mat hotove startovacie prompty
- pri novom spusteni ich len vlozit do prislusnych dvoch vlakien

## Minimalna verzia

Ak nechces hladat subory, pouzi len tuto jednu vetu:

`Pracuj podla AGENTS.md a PROJECT_MASTER_STATUS_SK.md a spravaj sa bud ako web vlakno, alebo ako admin vlakno podla tejto konverzacie.`
