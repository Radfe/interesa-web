<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

function hero_helper_prompt_map(): array {
    static $map = null;
    if (is_array($map)) {
        return $map;
    }

    $map = [];
    $file = dirname(__DIR__) . '/docs/article-hero-shotlist.csv';
    if (!is_file($file)) {
        return $map;
    }

    $handle = fopen($file, 'rb');
    if ($handle === false) {
        return $map;
    }

    $header = fgetcsv($handle);
    while (($row = fgetcsv($handle)) !== false) {
        if (!is_array($row) || count($row) < 7) {
            continue;
        }

        $slug = trim((string) ($row[0] ?? ''));
        if ($slug === '') {
            continue;
        }

        $map[$slug] = [
            'title' => (string) ($row[1] ?? ''),
            'category' => (string) ($row[2] ?? ''),
            'file_name' => (string) ($row[3] ?? ''),
            'asset_path' => (string) ($row[4] ?? ''),
            'alt_text' => (string) ($row[5] ?? ''),
            'prompt' => (string) ($row[6] ?? ''),
            'status' => (string) ($row[7] ?? ''),
        ];
    }

    fclose($handle);
    return $map;
}

$page_title = 'Hero Helper | Interesa';
$page_description = 'InternĂ˝ prehÄľad hero obrĂˇzkov ÄŤlĂˇnkov.';
$page_robots = 'noindex,nofollow';
$page_styles = [asset('css/hero-helper.css')];
$page_scripts = [asset('js/hero-helper.js')];

$promptMap = hero_helper_prompt_map();
$slugs = array_keys(article_registry());
sort($slugs);

$cards = [];
$done = 0;
$webpCount = 0;
$svgCount = 0;
foreach ($slugs as $slug) {
    $meta = article_meta($slug);
    $title = $meta['title'] !== '' ? (string) $meta['title'] : humanize_slug($slug);
    $category = normalize_category_slug((string) ($meta['category'] ?? ''));
    $image = interessa_article_image_meta($slug, 'thumb', true);
    $webpFile = __DIR__ . '/assets/img/articles/heroes/' . $slug . '.webp';
    $svgFile = __DIR__ . '/assets/img/articles/heroes/' . $slug . '.svg';
    $hasWebp = is_file($webpFile);
    $hasSvg = is_file($svgFile);
    if ($hasWebp) {
        $done++;
        $webpCount++;
    }
    if ($hasSvg) {
        $svgCount++;
    }

    $cards[] = [
        'slug' => $slug,
        'title' => $title,
        'category' => $category,
        'image' => $image,
        'has_webp' => $hasWebp,
        'has_svg' => $hasSvg,
        'prompt' => (string) ($promptMap[$slug]['prompt'] ?? ''),
        'asset_path' => (string) ($promptMap[$slug]['asset_path'] ?? ('public/assets/img/articles/heroes/' . $slug . '.webp')),
        'alt_text' => (string) ($promptMap[$slug]['alt_text'] ?? $title),
        'article_url' => article_url($slug),
    ];
}

require __DIR__ . '/inc/head.php';
?>
<section class="container hero-helper-page">
  <div class="hero-helper-head">
    <div>
      <p class="eyebrow">InternĂ˝ nĂˇstroj</p>
      <h1>Hero obrĂˇzky ÄŤlĂˇnkov</h1>
      <p class="lead">KaĹľdĂ˝ ÄŤlĂˇnok mĂˇ vlastnĂ˝ hero fallback. KeÄŹ nahrĂˇĹˇ finĂˇlny <code>.webp</code> so sprĂˇvnym slug nĂˇzvom, web ho zaÄŤne pouĹľĂ­vaĹĄ automaticky.</p>
    </div>
    <div class="hero-helper-stats">
      <div class="hero-stat"><strong><?= count($cards) ?></strong><span>ÄŤlĂˇnkov</span></div>
      <div class="hero-stat"><strong><?= $webpCount ?></strong><span>finĂˇlnych WebP</span></div>
      <div class="hero-stat"><strong><?= $svgCount ?></strong><span>SVG fallbackov</span></div>
    </div>
  </div>

  <div class="hero-helper-notice">
    <strong>Workflow:</strong> sleduj cieÄľovĂ˝ nĂˇzov sĂşboru, skopĂ­ruj prompt a po exporte uloĹľ <code>.webp</code> do <code>public/assets/img/articles/heroes/</code>.
  </div>

  <div class="hero-helper-grid">
    <?php foreach ($cards as $card): ?>
      <article class="hero-card<?= $card['has_webp'] ? ' is-webp' : ' is-svg' ?>">
        <a class="hero-card-image" href="<?= esc($card['article_url']) ?>" target="_blank" rel="noopener">
          <?= interessa_render_image($card['image'], ['class' => 'hero-card-image-media']) ?>
        </a>
        <div class="hero-card-body">
          <div class="hero-card-meta">
            <span class="hero-badge<?= $card['has_webp'] ? ' is-ready' : ' is-fallback' ?>"><?= $card['has_webp'] ? 'WebP hotovĂ˝' : 'SVG fallback' ?></span>
            <span class="hero-badge is-category"><?= esc(category_meta($card['category'])['title'] ?? humanize_slug($card['category'])) ?></span>
          </div>
          <h2><a href="<?= esc($card['article_url']) ?>" target="_blank" rel="noopener"><?= esc($card['title']) ?></a></h2>
          <p class="hero-card-slug"><code><?= esc($card['slug']) ?></code></p>
          <p class="hero-card-path"><strong>SĂşbor:</strong> <code><?= esc($card['asset_path']) ?></code></p>
          <p class="hero-card-alt"><strong>Alt:</strong> <?= esc($card['alt_text']) ?></p>
          <div class="hero-card-actions">
            <button type="button" class="btn btn-secondary js-copy-prompt" data-prompt="<?= esc($card['prompt']) ?>">KopĂ­rovaĹĄ prompt</button>
            <a class="btn btn-ghost" href="<?= esc($card['article_url']) ?>" target="_blank" rel="noopener">OtvoriĹĄ ÄŤlĂˇnok</a>
          </div>
          <details class="hero-card-prompt">
            <summary>ZobraziĹĄ prompt</summary>
            <pre><?= esc($card['prompt']) ?></pre>
          </details>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>