<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/dognet-helper.php';

$page_title = 'Dognet helper | Interesa';
$page_description = 'Lokalny helper pre GymBeam deeplinky v Dognete.';
$page_canonical = '/dognet-helper';
$page_robots = 'noindex,nofollow';
$page_styles = [asset('css/dognet-helper.css')];
$page_scripts = [asset('js/dognet-helper.js')];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));
    if ($action === 'save_deeplink') {
        try {
            $code = trim((string) ($_POST['code'] ?? ''));
            $deeplinkUrl = trim((string) ($_POST['deeplink_url'] ?? ''));
            dognet_helper_save_deeplink($code, $deeplinkUrl);

            $table = dognet_helper_load_rows();
            $nextCode = dognet_helper_next_pending_code($table['rows'], $code);
            $target = '/dognet-helper?saved=' . rawurlencode($code);
            if ($nextCode !== null && $nextCode !== '') {
                $target .= '&code=' . rawurlencode($nextCode);
            }

            header('Location: ' . $target, true, 303);
            exit;
        } catch (Throwable $e) {
            $error = trim($e->getMessage());
        }
    }
}

$table = dognet_helper_load_rows();
$rows = $table['rows'];
$headers = $table['headers'];
$pendingCount = count(array_filter($rows, static fn(array $row): bool => empty($row['_is_complete'])));
$completedCount = count($rows) - $pendingCount;
$currentCode = trim((string) ($_GET['code'] ?? ''));
$current = dognet_helper_find_row($rows, $currentCode);
if ($current === null) {
    $current = dognet_helper_first_pending($rows);
}
$savedCode = trim((string) ($_GET['saved'] ?? ''));

require __DIR__ . '/inc/head.php';
?>
<section class="container dognet-helper-page">
  <div class="dognet-helper-hero">
    <div>
      <p class="eyebrow">Dognet poloautomatika</p>
      <h1>GymBeam deeplink helper</h1>
      <p class="dognet-helper-lead">Kliknes na produkt, skopiruje sa ti URL a po vlozeni deeplinku ho helper ulozi do CSV aj do affiliate vrstvy webu. Dognet kampan si otvoris len vtedy, ked ju naozaj potrebujes.</p>
    </div>
    <div class="dognet-helper-summary">
      <div><strong><?= (int) $completedCount ?></strong><span>hotovo</span></div>
      <div><strong><?= (int) $pendingCount ?></strong><span>zostava</span></div>
      <div><strong><?= (int) count($rows) ?></strong><span>spolu</span></div>
    </div>
  </div>

  <?php if ($savedCode !== ''): ?>
    <div class="dognet-helper-flash is-success">Deeplink pre kod <strong><?= esc($savedCode) ?></strong> je ulozeny a napojeny do webu.</div>
  <?php endif; ?>

  <?php if ($error !== ''): ?>
    <div class="dognet-helper-flash is-error"><?= esc($error) ?></div>
  <?php endif; ?>

  <?php if ($current !== null): ?>
    <section class="dognet-current-card">
      <div class="dognet-current-head">
        <div>
          <p class="dognet-current-code"><?= esc((string) ($current['code'] ?? '')) ?></p>
          <h2><?= esc((string) ($current['product_name'] ?? 'Produkt')) ?></h2>
          <p class="dognet-current-notes"><?= esc((string) ($current['notes'] ?? '')) ?></p>
        </div>
        <span class="dognet-status <?= !empty($current['_is_complete']) ? 'is-done' : 'is-pending' ?>">
          <?= !empty($current['_is_complete']) ? 'Hotovo' : 'Caka na deeplink' ?>
        </span>
      </div>

      <div class="dognet-current-actions">
        <a class="btn" href="<?= esc((string) ($current['product_url'] ?? '#')) ?>" target="_blank" rel="noopener">Otvor produkt</a>
        <a class="btn btn-secondary" href="<?= esc(dognet_helper_campaign_url()) ?>" target="_blank" rel="noopener">Otvor Dognet kampan</a>
        <button class="btn btn-cta" type="button" data-copy-product-url="<?= esc((string) ($current['product_url'] ?? '')) ?>">Kopirovat URL</button>
      </div>

      <div class="dognet-helper-hint">
        Pouzi full version deeplinku. Helper po ulozeni prepne interny <code>/go/</code> odkaz rovno na Dognet.
      </div>

      <form class="dognet-helper-form" method="post" action="/dognet-helper<?= $currentCode !== '' ? '?code=' . rawurlencode($currentCode) : '' ?>">
        <input type="hidden" name="action" value="save_deeplink" />
        <input type="hidden" name="code" value="<?= esc((string) ($current['code'] ?? '')) ?>" />
        <label for="deeplink_url">Dognet deeplink</label>
        <textarea id="deeplink_url" name="deeplink_url" rows="4" placeholder="Sem vloz full Dognet deeplink"><?= esc((string) ($current['deeplink_url'] ?? '')) ?></textarea>
        <button class="btn btn-cta" type="submit">Ulozit deeplink a pokracovat</button>
      </form>
    </section>
  <?php endif; ?>

  <section class="dognet-helper-list">
    <div class="dognet-helper-list-head">
      <h2>Dalsie produkty</h2>
      <p>Vyber si hociktory riadok. Helper si pamata, co uz je hotove.</p>
    </div>

    <div class="dognet-helper-grid">
      <?php foreach ($rows as $row): ?>
        <?php $code = (string) ($row['code'] ?? ''); ?>
        <article class="dognet-helper-item <?= !empty($row['_is_complete']) ? 'is-done' : 'is-pending' ?>">
          <div class="dognet-helper-item-head">
            <h3><a href="/dognet-helper?code=<?= rawurlencode($code) ?>"><?= esc((string) ($row['product_name'] ?? $code)) ?></a></h3>
            <span class="dognet-status <?= !empty($row['_is_complete']) ? 'is-done' : 'is-pending' ?>"><?= !empty($row['_is_complete']) ? 'Hotovo' : 'Caka' ?></span>
          </div>
          <p class="dognet-helper-item-code"><?= esc($code) ?></p>
          <p class="dognet-helper-item-url"><?= esc((string) ($row['product_url'] ?? '')) ?></p>
          <div class="dognet-helper-item-actions">
            <a href="/dognet-helper?code=<?= rawurlencode($code) ?>">Otvor helper</a>
            <a href="<?= esc((string) ($row['product_url'] ?? '#')) ?>" target="_blank" rel="noopener">Produkt</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
