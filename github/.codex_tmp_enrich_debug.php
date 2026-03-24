<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require 'C:/data/praca/webova_stranka/github/public/inc/admin-auth.php';
interessa_admin_session_boot();
$_SESSION['interessa_admin_ok'] = true;
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
  'action' => 'enrich_product_from_source',
  'product_slug' => 'aktin-creatine-monohydrate',
  'return_section' => 'articles',
  'return_slug' => 'pre-workout-ako-vybrat',
  'article_slug' => 'pre-workout-ako-vybrat',
  'target_slot' => '2',
];
require 'C:/data/praca/webova_stranka/github/public/admin/index.php';
