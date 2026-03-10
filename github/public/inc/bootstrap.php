<?php declare(strict_types=1);

/** Jednotné nastavenie – volaj na začiatku každého PHP entrypointu */
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}
ini_set('default_charset', 'UTF-8');

/* pri ladení si zapni ?env=dev do URL */
$__ENV = ($_GET['env'] ?? 'prod');
if ($__ENV !== 'prod') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
}

define('ROOT', dirname(__DIR__));

/* helpers */
function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
