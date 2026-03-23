<?php
declare(strict_types=1);
function interessa_faqs(): array {
  return [
    'najlepsi-protein-na-chudnutie-wpc-vs-wpi' => [
      ['q' => 'Je WPI lepší na chudnutie ako WPC?', 'a' => 'Rozdiel je malý. WPI má o niečo menej laktózy/kalórií; rozhoduje celkový denný príjem.'],
      ['q' => 'Kedy piť proteín?', 'a' => 'Po tréningu alebo ako rýchly snack počas dňa.'],
      ['q' => 'Koľko bielkovín denne?', 'a' => 'Orientačne 1,6–2,2 g/kg/deň.'],
    ],
    'srvatkovy-protein-vs-izolat-vs-hydro' => [
      ['q' => 'Ktorý typ je najuniverzálnejší?', 'a' => 'Pre väčšinu ľudí WPC – dobrá chuť a cena. Pri nižšej tolerancii laktózy voľte WPI.'],
      ['q' => 'Má WPH zmysel pre každého?', 'a' => 'Skôr nie – je drahší a horkejší; špecifické použitie po náročných tréningoch.'],
    ],
    'protein-na-chudnutie' => [
      ['q' => 'Môžem nahradiť jedlo proteínom?', 'a' => 'Občas áno, ale dlhodobo je lepšia plnohodnotná strava.'],
      ['q' => 'Ako predísť hladu?', 'a' => 'Jedzte bielkoviny a vlákninu, pite vodu, spite 7–8 h.'],
    ],
    'vitamin-c' => [
      ['q' => 'Koľko vitamínu C denne?', 'a' => 'Bežne 200–500 mg; veľmi vysoké dávky dlhodobo nemajú jasný benefit.'],
      ['q' => 'Kedy užívať?', 'a' => 'Kedykoľvek počas dňa, ideálne s jedlom.'],
    ],
    'kedy-brat-kreatin-a-kolko' => [
      ['q' => 'Je dôležitý presný čas?', 'a' => 'Nie – podstatná je denná dávka 3–5 g.'],
      ['q' => 'Treba nasycovaciu fázu?', 'a' => 'Nie je nutná; môže len urýchliť nástup účinku.'],
    ],
  ];
}
function faq_for_slug(string $slug): array {
  $all = interessa_faqs();
  if (isset($all[$slug])) return $all[$slug];
  return [
    ['q' => 'Ako dávkovať?', 'a' => 'Závisí od cieľa a hmotnosti – pozrite odporúčania v článku.'],
    ['q' => 'Kedy brať?', 'a' => 'Najčastejšie po tréningu alebo počas dňa podľa doplnku.'],
    ['q' => 'Na čo si dať pozor?', 'a' => 'Sledujte zloženie, dávkovanie a prípadné interakcie s liekmi.'],
  ];
}
