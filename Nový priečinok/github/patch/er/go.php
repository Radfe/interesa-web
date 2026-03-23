<?php
declare(strict_types=1);
$root = __DIR__;
$code = $_GET['c'] ?? $_GET['code'] ?? null;
if ($code === null || $code === '') { http_response_code(400); echo "Missing parameter 'c'."; exit; }
$code = trim((string)$code);
$map = [];
$go_links_php = $root . '/inc/go-links.php';
if (is_file($go_links_php)) {
  require_once $go_links_php;
  if (function_exists('interessa_go_links')) {
    $maybe = interessa_go_links();
    if (is_array($maybe)) $map = $maybe;
  }
}
if (!$map) {
  $csv = $root . '/affiliate_simple_edit.csv';
  if (is_file($csv) && ($h = fopen($csv, 'r')) !== false) {
    $header = fgetcsv($h, 0, ',');
    while (($row = fgetcsv($h, 0, ',')) !== false) {
      if (count($row) < 2) continue;
      $map[trim($row[0])] = trim($row[1]);
    }
    fclose($h);
  }
}
$target = $map[$code] ?? null;
if (!$target) { http_response_code(404); echo "Unknown code."; exit; }
$query = $_GET; unset($query['c'], $query['code']);
$parsed = parse_url($target); parse_str($parsed['query'] ?? '', $tqs);
$final_qs = http_build_query(array_merge($tqs, $query));
$final = ($parsed['scheme'] ?? 'https') . '://' . $parsed['host'] . ($parsed['path'] ?? '');
if ($final_qs) $final .= '?' . $final_qs;
if (!empty($parsed['fragment'])) $final .= '#' . $parsed['fragment'];
$dir = $root . '/storage/logs/go'; if (!is_dir($dir)) @mkdir($dir, 0775, true);
$line = json_encode(['ts'=>date('c'),'code'=>$code,'target'=>$final,'ip'=>$_SERVER['REMOTE_ADDR'] ?? null,'ua'=>$_SERVER['HTTP_USER_AGENT'] ?? null,'ref'=>$_SERVER['HTTP_REFERER'] ?? null,'qs'=>$_SERVER['QUERY_STRING'] ?? null], JSON_UNESCAPED_SLASHES);
@file_put_contents($dir . '/' . date('Y-m-d') . '.log', $line . PHP_EOL, FILE_APPEND);
header('Cache-Control: no-store');
header('Location: ' . $final, true, 302); exit;
