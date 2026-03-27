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

## Odporucane wrappery

Bezny prakticky workflow je teraz:

1. `deploy_changed.cmd`
- deploy vsetkych zmenenych suborov v `public`

2. `deploy_explicit.cmd`
- deploy len suborov zo zoznamu v:
  - `.deploy_state/deploy_explicit_files.txt`
- alebo z vlastneho textoveho suboru:
  - `deploy_explicit.cmd moja-listina.txt`

3. `rollback_last.cmd`
- rollback posledneho deployu

Volitelny hotovy preset pre caste admin/security zmeny:
- `deploy_admin_security.cmd`

Kazdy deploy wrapper vie aj preview:
- `deploy_changed.cmd preview`
- `deploy_explicit.cmd .deploy_state\deploy_explicit_files.txt preview`
- `deploy_admin_security.cmd preview`

## Ako spustit deploy

Odporucany postup:

1. Sprav lokalny test.
2. Sprav commit alebo si aspon skontroluj `git diff`.
3. Spusti:

```cmd
deploy_changed.cmd
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
5. Spusti `deploy_changed.cmd`.
6. Po deployi otestuj produkciu.

## Explicit deploy bez PowerShell poli

Ak chces deploynut len par konkretnych suborov:

1. otvor `.deploy_state/deploy_explicit_files.txt`
2. napis do neho subory, napriklad:

```txt
public/admin/index.php
public/inc/admin-auth.php
public/router.php
```

3. spusti:

```cmd
deploy_explicit.cmd
```

To je vsetko. Ziadne multiline `@()` pole netreba.

## Preview a self-check

Pred realnym deployom mozes spustit preview.

Wrapper najprv vypise:
- ci existuje `scripts/deploy_config.ps1`
- ci existuje WinSCP DLL a EXE
- ci existuje local `public`
- aky je nastaveny remote root
- ci je doplneny FTP host, user a password

Potom vypise mapovanie:
- local path
- remote path

Ak nie je co deploynut, povie to ludsky a skonci bez chyby.

## Deploy log

Kazdy deploy a rollback teraz zapisuje log do:
- `.deploy_logs/`

V logu najdes:
- kedy sa deploy spustil
- aky rezim sa pouzil
- ktore subory sa mapovali
- ci deploy dopadol uspesne
- aky backup/manifest bol pouzity

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
- `.deploy_logs` drzi prakticku historiu deployov
