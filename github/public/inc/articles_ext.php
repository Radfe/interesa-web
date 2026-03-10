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
  'protein-na-chudnutie' => [
    'Proteín na chudnutie – ako vyberať, dávkovať a kedy sa oplatí',
    'Praktický sprievodca proteínom pri chudnutí: ktorý typ zvoliť, koľko ho dávať a aké chyby robí väčšina ľudí v redukcii.',
    'proteiny'
  ],
  'clear-protein' => [
    'Clear protein – čo to je, pre koho sa hodí a aké má plusy a mínusy',
    'Ľahký proteínový nápoj s džúsovou textúrou: kedy clear protein dáva zmysel, aké má výhody a kedy je lepšia klasika.',
    'proteiny'
  ],
  'veganske-proteiny-top-vyber-2025' => [
    'Vegánske proteíny – top výber 2025',
    'Najlepšie rastlinné proteíny a zmesi podľa zloženia, chuti a použiteľnosti v praxi.',
    'proteiny'
  ],
  'horcik-ktory-je-najlepsi-a-preco' => [
    'Horčík – ktorý je najlepší a prečo?',
    'Bisglycinát, citrát, malát a oxid: ktorá forma horčíka sa hodí na spánok, trávenie alebo bežné denné užívanie.',
    'mineraly'
  ],
  'zinek' => [
    'Zinok – kedy ho dopĺňať, aké formy zvoliť a na čo si dať pozor',
    'Pikolinát, citrát, bisglycinát aj glukonát: kedy má zinok zmysel, aké dávky bývajú praktické a prečo netreba preháňať.',
    'mineraly'
  ],
  'vitamin-d3' => [
    'Vitamín D3 – dávkovanie, formy a praktický výber',
    'Koľko vitamínu D3 býva praktické, či ho kombinovať s K2 a akú formu zvoliť pri bežnej suplementácii.',
    'mineraly'
  ],
  'vitamin-c' => [
    'Vitamín C – dávky, formy a kedy má suplementácia zmysel',
    'Ako rozmýšľať nad vitamínom C bez preháňania: kedy stačí strava, kedy dáva zmysel doplnok a ktoré formy sú v praxi najrozumnejšie.',
    'imunita'
  ],
  'imunita-prirodne-latky-ktore-funguju' => [
    'Imunita – ktoré prírodné látky majú zmysel?',
    'D3, zinok, vitamín C, betaglukány a probiotiká: čo má pri podpore imunity reálny zmysel a čo je skôr marketing.',
    'imunita'
  ],
  'probiotika-ako-vybrat' => [
    'Probiotiká – ako vybrať správny produkt a na čo si dať pozor',
    'Kmene, CFU, skladovanie a dĺžka užívania: čo má pri výbere probiotík reálny význam a kde ľudia najčastejšie chybujú.',
    'probiotika-travenie'
  ],
  'probiotika-a-travenie' => [
    'Probiotiká a trávenie – kedy dávajú zmysel a čo od nich čakať',
    'Kedy majú probiotiká pri trávení reálny zmysel, ako ich skúšať rozumne a prečo samy osebe nevyriešia slabý režim.',
    'probiotika-travenie'
  ],
  'proteiny-na-chudnutie' => [
    'Proteíny na chudnutie – čo funguje?',
    'Kedy voliť WPI alebo hydro, ako dávkovať a na čo si dať pozor pri výbere proteínu do redukcie.',
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
  'pre-workout' => [
    'Pre-workout – čo to je, kedy sa hodí a na čo si dať pozor',
    'Praktický sprievodca predtréningovkou: čo od nej čakať, kedy má zmysel a ako sa nenechať nachytať na silné etikety bez obsahu.',
    'pre-workout'
  ],
  'pre-workout-ako-vybrat' => [
    'Ako vybrať pre-workout – stimulant, pumpa alebo non-stim',
    'Ako čítať zloženie pre-workoutu, kedy voliť stim verziu a kedy je rozumnejší non-stim bez zbytočného preplácania.',
    'pre-workout'
  ],
];
