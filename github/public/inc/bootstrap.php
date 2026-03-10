<?php
declare(strict_types=1);

if (defined('INTERESA_BOOTSTRAP')) { return; }
define('INTERESA_BOOTSTRAP', 1);

if (!ob_get_level()) {
    ob_start();
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

ini_set('default_charset', 'UTF-8');
$env = (string) ($_GET['env'] ?? 'prod');
$strict = defined('E_STRICT') ? E_STRICT : 0;

if ($env !== 'prod') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~$strict & ~E_DEPRECATED);
}