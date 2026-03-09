<?php declare(strict_types=1);
/** Interesa – diagnostika prostredia (Apache/Nginx, mod_rewrite, PHP, docroot) */
header('Content-Type: text/plain; charset=utf-8');

$soft = $_SERVER['SERVER_SOFTWARE'] ?? '';
$isApache = (stripos($soft, 'Apache') !== false) || function_exists('apache_get_version');
$isNginx  = (stripos($soft, 'nginx') !== false);
$php      = PHP_VERSION;
$docroot  = $_SERVER['DOCUMENT_ROOT'] ?? '';
$script   = __FILE__;
$realroot = realpath($docroot) ?: $docroot;

$modRewrite = null;
if ($isApache && function_exists('apache_get_modules')) {
  $modRewrite = in_array('mod_rewrite', apache_get_modules(), true);
}

$summary = [
  'server_software' => $soft,
  'is_apache'       => $isApache ? 'yes' : 'no',
  'is_nginx'        => $isNginx ? 'yes' : 'no',
  'mod_rewrite'     => $modRewrite === null ? 'unknown' : ($modRewrite ? 'enabled' : 'disabled'),
  'php_version'     => $php,
  'document_root'   => $docroot,
  'document_root_realpath' => $realroot,
  'this_script'     => $script,
  'host'            => $_SERVER['HTTP_HOST'] ?? '',
  'uri'             => $_SERVER['REQUEST_URI'] ?? '/'
];

echo "=== Interesa diagnostika ===\n";
foreach ($summary as $k=>$v) echo str_pad($k, 26, ' ') . ": $v\n";

echo "\nPOZNÁMKA:\n";
echo "- Ak 'is_apache: yes' a 'mod_rewrite: enabled' -> budeme používať .htaccess rewrites.\n";
echo "- Ak 'is_nginx: yes' -> pošlem presný server block (rewrite) pre Nginx.\n";
echo "- document_root je presná cesta, kam budem smerovať všetky súbory.\n";
