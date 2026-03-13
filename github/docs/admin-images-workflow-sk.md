# Admin obrazky workflow (SK)

Toto je prakticky navod pre beznu pracu s obrazkami na webe.

Pouzivaj dva typy obrazkov:
- **Hero obrazok clanku** = editorialny obrazok hore v clanku
- **Produktovy obrazok** = konkretny obrazok produktu v odporucaniach

## 1. Ako doplnit hero obrazok clanku
1. otvor `/admin?section=images&slug=SLUG`
2. skopiruj:
   - prompt
   - filename
   - target path
3. vytvor obrazok v Canve alebo AI nastroji
4. exportuj ho ako `WebP`
5. odporucany format:
   - `1200x800`
   - bez textu v obrazku
   - ciste svetle pozadie
   - editorial / health / fitness styl
6. nahraj obrazok cez admin
7. otvor live clanok a skontroluj vysledok

## 2. Ako doplnit produktovy obrazok
1. otvor `/admin?section=products`
2. vyber produkt
3. skontroluj sekciu obrazka produktu

Potom pouzi jednu z tychto dvoch ciest:

### A. Najrychlejsia cesta: Zrkadlit remote
Pouzi ju vtedy, ked produkt uz ma schvaleny remote obrazok od merchanta.

1. klikni `Zrkadlit remote`
2. admin stiahne obrazok do lokalnej canonical cesty
3. otvor live clanok a skontroluj kartu produktu

Toto je najrychlejsi sposob, ako zatvarat image gapy na money pages.

### B. Manualny upload
Pouzi ho vtedy, ked remote obrazok nie je k dispozicii alebo chces vlastny obrazok.

1. skopiruj `target path`
2. priprav finalny obrazok
3. nahraj ho cez `Nahrat obrazok`
4. otvor live clanok a skontroluj kartu produktu

## 3. Kedy ist cez Images a kedy cez Products
- `Images` = ked riesis konkretny clanok a jeho hero + chybajuce obrazky produktov
- `Products` = ked riesis konkretny reusable produkt bez ohladu na clanok

Prakticke pravidlo:
- ak pracujes na jednom clanku, chod cez `Images`
- ak cistis katalog produktov, chod cez `Products`

## 4. Odporucane poradie prace
1. uprav clanok
2. dopln hero obrazok
3. dopln produktove obrazky
4. dopln affiliate linky
5. otvor live stranku a skontroluj frontend

## 5. Co skontrolovat na live stranke
- hero obrazok sedi k teme clanku
- produktove karty maju realny obrazok tam, kde je dostupny
- fallbacky nevyzeraju rozbito
- CTA vedu na spravne `/go/` odkazy
- stranka je citatelna aj na mobile

## 6. Naming pravidla
- male pismena
- bez diakritiky
- slova oddelene pomlckami
- hero obrazky:
  - `slug-clanku.webp`
- produktove obrazky:
  - canonical cesta z adminu, typicky
  - `img/products/merchant-slug/product-slug/main.webp`

## 7. Rychle rozhodnutie
- vidis `Zrkadlit remote`?
  - klikni ho ako prvu volbu
- nevidis `Zrkadlit remote`?
  - pouzi manualny upload
