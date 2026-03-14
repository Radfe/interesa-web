<?php
declare(strict_types=1);
/**
 * Safe extension of the article registry loaded after articles.php.
 * Item format: 'slug' => ['Title', 'Perex', 'category-slug']
 */
if (!isset($ART) || !is_array($ART)) { $ART = []; }

$ART += [
  'doplnky-vyzivy' => [
    'Doplnky vyzivy - top vyber 2026',
    'Najpouzivanejsie doplnky: kreatin, D3+K2, magnezium, kolagen a omega-3. Vyber podla ciela, davky a kvality.',
    'vyziva',
  ],
  'horcik-ktory-je-najlepsi-a-preco' => [
    'Horcik - ktory je najlepsi a preco?',
    'Porovnali sme rozne formy Mg a ich vstrebatelnost: bisglycinat, citrat, malat a oxid.',
    'mineraly',
  ],
  'imunita-prirodne-latky-ktore-funguju' => [
    'Imunita - 5 prirodnych latok, ktore funguju',
    'Vedecky overene doplnky pre obranyschopnost: D3+K2, C, zinok, echinacea a betaglukany.',
    'imunita',
  ],
  'kolagen-recenzia' => [
    'Kolagen - recenzia a vyber (typ I/II/III)',
    'Co sledovat pri kolagene: forma, typy, gramaz a pridany vitamin C.',
    'klby-koza',
  ],
  'kreatin-porovnanie' => [
    'Kreatin - porovnanie a vyber (Creapure, monohydrat, HCl)',
    'Najlepsi pomer cena/vykon, davkovanie a nasycovanie - potrebne ci nie?',
    'sila',
  ],
  'najlepsie-proteiny-2025' => [
    'Najlepsie proteiny 2026 - prehlad a vyber podla ciela',
    'Ako si vybrat protein podla ciela: objem, redukcia, intolerancia a kazdodenne pouzitie.',
    'proteiny',
  ],
  'najlepsie-proteiny-2026' => [
    'Najlepsie proteiny 2026 - prehlad a vyber podla ciela',
    'Ako si vybrat protein podla ciela: objem, redukcia, intolerancia a kazdodenne pouzitie.',
    'proteiny',
  ],
  'proteiny-na-chudnutie' => [
    'Proteiny na chudnutie - co funguje?',
    'Kedy volit WPI alebo Hydro, ako davkovat a ktore prichute maju najmenej cukru.',
    'proteiny',
  ],
  'srvatkovy-protein-vs-izolat-vs-hydro' => [
    'Srvatkovy protein vs. izolat vs. hydro - co sa oplati?',
    'WPC vs. WPI vs. Hydro: rozdiely v laktoze, rychlosti vstrebavania, cene a pouziti.',
    'proteiny',
  ],
  'veganske-proteiny-top-vyber-2025' => [
    'Veganske proteiny - top vyber 2026',
    'Najlepsie rastlinne proteiny (hrach, ryza, soja, zmesi). Otestovane podla chuti, zlozenia a ceny.',
    'proteiny',
  ],
  'veganske-proteiny-top-vyber-2026' => [
    'Veganske proteiny - top vyber 2026',
    'Najlepsie rastlinne proteiny (hrach, ryza, soja, zmesi). Otestovane podla chuti, zlozenia a ceny.',
    'proteiny',
  ],
];
