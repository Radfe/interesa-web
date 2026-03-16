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
  'aminokyseliny-bcaa-eaa' => [
    'Aminokyseliny BCAA a EAA - kedy maju zmysel',
    'Kedy aminokyseliny davaju realny zmysel, kedy ich nahradi protein a preco netreba kupovat kazdy treningovy doplnok.',
    'sila',
  ],
  'bcaa-vs-eaa' => [
    'BCAA vs EAA - ktore aminokyseliny maju vacsi zmysel',
    'Jednoduche porovnanie BCAA a EAA: co je medzi nimi rozdiel a kedy ma ich riesenie realny vyznam.',
    'sila',
  ],
  'ako-vybrat-probiotika-podla-problemu' => [
    'Ako vybrat probiotika podla problemu',
    'Kedy pri probiotikach riesit travenie, obdobie po antibiotikach alebo len beznu podporu mikrobiomu.',
    'imunita',
  ],
  'beta-glukan-ma-zmysel-na-imunitu' => [
    'Beta glukan - ma zmysel na imunitu?',
    'Kedy beta glukan dava zmysel, co od neho cakat pri imunite a preco nejde o rychly zazrak na druhy den.',
    'imunita',
  ],
  'je-kreatin-bezpecny-najcastejsie-obavy-a-fakty' => [
    'Je kreatin bezpecny? Najcastejsie obavy a fakty',
    'Jednoduchy prehlad obav pri kreatine: voda, oblicky, cyklovanie a co je realita a co len internetovy mytus.',
    'sila',
  ],
  'je-lacny-protein-zly-ako-citat-zlozenie' => [
    'Je lacny protein zly? Ako citat zlozenie',
    'Nizsia cena nemusi byt automaticky problem. Dolezitejsie je vediet, co je bezny kompromis a co uz znamena slabe zlozenie.',
    'proteiny',
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
  'kreatin-monohydrat-vs-hcl' => [
    'Kreatin monohydrat vs HCl - rozdiely a co kupit',
    'Kedy uplne staci monohydrat, kedy ma zmysel HCl a preco drahsia forma nemusi byt automaticky lepsia.',
    'sila',
  ],
  'kreatin-pre-zeny-ma-zmysel-alebo-nie' => [
    'Kreatin pre zeny - ma zmysel alebo nie',
    'Najcastejsie otazky zien okolo kreatinu: ci po nom priberu, ci sa hodi len do siloveho treningu a kedy ma realny zmysel.',
    'sila',
  ],
  'kreatin-vedlajsie-ucinky-a-fakty' => [
    'Kreatin - vedlajsie ucinky a fakty',
    'Voda, nafukovanie, bezpecnost a najcastejsie myty okolo kreatinu v zrozumitelnej podobe.',
    'sila',
  ],
  'kolagen' => [
    'Kolagen - co od neho cakat a ako si vybrat',
    'Kedy ma kolagen zmysel, aky typ sa riesi pri pleti a klboch a preco netreba cakat zazrak po par dnoch.',
    'klby-koza',
  ],
  'kolagen-na-plet-vs-kolagen-na-klby-aky-je-rozdiel' => [
    'Kolagen na plet vs kolagen na klby - aky je rozdiel',
    'Preco sa pri kolagene neriesi len znacka, ale aj ciel: plet, vlasy a nechty nie su to iste ako klby.',
    'klby-koza',
  ],
  'je-kolagen-vobec-ucinny-co-hovori-vyskum' => [
    'Je kolagen vobec ucinny? Co hovori vyskum',
    'Triezvy pohlad na kolagen: kedy moze mat zmysel, preco netreba cakat zazrak po tyzdni a ako nad nim premyslat podla ciela.',
    'klby-koza',
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
  'pre-workout-ako-vybrat' => [
    'Ako vybrat pre-workout - stimulanty, pumpa a realne davky',
    'Kofein, citrulin, beta-alanin a ich kombinacie: kedy dava pre-workout zmysel a kedy je lepsi non-stim pristup.',
    'sila',
  ],
  'pre-workout-bez-kofeinu-pre-koho-dava-zmysel' => [
    'Pre-workout bez kofeinu - pre koho dava zmysel',
    'Kedy ma non-stim pre-workout prakticky vyznam a pre koho je lepsi nez klasicky stimulant.',
    'sila',
  ],
  'multivitamin-ma-zmysel-alebo-su-to-vyhodene-peniaze' => [
    'Multivitamin - ma zmysel alebo su to vyhodene peniaze?',
    'Kedy je multivitamin praktickou poistkou, kedy je to len pohodlna skratka a co sledovat na etikete.',
    'vyziva',
  ],
  'magnezium-bisglycinat-vs-citrat-aky-je-rozdiel' => [
    'Magnezium bisglycinat vs citrat - aky je rozdiel',
    'Kedy dava vacsi zmysel bisglycinat, kedy citrat a preco netreba automaticky kupovat najdrahsiu formu len preto, ze znie lepsie.',
    'mineraly',
  ],
  'magnezium-rano-alebo-vecer-kedy-ho-brat' => [
    'Magnezium rano alebo vecer - kedy ho brat',
    'Kedy pri magneziu viac zavazi pravidelnost a tolerancia nez presna hodina a preco netreba z jednoducheho doplnku robit zlozitu strategiu.',
    'mineraly',
  ],
  'najlepsie-probiotika-po-antibiotikach' => [
    'Najlepsie probiotika po antibiotikach - co sledovat',
    'Na co sa pozerat pri probiotikach po antibiotikach, ako nad nimi rozmyslat a preco nestaci len nahodny napis na obale.',
    'imunita',
  ],
  'protein-po-treningu-kedy-ho-pit-a-kolko' => [
    'Protein po treningu - kedy ho pit a kolko',
    'Kedy ma protein po treningu realny zmysel, kolko ho orientacne staci a preco netreba stresovat z presnej minuty.',
    'proteiny',
  ],
  'najlepsi-protein-pre-zeny-co-realne-sledovat' => [
    'Najlepsi protein pre zeny - co realne sledovat',
    'Nie podla obalu, ale podla ciela, tolerancie a toho, kedy chces protein realne pouzivat.',
    'proteiny',
  ],
  'najlepsie-doplnky-na-klby-okrem-kolagenu' => [
    'Najlepsie doplnky na klby okrem kolagenu',
    'Kolagen nie je jedina moznost. Kedy pri klboch riesit skor komplexnu klbovu vyzivu alebo sirsi regeneracny pristup.',
    'klby-koza',
  ],
  'omega-3-kedy-ma-zmysel-a-co-sledovat' => [
    'Omega-3 - kedy ma zmysel a co sledovat',
    'Kedy omega-3 dava rozumny zmysel, preco ju ludia riesia pri sirsom zdravi a na co sa pozerat prakticky bez zbytocneho chaosu.',
    'vyziva',
  ],
  'clear-protein-pre-koho-dava-zmysel' => [
    'Clear protein - pre koho dava zmysel',
    'Kedy je clear protein praktickejsi nez klasicky husty shake, komu ulahci pravidelne pitie proteinu a kedy je to len drahsia zaujimavost.',
    'proteiny',
  ],
  'protein-do-kase-alebo-do-jedla-oplati-sa' => [
    'Protein do kase alebo do jedla - oplati sa',
    'Kedy dava pridavanie proteinu do jedla zmysel, kedy je to len zbytocne komplikovanie a preco nie je nutne pit shake pri kazdej prilezitosti.',
    'proteiny',
  ],
  'protein-na-ranajky-oplati-sa-alebo-nie' => [
    'Protein na ranajky - oplati sa alebo nie',
    'Kedy dava protein na ranajky prakticky zmysel, kedy je to len pohodlna forma bielkovin a preco to netreba brat ako povinny ritual.',
    'proteiny',
  ],
  'protein-s-vodou-alebo-s-mliekom-co-je-lepsie' => [
    'Protein s vodou alebo s mliekom - co je lepsie',
    'Kedy dava vacsi zmysel voda, kedy mlieko a preco nejde o univerzalne pravidlo, ale hlavne o ciel, chut a toleranciu.',
    'proteiny',
  ],
  'probiotika-pri-naduvani-co-realne-sledovat' => [
    'Probiotika pri naduvani - co realne sledovat',
    'Kedy maju pri nafukovani probiotika zmysel, preco nie su okamzite riesenie na kazdy problem a ako nad nimi premyslat rozumne.',
    'imunita',
  ],
  'probiotika-rano-alebo-vecer-zalezi-na-tom' => [
    'Probiotika rano alebo vecer - zalezi na tom',
    'Kedy je pri probiotikach dolezitejsia pravidelnost a trpezlivost nez presna hodina a preco netreba z jednoducheho doplnku robit zlozitu rutinu.',
    'imunita',
  ],
  'vitamin-d3-k2-spolu-alebo-osobitne' => [
    'Vitamin D3 a K2 - spolu alebo osobitne',
    'Kedy dava zmysel kombinacia D3 + K2, kedy netreba zbytocne komplikovat suplementaciu a preco nie je vzdy nutne riesit dva samostatne doplnky.',
    'mineraly',
  ],
  'vitamin-b-komplex-kedy-dava-zmysel' => [
    'Vitamin B komplex - kedy dava zmysel',
    'Kedy ma B-komplex prakticky zmysel, kedy je to len dalsi doplnok navyse a preco netreba automaticky kupovat co najvyssie davky.',
    'mineraly',
  ],
  'vitamin-b12-kedy-ho-riesit' => [
    'Vitamin B12 - kedy ho riesit',
    'Kedy ma vitamin B12 prakticky zmysel, preco ho niektori ludia riesia cielenejsie a preco netreba robit paniku len preto, ze je casto spominany online.',
    'mineraly',
  ],
  'vitamin-d-v-lete-treba-ho-brat' => [
    'Vitamin D v lete - treba ho brat',
    'Kedy v lete vitamin D este riesit, kedy z neho netreba robit automaticku povinnost a preco je dolezity sirsi kontext, nie len kalendar.',
    'mineraly',
  ],
  'kolagen-typ-1-2-3-co-znamenaju' => [
    'Kolagen typ 1 2 3 - co znamenaju',
    'Jednoduche vysvetlenie, preco sa pri kolagene spominaju typy I, II a III a preco je dolezitejsi ciel nez samotne cislo na obale.',
    'klby-koza',
  ],
  'hydrolyzovany-kolagen-vs-klasicky-kolagen' => [
    'Hydrolyzovany kolagen vs klasicky kolagen',
    'Kedy dava hydrolyzovany kolagen prakticky zmysel, preco sa casto spomina pri doplnkoch a preco samotny nazov este neznamena automaticky lepsi produkt.',
    'klby-koza',
  ],
  'vitamin-c' => [
    'Vitamin C - davky, zdroje a kedy ho doplnat',
    'Kedy vitamin C staci zo stravy, kedy dava zmysel doplnok a preco viac nie je vzdy lepsie.',
    'mineraly',
  ],
  'zinek' => [
    'Zinok - formy, davky a kedy ho doplnat',
    'Kedy ma zinok realny zmysel, na ake formy sa pozerat a preco netreba tlacit zbytocne vysoke davky.',
    'mineraly',
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
