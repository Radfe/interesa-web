<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

if (admin_is_authenticated()) {
    admin_redirect('/_admin/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (admin_attempt_login($username, $password)) {
        admin_flash('success', 'Prihlasenie uspesne.');
        admin_redirect('/_admin/dashboard.php');
    }

    admin_flash('error', 'Nespravne prihlasovacie udaje.');
    admin_redirect('/_admin/');
}

admin_shell_start('Prihlasenie');
?>
<h1>Admin prihlasenie</h1>
<p class="muted">Prihlaste sa a spravujte hero obrazky, card obrazky, produktove packshoty aj image briefy pre Canva workflow.</p>
<form method="post">
  <div class="field">
    <label for="username">Pouzivatel</label>
    <input id="username" name="username" type="text" value="admin" autocomplete="username" required>
  </div>
  <div class="field">
    <label for="password">Heslo</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>
  </div>
  <button class="btn" type="submit">Prihlasit sa</button>
</form>
<p class="muted" style="margin-top:14px;">Predvolene konto: <strong>admin</strong> / <strong>interesa-admin</strong></p>
<?php admin_shell_end(); ?>
