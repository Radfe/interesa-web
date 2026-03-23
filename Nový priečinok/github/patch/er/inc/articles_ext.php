<?php
if (!isset($CATS) || !is_array($CATS)) $CATS = [];
if (!isset($ART)  || !is_array($ART))  $ART  = [];

$EXTRA_CATS = [
  'vitaminy-mineraly' => ['Vitamíny & minerály','D3, C, horčík, zinok a spol.'],
  'probiotika'        => ['Probiotiká & trávenie','Mikrobióm, probiotiká, vláknina.'],
  'klby-kolagen'      => ['Kĺby & kolagén','Kolagén a podpora pohybového aparátu.'],
  'aminokyseliny'     => ['Aminokyseliny','BCAA, EAA, glutamín.'],
  'pre-workouty'      => ['Pre-workouty','Energia a sústredenie pred tréningom.'],
  'elektrolyty'       => ['Elektrolyty','Hydratácia: sodík, draslík, horčík.'],
  'omega-3'           => ['Omega-3','EPA/DHA a dávkovanie.'],
];
$CATS = array_merge($EXTRA_CATS,$CATS);

$EXTRA_ART = [
  'vitamin-d3'             => ['Vitamín D3 – dávky a formy','Ako dávkovať a akú formu zvoliť.','vitaminy-mineraly'],
  'vitamin-c'              => ['Vitamín C – kedy, koľko a aký','Askorbát vs. kys. askorbová, dávky.','vitaminy-mineraly'],
  'horcik'                 => ['Horčík – typy a vstrebávanie','Citrát, bisglycinát a dávkovanie.','vitaminy-mineraly'],
  'zinek'                  => ['Zinok – kedy dopĺňať','Formy a interakcie so stravou.','vitaminy-mineraly'],
  'probiotika-a-travenie'  => ['Probiotiká a trávenie – výber','Ktoré kmene majú zmysel a kedy.','probiotika'],
  'klby-a-kolagen'         => ['Kĺby & kolagén – sprievodca','Typy kolagénu a dávkovanie.','klby-kolagen'],
  'aminokyseliny-bcaa-eaa' => ['Aminokyseliny: BCAA vs. EAA','Rozdiely a kedy čo dáva zmysel.','aminokyseliny'],
  'pre-workout'            => ['Pre-workout – zloženie a bezpečnosť','Čo od neho čakať a na čo si dať pozor.','pre-workouty'],
  'elektrolyty'            => ['Elektrolyty – hydratácia pre výkon','Sodík, draslík, horčík; nápoj vs. kapsuly.','elektrolyty'],
  'omega-3'                => ['Omega-3 – EPA/DHA a dávkovanie','Rybie oleje, formy a čistota.','omega-3'],
  'clear-protein'          => ['Clear proteín – čo to je','Osviežujúca srvátka, kedy sa hodí.','proteiny'],
];
$ART = array_merge($EXTRA_ART,$ART);
