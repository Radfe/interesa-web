<?php
declare(strict_types=1);

/**
 * Obrázky:
 *  - buď lokálne:  /assets/img/products/mg-bisglycinate.webp
 *  - alebo externé: https://.../obrazok.jpg  (hotlinking)
 *
 * CTA:
 *  - 'code' = affiliate kód (ak ho máš v CSV/ go-links)
 *  - 'url'  = fallback (priame URL na produkt), použije sa ak affiliate kód zatiaľ nie je
 */

$TOP_PRODUCTS = [
  [
    'name'     => 'Magnézium bisglycinát',
    'subtitle' => 'Vysoká tolerancia',
    'rating'   => 4.8,
    'img'      => '/assets/img/products/mg-bisglycinate.webp', // nahraj sem alebo použi externú URL
    'code'     => 'horcik-ktory-je-najlepsi-a-preco-aktin',
    'url'      => 'https://www.aktin.sk/'  // dočasný fallback – nahraď priamou URL produktu
  ],
  [
    'name'     => 'Magnézium citrát',
    'subtitle' => 'Univerzálne použitie',
    'rating'   => 4.6,
    'img'      => '/assets/img/products/mg-citrat.webp',
    'code'     => 'horcik-ktory-je-najlepsi-a-preco-gymbeam',
    'url'      => 'https://gymbeam.sk/'    // dočasný fallback – nahraď priamou URL produktu
  ],
  [
    'name'     => 'Magnézium malát',
    'subtitle' => 'Energia a tréning',
    'rating'   => 4.5,
    'img'      => '/assets/img/products/mg-malat.webp',
    'code'     => 'horcik-ktory-je-najlepsi-a-preco-myprotein',
    'url'      => 'https://www.myprotein.sk/' // dočasný fallback
  ],
];
