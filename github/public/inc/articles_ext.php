<?php
declare(strict_types=1);
/**
 * Bezpečné rozšírenie zoznamu článkov $ART.
 * Položka: 'slug' => [ 'Title', 'Perex', 'category-slug' ]
 */
if (!isset($ART) || !is_array($ART)) { $ART = []; }

$ART += [
  'doplnky-vyzivy' => [
    'Doplnky výživy – ktoré majú zmysel a ako vybrať rozumný základ',
    'Praktický sprievodca doplnkami výživy: čo má zmysel, ako skladať jednoduchý základ a ako odfiltrovať marketingový balast.',
    'vyziva'
  ],
  'najlepsie-proteiny-2025' => [
    'Najlepšie proteíny 2025 – ako vybrať správny typ podľa cieľa',
    'Praktický prehľad proteínov podľa cieľa: WPC, WPI, clear protein aj rastlinné varianty bez zbytočného marketingového balastu.',
    'proteiny'
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
    'Bisglycinát, citrát, malát a oxid: ktorá forma horčíka sa hodí na spánok, trávenie alebo bežné denné užívanie.',
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
    'Kreatín – porovnanie foriem, dávkovanie a výber',
    'Monohydrát, Creapure a HCl: čo sa oplatí, ako dávkovať kreatín a kedy nemá zmysel preplácať drahšie verzie.',
    'kreatin'
  ],
  'kolagen-recenzia' => [
    'Kolagén – recenzie a výber (typ I/II/III)',
    'Čo sledovať pri kolagéne: forma, typy, gramáž a pridaný vitamín C.',
    'klby-koza'
  ],
];