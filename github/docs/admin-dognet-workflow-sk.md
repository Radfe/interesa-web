# Admin Dognet workflow (SK)

Toto je jednoduchy prakticky navod, ako cez admin doplnat Dognet deeplinky bez rucneho zasahu do kodu.

## Co admin robi za teba
Na verejnom webe nechava ciste interne odkazy typu:

- `/go/protein-na-chudnutie-gymbeam`
- `/go/kreatin-porovnanie-gymbeam`

Ty v admine doplnas len finalny Dognet deeplink. Web potom sam:
- drzi clean interny `/go/` odkaz na stranke
- presmeruje navstevnika na finalny Dognet ciel

## Kde to otvorit
V admine otvor:

- `Affiliate odkazy`

To je centralne miesto pre Dognet linky.

## Ked uz interny kod existuje
Postup:

1. otvor `Affiliate odkazy`
2. vyber existujuci kod
3. do pola `Cielova URL` vloz finalny Dognet deeplink
4. skontroluj:
   - `Merchant`
   - `Merchant slug`
   - `Product slug`
5. klikni `Ulozit affiliate odkaz`
6. klikni `Otvorit /go/ link`
7. skontroluj, ci presmerovanie ide na spravny produkt

## Ked interny kod este neexistuje
Postup:

1. otvor `Affiliate odkazy`
2. v bloku `Rychlo vytvorit novy affiliate kod` vypln:
   - `Kod`
   - `Merchant`
   - `Merchant slug`
   - `Product slug`
3. klikni `Vytvorit affiliate kod`
4. potom do hlavneho formulara vloz finalny Dognet deeplink
5. klikni `Ulozit affiliate odkaz`

## Ako ma vyzerat kod
Drz sa jednoducheho stylu:

- male pismena
- bez diakritiky
- slova oddelene pomlckami

Priklad:

- `kolagen-recenzia-gymbeam`
- `kreatin-porovnanie-aktin`

## Co znamena jednotlive pole
- `Kod` = interny clean identifikator pre `/go/...`
- `Cielova URL` = finalny Dognet deeplink
- `Merchant` = citatelny nazov obchodu
- `Merchant slug` = technicky slug obchodu, napr. `gymbeam`
- `Product slug` = reusable produkt v systeme, ak existuje
- `Typ linku`:
  - `affiliate` = finalny affiliate link
  - `product` = obycajny produktovy link bez affiliate trackingu

## Ako skontrolovat, ci je to spravne
Po ulozeni sprav 3 kontroly:

1. klikni `Otvorit /go/ link`
2. otvor live clanok a klikni CTA
3. skontroluj, ci ciel smeruje na spravny produkt

## Ked mas viac Dognet linkov naraz
Mas 2 moznosti:

### A. Rucne cez admin
Pouzi, ked ide o par kusov.

### B. CSV import
Pouzi, ked ide o vacsi batch.

Odporucany CSV format:

```csv
code,url,merchant,merchant_slug,product_slug,link_type
protein-na-chudnutie-gymbeam,https://go.dognet.com/...,GymBeam,gymbeam,gymbeam-true-whey,affiliate
kreatin-porovnanie-gymbeam,https://go.dognet.com/...,GymBeam,gymbeam,gymbeam-kreatin-monohydrat,affiliate
```

Potom v admine otvor:

- `Import / export`
- `Affiliate CSV import`

## Najjednoduchsi odporucany postup
Pri jednom clanku:

1. najprv text
2. potom produkty
3. potom obrazky
4. potom Dognet deeplinky
5. nakoniec kontrola live clanku

Takto sa najlahsie vyhnes chaosu.
