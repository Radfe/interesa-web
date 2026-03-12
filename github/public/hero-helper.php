<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/hero-prompts.php';

function hero_helper_prompt_map(): array {
    static $map = null;
    if (is_array($map)) {
        return $map;
    }

    $map = [];
    foreach (indexed_articles() as $slug => $_meta) {
        $map[$slug] = interessa_hero_prompt_meta($slug);
    }

    return $map;
}

function hero_helper_priority_slugs(): array {
    return [
        'protein-na-chudnutie',
        'kreatin-porovnanie',
        'kolagen-recenzia',
        'horcik-ktory-je-najlepsi-a-preco',
        'imunita-prirodne-latky-ktore-funguju',
        'pre-workout-ako-vybrat',
        'probiotika-ako-vybrat',
        'veganske-proteiny-top-vyber-2025',
        'najlepsie-proteiny-2025',
        'najlepsi-protein-na-chudnutie-wpc-vs-wpi',
    ];
}

$page_title = 'Hero Helper | Interesa';
$page_description = 'Interny prehlad hero obrazkov clankov.';
$page_robots = 'noindex,nofollow';
$page_styles = [asset('css/hero-helper.css')];
$page_scripts = [asset('js/hero-helper.js')];

$promptMap = hero_helper_prompt_map();
$articles = indexed_articles();
$priorityOrder = array_flip(hero_helper_priority_slugs());
uksort($articles, static function (string $left, string $right) use ($priorityOrder): int {
    $leftPriority = $priorityOrder[$left] ?? 9999;
    $rightPriority = $priorityOrder[$right] ?? 9999;
    if ($leftPriority !== $rightPriority) {
        return $leftPriority <=> $rightPriority;
    }

    return strcasecmp($left, $right);
});

$cards = [];
$webpCount = 0;
$svgCount = 0;
$priorityPending = 0;
$categorySummary = [];
foreach ($articles as $slug => $item) {
    $title = trim((string) ($item['title'] ?? ''));
    if ($title === '') {
        $title = humanize_slug($slug);
    }
    $title = interessa_fix_mojibake($title);

    $category = normalize_category_slug((string) ($item['category'] ?? ''));
    $categoryLabel = (string) (category_meta($category)['title'] ?? humanize_slug($category));
    $categoryLabel = interessa_fix_mojibake($categoryLabel);
    $image = interessa_article_image_meta($slug, 'thumb', true);
    $webpFile = __DIR__ . '/assets/img/articles/heroes/' . $slug . '.webp';
    $svgFile = __DIR__ . '/assets/img/articles/heroes/' . $slug . '.svg';
    $hasWebp = is_file($webpFile);
    $hasSvg = is_file($svgFile);
    $isPriority = isset($priorityOrder[$slug]);

    if ($hasWebp) {
        $webpCount++;
    }
    if ($hasSvg) {
        $svgCount++;
    }
    if ($isPriority && !$hasWebp) {
        $priorityPending++;
    }

    if (!isset($categorySummary[$category])) {
        $categorySummary[$category] = [
            'label' => $categoryLabel,
            'total' => 0,
            'webp' => 0,
            'pending' => 0,
        ];
    }
    $categorySummary[$category]['total']++;
    if ($hasWebp) {
        $categorySummary[$category]['webp']++;
    } else {
        $categorySummary[$category]['pending']++;
    }

    $cardMeta = $promptMap[$slug] ?? interessa_hero_prompt_meta($slug);
    $cards[] = [
        'slug' => $slug,
        'title' => $title,
        'category' => $category,
        'category_label' => $categoryLabel,
        'image' => $image,
        'has_webp' => $hasWebp,
        'has_svg' => $hasSvg,
        'is_priority' => $isPriority,
        'prompt' => (string) ($cardMeta['prompt'] ?? ''),
        'style_brief' => (string) ($cardMeta['style_brief'] ?? ''),
        'dimensions' => (string) ($cardMeta['dimensions'] ?? '1200x800'),
        'target_folder' => (string) ($cardMeta['target_folder'] ?? 'public/assets/img/articles/heroes/'),
        'target_filename' => (string) ($cardMeta['file_name'] ?? ($slug . '.webp')),
        'asset_path' => (string) ($cardMeta['asset_path'] ?? ('public/assets/img/articles/heroes/' . $slug . '.webp')),
        'alt_text' => interessa_fix_mojibake((string) ($cardMeta['alt_text'] ?? $title)),
        'article_url' => article_url($slug),
    ];
}

uasort($categorySummary, static function (array $left, array $right): int {
    if ($left['pending'] !== $right['pending']) {
        return $right['pending'] <=> $left['pending'];
    }

    return strcasecmp((string) $left['label'], (string) $right['label']);
});

require __DIR__ . '/inc/head.php';
?>
<section class="container hero-helper-page">
  <div class="hero-helper-head">
    <div>
      <p class="eyebrow">Interny nastroj</p>
      <h1>Hero obrazky clankov</h1>
      <p class="lead">Kazdy clanok ma vlastny hero fallback. Ked nahras finalny <code>.webp</code> so spravnym slug nazvom, web ho zacne pouzivat automaticky.</p>
    </div>
    <div class="hero-helper-stats">
      <div class="hero-stat"><strong><?= count($cards) ?></strong><span>clankov</span></div>
      <div class="hero-stat"><strong><?= $webpCount ?></strong><span>finalnych WebP</span></div>
      <div class="hero-stat"><strong><?= $svgCount ?></strong><span>SVG fallbackov</span></div>
      <div class="hero-stat"><strong><?= $priorityPending ?></strong><span>priorit caka</span></div>
    </div>
  </div>

  <div class="hero-helper-notice">
    <strong>Workflow:</strong> sleduj cielovy nazov suboru, skopiruj prompt a po exporte uloz <code>.webp</code> do <code>public/assets/img/articles/heroes/</code>. Karty s oznacenim <strong>Priorita 1</strong> su zoradene navrchu.
  </div>

  <div class="hero-helper-summary">
    <?php foreach ($categorySummary as $category => $summary): ?>
      <button type="button" class="hero-summary-card js-hero-category-filter" data-category="<?= esc($category) ?>">
        <strong><?= esc((string) $summary['label']) ?></strong>
        <span><?= (int) $summary['webp'] ?> hotovo / <?= (int) $summary['pending'] ?> caka</span>
      </button>
    <?php endforeach; ?>
  </div>

  <form class="hero-helper-toolbar" id="hero-helper-toolbar" autocomplete="off">
    <label class="hero-filter">
      <span>Hladat clanok</span>
      <input type="search" id="hero-filter-search" placeholder="Nazov alebo slug">
    </label>
    <label class="hero-filter">
      <span>Kategoria</span>
      <select id="hero-filter-category">
        <option value="">Vsetky kategorie</option>
        <?php foreach ($categorySummary as $category => $summary): ?>
          <option value="<?= esc($category) ?>"><?= esc((string) $summary['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="hero-filter hero-filter-check">
      <input type="checkbox" id="hero-filter-priority">
      <span>Len priority</span>
    </label>
    <label class="hero-filter hero-filter-check">
      <input type="checkbox" id="hero-filter-missing">
      <span>Len bez WebP</span>
    </label>
    <div class="hero-filter hero-filter-results">
      <span>Zobrazene karty</span>
      <strong id="hero-filter-count"><?= count($cards) ?></strong>
    </div>
  </form>

  <div class="hero-helper-grid" id="hero-helper-grid">
    <?php foreach ($cards as $card): ?>
      <article
        class="hero-card<?= $card['has_webp'] ? ' is-webp' : ' is-svg' ?><?= $card['is_priority'] ? ' is-priority' : '' ?>"
        data-title="<?= esc(function_exists('mb_strtolower') ? mb_strtolower($card['title']) : strtolower($card['title'])) ?>"
        data-slug="<?= esc($card['slug']) ?>"
        data-category="<?= esc($card['category']) ?>"
        data-priority="<?= $card['is_priority'] ? '1' : '0' ?>"
        data-has-webp="<?= $card['has_webp'] ? '1' : '0' ?>"
      >
        <a class="hero-card-image" href="<?= esc($card['article_url']) ?>" target="_blank" rel="noopener">
          <?= interessa_render_image($card['image'], ['class' => 'hero-card-image-media']) ?>
        </a>
        <div class="hero-card-body">
          <div class="hero-card-meta">
            <span class="hero-badge<?= $card['has_webp'] ? ' is-ready' : ' is-fallback' ?>"><?= $card['has_webp'] ? 'WebP hotovy' : 'SVG fallback' ?></span>
            <?php if ($card['is_priority']): ?>
              <span class="hero-badge is-priority">Priorita 1</span>
            <?php endif; ?>
            <?php if ($card['category_label'] !== ''): ?>
              <span class="hero-badge is-category"><?= esc($card['category_label']) ?></span>
            <?php endif; ?>
          </div>
          <h2><a href="<?= esc($card['article_url']) ?>" target="_blank" rel="noopener"><?= esc($card['title']) ?></a></h2>
          <p class="hero-card-slug"><code><?= esc($card['slug']) ?></code></p>
          <p class="hero-card-path"><strong>Subor:</strong> <code><?= esc($card['asset_path']) ?></code></p>
          <p class="hero-card-alt"><strong>Alt:</strong> <?= esc($card['alt_text']) ?></p>
          <p class="hero-card-path"><strong>Export:</strong> <code><?= esc($card['dimensions']) ?></code> do <code><?= esc($card['target_folder']) ?></code></p>
          <div class="hero-card-actions">
            <button type="button" class="btn btn-secondary js-copy-prompt" data-prompt="<?= esc($card['prompt']) ?>">Kopirovat prompt</button>
            <a class="btn btn-ghost" href="<?= esc($card['article_url']) ?>" target="_blank" rel="noopener">Otvorit clanok</a>
          </div>
          <details class="hero-card-prompt">
            <summary>Zobrazit brief a prompt</summary>
            <p><strong>Style brief:</strong> <?= esc($card['style_brief']) ?></p>
            <pre><?= esc($card['prompt']) ?></pre>
          </details>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
