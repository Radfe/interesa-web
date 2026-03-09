<?php declare(strict_types=1);
/** Interesa – výpis stromu súborov webrootu (bez .git, node_modules, vendor, cache) */
header('Content-Type: text/plain; charset=utf-8');

$root = realpath($_SERVER['DOCUMENT_ROOT'] ?? __DIR__ . '/..') ?: (__DIR__ . '/..');
$maxDepth = 10;
$maxItems = 20000;
$exclude  = ['.git', 'node_modules', 'vendor', '.well-known', '_admin/cache', 'storage/cache', 'cache'];

echo "=== TREE of {$root} ===\n";

$cnt = 0;
function walk(string $dir, string $prefix = '', int $depth = 0) {
  global $exclude, $maxDepth, $cnt, $maxItems, $root;
  if ($depth > $maxDepth || $cnt > $maxItems) return;

  $items = @scandir($dir);
  if ($items === false) return;
  $items = array_values(array_filter($items, fn($x)=>$x!=='.' && $x!=='..'));

  foreach ($items as $i => $name) {
    $path = $dir . DIRECTORY_SEPARATOR . $name;
    $rel  = ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);
    $skip = false;
    foreach ($exclude as $ex) {
      if ($rel === $ex || str_starts_with($rel, rtrim($ex,'/').'/')) { $skip = true; break; }
    }
    if ($skip) continue;

    $isLast = ($i === count($items)-1);
    $branch = $prefix . ($isLast ? '└── ' : '├── ');

    if (is_dir($path)) {
      echo $branch . $name . "/\n";
      $cnt++;
      walk($path, $prefix . ($isLast ? '    ' : '│   '), $depth+1);
    } else {
      $size = @filesize($path);
      $kb   = $size !== false ? number_format($size/1024, 1, '.', ' ') . ' KB' : '?';
      echo $branch . $name . "  [$kb]\n";
      $cnt++;
      if ($cnt > $maxItems) { echo "… (stopped at $maxItems items)\n"; return; }
    }
  }
}

walk($root);
echo "\nTIP: skopíruj celý výpis do nového chatu (ako kód).\n";
