# Admin quickstart (SK)

Tento admin nie je WordPress. Je to lahka interna vrstva nad existujucim flat-file webom.

Co admin robi:
- uprava clankov bez rucneho editovania HTML/PHP
- sprava reusable produktov
- sprava affiliate /go/ odkazov
- hero workflow pre clanky
- packshot workflow pre produkty
- porovnania a money-page scaffoldy

## Kde co najdes
- `/admin?section=articles` = clanky
- `/admin?section=products` = reusable produkty
- `/admin?section=images` = hero briefy a image workflow
- `/admin?section=affiliates` = Dognet /go/ odkazy
- `/admin?section=tools` = import a export
- `/admin?section=help` = rychla pomoc priamo v admine

## Bezny odporucany workflow
1. otvor clanok v `Clanky`
2. uprav titulok, intro, sekcie a SEO meta
3. pridaj reusable produkty
4. pouzi comparison helper alebo `Money-page scaffold`
5. otvor `Image briefy` a dopln hero obrazok
6. dopln packshoty produktov, ak chyba realny obrazok
7. dopln alebo skontroluj Dognet deeplinky v `Affiliate odkazy`
8. otvor live stranku a skontroluj frontend

## Ako upravit clanok
1. otvor `/admin?section=articles`
2. vyber clanok zo selectu hore
3. uprav:
   - Nazov
   - Intro
   - Meta title
   - Meta description
   - Sekcie
4. ak je to money page, pouzi reusable produkty pod editorom
5. uloz clanok
6. klikni `Live clanok`

## Ako spravit money page rychlejsie
1. vyber clanok typu comparison alebo review
2. v reusable produktoch oznac hotove produkty
3. klikni `Money-page scaffold`
4. ak chces rychle porovnanie iba z najlepsich produktov, pouzi:
   - `Len money-page ready`
   - `Len karty ready`
   - `Top 3 ready shortlist`
5. dolad text, poradie a finalne CTA

## Ako doplnit hero obrazok
1. otvor `/admin?section=images&slug=SLUG`
2. skopiruj:
   - prompt
   - filename
   - target path
3. vytvor obrazok v Canve alebo AI nastroji
4. exportuj ako WebP
5. nahraj ho cez admin
6. otvor live clanok a skontroluj hero

Odporucania pre hero:
- bez textu v obrazku
- editorial / health / fitness styl
- ciste svetle pozadie
- konzistentny look s ostatnymi clankami

## Ako doplnit packshot produktu
1. otvor `/admin?section=products`
2. vyber produkt
3. skontroluj blok packshotu
4. ak chyba lokalny packshot, nahraj ho cez upload
5. ak produkt este nema obrazok, pouzi canonical path, ktoru admin ukaze
6. vrat sa na live clanok a skontroluj kartu produktu

## Ako doplnit Dognet link
1. otvor `/admin?section=affiliates`
2. vyber existujuci kod alebo vytvor novy
3. vloz finalny Dognet deeplink
4. uloz
5. otestuj `/go/...` link alebo CTA na live clanku

Dolezite:
- na webe maju zostat ciste interne `/go/` odkazy
- finalny Dognet deeplink sa spravuje centralne v admine

## Ako zistit, co este chyba
Pouzi queue a backlogy v admine:
- v `Products` je queue nedokoncenych produktov
- v `Images` je backlog hero obrazkov a packshot medzier
- v `Articles` je workflow odporucanych produktov a pripravenosti money page

## Co kontrolovat pred publikovanim
- clanok ma titulok, intro a meta description
- hero obrazok je finalny WebP
- odporucane produkty maju affiliate a packshot
- CTA vedu na spravne `/go/` linky
- live stranka vyzera dobre na fronte