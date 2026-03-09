<?php
declare(strict_types=1);
/**
 * Bezpečné rozšírenie zoznamu článkov $ART.
 * Položka: 'slug' => [ 'Title', 'Perex', 'category-slug' ]
 */
if (!isset($ART) || !is_array($ART)) { $ART = []; }

$ART += [
  'doplnky-vyzivy' => [
    'Doplnky výživy – top výber 2025',
    'Najpoužívanejšie doplnky: kreatín, D3+K2, magnézium, kolagén a omega-3. Výber podľa cieľa, dávky a kvality.',
    'vyziva'
  ],
  'srvatkovy-protein-vs-izolat-vs-hydro' => [
    'Srvátkový proteín vs. izolát vs. hydro – čo sa oplatí?',
    'WPC vs. WPI vs. Hydro: rozdiely v laktóze, rýchlosti vstrebávania, cene a použití.',
    'proteiny'
  ],
  'veganske-proteiny-top-vyber-2025' => [
    'Vegánske proteíny – top výber 2025',
    'Najlepšie rastlinné proteíny (hrach, ryža, sója, zmesi). Otestované podľa chuti, zloženia a ceny.',
    'proteiny'
  ],
  'horcik-ktory-je-najlepsi-a-preco' => [
    'Horčík – ktorý je najlepší a prečo?',
    'Porovnali sme rôzne formy Mg a ich vstrebateľnosť: bisglycinát, citrát, malát, oxid.',
    'mineraly'
  ],
  'imunita-prirodne-latky-ktore-funguju' => [
    'Imunita – 5 prírodných látok, ktoré fungujú',
    'Vedecky overené doplnky pre obranyschopnosť: D3+K2, C, zinok, echinacea, betaglukány.',
    'imunita'
  ],
  'proteiny-na-chudnutie' => [
    'Proteíny na chudnutie – čo funguje?',
    'Kedy voliť WPI/Hydro, ako dávkovať a ktoré príchute majú najmenej cukru.',
    'proteiny'
  ],
  'kreatin-porovnanie' => [
    'Kreatín – porovnanie a výber (Creapure, monohydrát, HCl)',
    'Najlepší pomer cena/výkon, dávkovanie a nasycovanie – potrebné či nie?',
    'sila'
  ],
  'kolagen-recenzia' => [
    'Kolagén – recenzie a výber (typ I/II/III)',
    'Čo sledovať pri kolagéne: forma, typy, gramáž a pridaný vitamín C.',
    'klby-koža'
  ],
];
