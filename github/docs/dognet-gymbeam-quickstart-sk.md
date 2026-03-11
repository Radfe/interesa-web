# Dognet GymBeam Quickstart (SK)

Tento subor je pripraveny pre uplne jednoduchy workflow bez rucneho hladania kodov.

## Kde zacat

Pouzi subor:
- `public/storage/dognet/gymbeam-first-batch-template.csv`

## Co mas urobit

1. Prihlas sa do Dognetu.
2. Otvor kampan GymBeam.
3. Otvor generator deeplinkov.
4. Vezmi `product_url` z CSV a vloz ho do Dognet generatora.
5. Vysledny Dognet odkaz vloz do stlpca `deeplink_url`.
6. Opakuj pre vsetky riadky, ktore chces spustit.

## Ked bude CSV vyplnene

Spusti:

```bash
php public/tools/import-dognet-links.php public/storage/dognet/gymbeam-first-batch-template.csv gymbeam
```

Vystup toolu skopiruj do:
- `public/content/affiliates/links_overrides.php`

## Co je uz pripravene v projekte

- ciste interne route `/go/...`
- starter produktove URL pre GymBeam
- starter produktove obrazky z feedu
- prechodny stav `link_type = product`, kym nie su doplnene realne Dognet deeplinky

## Dolezite

Kym v `links_overrides.php` ostava `link_type = product`, web sa netvari, ze ide o finalny affiliate deeplink.
Po doplneni realnych Dognet deeplinkov ma byt `link_type = affiliate`.
## Najpohodlnejsia poloautomatika

Otvor helper:
- `http://127.0.0.1:5000/dognet-helper`
- alebo spusti `open-dognet-helper.vbs`

Workflow:
1. helper ti ukaze dalsi produkt
2. kliknes `Kopirovat URL + otvorit Dognet`
3. v Dognete vlozis URL a das vygenerovat deeplink
4. deeplink vlozis spat do helpera
5. helper ho ulozi do CSV aj do `links_overrides.php`