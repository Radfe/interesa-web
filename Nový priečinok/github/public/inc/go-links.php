<?php
declare(strict_types=1);
function interessa_go_links(): array {
  $DEFAULT = [
    'srvatkovy-protein-vs-izolat-vs-hydro-aktin'     => '',
    'srvatkovy-protein-vs-izolat-vs-hydro-gymbeam'   => '',
    'srvatkovy-protein-vs-izolat-vs-hydro-myprotein' => '',
    'proteiny-na-chudnutie-myprotein'                => '',
    'proteiny-na-chudnutie-gymbeam'                  => '',
    'veganske-proteiny-top-vyber-2025-aktin'         => '',
    'veganske-proteiny-top-vyber-2025-gymbeam'       => '',
    'kreatin-porovnanie-aktin'                       => '',
    'kreatin-porovnanie-gymbeam'                     => '',
    'kolagen-recenzia-gymbeam'                       => '',
    'kolagen-recenzia-aktin'                         => '',
    'horcik-ktory-je-najlepsi-a-preco-aktin'         => '',
    'horcik-ktory-je-najlepsi-a-preco-gymbeam'       => '',
    'imunita-prirodne-latky-ktore-funguju-aktin'     => '',
    'imunita-prirodne-latky-ktore-funguju-myprotein' => '',
  ];
  foreach (['/affiliate_simple_edit.csv','/affiliate_links.csv'] as $csvRel){
    $csv = dirname(__DIR__).$csvRel;
    if(!is_file($csv) || !is_readable($csv)) continue;
    $fh=fopen($csv,'r'); if(!$fh) continue;
    $header=fgetcsv($fh,0,','); $sep=',';
    if($header && count($header)===1 && strpos($header[0],';')!==false){ fclose($fh); $fh=fopen($csv,'r'); $header=fgetcsv($fh,0,';'); $sep=';'; }
    $hmap=[]; foreach((array)$header as $i=>$h){ $hmap[$i]=strtolower(trim((string)$h)); }
    $iC=array_search('code',$hmap); $iU=array_search('url',$hmap);
    if($iC!==false && $iU!==false){
      while(($row=fgetcsv($fh,0,$sep))!==false){
        $c=trim((string)($row[$iC]??'')); $u=trim((string)($row[$iU]??''));
        if($c!=='' && $u!=='' && stripos($u,'http')===0){ $DEFAULT[$c]=$u; }
      }
    }
    fclose($fh);
  }
  return $DEFAULT;
}
function go_links(): array { return interessa_go_links(); }
$GO_LINKS = interessa_go_links();
