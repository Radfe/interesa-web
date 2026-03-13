<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/../inc/top-products.php';

admin_require_auth();

$slug = preg_replace('~[^a-z0-9\-_]+~i', '', (string) ($_GET['slug'] ?? '')) ?? '';
$meta = $slug !== '' ? article_meta($slug) : null;
if ($slug === '' || !is_array($meta) || !is_file(__DIR__ . '/../content/articles/' . $slug . '.html')) {
    admin_flash('error', 'Clanok sa nenasiel.');
    admin_redirect('/_admin/dashboard.php');
}

$registry = admin_media_registry_raw();
$articleData = is_array($registry['articles'][$slug] ?? null) ? $registry['articles'][$slug] : [];
$productRows = article_products($slug);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $articleFields = [
        'hero_image' => trim((string) ($_POST['hero_image'] ?? '')),
        'card_image' => trim((string) ($_POST['card_image'] ?? '')),
        'hero_alt' => trim((string) ($_POST['hero_alt'] ?? '')),
        'card_alt' => trim((string) ($_POST['card_alt'] ?? '')),
        'brief' => trim((string) ($_POST['brief'] ?? '')),
        'canva_prompt' => trim((string) ($_POST['canva_prompt'] ?? '')),
    ];
    $articleFields = array_filter($articleFields, static fn(string $value): bool => $value !== '');

    if ($articleFields === []) {
        unset($registry['articles'][$slug]);
    } else {
        $registry['articles'][$slug] = $articleFields;
    }

    $productPayload = $_POST['products'] ?? [];
    $productData = [];
    if (is_array($productPayload)) {
        foreach ($productPayload as $key => $row) {
            if (!is_array($row)) {
                continue;
            }

            $clean = [
                'image' => trim((string) ($row['image'] ?? '')),
                'name' => trim((string) ($row['name'] ?? '')),
                'subtitle' => trim((string) ($row['subtitle'] ?? '')),
                'code' => trim((string) ($row['code'] ?? '')),
                'url' => trim((string) ($row['url'] ?? '')),
            ];
            $clean = array_filter($clean, static fn(string $value): bool => $value !== '');
            if ($clean !== []) {
                $productData[$key] = $clean;
            }
        }
    }

    if ($productData === []) {
        unset($registry['products'][$slug]);
    } else {
        $registry['products'][$slug] = $productData;
    }

    if (!admin_save_media_registry($registry)) {
        admin_flash('error', 'Media metadata sa nepodarilo ulozit.');
    } else {
        admin_flash('success', 'Clanok a produktove obrazky boli ulozene.');
    }

    admin_redirect('/_admin/article.php?slug=' . rawurlencode($slug));
}

$articleMedia = article_media($slug);
$products = article_products($slug);

admin_shell_start('Uprava clanku');
?>
<section class="panel">
  <div class="actions" style="justify-content:space-between;align-items:flex-start;">
    <div>
      <h1><?= esc($meta['title']) ?></h1>
      <p class="muted" style="margin:0;"><?= esc($slug) ?><?php if ($meta['category'] !== ''): ?> · <?= esc($meta['category']) ?><?php endif; ?></p>
    </div>
    <div class="actions">
      <a class="btn secondary" href="/_admin/dashboard.php">Spat na zoznam</a>
      <a class="btn secondary" href="<?= esc(article_url($slug)) ?>" target="_blank" rel="noreferrer">Otvorit frontend</a>
    </div>
  </div>
</section>

<section class="preview-grid">
  <article class="preview">
    <img src="<?= esc($articleMedia['hero_image']) ?>" alt="<?= esc($articleMedia['hero_alt']) ?>" loading="lazy" decoding="async">
    <div class="meta">
      <strong>Hero preview</strong>
      <div class="muted">Tento obrazok sa pouzije v clanku a pre social share meta.</div>
    </div>
  </article>
  <article class="preview">
    <img src="<?= esc($articleMedia['card_image']) ?>" alt="<?= esc($articleMedia['card_alt']) ?>" loading="lazy" decoding="async">
    <div class="meta">
      <strong>Card preview</strong>
      <div class="muted">Tento obrazok sa pouzije na homepage, v zozname clankov a v category kartach.</div>
    </div>
  </article>
</section>

<form method="post">
  <section class="panel grid">
    <div>
      <h2>Obrazky clanku</h2>
      <p class="muted">Pouzite lokalnu cestu ako <code>/assets/img/articles/moj-obrazok.webp</code> alebo externu URL. Ak pole ostane prazdne, web pouzije realny subor podla slugu alebo editorial fallback.</p>
    </div>
    <div class="two-col">
      <div class="field">
        <label for="hero_image">Hero image path / URL</label>
        <input id="hero_image" name="hero_image" type="text" value="<?= esc((string) ($articleData['hero_image'] ?? '')) ?>">
      </div>
      <div class="field">
        <label for="card_image">Card image path / URL</label>
        <input id="card_image" name="card_image" type="text" value="<?= esc((string) ($articleData['card_image'] ?? '')) ?>">
      </div>
      <div class="field">
        <label for="hero_alt">Hero alt text</label>
        <input id="hero_alt" name="hero_alt" type="text" value="<?= esc((string) ($articleData['hero_alt'] ?? $meta['title'])) ?>">
      </div>
      <div class="field">
        <label for="card_alt">Card alt text</label>
        <input id="card_alt" name="card_alt" type="text" value="<?= esc((string) ($articleData['card_alt'] ?? $meta['title'])) ?>">
      </div>
    </div>
  </section>

  <section class="panel grid">
    <div>
      <h2>Image brief a Canva prompt</h2>
      <p class="muted">Sem si ulozite interny brief a finalny prompt. Ak este nemate hotovy vizual, staci vyplnit brief alebo Canva prompt a frontend pouzije fallback obrazok, kym hero nenahrate.</p>
    </div>
    <div class="field">
      <label for="brief">Image brief</label>
      <textarea id="brief" name="brief"><?= esc((string) ($articleData['brief'] ?? '')) ?></textarea>
    </div>
    <div class="field">
      <label for="canva_prompt">Canva prompt</label>
      <textarea id="canva_prompt" name="canva_prompt"><?= esc((string) ($articleData['canva_prompt'] ?? '')) ?></textarea>
    </div>
  </section>

  <section class="panel grid">
    <div>
      <h2>Produktove packshoty</h2>
      <p class="muted">Ak clanok nema produktovy blok, tato sekcia ostane prazdna. Preferujte realne merchant packshoty cez lokalny asset alebo priamu URL obrazka.</p>
    </div>
    <?php if ($products === []): ?>
      <p class="muted">Pre tento clanok zatial neevidujeme produktove karty v <code>public/content/products/<?= esc($slug) ?>.php</code>.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Preview</th>
            <th>Produkt</th>
            <th>Obrazok</th>
            <th>Affiliate / URL</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $product): $key = $product['key']; $override = $registry['products'][$slug][$key] ?? []; ?>
            <tr>
              <td><img src="<?= esc($product['img']) ?>" alt="<?= esc($product['name']) ?>" loading="lazy" decoding="async"></td>
              <td>
                <div class="field">
                  <label for="product-name-<?= esc($key) ?>">Nazov</label>
                  <input id="product-name-<?= esc($key) ?>" name="products[<?= esc($key) ?>][name]" type="text" value="<?= esc((string) ($override['name'] ?? $product['name'])) ?>">
                </div>
                <div class="field">
                  <label for="product-subtitle-<?= esc($key) ?>">Subtitle</label>
                  <input id="product-subtitle-<?= esc($key) ?>" name="products[<?= esc($key) ?>][subtitle]" type="text" value="<?= esc((string) ($override['subtitle'] ?? $product['subtitle'] ?? '')) ?>">
                </div>
              </td>
              <td>
                <div class="field">
                  <label for="product-image-<?= esc($key) ?>">Packshot path / URL</label>
                  <input id="product-image-<?= esc($key) ?>" name="products[<?= esc($key) ?>][image]" type="text" value="<?= esc((string) ($override['image'] ?? '')) ?>">
                </div>
              </td>
              <td>
                <div class="field">
                  <label for="product-code-<?= esc($key) ?>">Affiliate code</label>
                  <input id="product-code-<?= esc($key) ?>" name="products[<?= esc($key) ?>][code]" type="text" value="<?= esc((string) ($override['code'] ?? $product['code'] ?? '')) ?>">
                </div>
                <div class="field">
                  <label for="product-url-<?= esc($key) ?>">Fallback URL</label>
                  <input id="product-url-<?= esc($key) ?>" name="products[<?= esc($key) ?>][url]" type="text" value="<?= esc((string) ($override['url'] ?? $product['url'] ?? '')) ?>">
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <div class="actions">
    <button class="btn" type="submit">Ulozit zmeny</button>
    <a class="btn secondary" href="<?= esc(article_url($slug)) ?>" target="_blank" rel="noreferrer">Skontrolovat frontend</a>
  </div>
</form>
<?php admin_shell_end(); ?>
