<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

admin_logout();
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
admin_flash('success', 'Boli ste odhlaseny.');
admin_redirect('/_admin/');
