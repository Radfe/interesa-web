<?php
// Interesa – repair functions.php (stabilná základná verzia)
// Spusti: /interesa_repair_functions_v1.php?run=1
declare(strict_types=1);
error_reporting(E_ALL); ini_set('display_errors','1');

if (!isset($_GET['run'])) { echo "<h1>Repair functions.php</h1><p><a href='?run=1'>Spustiť opravu</a></p>"; exit; }

$root = __DIR__;
$fp   = $root.'/inc/functions.php';
if (!is_file($fp)) { http_response_code(500); echo "Nenájdené: inc/functions.php"; exit; }

// 1) záloha pôvodného súboru
$bak = $root.'/inc/functions.php.bak_'.date('Ymd_His');
copy($fp, $bak);

// 2) nová stabilná verzia functions.php (bez duplicit a bez heredoc)
$new  = "<?php\n";
$new .= "declare(strict_types=1);\n";

// base_url
$new .= "if(!function_exists('base_url')){ function base_url(): string { \$s=( !empty(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS']!=='off')?'https://':'http://'; \$h=\$_SERVER['HTTP_HOST']??'localhost'; return rtrim(\$s.\$h,'/'); } }\n";
// site_url
$new .= "if(!function_exists('site_url')){ function site_url(string \$path=''): string { \$b=base_url(); if(\$path==='') return \$b.'/'; if(\$path[0]!='/') \$path='/'.\$path; return \$b.\$path; } }\n";
// esc
$new .= "if(!function_exists('esc')){ function esc(\$v): string { return htmlspecialchars((string)\$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); } }\n";
// seo_head
$new .= "if(!function_exists('seo_head')){ function seo_head(array \$page=[]): void {\n";
$new .= "  \$title=\$page['title']??'Interesa.sk';\n";
$new .= "  \$desc =\$page['description']??'Interesa.sk – nezávislé porovnania a sprievodcovia výberom.';\n";
$new .= "  \$canon=\$page['canonical']??(base_url().(\$_SERVER['REQUEST_URI']??'/'));\n";
$new .= "  \$og   =\$page['og_image']??site_url('/assets/img/og-default.jpg');\n";
$new .= "  echo '<title>'.esc(\$title).'</title>'.\"\\n\";\n";
$new .= "  echo '<meta name=\"description\" content=\"'.esc(\$desc).'\">'.\"\\n\";\n";
$new .= "  echo '<link rel=\"canonical\" href=\"'.esc(\$canon).'\">'.\"\\n\";\n";
$new .= "  echo '<meta property=\"og:title\" content=\"'.esc(\$title).'\">'.\"\\n\";\n";
$new .= "  echo '<meta property=\"og:description\" content=\"'.esc(\$desc).'\">'.\"\\n\";\n";
$new .= "  echo '<meta property=\"og:url\" content=\"'.esc(\$canon).'\">'.\"\\n\";\n";
$new .= "  echo '<meta property=\"og:image\" content=\"'.esc(\$og).'\">'.\"\\n\";\n";
$new .= "} }\n";
// breadcrumbs_html
$new .= "if(!function_exists('breadcrumbs_html')){ function breadcrumbs_html(): string {\n";
$new .= "  \$uri=parse_url(\$_SERVER['REQUEST_URI']??'/', PHP_URL_PATH); \$uri=\$uri?:'/';\n";
$new .= "  \$parts=array_values(array_filter(explode('/', trim(\$uri,'/'))));\n";
$new .= "  \$out=[]; \$acc=''; \$out[]='<a href=\"'.esc(site_url('/')).'\">Domov</a>';\n";
$new .= "  foreach(\$parts as \$p){ \$acc.='/'.\$p; \$label=ucfirst(str_replace(['-','_'],' ',\$p)); \$out[]='<a href=\"'.esc(site_url(\$acc.'/')).'\">'.esc(\$label).'</a>'; }\n";
$new .= "  return '<nav class=\"breadcrumbs\">'.implode(' &rsaquo; ',\$out).'</nav>';\n";
$new .= "} }\n";
// article_img – JEDINÁ definícia
$new .= "if(!function_exists('article_img')){ function article_img(\$slug){\n";
$new .= "  \$dir=dirname(__DIR__).'/assets/img/articles/'; \$base='/assets/img/articles/'.\$slug;\n";
$new .= "  foreach(['.webp','.jpg','.jpeg','.png'] as \$ext){ if(is_file(\$dir.\$slug.\$ext)) return site_url(\$base.\$ext); }\n";
$new .= "  return site_url('/assets/img/placeholder-16x9.svg');\n";
$new .= "} }\n";
// views helpers
$new .= "if(!function_exists('views_file')){ function views_file(): string { \$d=dirname(__DIR__).'/storage'; if(!is_dir(\$d)) @mkdir(\$d,0775,true); return \$d.'/views.json'; } }\n";
$new .= "if(!function_exists('record_view')){ function record_view(string \$slug): void { \$f=views_file(); \$data=is_file(\$f)?json_decode((string)file_get_contents(\$f),true):[]; if(!is_array(\$data)) \$data=[]; \$data[\$slug]=(\$data[\$slug]??0)+1; file_put_contents(\$f,json_encode(\$data,JSON_UNESCAPED_UNICODE)); } }\n";
$new .= "if(!function_exists('top_articles')){ function top_articles(int \$limit=6): array { \$ART=[]; @include __DIR__.'/articles.php'; if(is_file(__DIR__.'/articles_ext.php')) @include __DIR__.'/articles_ext.php'; \$f=views_file(); \$data=is_file(\$f)?json_decode((string)file_get_contents(\$f),true):[]; if(is_array(\$data)&&\$data){ arsort(\$data); \$keys=array_values(array_filter(array_keys(\$data),fn(\$k)=>isset(\$ART[\$k]))); return array_slice(\$keys,0,\$limit);} return array_slice(array_keys(\$ART),0,\$limit); } }\n";

// 3) zapíš novú verziu
file_put_contents($fp, $new);

echo "<h2>Hotovo ✅</h2>";
echo "<p>inc/functions.php bol opravený. Záloha: <code>".htmlspecialchars($bak, ENT_QUOTES)."</code></p>";
echo "<p>Obnov stránku (Ctrl/Cmd+Shift+R). Ak je všetko OK, tento repair súbor môžeš vymazať.</p>";
