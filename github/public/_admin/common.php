<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('admin_credentials')) {
    function admin_credentials(): array {
        $file = __DIR__ . '/../storage/admin-auth.php';
        $data = is_file($file) ? include $file : [];

        return [
            'username' => (string) ($data['username'] ?? 'admin'),
            'password_hash' => (string) ($data['password_hash'] ?? ''),
        ];
    }
}

if (!function_exists('admin_is_authenticated')) {
    function admin_is_authenticated(): bool {
        return (bool) ($_SESSION['interesa_admin'] ?? false);
    }
}

if (!function_exists('admin_attempt_login')) {
    function admin_attempt_login(string $username, string $password): bool {
        $credentials = admin_credentials();
        $ok = hash_equals($credentials['username'], trim($username))
            && $credentials['password_hash'] !== ''
            && password_verify($password, $credentials['password_hash']);

        if ($ok) {
            $_SESSION['interesa_admin'] = true;
            $_SESSION['interesa_admin_user'] = $credentials['username'];
        }

        return $ok;
    }
}

if (!function_exists('admin_logout')) {
    function admin_logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }

        session_destroy();
    }
}

if (!function_exists('admin_redirect')) {
    function admin_redirect(string $path): never {
        header('Location: ' . $path);
        exit;
    }
}

if (!function_exists('admin_require_auth')) {
    function admin_require_auth(): void {
        if (!admin_is_authenticated()) {
            admin_flash('error', 'Prihlaste sa do adminu.');
            admin_redirect('/_admin/');
        }
    }
}

if (!function_exists('admin_flash')) {
    function admin_flash(string $type, string $message): void {
        $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('admin_take_flash')) {
    function admin_take_flash(): ?array {
        $flash = $_SESSION['admin_flash'] ?? null;
        unset($_SESSION['admin_flash']);

        return is_array($flash) ? $flash : null;
    }
}

if (!function_exists('admin_media_registry_raw')) {
    function admin_media_registry_raw(): array {
        $file = interesa_media_storage_path();
        if (!is_file($file)) {
            return ['articles' => [], 'categories' => [], 'products' => []];
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        $decoded = is_array($decoded) ? $decoded : [];
        $decoded['articles'] = is_array($decoded['articles'] ?? null) ? $decoded['articles'] : [];
        $decoded['categories'] = is_array($decoded['categories'] ?? null) ? $decoded['categories'] : [];
        $decoded['products'] = is_array($decoded['products'] ?? null) ? $decoded['products'] : [];

        return $decoded;
    }
}

if (!function_exists('admin_save_media_registry')) {
    function admin_save_media_registry(array $registry): bool {
        $registry['articles'] = is_array($registry['articles'] ?? null) ? $registry['articles'] : [];
        $registry['categories'] = is_array($registry['categories'] ?? null) ? $registry['categories'] : [];
        $registry['products'] = is_array($registry['products'] ?? null) ? $registry['products'] : [];

        $json = json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            return false;
        }

        return file_put_contents(interesa_media_storage_path(), $json . PHP_EOL) !== false;
    }
}

if (!function_exists('admin_article_rows')) {
    function admin_article_rows(): array {
        $rows = [];
        $registry = admin_media_registry_raw();
        foreach (article_registry() as $slug => $meta) {
            $products = article_products($slug);
            $articleMedia = article_media($slug);
            $rows[] = [
                'slug' => $slug,
                'title' => $meta[0] ?? humanize_slug($slug),
                'category' => $meta[2] ?? '',
                'image' => $articleMedia['card_image'],
                'has_custom_hero' => !empty(($registry['articles'][$slug] ?? [])['hero_image']),
                'has_custom_card' => !empty(($registry['articles'][$slug] ?? [])['card_image']),
                'product_count' => count($products),
            ];
        }

        usort($rows, static fn(array $a, array $b): int => strcmp($a['title'], $b['title']));
        return $rows;
    }
}

if (!function_exists('admin_shell_start')) {
    function admin_shell_start(string $title): void {
        $flash = admin_take_flash();
        ?>
<!doctype html>
<html lang="sk">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title) ?> | Interesa Admin</title>
  <style>
    :root{color-scheme:light;--bg:#f4f7f6;--panel:#ffffff;--line:#dbe5e1;--text:#10211c;--muted:#587068;--brand:#0f8f5b;--brand-dark:#0b6e45;--danger:#b42318;--shadow:0 14px 34px rgba(16,33,28,.08)}
    *{box-sizing:border-box} body{margin:0;font-family:Segoe UI,Arial,sans-serif;background:var(--bg);color:var(--text)}
    a{color:var(--brand-dark);text-decoration:none} a:hover{text-decoration:underline}
    .shell{display:grid;grid-template-columns:240px minmax(0,1fr);min-height:100vh}
    .nav{background:#0f172a;color:#fff;padding:24px 20px;display:grid;align-content:start;gap:18px}
    .nav a{color:#d1fae5;font-weight:600}
    .nav .brand{font-size:1.15rem;color:#fff}
    .content{padding:28px}
    .wrap{max-width:1180px;margin:0 auto;display:grid;gap:20px}
    .panel{background:var(--panel);border:1px solid var(--line);border-radius:20px;box-shadow:var(--shadow);padding:22px}
    .panel h1,.panel h2,.panel h3{margin:0 0 14px}
    .muted{color:var(--muted)}
    .flash{padding:14px 16px;border-radius:14px;font-weight:600}
    .flash.success{background:#dcfce7;color:#166534}
    .flash.error{background:#fee4e2;color:var(--danger)}
    .grid{display:grid;gap:18px}
    .grid.cards{grid-template-columns:repeat(auto-fill,minmax(270px,1fr))}
    .card{background:#fff;border:1px solid var(--line);border-radius:18px;overflow:hidden}
    .card img{width:100%;height:170px;object-fit:cover;background:#e6f0ed}
    .card-body{padding:16px;display:grid;gap:10px}
    .badge-row{display:flex;gap:8px;flex-wrap:wrap}
    .badge{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:#ecfdf3;color:#166534;font-size:.82rem;font-weight:700}
    .badge.neutral{background:#eef2f6;color:#334155}
    .btn{display:inline-flex;align-items:center;justify-content:center;padding:11px 14px;border-radius:12px;border:1px solid transparent;background:var(--brand);color:#fff;font-weight:700;cursor:pointer}
    .btn.secondary{background:#fff;color:var(--text);border-color:var(--line)}
    .btn.danger{background:#fff0ef;color:var(--danger);border-color:#f5c7c3}
    form{display:grid;gap:16px}
    .field{display:grid;gap:8px}
    .field label{font-weight:700}
    .field input,.field textarea{width:100%;padding:12px 14px;border-radius:12px;border:1px solid #c8d5d0;font:inherit;background:#fff}
    .field textarea{min-height:120px;resize:vertical}
    .two-col{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
    .preview-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
    .preview{background:#fff;border:1px solid var(--line);border-radius:18px;overflow:hidden}
    .preview img{width:100%;height:220px;object-fit:cover;background:#e6f0ed}
    .preview .meta{padding:14px}
    .table{width:100%;border-collapse:collapse}
    .table th,.table td{padding:12px;border-top:1px solid var(--line);vertical-align:top;text-align:left}
    .table img{width:92px;height:92px;border-radius:14px;object-fit:cover;background:#e6f0ed}
    .actions{display:flex;gap:12px;flex-wrap:wrap}
    .login-shell{min-height:100vh;display:grid;place-items:center;padding:24px}
    .login-card{width:min(460px,100%);background:#fff;border:1px solid var(--line);border-radius:22px;box-shadow:var(--shadow);padding:28px}
    @media (max-width:980px){.shell{grid-template-columns:1fr}.nav{position:sticky;top:0;z-index:5}.two-col,.preview-grid{grid-template-columns:1fr}.content{padding:20px}}
  </style>
</head>
<body>
<?php if (admin_is_authenticated()): ?>
  <div class="shell">
    <aside class="nav">
      <div class="brand">Interesa Admin</div>
      <a href="/_admin/dashboard.php">Clanky</a>
      <a href="/clanky/" target="_blank" rel="noreferrer">Frontend clanky</a>
      <a href="/_admin/logout.php">Odhlasit sa</a>
    </aside>
    <main class="content">
      <div class="wrap">
        <?php if ($flash): ?>
          <div class="flash <?= esc($flash['type']) ?>"><?= esc($flash['message']) ?></div>
        <?php endif; ?>
<?php else: ?>
  <div class="login-shell">
    <div class="login-card">
      <?php if ($flash): ?>
        <div class="flash <?= esc($flash['type']) ?>" style="margin-bottom:16px;"><?= esc($flash['message']) ?></div>
      <?php endif; ?>
<?php endif; ?>
        <?php
    }
}

if (!function_exists('admin_shell_end')) {
    function admin_shell_end(): void {
        if (admin_is_authenticated()) {
            echo "      </div>\n    </main>\n  </div>\n";
        } else {
            echo "    </div>\n  </div>\n";
        }

        echo "</body>\n</html>";
    }
}
