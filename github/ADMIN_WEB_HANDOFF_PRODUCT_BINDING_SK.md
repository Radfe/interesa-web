# Handoff pre web vlakno - produkty v clankoch z adminu

## Co treba zmenit

Verejny web ma pri money clankoch prestat brat produkty len zo stareho pevne napisaneho suboru a ma vediet pouzit aj admin data z override vrstvy.

Najma treba, aby web vedel citat:
- `recommended_products`
- `comparison`
- `product_plan`

z article override dat.

## V ktorych suboroch

- [public/inc/article-commerce.php](C:/data/praca/webova_stranka/github/public/inc/article-commerce.php)
- pripadne [public/article.php](C:/data/praca/webova_stranka/github/public/article.php), ak sa ukaze, ze treba doplnit len preposlanie dat dalej

## Preco

Dnes admin uz vie pripravit:
- poradie produktov v clanku
- ci produkt patri do porovnania
- ci patri do odporucanych produktov
- ktory produkt je hlavna odporucana volba

Ale verejny web stale pri casti money clankov cita hlavne stary pevny zoznam z `article-commerce.php`.

To znamena:
- admin data sa daju ulozit
- ale na verejnom webe sa este nemusia prejavit automaticky

## Odporucany smer

1. Najprv skusit, ci pre dany clanok existuje admin override s:
   - `recommended_products`
   - `comparison`
   - `product_plan`
2. Ak existuje, pouzit admin data ako prioritu.
3. Ak neexistuje, ponechat doterajsi fallback na pevne napisane data.

## Prve clanky na otestovanie

- `najlepsie-proteiny-2026`
- `kreatin-porovnanie`
- `doplnky-vyzivy`

## Poznamka

Admin vrstva uz vie tieto data pripravit.
Web vrstva ich teraz potrebuje len zacat citat ako prvy zdroj namiesto toho, aby sa spoliehala len na starsi pevny zoznam.
