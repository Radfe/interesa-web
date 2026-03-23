<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/article-commerce.php';

$q = trim((string) ($_GET['q'] ?? ''));
$categoryFilter = normalize_category_slug((string) ($_GET['category'] ?? ''));
$formatFilter = trim((string) ($_GET['format'] ?? ''));
$commercialOnly = (string) ($_GET['commercial'] ?? '') === '1';
$coverageFilter = trim((string) ($_GET['coverage'] ?? ''));
$categoryMeta = $categoryFilter !== '' ? category_meta($categoryFilter) : null;
$page_title = ($q !== '' ? interessa_text('Hladat: ') . $q : interessa_text('Hladat clanky')) . ' | Interesa';
$page_description = interessa_text('Vyhladavanie clankov na Interesa.sk');
$page_canonical = '/search' . (($q !== '' || $categoryMeta !== null) ? '?' . http_build_query(array_filter(['q' => $q, 'category' => $categoryMeta['slug'] ?? null])) : '');
$page_robots = 'noindex,follow';
$page_og_type = 'website';
$page_schema = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'SearchResultsPage',
        'name' => $q !== '' ? interessa_text('Hladat: ') . $q : interessa_text('Hladat clanky'),
        'description' => $page_description,
        'url' => absolute_url('/search' . (($q !== '' || $categoryMeta !== null || $formatFilter !== '' || $commercialOnly || $coverageFilter !== '') ? '?' . http_build_query(array_filter(['q' => $q, 'category' => $categoryMeta['slug'] ?? null, 'format' => $formatFilter !== '' ? $formatFilter : null, 'commercial' => $commercialOnly ? '1' : null, 'coverage' => $coverageFilter !== '' ? $coverageFilter : null])) : '')),
    ],
];
include __DIR__ . '/inc/head.php';

function interessa_search_normalize(string $value): string {
    $value = interessa_fix_mojibake($value);
    $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    $value = strtr($value, [
        'á' => 'a', 'ä' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e',
        'í' => 'i', 'ĺ' => 'l', 'ľ' => 'l', 'ň' => 'n', 'ó' => 'o', 'ô' => 'o',
        'ŕ' => 'r', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ú' => 'u', 'ů' => 'u',
        'ý' => 'y', 'ž' => 'z',
    ]);
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $normalized = $ascii !== false ? strtolower($ascii) : strtolower($value);
    $normalized = preg_replace('~[^a-z0-9]+~', ' ', $normalized) ?? $normalized;
    return trim(preg_replace('~\s+~', ' ', $normalized) ?? $normalized);
}

function match_article_result(string $slug, array $meta, string $query): ?array {
    if ($query === '') {
        return null;
    }

    $title = (string) ($meta['title'] ?? $meta[0] ?? humanize_slug($slug));
    $description = trim((string) ($meta['description'] ?? $meta[1] ?? ''));
    if ($description === '') {
        $description = interessa_article_teaser_description($slug);
    }
    $haystack = interessa_search_normalize($title . ' ' . $description);
    $needle = interessa_search_normalize($query);
    $score = 0;

    if (stripos($haystack, $needle) !== false) {
        $score += 5;
    }

    $file = __DIR__ . '/content/articles/' . $slug . '.html';
    if (is_file($file)) {
        $text = interessa_search_normalize(strip_tags(interessa_fix_mojibake((string) file_get_contents($file))));
        if (stripos($text, $needle) !== false) {
            $score += 3;
        }
    }

    if ($score === 0) {
        return null;
    }

    $categorySlug = normalize_category_slug((string) ($meta['category'] ?? ''));
    $resolvedCategoryMeta = $categorySlug !== '' ? category_meta($categorySlug) : null;

    return [
        'score' => $score,
        'slug' => $slug,
        'title' => interessa_fix_mojibake($title),
        'description' => interessa_fix_mojibake($description),
        'url' => article_url($slug),
        'image' => interessa_article_image_meta($slug, 'thumb', true),
        'category' => $resolvedCategoryMeta,
        'format_slug' => interessa_article_format_slug($slug, $title),
        'format_label' => interessa_article_format_label($slug, $title),
        'has_commerce' => interessa_article_has_commerce($slug),
        'coverage_state' => interessa_article_commerce_coverage_state($slug),
        'mtime' => is_file($file) ? (int) @filemtime($file) : 0,
    ];
}
?>
<section class="container two-col">
  <div class="content">
    <article class="card">
      <div class="section-head">
        <h1><?= interessa_text('Hladat') ?></h1>
        <?php if ($q === ''): ?>
          <p class="meta">Zadaj vyraz do vyhladavania a najdes relevantne clanky, porovnania aj navody.</p>
        <?php else: ?>
          <p class="meta">Vysledky pre: <strong><?= esc($q) ?></strong><?php if ($categoryMeta !== null): ?> v teme <strong><?= esc((string) $categoryMeta['title']) ?></strong><?php endif; ?></p>
        <?php endif; ?>
      </div>

      <?php
      $hits = [];
      if ($q !== '') {
          foreach (indexed_articles() as $item) {
              $slug = (string) ($item['slug'] ?? '');
              if ($slug === '') {
                  continue;
              }

              $match = match_article_result($slug, $item, $q);
              if ($match !== null) {
                  $hits[] = $match;
              }
          }
          if ($categoryMeta !== null) {
              $hits = array_values(array_filter($hits, static function (array $hit) use ($categoryMeta): bool {
                  return is_array($hit['category'] ?? null) && (string) ($hit['category']['slug'] ?? '') === (string) $categoryMeta['slug'];
              }));
          }
          usort($hits, static fn(array $a, array $b): int => ((int) $b['score']) <=> ((int) $a['score']));
      }

      $searchCategories = [];
      $topFormats = [];
      $formatLabels = interessa_article_format_map();
      foreach ($hits as $hit) {
          $formatKey = (string) ($hit['format_slug'] ?? interessa_article_format_slug((string) ($hit['slug'] ?? ''), (string) ($hit['title'] ?? '')));
          if (!is_array($hit['category'] ?? null)) {
              if ($formatKey !== '') {
                  $topFormats[$formatKey] = ($topFormats[$formatKey] ?? 0) + 1;
              }
              continue;
          }
          $slug = (string) ($hit['category']['slug'] ?? '');
          if ($slug === '') {
              if ($formatKey !== '') {
                  $topFormats[$formatKey] = ($topFormats[$formatKey] ?? 0) + 1;
              }
              continue;
          }
          if (!isset($searchCategories[$slug])) {
              $searchCategories[$slug] = [
                  'title' => (string) ($hit['category']['title'] ?? $slug),
                  'count' => 0,
              ];
          }
          $searchCategories[$slug]['count']++;
          if ($formatKey !== '') {
              $topFormats[$formatKey] = ($topFormats[$formatKey] ?? 0) + 1;
          }
      }
      if ($formatFilter !== '' && isset($formatLabels[$formatFilter])) {
          $hits = array_values(array_filter($hits, static function (array $hit) use ($formatFilter): bool {
              $formatSlug = (string) ($hit['format_slug'] ?? interessa_article_format_slug((string) ($hit['slug'] ?? ''), (string) ($hit['title'] ?? '')));
              return $formatSlug === $formatFilter;
          }));
      }
      $commercialCountInScope = count(array_filter($hits, static function (array $hit): bool {
          return !empty($hit['has_commerce']);
      }));
      if ($commercialOnly) {
          $hits = array_values(array_filter($hits, static function (array $hit): bool {
              return !empty($hit['has_commerce']);
          }));
      }
      if ($coverageFilter === 'full') {
          $hits = array_values(array_filter($hits, static function (array $hit): bool {
              return (string) ($hit['coverage_state'] ?? '') === 'full';
          }));
      }
      $fullCoverageCountInScope = count(array_filter($hits, static function (array $hit): bool {
          return (string) ($hit['coverage_state'] ?? '') === 'full';
      }));
      arsort($topFormats);
      $topFormats = array_slice($topFormats, 0, 4, true);
      ?>

      <?php if ($q === ''): ?>
        <p class="note">Zadaj vyraz do vyhladavania.</p>
      <?php else: ?>
        <?php if ($searchCategories !== []): ?>
          <div class="filters-bar" aria-label="Filtre vysledkov">
            <?php $baseQuery = array_filter(['q' => $q, 'format' => $formatFilter !== '' ? $formatFilter : null, 'commercial' => $commercialOnly ? '1' : null, 'coverage' => $coverageFilter !== '' ? $coverageFilter : null]); ?>
            <a class="filter-chip<?= $categoryMeta === null ? ' is-active' : '' ?>" href="/search?<?= esc(http_build_query($baseQuery)) ?>">Vsetky temy</a>
            <?php foreach ($searchCategories as $slug => $row): ?>
              <?php $active = $categoryMeta !== null && $categoryMeta['slug'] === $slug; ?>
              <?php $query = array_filter(['q' => $q, 'category' => $slug, 'format' => $formatFilter !== '' ? $formatFilter : null, 'commercial' => $commercialOnly ? '1' : null, 'coverage' => $coverageFilter !== '' ? $coverageFilter : null]); ?>
              <a class="filter-chip<?= $active ? ' is-active' : '' ?>" href="/search?<?= esc(http_build_query($query)) ?>"><span class="filter-chip__icon" aria-hidden="true"><?= interessa_category_icon((string) $slug) ?></span><?= esc((string) $row['title']) ?> (<?= esc((string) $row['count']) ?>)</a>
            <?php endforeach; ?>
            <?php $commercialQuery = array_filter(['q' => $q, 'category' => $categoryMeta['slug'] ?? null, 'format' => $formatFilter !== '' ? $formatFilter : null, 'commercial' => '1', 'coverage' => $coverageFilter !== '' ? $coverageFilter : null]); ?>
            <?php $commercialResetQuery = array_filter(['q' => $q, 'category' => $categoryMeta['slug'] ?? null, 'format' => $formatFilter !== '' ? $formatFilter : null, 'coverage' => $coverageFilter !== '' ? $coverageFilter : null]); ?>
            <a class="filter-chip<?= $commercialOnly ? ' is-active' : ' is-muted' ?>" href="/search?<?= esc(http_build_query($commercialOnly ? $commercialResetQuery : $commercialQuery)) ?>">S odporucaniami (<?= esc((string) $commercialCountInScope) ?>)</a>
            <?php $coverageQuery = array_filter(['q' => $q, 'category' => $categoryMeta['slug'] ?? null, 'format' => $formatFilter !== '' ? $formatFilter : null, 'commercial' => $commercialOnly ? '1' : null, 'coverage' => 'full']); ?>
            <?php $coverageResetQuery = array_filter(['q' => $q, 'category' => $categoryMeta['slug'] ?? null, 'format' => $formatFilter !== '' ? $formatFilter : null, 'commercial' => $commercialOnly ? '1' : null]); ?>
            <a class="filter-chip<?= $coverageFilter === 'full' ? ' is-active' : ' is-muted' ?>" href="/search?<?= esc(http_build_query($coverageFilter === 'full' ? $coverageResetQuery : $coverageQuery)) ?>">S realnymi fotkami (<?= esc((string) $fullCoverageCountInScope) ?>)</a>
          </div>
        <?php endif; ?>

        <?php if ($topFormats !== []): ?>
          <div class="filters-bar archive-format-bar" aria-label="Formaty vysledkov">
            <?php foreach ($topFormats as $formatSlug => $count): ?>
              <?php $isActiveFormat = $formatFilter !== '' && $formatFilter === (string) $formatSlug; ?>
              <?php $query = array_filter(['q' => $q, 'category' => $categoryMeta['slug'] ?? null, 'format' => $formatSlug, 'commercial' => $commercialOnly ? '1' : null, 'coverage' => $coverageFilter !== '' ? $coverageFilter : null]); ?>
              <a class="filter-chip<?= $isActiveFormat ? ' is-active' : ' is-muted' ?>" href="/search?<?= esc(http_build_query($query)) ?>">
                <span class="article-card-chip is-format"><?= esc((string) ($formatLabels[(string) $formatSlug] ?? humanize_slug((string) $formatSlug))) ?></span>
                <?= esc((string) $count) ?>
              </a>
            <?php endforeach; ?>
            <?php if ($formatFilter !== ''): ?>
              <a class="filter-chip" href="/search?<?= esc(http_build_query(array_filter(['q' => $q, 'category' => $categoryMeta['slug'] ?? null, 'commercial' => $commercialOnly ? '1' : null, 'coverage' => $coverageFilter !== '' ? $coverageFilter : null]))) ?>">Zrusit format</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if (!$hits): ?>
          <div class="empty-state">
            <p class="note">
              <?php if ($coverageFilter === 'full'): ?>
                Nenasli sa ziadne clanky s realnymi fotkami produktov.
              <?php elseif ($commercialOnly): ?>
                Nenasli sa ziadne clanky s odporucaniami produktov.
              <?php else: ?>
                Nenasli sa ziadne clanky.
              <?php endif; ?>
            </p>
            <p class="muted">
              <?php if ($commercialOnly): ?>
                Skus vypnut filter <strong>S odporucaniami</strong>, zmenit temu alebo otvorit inu hlavnu oblast webu.
              <?php else: ?>
                Skus vseobecnejsi vyraz alebo otvor niektoru z hlavnych tem webu.
              <?php endif; ?>
            </p>
            <div class="hero-cta">
              <a class="btn btn-ghost" href="/kategorie/proteiny">Zdrave proteiny</a>
              <a class="btn btn-ghost" href="/kategorie/mineraly">Vitaminy a mineraly</a>
              <a class="btn btn-ghost" href="/clanky/">Vsetky clanky</a>
            </div>
          </div>
        <?php else: ?>
          <p class="search-summary muted">
            Najdene vysledky: <strong><?= esc((string) count($hits)) ?></strong>
            <?php if ($commercialOnly): ?>
              <span class="search-summary-chip">iba s odporucaniami produktov</span>
            <?php endif; ?>
            <?php if ($coverageFilter === 'full'): ?>
              <span class="search-summary-chip">iba s realnymi fotkami</span>
            <?php endif; ?>
            <?php if ($categoryMeta !== null): ?>
              <span class="search-summary-chip">tema: <?= esc((string) $categoryMeta['title']) ?></span>
            <?php endif; ?>
            <?php if ($formatFilter !== '' && isset($formatLabels[$formatFilter])): ?>
              <span class="search-summary-chip">format: <?= esc((string) $formatLabels[$formatFilter]) ?></span>
            <?php endif; ?>
          </p>
          <div class="hub-grid article-teaser-grid">
            <?php foreach ($hits as $hit): ?>
              <?php $updatedDate = !empty($hit['mtime']) ? date('d.m.Y', (int) $hit['mtime']) : ''; ?>
              <?php $commerceSummary = interessa_article_commerce_summary((string) ($hit['slug'] ?? '')); ?>
              <article class="hub-card article-teaser-card">
                <a href="<?= esc((string) $hit['url']) ?>">
                  <?= interessa_render_image((array) $hit['image'], ['class' => 'hub-card-image', 'alt' => (string) $hit['title']]) ?>
                </a>
                <div class="hub-card-body article-teaser-body">
                  <div class="article-card-meta">
                    <span class="article-card-chip is-format"><?= esc((string) ($hit['format_label'] ?? 'Clanok')) ?></span>
                    <?php if (is_array($hit['category'] ?? null)): ?>
                      <a class="hub-card-label" href="/search?q=<?= esc(rawurlencode($q)) ?>&amp;category=<?= esc((string) $hit['category']['slug']) ?>"><?= esc((string) $hit['category']['title']) ?></a>
                    <?php endif; ?>
                    <?php if ($updatedDate !== ''): ?><span class="article-card-date">Aktualizovane: <?= esc($updatedDate) ?></span><?php endif; ?>
                  </div>
                  <?= interessa_render_article_commerce_submeta((string) ($hit['slug'] ?? ''), 'compact') ?>
                  <h3><a href="<?= esc((string) $hit['url']) ?>"><?= esc((string) $hit['title']) ?></a></h3>
                  <?php if ((string) ($hit['description'] ?? '') !== ''): ?><p><?= esc((string) $hit['description']) ?></p><?php endif; ?>
                  <a class="card-link" href="<?= esc((string) $hit['url']) ?>"><?= esc(interessa_article_cta_label((string) ($hit['slug'] ?? ''), (string) ($hit['title'] ?? ''))) ?></a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </article>
  </div>

  <?php include __DIR__ . '/inc/sidebar.php'; ?>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>
