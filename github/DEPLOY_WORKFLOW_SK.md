# Deploy workflow - Interesa.sk

Tento workflow je jednoduchy poloautomaticky deploy pre WEDOS hosting na Windows.

## Co sa nasadzuje

- len obsah z `public`
- mapovanie je:
  - `public/admin/...` -> `/www/admin/...`
  - `public/inc/...` -> `/www/inc/...`
  - `public/.htaccess` -> `/www/.htaccess`
  - `public/assets/...` -> `/www/assets/...`
  - `public/content/...` -> `/www/content/...`
  - `public/robots.txt` -> `/www/robots.txt`
  - `public/sitemap.xml` -> `/www/sitemap.xml`

Prakticke pravidlo:
- lokalne `public/...` = produkcne `/www/...`

## Co sa nenasadzuje

- nic mimo `public`
- dokumenty v root priecinku
- lokalne helper subory
- `.codex-local`
- deploy config s heslom
- lokalne backupy v `.deploy_backups`

## Jednorazove lokalne nastavenie

1. Nainstaluj WinSCP.
2. Skopiruj `scripts/deploy_config.example.ps1` na `scripts/deploy_config.ps1`.
3. Dopln:
   - `HostName`
   - `UserName`
   - `Password`
   - pripadne fingerprint certifikatu
4. Skontroluj, ze:
   - `ProjectRoot` ukazuje na root repozitara
   - `LocalPublicRoot` ukazuje na `public`
   - `RemoteRoot` je `/www`

Do gitu sa commituje len example subor.
Realny `scripts/deploy_config.ps1` ma ostat lokalny.

## Ako spustit deploy

Odporucany postup:

1. Sprav lokalny test.
2. Sprav commit alebo si aspon skontroluj `git diff`.
3. Spusti:

```cmd
deploy_live.cmd
```

Script:
- zisti zmenene subory v `public`
- porovna ich proti poslednemu deploynutemu commitu alebo fallback `HEAD~1`
- vytvori backup aktualnych produkcnych verzii
- nahra nove verzie na server
- ulozi manifest do `.deploy_backups/<timestamp>/manifest.json`

## Ako spustit rollback

Ak sa po deployi nieco pokazi, spusti:

```cmd
rollback_last.cmd
```

Script:
- zoberie posledny backup z `.deploy_backups`
- nahra stare verzie suborov spat na server
- ak subor pred deployom na produkcii neexistoval, pri rollbacku ho odstrani

## Bezny workflow po Codex zmene

1. Codex alebo ty upravis subory v `public`.
2. Lokalny web otestuj cez `start-interesa.cmd`.
3. Skontroluj zmeny v gite.
4. Odporucany krok:
   - sprav commit pred deployom
5. Spusti `deploy_live.cmd`.
6. Po deployi otestuj produkciu.

## Co otestovat po deployi

- homepage
- `/admin`
- aspon jeden clanok
- aspon jednu kategoriu
- hlavne CSS a obrazky
- affiliate klik `/go/...`
- `robots.txt`
- `sitemap.xml`

Ak sa menil admin:
- prihlasenie
- otvorenie `Clanky`
- otvorenie `Produkty`

## Co robit pri chybe

1. Precitaj vystup v deploy okne.
2. Ak zlyhal upload, neopakuj slepo viac deployov bez kontroly configu.
3. Ak sa web po deployi rozbije:
   - spusti `rollback_last.cmd`
4. Potom skontroluj:
   - ci sedi FTP/FTPS host
   - ci sedi login
   - ci sedi `RemoteRoot = /www`
   - ci WinSCP vidi serverovy certifikat/fingerprint

## Git pravidlo

- odporucany commit pred deployom
- deploy sa robi len pre zmeny v `public`
- rollback je nezavisly od gitu, ide cez backupy

To znamena:
- git drzi historiu kodu
- `.deploy_backups` drzi rychly prakticky rollback produkcie
