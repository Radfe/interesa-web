<?php
declare(strict_types=1);

/**
 * Jednoduchý file-logger s dennými súbormi.
 * Použitie: interessa_log('go', ['event' => 'click']);
 */
if (!function_exists('interessa_log')) {
    function interessa_log(string $channel, array $data): void {
        $root = dirname(__DIR__);
        $dir = $root . '/storage/logs/' . $channel;
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $line = json_encode(['ts' => date('c')] + $data, JSON_UNESCAPED_SLASHES);
        @file_put_contents($dir . '/' . date('Y-m-d') . '.log', $line . PHP_EOL, FILE_APPEND);
    }
}
