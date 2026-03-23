<?php
declare(strict_types=1);

$slug = $CATEGORY_SLUG ?? ($slug ?? ($_GET['slug'] ?? ''));
$_GET['slug'] = (string) $slug;
require __DIR__ . '/category.php';