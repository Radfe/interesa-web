# Obrázky a ikony

Web teraz používa jednotný workflow pre hero obrázky článkov.

## Hero obrázky článkov
- cieľový priečinok: `public/assets/img/articles/heroes/`
- finálny formát: `webp`
- dočasný fallback: `svg` generovaný automaticky pre každý článok
- názov súboru: slug článku, napr. `kolagen-recenzia.webp`
- odporúčané rozlíšenie: približne `1200x800`
- cieľová veľkosť: do `350 KB`
- bez textu v obrázku
- jednotný štýl: svetlé minimalistické pozadie, jemné pastelové farby, moderný health/fitness look

## Ako to funguje
1. Web najprv hľadá `public/assets/img/articles/heroes/<slug>.webp`.
2. Ak WebP ešte neexistuje, použije automaticky vygenerovaný `svg` fallback pre daný článok.
3. Keď nahráš finálny `webp`, začne sa používať automaticky bez ďalšej úpravy kódu.

## Alt text
- alt text sa berie automaticky z názvu článku
- netreba ho ručne dopisovať do šablón

## Canva workflow
- zadania a prompty sú v `docs/article-hero-shotlist-sk.md` a `docs/article-hero-shotlist.csv`
- po exporte WebP len nahraj súbor do `public/assets/img/articles/heroes/` a obnov stránku

## Ikony
- hlavné menu ostáva textové
- staré PNG/SVG ikonky sa berú len ako legacy assety, nie ako nový smer vizuálu