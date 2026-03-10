<?php
declare(strict_types=1);

$slug = $GLOBALS['__ARTICLE_SLUG'] ?? ($slug ?? '');
if ($slug === '') {
    $slug = basename(__FILE__, '.php');
}
$_GET['slug'] = $slug;
require __DIR__ . '/article.php';