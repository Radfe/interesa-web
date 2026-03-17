# Web -> Admin handoff: logo, favicon a Canva workflow

Datum:
- 2026-03-17

## Co uz v admine existuje

Admin uz dnes ma sekciu:
- [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php):5332

V nej uz su 3 logicke casti:
- hlavne logo
- ikonka + favicon bundle
- OG obrazok pre zdielanie

Uz pritomne akcne body:
- upload hlavneho loga:
  - [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php):1779
- upload icon bundle:
  - [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php):1788
- upload OG obrazka:
  - [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php):1793

Uz pritomne prompty pre Canvu:
- [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php):2287

Uz pritomna automaticka priprava malych ikon z jedneho zdrojoveho obrazka:
- [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php):6908
- [public/admin/index.php](C:/data/praca/webova_stranka/github/public/admin/index.php):6919

## Zaver

Netreba stavat novy brand system od nuly.

Treba:
1. overit, preco upload loga realne nefunguje v pouzivatelskom toku
2. ak pada, opravit existujuci upload
3. zachovat jednoduchy workflow:
   - skopirovat prompt do Canvy
   - vygenerovat logo / icon / OG
   - nahrat do adminu
   - admin pripravi male verzie ikon automaticky

## Co ma admin vlakno konkretne preverit

### Hlavne logo
- ci `save_brand_logo` naozaj ulozi subor na spravne miesto
- ci sa po ulozeni zmeni preview `Aktualne logo`
- ci sa logo zobrazi aj na verejnom webe

### Ikonka a favicon
- ci `save_brand_icon_bundle` realne ulozi:
  - `logo-icon`
  - `favicon-32`
  - `favicon-48`
  - `apple-touch-icon`
- ci sa po ulozeni zmeni preview v sekcii `Aktualne male verzie`

### OG obrazok
- ci `save_brand_og_default` ulozi defaultny social share asset
- ci sa to premietne do verejnych OG tagov

## Co z Canvy Pro dava zmysel

Canvu Pro ma zmysel pouzit pre:
- horizontalne logo
- stvorcovy icon source pre favicon bundle
- OG default obrazok
- category hero a category thumb obrazky
- article hero obrazky pre top a casto listovane clanky

## Co zatial nema zmysel

Zatial nema zmysel stavat v admine dalsie miesta na nahodne dekorativne grafiky po stranke.

Nema to teraz launch prioritu.

Najvyssia hodnota je:
- brand identita
- OG assety
- article a category obrazky

Nie:
- genericke „grafiky na stranku navyse“
- nahodne ilustracie bez jasneho slotu na webe

## Odporucany admin krok

1. najprv opravit alebo potvrdit funkcny upload loga
2. potom potvrdit funkcny upload icon bundle
3. potom potvrdit funkcny upload OG obrazka
4. potom sa vratit k doplnaniu article obrazkov a produktov
