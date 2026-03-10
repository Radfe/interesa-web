<?php
declare(strict_types=1);

$_GET['slug'] = preg_replace('~[^a-z0-9\-_]+~i', '', (string) ($_GET['slug'] ?? ''));
require __DIR__ . '/../category.php';