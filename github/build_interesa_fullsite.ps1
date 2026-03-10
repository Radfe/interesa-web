# build_interesa_fullsite.ps1
$ErrorActionPreference = "Stop"
$root = "interesa_fullsite"
if (Test-Path $root) { Remove-Item $root -Recurse -Force }
New-Item -ItemType Directory -Force -Path $root,"$root/inc","$root/assets/css","$root/assets/js","$root/assets/img","$root/clanky","$root/kategorie","$root/stranky" | Out-Null

function W($path, $content) {
  $full = Join-Path $root $path
  $dir = Split-Path $full -Parent
  if (!(Test-Path $dir)) { New-Item -ItemType Directory -Force -Path $dir | Out-Null }
  $content | Set-Content -Encoding UTF8 -Path $full
}

# ---- CORE ----
W ".htaccess" @"
# Performance, caching, security
<IfModule mod_deflate.c>
AddOutputFilterByType DEFLATE text/plain text/html text/xml text/css application/json application/javascript
</IfModule>
<IfModule mod_expires.c>
ExpiresActive On
ExpiresByType text/css "access plus 7 days"
ExpiresByType application/javascript "access plus 7 days"
ExpiresByType image/png "access plus 30 days"
ExpiresByType image/svg+xml "access plus 30 days"
ExpiresByType image/jpeg "access plus 30 days"
</IfModule>
Header set X-Content-Type-Options "nosniff"
Header set Referrer-Policy "strict-origin-when-cross-origin"
Header set Permissions-Policy "camera=(), microphone=(), geolocation=()"

RewriteEngine On
RewriteBase /
RewriteRule ^go/([^/]+)/?$ go.php?slug=$1 [L,QSA]
"@

W "go.php" @"
<?php
header('Referrer-Policy: no-referrer-when-downgrade'); header('X-Robots-Tag: noindex, nofollow');
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if($slug===''){ http_response_code(400); echo 'Missing slug.'; exit; }
$csv = __DIR__.'/affiliate_simple_edit.csv';
if(!file_exists($csv)){ http_response_code(500); echo 'Data file not found.'; exit; }
$h=fopen($csv,'r'); $headers=fgetcsv($h); $rows=[];
while(($r=fgetcsv($h))!==false){ $rows[] = array_combine($headers,$r); } fclose($h);
$target=null; foreach($rows as $row){ if(($row['suggested_go_slug']??'')===$slug){ $target=$row; break; } }
if(!$target){ http_response_code(404); echo 'Slug not found.'; exit; }
$aff = trim($target['affiliate_link_to_fill'] ?? ''); $dest = trim($target['destination_url'] ?? '');
$url = $aff!=='' ? $aff : $dest;
if($url==='' || !preg_match('~^https?://~i',$url)){ http_response_code(400); echo 'Invalid URL.'; exit; }
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache');
header('Location: '.$url, true, 302); exit;
"@

W "robots.txt" "User-agent: *`nSitemap: https://www.interesa.sk/sitemap.xml`n"

# ---- ASSETS ----
W "assets/css/style.css" @"
:root{--bg:#fff;--text:#111827;--muted:#6b7280;--brand:#10b981;--brand-2:#059669;--cta:#ef4444;--cta-2:#dc2626;--bd:#e5e7eb;--card:#f9fafb}
*{box-sizing:border-box}html,body{margin:0;padding:0;background:var(--bg);color:var(--text);font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif;line-height:1.65}
.wrap{width:min(1160px,92%);margin:0 auto}
.site-header{border-bottom:1px solid var(--bd);position:sticky;top:0;background:#fff;z-index:40}
.header-inner{display:flex;align-items:center;gap:1rem;padding:.75rem 0}
.logo img{height:34px;display:block}
.nav{display:flex;gap:1rem;margin-left:1rem}
.nav a{text-decoration:none;color:var(--text);font-weight:600}
.search{margin-left:auto}.search input{padding:.55rem .75rem;border:1px solid var(--bd);border-radius:.5rem;min-width:220px}
.layout{display:grid;grid-template-columns:1fr 320px;gap:1.5rem}
.sidebar .banner{position:sticky;top:80px;border:1px solid var(--bd);border-radius:.75rem;overflow:hidden;background:#fff}
.sidebar .banner img{display:block;width:100%;height:auto}
.sidebar .meta{display:block;padding:.4rem .6rem;color:var(--muted);font-size:.85rem}
.hero{padding:1rem 0 0}.hero h1{font-size:clamp(1.6rem,3.2vw,2.2rem);margin:.5rem 0}
.grid-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}
.card{border:1px solid var(--bd);border-radius:.9rem;background:var(--card);padding:1rem}.card p{color:var(--muted)}
.btn{display:inline-flex;align-items:center;gap:.4rem;background:var(--brand);color:#fff;text-decoration:none;padding:.65rem .9rem;border-radius:.55rem;font-weight:700}.btn:hover{background:var(--brand-2)}
.btn-aff{display:inline-flex;flex-direction:column;align-items:flex-start;gap:.1rem;background:var(--cta);color:#fff;text-decoration:none;padding:.6rem .85rem;border-radius:.6rem}.btn-aff:hover{background:var(--cta-2)}
.list .item{background:#fff;border:1px solid var(--bd);border-radius:.8rem;padding:1rem;margin:.75rem 0}
.post{max-width:780px}.post .lead{color:var(--muted)}.post .toc{background:#fff;border:1px dashed var(--bd);padding:1rem;border-radius:.6rem}.post h2{margin-top:2rem}
.note{background:#fff7ed;border:1px solid #fed7aa;padding:.8rem;border-radius:.6rem}
.site-footer{margin-top:1.5rem;border-top:1px solid var(--bd);padding:1.5rem 0;background:#fff}.logo-mini{height:28px}
@media (max-width:980px){.layout{grid-template-columns:1fr}.sidebar{order:-1}}
"@

W "assets/js/script.js" "// čisté linky; žiadne automatické UTM`n"

# Minimálne placeholder SVG obrázky (nahradíš svojimi)
$svg = @"
<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 600 300'><rect width='100%' height='100%' fill='#e5e7eb'/><text x='50%' y='50%' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='24' fill='#111827'>Interesa</text></svg>
"@
W "assets/img/logo-full.svg" $svg
W "assets/img/logo-icon.svg" $svg
W "assets/img/og-default.jpg" $svg
W "assets/img/favicon-32.png" $svg
W "assets/img/apple-touch-icon.png" $svg

# ---- INC ----
W "inc/functions.php" @"
<?php
function site_url($path=''){ $base='https://www.interesa.sk'; if($path && $path[0] !== '/') $path='/'.$path; return $base.$path; }
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function seo_head($p=[]){
  $d=['title'=>'Interesa – recenzie, porovnania a návody','description'=>'Redakčné porovnania a sprievodcovia.','canonical'=>site_url($_SERVER['REQUEST_URI']??'/'),'og_image'=>site_url('/assets/img/og-default.jpg'),'noindex'=>false];
  $p=array_merge($d,$p); $t=esc($p['title']);$de=esc($p['description']);$ca=esc($p['canonical']);$og=esc($p['og_image']);
  echo "<title>{$t}</title>\n<meta name=\"description\" content=\"{$de}\">\n<link rel=\"canonical\" href=\"{$ca}\">";
  if(!empty($p['noindex'])) echo "\n<meta name=\"robots\" content=\"noindex,follow\">";
  echo "\n<meta property=\"og:type\" content=\"article\"><meta property=\"og:title\" content=\"{$t}\"><meta property=\"og:description\" content=\"{$de}\"><meta property=\"og:image\" content=\"{$og}\"><meta property=\"og:url\" content=\"{$ca}\">";
  echo "\n<meta name=\"twitter:card\" content=\"summary_large_image\"><meta name=\"twitter:title\" content=\"{$t}\"><meta name=\"twitter:description\" content=\"{$de}\"><meta name=\"twitter:image\" content=\"{$og}\">";
}

function breadcrumb_schema($items){
  $list=['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>[]];
  $pos=1; foreach($items as $name=>$url){ $list['itemListElement'][]=['@type'=>'ListItem','position'=>$pos++,'name'=>$name,'item'=>$url]; }
  echo '<script type="application/ld+json">'.json_encode($list, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>';
}

function article_schema($meta){
  $schema=["@context"=>"https://schema.org","@type"=>"Article","headline"=>$meta['title']??"","datePublished"=>$meta['datePublished']??"","dateModified"=>$meta['dateModified']??($meta['datePublished']??""),"author"=>["@type"=>"Organization","name"=>"Interesa"],"publisher"=>["@type"=>"Organization","name"=>"Interesa","logo"=>["@type"=>"ImageObject","url"=>site_url('/assets/img/logo-icon.svg')]],"mainEntityOfPage"=>["@type"=>"WebPage","@id"=>$meta['canonical']??site_url('/')],"image"=>$meta['og_image']??site_url('/assets/img/og-default.jpg')];
  echo '<script type="application/ld+json">'.json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>';
}

function aff_button_slug($label,$slug,$shop=''){
  $href='/go/'.$slug;
  echo '<a class="btn-aff" rel="sponsored nofollow" target="_blank" href="'.esc($href).'"><span>'.esc($label?:'Kúpiť').'</span><small>'.esc($shop).'</small></a>';
}

function load_aff_csv(){ static $rows=null; if($rows!==null) return $rows;
  $csv=__DIR__.'/../affiliate_simple_edit.csv'; $rows=[];
  if(file_exists($csv) && ($h=fopen($csv,'r'))){ $headers=fgetcsv($h); while(($r=fgetcsv($h))!==false){ $rows[]=array_combine($headers,$r); } fclose($h); }
  return $rows;
}
function products_for_article($slug){
  $all=load_aff_csv(); $prio=['High'=>0,'Medium'=>1,'Low'=>2]; $out=[];
  foreach($all as $r){ if(($r['article_target_slug']??'')===$slug){ $r['_p']=$prio[$r['priority']??''] ?? 9; $out[]=$r; } }
  usort($out,function($a,$b){ return ($a['_p']<=>$b['_p']) ?: strcmp($a['title']??'', $b['title']??''); });
  return $out;
}
function render_products($slug){
  $items=products_for_article($slug);
  if(!$items){ echo '<div class="note">Tip: doplňte CSV (affiliate_simple_edit.csv) pre tento článok.</div>'; return; }
  $i=1; foreach($items as $r){
    $title=esc($r['title']??'Odporúčaný produkt'); $shop=esc($r['shop_name']??'Obchod'); $cta=esc($r['cta_text']??'Kúpiť'); $goslug=esc($r['suggested_go_slug']??'');
    echo '<div class="list item"><div><strong>#'.$i.' '.$title.'</strong> <span class="meta">• '.$shop.'</span></div><div style="margin:.5rem 0;display:flex;gap:.5rem;flex-wrap:wrap">';
    if($goslug){ aff_button_slug($cta,$goslug,$shop); } else { echo '<em>Pripravujeme odkaz</em>'; }
    echo '</div></div>'; $i++;
  }
}
"@

W "inc/head.php" @"
<?php require_once __DIR__.'/functions.php'; ?><!doctype html>
<html lang="sk"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/png" sizes="32x32" href="<?= site_url('/assets/img/favicon-32.png') ?>">
<link rel="apple-touch-icon" href="<?= site_url('/assets/img/apple-touch-icon.png') ?>">
<?php seo_head($page ?? []); ?>
<link rel="stylesheet" href="<?= site_url('/assets/css/style.css') ?>">
</head><body>
<header class="site-header">
  <div class="wrap header-inner">
    <a href="<?= site_url('/') ?>" class="logo"><img src="<?= site_url('/assets/img/logo-full.svg') ?>" alt="Interesa"></a>
    <nav class="nav">
      <a href="<?= site_url('/kategorie/') ?>">Kategórie</a>
      <a href="<?= site_url('/clanky/') ?>">Články</a>
      <a href="<?= site_url('/stranky/o-nas.php') ?>">O nás</a>
      <a href="<?= site_url('/stranky/kontakt.php') ?>">Kontakt</a>
    </nav>
    <form class="search" action="<?= site_url('/clanky/') ?>" method="get"><input name="q" type="search" placeholder="Hľadať články…"></form>
  </div>
</header>
<main id="obsah" class="wrap"><div class="layout">
"@

W "inc/sidebar.php" @"
<?php // pravý banner (nahraď reálnym kódom z Dognet/Heureka) ?>
<aside class="sidebar">
  <div class="banner">
    <a href="#" rel="sponsored nofollow" target="_blank"><img src="<?= site_url('/assets/img/og-default.jpg') ?>" alt="Banner" loading="lazy"></a>
    <small class="meta">Partnerský obsah</small>
  </div>
</aside>
"@

W "inc/footer.php" @"
<?php // footer ?>
</div></main>
<footer class="site-footer">
  <div class="wrap grid-3">
    <div><img class="logo-mini" src="<?= site_url('/assets/img/logo-icon.svg') ?>" alt="">
      <p>Interesa – redakčné recenzie, porovnania a návody. Niektoré odkazy sú affiliate (rel="sponsored nofollow").</p>
    </div>
    <div><h4>Obsah</h4><ul>
      <li><a href="<?= site_url('/kategorie/') ?>">Kategórie</a></li>
      <li><a href="<?= site_url('/clanky/') ?>">Všetky články</a></li>
    </ul></div>
    <div><h4>Právne</h4><ul>
      <li><a href="<?= site_url('/stranky/affiliate.php') ?>">Affiliate & podmienky</a></li>
      <li><a href="<?= site_url('/stranky/zasady-ochrany-osobnych-udajov.php') ?>">Zásady ochrany</a></li>
    </ul></div>
  </div>
  <div class="copyright">© <?= date('Y') ?> Interesa</div>
</footer>
<script src="<?= site_url('/assets/js/script.js') ?>" defer></script>
</body></html>
"@

# ---- HOME + STATIC ----
W "index.php" @"
<?php $page=['title'=>'Interesa – recenzie, porovnania a návody','description'=>'Redakčný web so sprievodcami a porovnaniami.']; include __DIR__.'/inc/head.php'; ?>
<section class="hero" style="grid-column:1 / -1">
  <h1>Interesa vyberá rozumne</h1>
  <div class="grid-3">
    <div class="card"><h3>Proteíny</h3><p>Sprievodca + rebríčky</p><a class="btn" href="<?= site_url('/kategorie/proteiny.php') ?>">Zobraziť</a></div>
    <div class="card"><h3>Kreatín</h3><p>Formy, dávkovanie, FAQ</p><a class="btn" href="<?= site_url('/kategorie/kreatin.php') ?>">Zobraziť</a></div>
    <div class="card"><h3>Vitamíny & minerály</h3><p>D3, C, Mg, Zn</p><a class="btn" href="<?= site_url('/kategorie/vitaminy-mineraly.php') ?>">Zobraziť</a></div>
  </div>
</section>
<?php include __DIR__.'/inc/sidebar.php'; ?>
<?php include __DIR__.'/inc/footer.php'; ?>
"@

function Page($rel, $title, $desc, $html) {
  W $rel @"
<?php $page=['title'=>'$title','description'=>'$desc','canonical'=>site_url('/$rel')]; include __DIR__.'/../inc/head.php'; ?>
<article class="post"><h1>$title</h1>$html</article>
<?php include __DIR__.'/../inc/sidebar.php'; ?><?php include __DIR__.'/../inc/footer.php'; ?>
"@
}
Page "stranky/o-nas.php" "O nás" "Kto stojí za Interesa" "<p>Interesa je redakcia s dôrazom na zrozumiteľnosť a transparentnosť.</p>"
Page "stranky/kontakt.php" "Kontakt" "Napíšte nám" "<p>Kontakt: <a href='mailto:redakcia@interesa.sk'>redakcia@interesa.sk</a></p>"
Page "stranky/affiliate.php" "Affiliate & podmienky" "Informácie o províziách a podmienkach" "<p>Odkazy môžu byť affiliate (rel='sponsored nofollow'). Texty nie sú zdravotným poradenstvom.</p>"
Page "stranky/zasady-ochrany-osobnych-udajov.php" "Zásady ochrany osobných údajov" "Ochrana súkromia" "<p>Používame len nevyhnutné cookies.</p>"

# ---- KATEGÓRIE + ČLÁNKY ----
$categories = @{
 "proteiny" = @("Proteíny","Všetko o srvátke, izoláte, hydro, kaseíne a výbere proteínov.")
 "kreatin" = @("Kreatín","Formy, dávkovanie a vedľajšie účinky.")
 "aminokyseliny" = @("Aminokyseliny","BCAA, EAA, glutamín a elektrolyty.")
 "vitaminy-mineraly" = @("Vitamíny & minerály","D3, C, horčík, zinok a spol.")
 "probiotika-travenie" = @("Probiotiká & trávenie","Črevný mikrobióm, probiotiká a vláknina.")
 "klby-a-kolagen" = @("Kĺby & kolagén","Kolagén a podpora pohybového aparátu.")
 "chudnutie" = @("Chudnutie","Realistický prístup a výber doplnkov.")
 "pre-workout" = @("Predtréningovky","Zloženie a účinky.")
 "doplnkove-prislusenstvo" = @("Doplnkové príslušenstvo","Šejker a praktické veci.")
}
$articles = @{
 "protein-na-chudnutie" = @("Proteín na chudnutie – čo zvoliť?","Ako vybrať proteín na chudnutie, rozdiely a odporúčania.","proteiny")
 "srvatkovy-protein-vs-izolat-vs-hydro" = @("Srvátkový proteín vs. izolát vs. hydro","Porovnanie typov srvátkových proteínov.","proteiny")
 "najlepsie-proteiny-2025" = @("Najlepšie proteíny 2025 – redakčný výber","Rebríček podľa zloženia, chuti a ceny.","proteiny")
 "whey-clear-vs-classic" = @("Clear whey vs. klasický whey","Čo čakať od „limonádového“ proteínu.","proteiny")
 "kasein-na-noc" = @("Kazeín na noc – má zmysel?","Výhody pomalého uvoľňovania bielkovín.","proteiny")
 "protein-pre-zeny-myty" = @("„Dámsky“ proteín – mýty a fakty","Prečo nie je nutná „ružová“ verzia.","proteiny")
 "shaker-vyber-a-udrzba" = @("Šejker – výber a údržba","Ako zvoliť a udržiavať šejker bez zápachu.","doplnkove-prislusenstvo")
 "kreatin-monohydrat-vs-hcl" = @("Kreatín monohydrát vs. HCL","Rozdiely, cena a tolerancia.","kreatin")
 "kreatin-davkovanie-kedy-brat" = @("Kedy brať kreatín a koľko?","Praktický režim dávkovania.","kreatin")
 "kreatin-vedlajsie-ucinky" = @("Kreatín – vedľajšie účinky a fakty","Čo je normálne a na čo si dať pozor.","kreatin")
 "bcaa-vs-eaa" = @("BCAA vs. EAA – ktorý doplnok zvoliť?","Rozdiely a výber podľa cieľa.","aminokyseliny")
 "glutamin-ma-zmysel" = @("Glutamín – má zmysel pre bežného cvičenca?","Kedy môže a nemusí pomôcť.","aminokyseliny")
 "elektrolyty-pocas-treningu" = @("Elektrolyty počas tréningu","Kedy riešiť sodík, draslík, horčík.","aminokyseliny")
 "pre-workout-ako-vybrat" = @("Predtréningovky – ako vybrať","Kofeín, citrulín, beta-alanín a spol.","pre-workout")
 "omega-3-ako-vybrat" = @("Omega-3 – ako vybrať a dávkovať","EPA/DHA, forma a rybí pach.","vitaminy-mineraly")
 "magnezium-typy-porovnanie" = @("Horčík – ktoré formy fungujú","Citrát vs. bisglycinát vs. oxid.","vitaminy-mineraly")
 "zinok-ako-doplnat" = @("Zinok – ako dopĺňať","Kedy a aká forma dáva zmysel.","vitaminy-mineraly")
 "vitamin-d3-a-imunita" = @("Vitamín D3 a imunita","Ako dopĺňať a s čím kombinovať.","vitaminy-mineraly")
 "vitamin-c-fakty" = @("Vitamín C – fakty bez mýtov","Kedy stačí strava a kedy doplnok.","vitaminy-mineraly")
 "probiotika-ako-vybrat" = @("Probiotiká – ako vybrať","Kmene, dávkovanie a skladovanie.","probiotika-travenie")
 "symprove-recenziapopis" = @("Symprove – recenzia","Skúsenosti, dávkovanie a plusy/mínusy.","probiotika-travenie")
 "kolagen-na-klby-porovnanie" = @("Kolagén na kĺby – typy a porovnanie","Hydrolyzovaný, typ I/II/III, vitamín C.","klby-a-kolagen")
 "spalovace-tukov-realita" = @("Spaľovače tukov – čo (ne)čakať","Marketing vs. realita a bezpečnosť.","chudnutie")
 "meal-replacement-cocktaily" = @("Meal replacement koktaily – kedy dávajú zmysel","Kedy nahradiť jedlo nápojom.","chudnutie")
}

# Kategórie index
$kIndex = "<?php $page=['title'=>'Kategórie | Interesa','description'=>'Témy a sprievodcovia.']; include __DIR__.'/../inc/head.php'; ?>`n<h1>Kategórie</h1>`n<div class='grid-3'>`n"
foreach ($categories.GetEnumerator()) {
  $cslug = $_.Key; $cname=$_.Value[0]; $cdesc=$_.Value[1]
  $kIndex += "<div class='card'><h3>$cname</h3><p>$cdesc</p><a class='btn' href='<?= site_url('/kategorie/$cslug.php') ?>'>Zobraziť</a></div>`n"
}
$kIndex += "</div>`n<?php include __DIR__.'/../inc/sidebar.php'; ?>`n<?php include __DIR__.'/../inc/footer.php'; ?>"
W "kategorie/index.php" $kIndex

# Kategórie stránky
foreach ($categories.GetEnumerator()) {
  $cslug = $_.Key; $cname=$_.Value[0]; $cdesc=$_.Value[1]
  $items = @()
  foreach ($articles.Keys) { if ($articles[$_][2] -eq $cslug) { $items += $_ } }
  $body = "<?php $page=['title'=>'$cname | Interesa','description'=>'$cdesc']; include __DIR__.'/../inc/head.php'; ?>`n<h1>$cname</h1>`n<div class='list'>`n"
  foreach ($items) {
    $t = $articles[$_][0]
    $body += "  <a class='item' href='<?= site_url('/clanky/$_.php') ?>'><strong>$t</strong></a>`n"
  }
  $body += "</div>`n<?php include __DIR__.'/../inc/sidebar.php'; ?>`n<?php include __DIR__.'/../inc/footer.php'; ?>`n"
  W "kategorie/$cslug.php" $body
}

# Šablóna článku
W "clanky/template.php" @"
<?php
require_once __DIR__.'/../inc/functions.php';
if(!isset($slug) || !$slug){ http_response_code(404); exit('Missing slug'); }

\$ART = [
  'protein-na-chudnutie' => ['Proteín na chudnutie – čo zvoliť?','Ako vybrať proteín na chudnutie, rozdiely a odporúčania.','proteiny'],
  'srvatkovy-protein-vs-izolat-vs-hydro' => ['Srvátkový proteín vs. izolát vs. hydro','Porovnanie typov srvátkových proteínov.','proteiny'],
  'najlepsie-proteiny-2025' => ['Najlepšie proteíny 2025 – redakčný výber','Rebríček podľa zloženia, chuti a ceny.','proteiny'],
  'whey-clear-vs-classic' => ['Clear whey vs. klasický whey','Čo čakať od „limonádového“ proteínu.','proteiny'],
  'kasein-na-noc' => ['Kazeín na noc – má zmysel?','Výhody pomalého uvoľňovania bielkovín.','proteiny'],
  'protein-pre-zeny-myty' => ['„Dámsky“ proteín – mýty a fakty','Prečo nie je nutná „ružová“ verzia.','proteiny'],
  'shaker-vyber-a-udrzba' => ['Šejker – výber a údržba','Ako zvoliť a udržiavať šejker bez zápachu.','doplnkove-prislusenstvo'],
  'kreatin-monohydrat-vs-hcl' => ['Kreatín monohydrát vs. HCL','Rozdiely, cena a tolerancia.','kreatin'],
  'kreatin-davkovanie-kedy-brat' => ['Kedy brať kreatín a koľko?','Praktický režim dávkovania.','kreatin'],
  'kreatin-vedlajsie-ucinky' => ['Kreatín – vedľajšie]()
