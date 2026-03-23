<?php
declare(strict_types=1);
function views_track(string $slug): void {
  $file = __DIR__ . '/../storage/views.json';
  $db = [];
  if (is_file($file)) $db = json_decode((string)@file_get_contents($file), true) ?: [];
  $db[$slug] = (int)($db[$slug] ?? 0) + 1;
  @file_put_contents($file, json_encode($db, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
}
function views_top(int $limit=6): array {
  $file = __DIR__ . '/../storage/views.json';
  if (!is_file($file)) return [];
  $db = json_decode((string)file_get_contents($file), true) ?: [];
  arsort($db);
  return array_slice($db, 0, $limit, true);
}
