<?php
declare(strict_types=1);

/**
 * Jednoduchý register FAQ pre vybrané články.
 * Vráti pole otázok/odpovedí alebo prázdne pole.
 */

function faq_for_slug(string $slug): array {
  $FAQ = [
    'doplnky-vyzivy' => [
      ['q' => 'Mám robiť kreatínové nasycovanie?', 'a' => 'Netreba. Stačí 3–5 g denne – efekt sa dostaví do pár týždňov.'],
      ['q' => 'Je D3 bezpečný na denné užívanie?', 'a' => 'Áno v odporúčaných dávkach (napr. 2000 IU). Pri vyšších dávkach konzultuj lekára.'],
      ['q' => 'Ktoré magnézium nezdráždi žalúdok?', 'a' => 'Organické formy (bisglycinát, citrát) bývajú šetrnejšie než oxid.'],
    ],
  ];
  return $FAQ[$slug] ?? [];
}
