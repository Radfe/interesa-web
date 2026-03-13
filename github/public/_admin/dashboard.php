<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

admin_require_auth();

$query = trim((string) ($_GET['q'] ?? ''));
$rows = admin_article_rows();
if ($query !== '') {
    $rows = array_values(array_filter($rows, static function (array $row) use ($query): bool {
        $normalize = static function (string $value): string {
            return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        };

        return str_contains($normalize($row['title']), $normalize($query))
            || str_contains($row['slug'], strtolower($query));
    }));
}

admin_shell_start('Clanky');
?>
<section class="panel">
  <h1>Sprava obrazkov pre clanky</h1>
  <p class="muted">Kazdy clanok vie mat vlastny hero obrazok, card obrazok, produktove packshoty, brief a Canva prompt bez zmeny architektury webu.</p>
  <form method="get" class="actions" style="margin-top:18px;">
    <div class="field" style="flex:1 1 260px;margin:0;">
      <label for="q">Vyhladat clanok</label>
      <input id="q" name="q" type="search" value="<?= esc($query) ?>" placeholder="napr. protein alebo vitamin d3">
    </div>
    <button class="btn secondary" type="submit">Filtrovat</button>
  </form>
</section>

<section class="grid cards">
  <?php foreach ($rows as $row): ?>
    <article class="card">
      <img src="<?= esc($row['image']) ?>" alt="<?= esc($row['title']) ?>" loading="lazy" decoding="async">
      <div class="card-body">
        <div>
          <h2 style="font-size:1.05rem;margin:0 0 6px;"><?= esc($row['title']) ?></h2>
          <div class="muted"><?= esc($row['slug']) ?></div>
        </div>
        <div class="badge-row">
          <span class="badge neutral"><?= esc($row['category'] !== '' ? $row['category'] : 'bez kategorie') ?></span>
          <?php if ($row['has_custom_hero']): ?><span class="badge">hero obrazok</span><?php endif; ?>
          <?php if ($row['has_custom_card']): ?><span class="badge">card obrazok</span><?php endif; ?>
          <?php if ($row['product_count'] > 0): ?><span class="badge neutral"><?= (int) $row['product_count'] ?> produkty</span><?php endif; ?>
        </div>
        <div class="actions">
          <a class="btn" href="/_admin/article.php?slug=<?= rawurlencode($row['slug']) ?>">Upravit</a>
          <a class="btn secondary" href="<?= esc(article_url($row['slug'])) ?>" target="_blank" rel="noreferrer">Preview</a>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</section>
<?php admin_shell_end(); ?>
