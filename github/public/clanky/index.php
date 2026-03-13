<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/article-commerce.php';

$categoryFilter = normalize_category_slug((string) ($_GET['category'] ?? ''));
$formatFilter = trim((string) ($_GET['format'] ?? ''));
$commercialOnly = (string) ($_GET['commercial'] ?? '') === '1';
$coverageFilter = trim((string) ($_GET['coverage'] ?? ''));
$categoryMeta = $categoryFilter !== '' ? category_meta($categoryFilter) : null;
$page_title = ($categoryMeta !== null ? $categoryMeta['title'] . ' | ' . interessa_text('Clanky') : interessa_text('Clanky')) . ' | Interesa';
$page_description = $categoryMeta !== null
    ? interessa_text('Prehlad clankov v teme ') . $categoryMeta['title'] . '.'
    : interessa_text('Prehlad clankov o proteinoch, vyzive, vitaminoch a dalsich doplnkoch.');
$page_canonical = '/clanky' . ($categoryMeta !== null ? '?category=' . rawurlencode($categoryMeta['slug']) : '');
$page_og_type = 'website';
include __DIR__ . '/../inc/head.php';

$allItems = array_values(indexed_articles());
$categoryCounts = [];
$formatCounts = [];
$formatLabels = interessa_article_format_map();
foreach ($allItems as &$item) {
    $slug = (string) ($item['slug'] ?? '');
    $file = dirname(__DIR__) . '/content/articles/' . $slug . '.html';
    $item['mtime'] = is_file($file) ? (int) @filemtime($file) : 0;
    $item['image'] = interessa_article_image_meta($slug, 'thumb', true);
    $itemCategorySlug = normalize_category_slug((string) ($item['category'] ?? ''));
    $item['category_meta'] = $itemCategorySlug !== '' ? category_meta($itemCategorySlug) : null;
    $item['format_slug'] = interessa_article_format_slug($slug, (string) ($item['title'] ?? ''));
    $item['format_label'] = $formatLabels[(string) ($item['format_slug'] ?? '')] ?? interessa_article_format_label($slug, (string) ($item['title'] ?? ''));
    $item['has_commerce'] = interessa_article_has_commerce($slug);
    $item['coverage_state'] = interessa_article_commerce_coverage_state($slug);
    if ($itemCategorySlug !== '') {
        $categoryCounts[$itemCategorySlug] = ($categoryCounts[$itemCategorySlug] ?? 0) + 1;
    }
    $formatKey = (string) ($item['format_slug'] ?? '');
    if ($formatKey !== '') {
        $formatCounts[$formatKey] = ($formatCounts[$formatKey] ?? 0) + 1;
    }
}
unset($item);

$items = $allItems;
if ($categoryMeta !== null) {
    $items = array_values(array_filter($items, static function (array $item) use ($categoryMeta): bool {
        $itemCategory = is_array($item['category_meta'] ?? null) ? (string) ($item['category_meta']['slug'] ?? '') : '';
        return $itemCategory === (string) $categoryMeta['slug'];
    }));
}
$commercialCountInScope = count(array_filter($items, static function (array $item): bool {
    return !empty($item['has_commerce']);
}));
if ($formatFilter !== '' && isset($formatLabels[$formatFilter])) {
    $items = array_values(array_filter($items, static function (array $item) use ($formatFilter): bool {
        return (string) ($item['format_slug'] ?? '') === $formatFilter;
    }));
}
if ($commercialOnly) {
    $items = array_values(array_filter($items, static function (array $item): bool {
        return !empty($item['has_commerce']);
    }));
}
if ($coverageFilter === 'full') {
    $items = array_values(array_filter($items, static function (array $item): bool {
        return (string) ($item['coverage_state'] ?? '') === 'full';
    }));
}
$fullCoverageCountInScope = count(array_filter($items, static function (array $item): bool {
    return (string) ($item['coverage_state'] ?? '') === 'full';
}));

usort($items, static fn(array $a, array $b): int => ((int) ($b['mtime'] ?? 0)) <=> ((int) ($a['mtime'] ?? 0)));
$categories = [];
foreach (category_registry() as $slug => $row) {
    $meta = category_meta($slug);
    if ($meta !== null) {
        $categories[$slug] = $meta;
    }
}
$recentWindow = strtotime('-60 days');
$recentArticlesCount = count(array_filter($allItems, static function (array $item) use ($recentWindow): bool {
    return (int) ($item['mtime'] ?? 0) >= $recentWindow;
}));
$commercialArticleCount = count(array_filter($allItems, static function (array $item): bool {
    return !empty($item['has_commerce']);
}));
arsort($formatCounts);
$topFormats = array_slice($formatCounts, 0, 4, true);
?>
<section class="container two-col">
  <div class="content">
    <article class="card">
      <div class="section-head">
        <h1><?= esc($categoryMeta['title'] ?? interessa_text('Clanky')) ?></h1>
        <p class="meta">
          <?php if ($categoryMeta !== null): ?>
            Kuratorovany prehlad clankov v teme <?= esc($categoryMeta['title']) ?>.
          <?php else: ?>
            Prehlad buying guide clankov, porovnani, recenzii a zakladnych navodov napriec hlavnymi temami webu.
          <?php endif; ?>
        </p>
      </div>

      <div class="archive-stats" aria-label="Prehlad archivu clankov">
        <div class="archive-stat">
          <strong><?= esc((string) count($allItems)) ?></strong>
          <span>vsetky clanky</span>
        </div>
        <div class="archive-stat">
          <strong><?= esc((string) count($categories)) ?></strong>
          <span>hlavne temy</span>
        </div>
        <div class="archive-stat">
          <strong><?= esc((string) $recentArticlesCount) ?></strong>
          <span>aktualizovane za 60 dni</span>
        </div>
        <div class="archive-stat">
          <strong><?= esc((string) $commercialArticleCount) ?></strong>
          <span>clanky s odporucaniami</span>
        </div>
      </div>

      <div class="filters-bar" aria-label="Filtre kategorii">
        <?php $baseQuery = array_filter(['format' => $formatFilter !== '' ? $formatFilter : null, 'commercial' => $commercialOnly ? '1' : null, 'coverage' => $coverageFilter !== '' ? $coverageFilter : null]); ?>
        <a class="filter-chip<?= $categoryMeta === null ? ' is-active' : '' ?>" href="/clanky<?= $baseQuery !== [] ? '/?' . esc(http_build_query($baseQuery)) : '/' ?>"><?= interessa_text('Vsetko') ?> (<?= esc((string) count($allItems)) ?>)</a>
        <?php foreach ($categories as $slug => $row): ?>
          <?php $active = $categoryMeta !== null && $categoryMeta['slug'] === $slug; ?>
          <?php $query = array_filter(['category' => $slug, 'format' => $formatFilter !== '' ? $formatFilter : null, 'commercial' => $commercialOnly ? '1' : null, 'coverage' => $coverageFilter !== '' ? $coverageFilter : null]); ?>
          <a class="filter-chip<?= $active ? ' is-active' : '' ?>" href="/clanky/?<?= esc(http_build_query($query)) ?>"><span class="filter-chip__icon" aria-hidden="true"><?= interessa_category_icon((string) $slug) ?></span><?= esc((string) $row['title']) ?> (<?= esc((string) ($categoryCounts[$slug] ?? 0)) ?>)</a>
        <?php endforeach; ?>
        <?php $commercialQuery = array_filter(['category' => $categoryMeta['slug'] ?? null, 'format' => $formatFilter !== '' ? $formatFilter : null, 'commercial' => '1', 'coverage' => $coverageFilter !== '' ? $coverageFilter : null]); ?>
        <a class="filter-chip<?= $commercialOnly ? ' is-active' : ' is-muted' ?>" href="/clanky<?= $commercialOnly ? (($categoryMeta !== null || $formatFilter !== '' || $coverageFilter !== '') ? '/?' . esc(http_build_query(array_filter(['category' => $categoryMeta['slug'] ?? null, 'format' => $formatFilter !== '' ? $formatFilter : null, 'coverage' => $coverageFilter !== '' ? $coverageFilter : null]))) : '/') : '/?' . esc(http_build_query($commercialQuery)) ?>">S odporucaniami (<?= esc((string) $commercialCountInScope) ?>)</a>
        <?php $coverageQuery = array_filter(['category' => $categoryMeta['slug'] ?? null, 'format' => $formatFilter !== '' ? $formatFilter : null, 'commercial' => $commercialOnly ? '1' : null, 'coverage' => 'full']); ?>
        <?php $coverageResetQuery = array_filter(['category' => $categoryMeta['slug'] ?? null, 'format' => $formatFilter !== '' ? $formatFilter : null, 'commercial' => $commercialOnly ? '1' : null]); ?>
          <a class="filter-chip<?= $coverageFilter === 'full' ? ' is-active' : ' is-muted' ?>" href="/clanky<?= $coverageFilter === 'full' ? ($coverageResetQuery !== [] ? '/?' . esc(http_build_query($coverageResetQuery)) : '/') : '/?' . esc(http_build_query($coverageQuery)) ?>">Kompletne vybery (<?= esc((string) $fullCoverageCountInScope) ?>)</a>
      </div>

      <?php if ($topFormats !== []): ?>
        <div class="filters-bar archive-format-bar" aria-label="Najcastejsie formaty clankov">
          <?php foreach ($topFormats as $formatSlug => $count): ?>
            <?php $isActiveFormat = $formatFilter !== '' && $formatFilter === (string) $formatSlug; ?>
            <?php $query = array_filter(['category' => $categoryMeta['slug'] ?? null, 'format' => $formatSlug, 'commercial' => $commercialOnly ? '1' : null, 'coverage' => $coverageFilter !== '' ? $coverageFilter : null]); ?>
            <a class="filter-chip<?= $isActiveFormat ? ' is-active' : ' is-muted' ?>" href="/clanky<?= $query !== [] ? '/?' . esc(http_build_query($query)) : '/' ?>">
              <span class="article-card-chip is-format"><?= esc((string) ($formatLabels[(string) $formatSlug] ?? humanize_slug((string) $formatSlug))) ?></span>
              <?= esc((string) $count) ?>
            </a>
          <?php endforeach; ?>
          <?php if ($formatFilter !== ''): ?>
              <a class="filter-chip" href="/clanky<?= ($categoryMeta !== null || $commercialOnly || $coverageFilter !== '') ? '/?' . esc(http_build_query(array_filter(['category' => $categoryMeta['slug'] ?? null, 'commercial' => $commercialOnly ? '1' : null, 'coverage' => $coverageFilter !== '' ? $coverageFilter : null]))) : '/' ?>">Zrusit format</a>
            <?php endif; ?>
          </div>
      <?php endif; ?>

      <?php if (!$items): ?>
        <p class="note">
          <?php if ($coverageFilter === 'full'): ?>
            Pre tento filter zatial nie su ziadne clanky s hotovymi produktovymi obrazkami.
          <?php elseif ($commercialOnly): ?>
            Pre tento filter zatial nie su ziadne clanky s odporucaniami produktov.
          <?php else: ?>
            Pre tento filter zatial nie su ziadne clanky.
          <?php endif; ?>
        </p>
        <?php if ($commercialOnly || $coverageFilter === 'full'): ?>
            <p class="muted">Skus vypnut filter <strong><?= $coverageFilter === 'full' ? 'Kompletne vybery' : 'S odporucaniami' ?></strong> alebo otvor inu temu, kde je uz hotovy komercny obsah.</p>
        <?php endif; ?>
      <?php else: ?>
        <p class="search-summary muted">
          Zobrazene clanky: <strong><?= esc((string) count($items)) ?></strong>
          <?php if ($commercialOnly): ?>
            <span class="search-summary-chip">iba s odporucaniami produktov</span>
          <?php endif; ?>
          <?php if ($coverageFilter === 'full'): ?>
            <span class="search-summary-chip">iba s hotovymi obrazkami</span>
          <?php endif; ?>
          <?php if ($categoryMeta !== null): ?>
            <span class="search-summary-chip">tema: <?= esc((string) $categoryMeta['title']) ?></span>
          <?php endif; ?>
          <?php if ($formatFilter !== '' && isset($formatLabels[$formatFilter])): ?>
            <span class="search-summary-chip">format: <?= esc((string) $formatLabels[$formatFilter]) ?></span>
          <?php endif; ?>
        </p>
        <div class="hub-grid article-teaser-grid">
          <?php foreach ($items as $item): ?>
            <?php
            $slug = (string) ($item['slug'] ?? '');
            $url = article_url($slug);
            $title = (string) ($item['title'] ?? '');
            $description = trim((string) ($item['description'] ?? ''));
            if ($description === '') {
                $description = interessa_article_teaser_description($slug);
            }
            $itemCategoryMeta = is_array($item['category_meta'] ?? null) ? $item['category_meta'] : null;
            $updatedDate = !empty($item['mtime']) ? date('d.m.Y', (int) $item['mtime']) : '';
            $formatLabel = (string) ($item['format_label'] ?? interessa_article_format_label($slug, $title));
            $commerceSummary = interessa_article_commerce_summary($slug);
            ?>
            <article class="hub-card article-teaser-card">
              <a href="<?= esc($url) ?>">
                <?= interessa_render_image((array) $item['image'], ['class' => 'hub-card-image', 'alt' => $title]) ?>
              </a>
              <div class="hub-card-body article-teaser-body">
                <div class="article-card-meta">
                  <span class="article-card-chip is-format"><?= esc($formatLabel) ?></span>
                  <?php if ($itemCategoryMeta !== null): ?>
                    <a class="hub-card-label" href="/clanky/?category=<?= esc((string) $itemCategoryMeta['slug']) ?>"><?= esc((string) $itemCategoryMeta['title']) ?></a>
                  <?php endif; ?>
                  <?php if ($updatedDate !== ''): ?><span class="article-card-date">Aktualizovane: <?= esc($updatedDate) ?></span><?php endif; ?>
                </div>
                <?= interessa_render_article_commerce_submeta($slug, 'compact') ?>
                <h3><a href="<?= esc($url) ?>"><?= esc($title) ?></a></h3>
                <?php if ($description !== ''): ?><p><?= esc($description) ?></p><?php endif; ?>
                <a class="card-link" href="<?= esc($url) ?>"><?= esc(interessa_article_cta_label($slug, $title)) ?></a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </article>
  </div>

  <?php include __DIR__ . '/../inc/sidebar.php'; ?>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>
