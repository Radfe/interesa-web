<?php
/**
 * Interesa – functions.php (pevná verzia + polyfill)
 */

declare(strict_types=1);

// ---- POLYFILL pre PHP < 8 (ak treba) ----
if (!function_exists('str_starts_with')) {
  function str_starts_with($haystack, $needle){ return $needle === '' || strpos($haystack, $needle) === 0; }
}
if (!function_exists('str_contains')) {
  function str_contains($haystack, $needle){ return $needle === '' || strpos($haystack, $needle) !== false; }
}

if (defined('INTERESA_FN')) { return; }
define('INTERESA_FN', true);

/* ---------------- ZÁKLAD: URL/ESC ---------------- */

function _safe_host(): string {
  $host = $_SERVER['HTTP_HOST'] ?? '';
  if (isset($_SERVER['HTTP_X_FORWARDED_HOST']) && $host === '') {
    $xfh = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_HOST']);
    $host = trim($xfh[0] ?? '');
  }
  $host = strtolower(preg_replace('~[^a-z0-9\\.-:]+~', '', $host) ?? '');
  return $host !== '' ? $host : 'localhost';
}

function _scheme(): string {
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
  return $https ? 'https' : 'http';
}

function base_url(): string { return _scheme() . '://' . _safe_host(); }

function site_url(string $path = ''): string {
  if ($path !== '' && $path[0] !== '/') $path = '/'.$path;
  return rtrim(base_url(), '/') . $path;
}

function esc(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

/* ---------------- TECHNICKÝ HEAD ---------------- */
function seo_head(array $p = []): void {
  $uriNoQuery = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
  $d = [
    'title'       => 'Interesa – recenzie, porovnania a návody',
    'description' => 'Redakčné porovnania a sprievodcovia.',
    'canonical'   => site_url($uriNoQuery),
    'og_image'    => site_url('/assets/img/og-default.jpg'),
    'og_type'     => 'website',
    'noindex'     => false,
  ];
  $p = array_merge($d, $p);
  $t=esc((string)$p['title']); $de=esc((string)$p['description']); $ca=esc((string)$p['canonical']); $og=esc((string)$p['og_image']); $ot=esc((string)$p['og_type']);
  echo "<title>{$t}</title>\n";
  echo "<meta name=\"description\" content=\"{$de}\">\n";
  echo "<link rel=\"canonical\" href=\"{$ca}\">\n";
  if (!empty($p['noindex'])) echo "<meta name=\"robots\" content=\"noindex,follow\">\n";
  echo "<meta property=\"og:type\" content=\"{$ot}\">\n<meta property=\"og:title\" content=\"{$t}\">\n<meta property=\"og:description\" content=\"{$de}\">\n<meta property=\"og:image\" content=\"{$og}\">\n<meta property=\"og:url\" content=\"{$ca}\">\n";
  echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n<meta name=\"twitter:title\" content=\"{$t}\">\n<meta name=\"twitter:description\" content=\"{$de}\">\n<meta name=\"twitter:image\" content=\"{$og}\">\n";
}

/* ---------------- BREADCRUMBS ---------------- */
function breadcrumb_schema(array $items): void {
  $list=['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>[]];
  $pos=1; foreach($items as $name=>$url){ $list['itemListElement'][]=['@type'=>'ListItem','position'=>$pos++,'name'=>$name,'item'=>$url]; }
  echo '<script type="application/ld+json">'.json_encode($list, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>';
}

function guess_breadcrumbs(): array {
  $uri=strtok($_SERVER['REQUEST_URI']??'/','?'); $parts=array_values(array_filter(explode('/',$uri))); $items=['Domov'=>site_url('/')];
  $CATS=$ART=[]; $map=__DIR__.'/articles.php'; if (is_file($map)) require_once $map;
  if (!$parts) return $items;
  if ($parts[0]==='kategorie' && !empty($parts[1])) { $cat=pathinfo($parts[1], PATHINFO_FILENAME); if (isset($CATS[$cat])) $items[$CATS[$cat][0]]=site_url('/kategorie/'.$cat.'.php'); }
  elseif ($parts[0]==='clanky' && !empty($parts[1])) {
    $slug=pathinfo($parts[1], PATHINFO_FILENAME);
    if (isset($ART[$slug])){ $cat=$ART[$slug][2]; $items['Kategórie']=site_url('/kategorie/'); if(isset($CATS[$cat])) $items[$CATS[$cat][0]]=site_url('/kategorie/'.$cat.'.php'); $items[$ART[$slug][0]]=site_url('/clanky/'.$slug.'.php'); }
  } else {
    $title=['o-nas'=>'O nás','kontakt'=>'Kontakt','zasady-ochrany-osobnych-udajov'=>'Zásady ochrany osobných údajov'];
    $last=pathinfo($parts[count($parts)-1]??'', PATHINFO_FILENAME); if(isset($title[$last])) $items[$title[$last]]=site_url('/'.trim($uri,'/'));
  }
  return $items;
}

function breadcrumbs_html(): string {
  $bc=guess_breadcrumbs(); $out=[]; $i=0; $total=count($bc);
  foreach($bc as $name=>$url){ $i++; $out[] = ($i===$total)?'<strong>'.esc($name).'</strong>':'<a href="'.esc($url).'">'.esc($name).'</a>'; }
  return implode('<span>›</span>',$out);
}

/* ---------------- OBRÁZKY ČLÁNKOV ---------------- */
function article_img(string $slug): string {
  $dir=dirname(__DIR__).'/assets/img/articles/'; $base='/assets/img/articles/'.$slug;
  foreach(['.webp','.jpg','.jpeg','.png'] as $ext){ if(is_file($dir.$slug.$ext)) return site_url($base.$ext); }
  return site_url('/assets/img/placeholder-16x9.svg');
}

/* ---------------- ZOZNAMY ČLÁNKOV ---------------- */
function latest_articles(int $limit=8): array {
  $CATS=$ART=[]; $map=__DIR__.'/articles.php'; if (is_file($map)) require_once $map;
  $slugs=array_keys($ART); return array_slice($slugs,0,max(0,$limit));
}

/* ---------------- ČÍTANOSŤ ---------------- */
function views_file(): string { $dir=dirname(__DIR__).'/storage'; if(!is_dir($dir)) @mkdir($dir,0775,true); return $dir.'/views.json'; }

function record_view(string $slug): void {
  $f=views_file(); $data=[]; if (is_file($f)) { $json=(string)@file_get_contents($f); $data=json_decode($json,true)?:[]; }
  $data[$slug]=(int)($data[$slug]??0)+1; @file_put_contents($f, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function top_articles(int $limit=5): array {
  $CATS=$ART=[]; $map=__DIR__.'/articles.php'; if (is_file($map)) require_once $map;
  $f=views_file(); $data=[]; if (is_file($f)) { $json=(string)@file_get_contents($f); $data=json_decode($json,true)?:[]; }
  if (!$data){ $keys=array_keys($ART); return array_slice($keys,0,$limit); }
  arsort($data); $keys=array_keys($data); $keys=array_values(array_filter($keys, static fn($s)=>isset($ART[$s]))); return array_slice($keys,0,$limit);
}

/* ---------------- AFFILIATE / CTA ---------------- */
function go_url(string $slug): string { $slug=strtolower(trim($slug)); $slug=preg_replace('~[^a-z0-9-]+~','-',$slug); $slug=trim($slug,'-'); return site_url('/go/'.$slug); }

(function(){ $path=__DIR__.'/components/cta_button.php'; if (is_file($path)) require_once $path;
  if (!function_exists('cta_button')) {
    function cta_button(string $slug, string $label='Kúpiť', array $attrs=[]): string {
      $href=go_url($slug);
      $base=['class'=>'btn btn-cta','href'=>$href,'rel'=>'nofollow sponsored noopener','target'=>'_blank','data-slug'=>$slug];
      $final=array_merge($base,$attrs); $attr=''; foreach($final as $k=>$v){ $attr.=' '.esc((string)$k).'="'.esc((string)$v).'"'; }
      return '<a'.$attr.'>'.esc($label).'</a>';
    }
  }
})();

function aff_button_slug(string $label, string $slug, string $shop=''): string { return cta_button($slug, $label!==''?$label:'Kúpiť',['data-shop'=>$shop]); }

/* ---------------- CSV / PRODUKTY PRE ČLÁNOK ---------------- */
function load_aff_csv(): array {
  static $rows=null; if($rows!==null) return $rows;
  $csv=__DIR__.'/../affiliate_simple_edit.csv'; $rows=[];
  if (is_file($csv) && ($h=@fopen($csv,'r'))) {
    $headers=fgetcsv($h)?:[]; if($headers && isset($headers[0])) $headers[0]=preg_replace('/^\xEF\xBB\xBF/','',(string)$headers[0]);
    while(($r=fgetcsv($h))!==false){ if(count($r)!==count($headers)) continue; $rows[]=array_combine($headers,$r); }
    fclose($h);
  }
  return $rows;
}

function products_for_article(string $slug): array {
  $all=load_aff_csv(); $prio=['High'=>0,'Medium'=>1,'Low'=>2]; $out=[];
  foreach($all as $r){ if(($r['article_target_slug']??'')===$slug){ $r['_p']=$prio[$r['priority']??'']??9; $out[]=$r; } }
  usort($out, static function($a,$b){ $cmp=($a['_p']<=>$b['_p']); return $cmp!==0?$cmp:strcmp($a['title']??'',$b['title']??''); });
  return $out;
}

function render_products(string $slug): void {
  $items=products_for_article($slug);
  if(!$items){ echo '<div class="note">Tip: doplňte CSV pre tento článok.</div>'; return; }
  $i=1; foreach($items as $r){
    $title=esc($r['title']??'Odporúčaný produkt'); $shop=esc($r['shop_name']??'Obchod'); $ctaTxt=esc($r['cta_text']??'Kúpiť'); $goslug=(string)($r['suggested_go_slug']??'');
    echo '<div class="list item"><div><strong>#'.$i.' '.$title.'</strong> <span class="meta">• '.$shop.'</span></div><div style="margin:.5rem 0;display:flex;gap:.5rem;flex-wrap:wrap">';
    echo $goslug!=='' ? cta_button($goslug,$ctaTxt,['data-shop'=>$shop]) : '<em>Pripravujeme odkaz</em>';
    echo '</div></div>'; $i++;
  }
}
