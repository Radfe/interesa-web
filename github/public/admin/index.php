<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/inc/functions.php';
require_once dirname(__DIR__) . '/inc/products.php';
require_once dirname(__DIR__) . '/inc/hero-prompts.php';
require_once dirname(__DIR__) . '/inc/admin-content.php';

function interessa_admin_selected_section(): string {
    $section = strtolower(trim((string) ($_GET['section'] ?? 'articles')));
    return in_array($section, ['articles', 'products', 'images', 'affiliates'], true) ? $section : 'articles';
}

function interessa_admin_redirect(string $section, array $query = []): never {
    $query = array_filter($query, static fn(mixed $value): bool => (string) $value !== '');
    $query['section'] = $section;
    header('Location: /admin?' . http_build_query($query), true, 303);
    exit;
}

function interessa_admin_decode_json_textarea(string $value, string $label): array {
    $value = trim($value);
    if ($value === '') {
        return [];
    }

    $decoded = json_decode($value, true);
    if (!is_array($decoded)) {
        throw new RuntimeException($label . ' musi byt validne JSON pole.');
    }

    return $decoded;
}

function interessa_admin_collect_sections(): array {
    $headings = $_POST['section_heading'] ?? [];
    $bodies = $_POST['section_body'] ?? [];
    $sections = [];

    if (!is_array($headings) || !is_array($bodies)) {
        return $sections;
    }

    $count = max(count($headings), count($bodies));
    for ($i = 0; $i < $count; $i++) {
        $sections[] = [
            'heading' => trim((string) ($headings[$i] ?? '')),
            'body' => trim((string) ($bodies[$i] ?? '')),
        ];
    }

    return $sections;
}

function interessa_admin_article_options(): array {
    $items = indexed_articles();
    uasort($items, static function (array $left, array $right): int {
        return strcasecmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
    });
    return $items;
}

$page_title = 'Admin | Interesa';
$page_description = 'Interny admin panel pre clanky, produkty, obrazky a affiliate odkazy.';
$page_canonical = '/admin';
$page_robots = 'noindex,nofollow';
$page_styles = [asset('css/admin.css')];

$section = interessa_admin_selected_section();
$flash = trim((string) ($_GET['saved'] ?? ''));
$error = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = trim((string) ($_POST['action'] ?? ''));

        if ($action === 'save_article') {
            $slug = canonical_article_slug(trim((string) ($_POST['slug'] ?? '')));
            $comparisonColumns = interessa_admin_decode_json_textarea((string) ($_POST['comparison_columns_json'] ?? ''), 'Stlpce porovnania');
            $comparisonRows = interessa_admin_decode_json_textarea((string) ($_POST['comparison_rows_json'] ?? ''), 'Riadky porovnania');
            $recommended = interessa_admin_lines_to_array((string) ($_POST['recommended_products'] ?? ''));
            $payload = [
                'title' => (string) ($_POST['title'] ?? ''),
                'intro' => (string) ($_POST['intro'] ?? ''),
                'meta_title' => (string) ($_POST['meta_title'] ?? ''),
                'meta_description' => (string) ($_POST['meta_description'] ?? ''),
                'hero_asset' => (string) ($_POST['hero_asset'] ?? ''),
                'sections' => interessa_admin_collect_sections(),
                'comparison' => [
                    'title' => (string) ($_POST['comparison_title'] ?? ''),
                    'intro' => (string) ($_POST['comparison_intro'] ?? ''),
                    'columns' => $comparisonColumns,
                    'rows' => $comparisonRows,
                ],
                'recommended_products' => $recommended,
            ];

            if (!empty($_FILES['hero_image']['tmp_name'])) {
                $payload['hero_asset'] = interessa_admin_store_uploaded_article_hero($slug, $_FILES['hero_image']);
            }

            interessa_admin_save_article_override($slug, $payload);
            interessa_admin_redirect('articles', ['slug' => $slug, 'saved' => 'article']);
        }

        if ($action === 'save_product') {
            $slug = trim((string) ($_POST['product_slug'] ?? ''));
            $merchantSlug = trim((string) ($_POST['merchant_slug'] ?? ''));
            $payload = [
                'name' => (string) ($_POST['name'] ?? ''),
                'brand' => (string) ($_POST['brand'] ?? ''),
                'merchant' => (string) ($_POST['merchant'] ?? ''),
                'merchant_slug' => $merchantSlug,
                'category' => (string) ($_POST['category'] ?? ''),
                'affiliate_code' => (string) ($_POST['affiliate_code'] ?? ''),
                'fallback_url' => (string) ($_POST['fallback_url'] ?? ''),
                'summary' => (string) ($_POST['summary'] ?? ''),
                'rating' => (string) ($_POST['rating'] ?? ''),
                'pros' => (string) ($_POST['pros'] ?? ''),
                'cons' => (string) ($_POST['cons'] ?? ''),
                'image_remote_src' => (string) ($_POST['image_remote_src'] ?? ''),
            ];

            if (!empty($_FILES['product_image']['tmp_name'])) {
                $payload['image_asset'] = interessa_admin_store_uploaded_product_image($slug, $merchantSlug, $_FILES['product_image']);
            }

            interessa_admin_save_product_record($slug, $payload);
            interessa_admin_redirect('products', ['product' => $slug, 'saved' => 'product']);
        }

        if ($action === 'save_affiliate') {
            $code = trim((string) ($_POST['code'] ?? ''));
            interessa_admin_save_affiliate_record($code, [
                'url' => (string) ($_POST['url'] ?? ''),
                'merchant' => (string) ($_POST['merchant'] ?? ''),
                'merchant_slug' => (string) ($_POST['merchant_slug'] ?? ''),
                'product_slug' => (string) ($_POST['product_slug'] ?? ''),
                'link_type' => (string) ($_POST['link_type'] ?? 'affiliate'),
            ]);
            interessa_admin_redirect('affiliates', ['code' => $code, 'saved' => 'affiliate']);
        }

        if ($action === 'upload_hero_only') {
            $slug = canonical_article_slug(trim((string) ($_POST['slug'] ?? '')));
            $asset = interessa_admin_store_uploaded_article_hero($slug, $_FILES['hero_image']);
            $override = interessa_admin_article_override($slug);
            $override['hero_asset'] = $asset;
            interessa_admin_save_article_override($slug, $override);
            interessa_admin_redirect('images', ['slug' => $slug, 'saved' => 'hero']);
        }
    }
} catch (Throwable $e) {
    $error = trim($e->getMessage());
}

$articleOptions = interessa_admin_article_options();
$selectedArticleSlug = canonical_article_slug(trim((string) ($_GET['slug'] ?? array_key_first($articleOptions) ?? '')));
$selectedArticleMeta = $selectedArticleSlug !== '' ? article_meta($selectedArticleSlug) : ['title' => '', 'description' => '', 'category' => ''];
$selectedArticleOverride = $selectedArticleSlug !== '' ? interessa_admin_article_content($selectedArticleSlug) : interessa_admin_normalize_article_override('', []);
$articlePrompt = $selectedArticleSlug !== '' ? interessa_hero_prompt_meta($selectedArticleSlug) : [];

$catalog = interessa_product_catalog();
$productSlugs = array_keys($catalog);
sort($productSlugs);
$selectedProductSlug = trim((string) ($_GET['product'] ?? ($productSlugs[0] ?? '')));
$selectedProduct = $selectedProductSlug !== '' ? interessa_product($selectedProductSlug) : null;
$selectedProduct = is_array($selectedProduct) ? interessa_normalize_product($selectedProduct) : null;

$affiliateRegistry = aff_registry();
$affiliateCodes = array_keys($affiliateRegistry);
sort($affiliateCodes);
$selectedAffiliateCode = trim((string) ($_GET['code'] ?? ($affiliateCodes[0] ?? '')));
$selectedAffiliate = $selectedAffiliateCode !== '' ? aff_record($selectedAffiliateCode) : null;

$sections = is_array($selectedArticleOverride['sections'] ?? null) ? $selectedArticleOverride['sections'] : [];
while (count($sections) < 5) {
    $sections[] = ['heading' => '', 'body' => ''];
}

$comparison = is_array($selectedArticleOverride['comparison'] ?? null) ? $selectedArticleOverride['comparison'] : ['columns' => [], 'rows' => []];
$recommendedProductsText = implode(PHP_EOL, is_array($selectedArticleOverride['recommended_products'] ?? null) ? $selectedArticleOverride['recommended_products'] : []);

require dirname(__DIR__) . '/inc/head.php';
?>
<section class="container admin-page">
  <div class="admin-shell">
    <aside class="admin-sidebar">
      <h1>Admin</h1>
      <p class="admin-meta">Lahky flat-file panel pre obsah a obrazky.</p>
      <nav class="admin-nav">
        <a class="<?= $section === 'articles' ? 'is-active' : '' ?>" href="/admin?section=articles&slug=<?= esc($selectedArticleSlug) ?>">Clanky</a>
        <a class="<?= $section === 'products' ? 'is-active' : '' ?>" href="/admin?section=products&product=<?= esc($selectedProductSlug) ?>">Produkty</a>
        <a class="<?= $section === 'images' ? 'is-active' : '' ?>" href="/admin?section=images&slug=<?= esc($selectedArticleSlug) ?>">Image briefy</a>
        <a class="<?= $section === 'affiliates' ? 'is-active' : '' ?>" href="/admin?section=affiliates&code=<?= esc($selectedAffiliateCode) ?>">Affiliate odkazy</a>
      </nav>
      <div class="admin-note">
        Frontend ostava flat-file. Admin uklada len override data a obrazky.
      </div>
    </aside>

    <div class="admin-main">
      <?php if ($flash !== ''): ?>
        <div class="admin-flash is-success">Ulozene: <?= esc($flash) ?></div>
      <?php endif; ?>
      <?php if ($error !== ''): ?>
        <div class="admin-flash is-error"><?= esc($error) ?></div>
      <?php endif; ?>

      <?php if ($section === 'articles'): ?>
        <section class="admin-card">
          <div class="admin-card-head">
            <div>
              <p class="admin-kicker">Article management</p>
              <h2>Strukturovany obsah clanku</h2>
            </div>
            <form method="get" action="/admin" class="admin-inline-form">
              <input type="hidden" name="section" value="articles" />
              <select name="slug" onchange="this.form.submit()">
                <?php foreach ($articleOptions as $slug => $item): ?>
                  <option value="<?= esc($slug) ?>" <?= $slug === $selectedArticleSlug ? 'selected' : '' ?>><?= esc($item['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </div>

          <form method="post" enctype="multipart/form-data" class="admin-form admin-form-stack">
            <input type="hidden" name="action" value="save_article" />
            <input type="hidden" name="slug" value="<?= esc($selectedArticleSlug) ?>" />

            <div class="admin-grid two-up">
              <label>
                <span>Titulok</span>
                <input type="text" name="title" value="<?= esc((string) ($selectedArticleOverride['title'] ?: $selectedArticleMeta['title'])) ?>" />
              </label>
              <label>
                <span>Hero asset</span>
                <input type="text" name="hero_asset" value="<?= esc((string) ($selectedArticleOverride['hero_asset'] ?? '')) ?>" placeholder="img/articles/heroes/slug.webp" />
              </label>
            </div>

            <label>
              <span>Intro</span>
              <textarea name="intro" rows="3"><?= esc((string) ($selectedArticleOverride['intro'] ?: $selectedArticleMeta['description'])) ?></textarea>
            </label>

            <div class="admin-grid two-up">
              <label>
                <span>Meta title</span>
                <input type="text" name="meta_title" value="<?= esc((string) ($selectedArticleOverride['meta_title'] ?? '')) ?>" />
              </label>
              <label>
                <span>Meta description</span>
                <input type="text" name="meta_description" value="<?= esc((string) ($selectedArticleOverride['meta_description'] ?? '')) ?>" />
              </label>
            </div>

            <div class="admin-subsection">
              <h3>Sekcie clanku</h3>
              <?php foreach ($sections as $index => $sectionRow): ?>
                <div class="admin-section-row">
                  <input type="text" name="section_heading[]" value="<?= esc((string) ($sectionRow['heading'] ?? '')) ?>" placeholder="Nadpis sekcie" />
                  <textarea name="section_body[]" rows="4" placeholder="Obsah sekcie"><?= esc((string) ($sectionRow['body'] ?? '')) ?></textarea>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="admin-subsection">
              <h3>Porovnanie</h3>
              <div class="admin-grid two-up">
                <label>
                  <span>Nadpis porovnania</span>
                  <input type="text" name="comparison_title" value="<?= esc((string) ($comparison['title'] ?? '')) ?>" />
                </label>
                <label>
                  <span>Intro porovnania</span>
                  <input type="text" name="comparison_intro" value="<?= esc((string) ($comparison['intro'] ?? '')) ?>" />
                </label>
              </div>
              <label>
                <span>Stlpce porovnania (JSON)</span>
                <textarea name="comparison_columns_json" rows="6"><?= esc(json_encode($comparison['columns'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></textarea>
              </label>
              <label>
                <span>Riadky porovnania (JSON)</span>
                <textarea name="comparison_rows_json" rows="8"><?= esc(json_encode($comparison['rows'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></textarea>
              </label>
            </div>

            <div class="admin-grid two-up">
              <label>
                <span>Odporucane produkty (slug na riadok)</span>
                <textarea name="recommended_products" rows="6"><?= esc($recommendedProductsText) ?></textarea>
              </label>
              <label>
                <span>Nahrat hero obrazok</span>
                <input type="file" name="hero_image" accept="image/webp,image/png,image/jpeg" />
              </label>
            </div>

            <div class="admin-actions">
              <button class="btn btn-cta" type="submit">Ulozit clanok</button>
              <a class="btn btn-secondary" href="<?= esc(article_url($selectedArticleSlug)) ?>" target="_blank" rel="noopener">Otvorit clanok</a>
            </div>
          </form>
        </section>
      <?php endif; ?>

      <?php if ($section === 'products'): ?>
        <section class="admin-card">
          <div class="admin-card-head">
            <div>
              <p class="admin-kicker">Product management</p>
              <h2>Reusable produkty</h2>
            </div>
            <form method="get" action="/admin" class="admin-inline-form">
              <input type="hidden" name="section" value="products" />
              <select name="product" onchange="this.form.submit()">
                <?php foreach ($productSlugs as $slug): ?>
                  <option value="<?= esc($slug) ?>" <?= $slug === $selectedProductSlug ? 'selected' : '' ?>><?= esc((string) ($catalog[$slug]['name'] ?? $slug)) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </div>

          <form method="post" enctype="multipart/form-data" class="admin-form admin-form-stack">
            <input type="hidden" name="action" value="save_product" />
            <div class="admin-grid three-up">
              <label><span>Slug</span><input type="text" name="product_slug" value="<?= esc((string) ($selectedProduct['slug'] ?? $selectedProductSlug)) ?>" /></label>
              <label><span>Nazov</span><input type="text" name="name" value="<?= esc((string) ($selectedProduct['name'] ?? '')) ?>" /></label>
              <label><span>Brand</span><input type="text" name="brand" value="<?= esc((string) ($selectedProduct['brand'] ?? '')) ?>" /></label>
            </div>
            <div class="admin-grid three-up">
              <label><span>Obchod</span><input type="text" name="merchant" value="<?= esc((string) ($selectedProduct['merchant'] ?? '')) ?>" /></label>
              <label><span>Merchant slug</span><input type="text" name="merchant_slug" value="<?= esc((string) ($selectedProduct['merchant_slug'] ?? '')) ?>" /></label>
              <label><span>Kategoria</span><input type="text" name="category" value="<?= esc((string) ($selectedProduct['category'] ?? '')) ?>" /></label>
            </div>
            <div class="admin-grid three-up">
              <label><span>Affiliate code</span><input type="text" name="affiliate_code" value="<?= esc((string) ($selectedProduct['affiliate_code'] ?? '')) ?>" /></label>
              <label><span>Fallback URL</span><input type="url" name="fallback_url" value="<?= esc((string) ($selectedProduct['fallback_url'] ?? '')) ?>" /></label>
              <label><span>Rating</span><input type="number" min="0" max="5" step="0.1" name="rating" value="<?= esc((string) ($selectedProduct['rating'] ?? '')) ?>" /></label>
            </div>
            <label>
              <span>Kratky popis</span>
              <textarea name="summary" rows="3"><?= esc((string) ($selectedProduct['summary'] ?? '')) ?></textarea>
            </label>
            <div class="admin-grid two-up">
              <label>
                <span>Plusy (riadok = 1 bod)</span>
                <textarea name="pros" rows="6"><?= esc(implode(PHP_EOL, is_array($selectedProduct['pros'] ?? null) ? $selectedProduct['pros'] : [])) ?></textarea>
              </label>
              <label>
                <span>Minusy (riadok = 1 bod)</span>
                <textarea name="cons" rows="6"><?= esc(implode(PHP_EOL, is_array($selectedProduct['cons'] ?? null) ? $selectedProduct['cons'] : [])) ?></textarea>
              </label>
            </div>
            <div class="admin-grid two-up">
              <label>
                <span>Remote image URL</span>
                <input type="url" name="image_remote_src" value="<?= esc((string) ($selectedProduct['image_remote_src'] ?? '')) ?>" />
              </label>
              <label>
                <span>Nahrat lokalny packshot</span>
                <input type="file" name="product_image" accept="image/webp,image/png,image/jpeg" />
              </label>
            </div>
            <div class="admin-actions">
              <button class="btn btn-cta" type="submit">Ulozit produkt</button>
            </div>
          </form>
        </section>
      <?php endif; ?>

      <?php if ($section === 'images'): ?>
        <section class="admin-card">
          <div class="admin-card-head">
            <div>
              <p class="admin-kicker">Image brief generator</p>
              <h2>Canva / AI workflow</h2>
            </div>
            <form method="get" action="/admin" class="admin-inline-form">
              <input type="hidden" name="section" value="images" />
              <select name="slug" onchange="this.form.submit()">
                <?php foreach ($articleOptions as $slug => $item): ?>
                  <option value="<?= esc($slug) ?>" <?= $slug === $selectedArticleSlug ? 'selected' : '' ?>><?= esc($item['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </div>

          <div class="admin-brief-grid">
            <div class="admin-brief-card">
              <h3>Brief</h3>
              <p><strong>Prompt:</strong><br><?= esc((string) ($articlePrompt['prompt'] ?? '')) ?></p>
              <p><strong>Filename:</strong><br><?= esc((string) ($articlePrompt['file_name'] ?? '')) ?></p>
              <p><strong>Alt text:</strong><br><?= esc((string) ($articlePrompt['alt_text'] ?? '')) ?></p>
              <p><strong>Dimensions:</strong><br><?= esc((string) ($articlePrompt['dimensions'] ?? '1200x800')) ?></p>
              <p><strong>Target path:</strong><br><?= esc((string) ($articlePrompt['asset_path'] ?? '')) ?></p>
            </div>
            <div class="admin-brief-card">
              <h3>Workflow</h3>
              <ol class="admin-workflow-list">
                <li>Skopiruj prompt do Canvy alebo AI generatora.</li>
                <li>Exportuj WebP v rozmere 1200x800.</li>
                <li>Dodrz naming podla odporucaneho filename.</li>
                <li>Nahraj obrazok sem alebo do cielovej cesty v assets.</li>
                <li>Clanok automaticky pouzije novy hero asset.</li>
              </ol>
              <form method="post" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="action" value="upload_hero_only" />
                <input type="hidden" name="slug" value="<?= esc($selectedArticleSlug) ?>" />
                <label>
                  <span>Nahrat finalny hero obrazok</span>
                  <input type="file" name="hero_image" accept="image/webp,image/png,image/jpeg" required />
                </label>
                <button class="btn btn-cta" type="submit">Nahrat hero obrazok</button>
              </form>
            </div>
          </div>
        </section>
      <?php endif; ?>

      <?php if ($section === 'affiliates'): ?>
        <section class="admin-card">
          <div class="admin-card-head">
            <div>
              <p class="admin-kicker">Affiliate management</p>
              <h2>Centralizovane /go/ odkazy</h2>
            </div>
            <form method="get" action="/admin" class="admin-inline-form">
              <input type="hidden" name="section" value="affiliates" />
              <select name="code" onchange="this.form.submit()">
                <?php foreach ($affiliateCodes as $code): ?>
                  <option value="<?= esc($code) ?>" <?= $code === $selectedAffiliateCode ? 'selected' : '' ?>><?= esc($code) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </div>

          <form method="post" class="admin-form admin-form-stack">
            <input type="hidden" name="action" value="save_affiliate" />
            <div class="admin-grid two-up">
              <label><span>Kod</span><input type="text" name="code" value="<?= esc((string) ($selectedAffiliate['code'] ?? $selectedAffiliateCode)) ?>" /></label>
              <label><span>Typ linku</span>
                <select name="link_type">
                  <?php $linkType = (string) ($selectedAffiliate['link_type'] ?? 'affiliate'); ?>
                  <option value="affiliate" <?= $linkType === 'affiliate' ? 'selected' : '' ?>>affiliate</option>
                  <option value="product" <?= $linkType === 'product' ? 'selected' : '' ?>>product</option>
                </select>
              </label>
            </div>
            <label><span>Cielova URL</span><input type="url" name="url" value="<?= esc((string) ($selectedAffiliate['url'] ?? '')) ?>" /></label>
            <div class="admin-grid three-up">
              <label><span>Obchod</span><input type="text" name="merchant" value="<?= esc((string) ($selectedAffiliate['merchant'] ?? '')) ?>" /></label>
              <label><span>Merchant slug</span><input type="text" name="merchant_slug" value="<?= esc((string) ($selectedAffiliate['merchant_slug'] ?? '')) ?>" /></label>
              <label><span>Product slug</span><input type="text" name="product_slug" value="<?= esc((string) ($selectedAffiliate['product_slug'] ?? '')) ?>" /></label>
            </div>
            <div class="admin-actions">
              <button class="btn btn-cta" type="submit">Ulozit affiliate odkaz</button>
              <?php if ($selectedAffiliateCode !== ''): ?>
                <a class="btn btn-secondary" href="/go/<?= rawurlencode($selectedAffiliateCode) ?>" target="_blank" rel="noopener">Otvorit /go/ link</a>
              <?php endif; ?>
            </div>
          </form>
        </section>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php require dirname(__DIR__) . '/inc/footer.php'; ?>
