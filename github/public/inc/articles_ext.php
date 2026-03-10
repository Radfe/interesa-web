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
    'Srvátkový proteín vs. izolát vs. hydrolyzát – čo sa oplatí?',
    'WPC, WPI a hydrolyzát v praxi: rozdiely v laktóze, cene, použití a tom, kedy má zmysel platiť za čistejšiu formu.',
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
    'Imunita – ktoré prírodné látky majú zmysel?',
    'D3, zinok, vitamín C, betaglukány a probiotiká: čo má pri podpore imunity reálny zmysel a čo je skôr marketing.',
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
    'Kolagén – recenzia a výber podľa typu I, II a III',
    'Ako vybrať kolagén na kĺby, pokožku a vlasy: rozdiel medzi typmi I, II a III, dávkovanie aj praktické chyby pri kúpe.',
    'klby-koza'
  ],
  'vitamin-d3' => [
    'Vitamín D3 – dávkovanie, formy a praktický výber',
    'Koľko vitamínu D3 býva praktické, či ho kombinovať s K2 a akú formu zvoliť pri bežnej suplementácii.',
    'mineraly'
  ],
];