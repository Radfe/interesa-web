<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
$_SESSION['admin_auth'] = true;
$_GET['section'] = 'brand';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = '127.0.0.1:5001';
$_SERVER['REQUEST_URI'] = '/admin?section=brand';
$_SERVER['SERVER_NAME'] = '127.0.0.1';
$_SERVER['SERVER_PORT'] = '5001';
chdir(__DIR__ . '/public');
include 'admin/index.php';
?>
