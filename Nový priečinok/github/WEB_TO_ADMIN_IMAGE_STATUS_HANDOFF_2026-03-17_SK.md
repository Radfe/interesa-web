# Web -> Admin handoff: stav obrazkov clankov

Datum:
- 2026-03-17

Kontext:
- Pouzivatel si vsimol, ze vela kariet clankov na webe posobi rovnako.
- Pri audite sa potvrdilo, ze problem nie je len vizualny dojem, ale aj sposob, akym admin reportuje stav obrazkov.

## Co je potvrdene

- admin sekcia `Obrazky` realne existuje a workflow na doplnenie obrazkov pre clanky uz je v admine pritomny
- problem nie je v tom, ze by admin obrazky pre clanky nevedel riesit
- problem je v tom, ze sucasne pocty v admine posobia zavadzajuco

## Dolezite zistenie

Sposob pocitania v admine je teraz:
- `interessa_admin_image_queue(...)` vola `interessa_article_image_meta($slug, 'hero', true)`
- nasledne sa hodnoti len to, ci vysledne `src` konci na `.webp`

To znamena:
- ak clanok nema vlastny article obrazok
- ale padne na fallback obrazok temy
- admin ho moze povazovat za `ready`, hoci nema vlastny obrazok clanku

Kodove miesta:
- [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php):1064
- [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php):2434
- [public/inc/media.php](C:/data/praca/webova_stranka/github/public/inc/media.php):326
- [public/inc/media.php](C:/data/praca/webova_stranka/github/public/inc/media.php):373

## Realny stav, ktory z toho plynie

- HTML clankov v [public/content/articles](C:/data/praca/webova_stranka/github/public/content/articles): `272`
- zaznamov v article media registry [public/content/media/articles.php](C:/data/praca/webova_stranka/github/public/content/media/articles.php): `34`
- zaznamov v category media registry [public/content/media/categories.php](C:/data/praca/webova_stranka/github/public/content/media/categories.php): `12`
- realnych article hero `.webp` suborov v [public/assets/img/articles/heroes](C:/data/praca/webova_stranka/github/public/assets/img/articles/heroes): `30`

Zaver:
- vacsina clankov stale nema vlastny article obrazok
- web si pri mnohych z nich pomaha fallbackom z temy
- to je presne dovod, preco category listingy predtym posobili ako duplikaty

## Co admin potrebuje upravit

Admin ma v sekcii `Obrazky` jasne oddelit tieto 3 stavy:

1. `Vlastny obrazok clanku`
- clanok ma vlastny hero obrazok v article registry

2. `Len fallback temy`
- clanok nema vlastny article obrazok
- ale zobrazi sa cez category fallback

3. `Naozaj chyba`
- clanok nema vlastny article obrazok
- a nie je ani kvalitny fallback

## Co ma byt v admine zrozumitelnejsie

- pocitadlo `Clanky bez hlavneho obrazka` nema znamenat len `nema finalne webp`
- ma znamenat `nema vlastny article hero obrazok`
- pri kazdom clanku ma byt vidno, ci ide o:
  - `article`
  - `category-fallback`
  - `placeholder`

## Priorita pre admin vlakno

Nie je treba vymyslat novy obrazkovy system.

Treba:
- zrozumitelnejsie pocitat stav obrazkov
- prestat miesat fallback temy s hotovym obrazkom clanku
- dat pouzivatelovi jasny zoznam clankov, ktore stale potrebuju vlastny obrazok

## Co z toho plynie pre pouzivatela

- pouzivatel nema pokazenu admin aplikaciu v zmysle, ze by nevedela obrazky doplnat
- ale bol zavadzany tym, ze fallback z temy vyzeral ako hotovy obrazok clanku
- preto treba teraz doplnat realne article obrazky najma pre clanky, ktore sa casto ukazuju v kategoriách a listingoch
