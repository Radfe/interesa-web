# patch-002 – import Dognet máp /go/<kod>

## Obsah patchu
- `inc/go-links.php` – komplet pregenerované z `affiliate_simple_edit.csv`.
  - Placeholdery `https://REPLACE...` majú bezpečnú náhradu: `https://interesa.sk/affiliate-missing.php?code=...`

## Nasadenie
1. Rozbaľ ZIP **patch-002.zip**.
2. Nahraj súbor **inc/go-links.php** do koreňa webu (prepíš existujúci súbor).
3. Otestuj náhodné kódy, napr.:
   - `/go/srvatkovy-protein-vs-izolat-vs-hydro-gymbeam`
   - `/go/najlepsi-protein-na-chudnutie-wpc-vs-wpi-aktin`
   - `/go/veganske-proteiny-top-gymbeam` (nový článok z patch-001)
4. Ak máš už finálne Dognet deeplinky, aktualizuj `affiliate_simple_edit.csv` a ja spravím nový patch (alebo pošli priamo zoznam).

## Rollback
- Prekopíruj pôvodný **inc/go-links.php** zo zálohy a prepíš ho naspäť.

## Poznámka
- `go.php` už nepodlieha zmene; používa funkciu `interessa_go_links()` a bude okamžite používať nové mapovanie.
