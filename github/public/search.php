<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$q = trim((string) ($_GET['q'] ?? ''));
$categoryFilter = normalize_category_slug((string) ($_GET['category'] ?? ''));
$categoryMeta = $categoryFilter !== '' ? category_meta($categoryFilter) : null;
$page_title = ($q !== '' ? interessa_text('Hľadať: ') . $q : interessa_text('Hľadať články')) . ' | Interesa';
$page_description = interessa_text('Vyhľadávanie článkov na Interesa.sk');
$page_canonical = '/search' . (($q !== '' || $categoryMeta !== null) ? '?' . http_build_query(array_filter(['q' => $q, 'category' => $categoryMeta['slug'] ?? null])) : '');
$page_robots = 'noindex,follow';
$page_og_type = 'website';
$page_schema = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'SearchResultsPage',
        'name' => $q !== '' ? interessa_text('Hľadať: ') . $q : interessa_text('Hľadať články'),
        'description' => $page_description,
        'url' => absolute_url('/search' . (($q !== '' || $categoryMeta !== null) ? '?' . http_build_query(array_filter(['q' => $q, 'category' => $categoryMeta['slug'] ?? null])) : '')),
    ],
];
include __DIR__ . '/inc/head.php';

function match_article_result(string $slug, array $meta, string $query): ?array {
    if ($query === '') {
        return null;
    }

    $title = (string) ($meta['title'] ?? $meta[0] ?? humanize_slug($slug));
    $description = (string) ($meta['description'] ?? $meta[1] ?? '');
    $haystack = $title . ' ' . $description;
    $needle = $query;
    $score = 0;

    if (interessa_contains($haystack, $needle)) {
        $score += 5;
    }

    $file = __DIR__ . '/content/articles/' . $slug . '.html';
    if (is_file($file)) {
        $text = strip_tags(interessa_fix_mojibake((string) file_get_contents($file)));
        if (interessa_contains($text, $needle)) {
            $score += 3;
        }
    }

    if ($score === 0) {
        return null;
    }

    $categorySlug = normalize_category_slug((string) ($meta['category'] ?? ''));
    $categoryMeta = $categorySlug !== '' ? category_meta($categorySlug) : null;

    return [
        'score' => $score,
        'slug' => $slug,
        'title' => $title,
        'description' => $description,
        'url' => article_url($slug),
        'image' => interessa_article_image_meta($slug, 'thumb', true),
        'category' => $categoryMeta,
    ];
}
?>
<section class="container two-col">
  <div class="content">
    <article class="card">
      <div class="section-head">
        <h1><?= interessa_text('Hľadať') ?></h1>
        <?php if ($q === ''): ?>
          <p class="meta">Zadaj výraz do vyhľadávania a nájdeš relevantné články, porovnania aj návody.</p>
        <?php else: ?>
          <p class="meta">Výsledky pre: <strong><?= esc($q) ?></strong><?php if ($categoryMeta !== null): ?> v téme <strong><?= esc((string) $categoryMeta['title']) ?></strong><?php endif; ?></p>
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
      foreach ($hits as $hit) {
          if (!is_array($hit['category'] ?? null)) {
              continue;
          }
          $slug = (string) ($hit['category']['slug'] ?? '');
          if ($slug === '') {
              continue;
          }
          if (!isset($searchCategories[$slug])) {
              $searchCategories[$slug] = [
                  'title' => (string) ($hit['category']['title'] ?? $slug),
                  'count' => 0,
              ];
          }
          $searchCategories[$slug]['count']++;
      }
      ?>

      <?php if ($q === ''): ?>
        <p class="note">Zadaj výraz do vyhľadávania.</p>
      <?php else: ?>
        <?php if ($searchCategories !== []): ?>
          <div class="filters-bar" aria-label="Filtre výsledkov">
            <a class="filter-chip<?= $categoryMeta === null ? ' is-active' : '' ?>" href="/search?q=<?= esc(rawurlencode($q)) ?>">Všetky témy</a>
            <?php foreach ($searchCategories as $slug => $row): ?>
              <?php $active = $categoryMeta !== null && $categoryMeta['slug'] === $slug; ?>
              <a class="filter-chip<?= $active ? ' is-active' : '' ?>" href="/search?q=<?= esc(rawurlencode($q)) ?>&amp;category=<?= esc($slug) ?>"><span class="filter-chip__icon" aria-hidden="true"><?= interessa_category_icon((string) $slug) ?></span><?= esc((string) $row['title']) ?> (<?= esc((string) $row['count']) ?>)</a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!$hits): ?>
          <p class="note">Nenašli sa žiadne články.</p>
        <?php else: ?>
          <p class="search-summary muted">Nájdené výsledky: <strong><?= esc((string) count($hits)) ?></strong></p>
          <div class="hub-grid article-teaser-grid">
            <?php foreach ($hits as $hit): ?>
              <article class="hub-card article-teaser-card">
                <a href="<?= esc((string) $hit['url']) ?>">
                  <?= interessa_render_image((array) $hit['image'], ['class' => 'hub-card-image', 'alt' => (string) $hit['title']]) ?>
                </a>
                <div class="hub-card-body article-teaser-body">
                  <?php if (is_array($hit['category'] ?? null)): ?>
                    <a class="hub-card-label" href="/search?q=<?= esc(rawurlencode($q)) ?>&amp;category=<?= esc((string) $hit['category']['slug']) ?>"><?= esc((string) $hit['category']['title']) ?></a>
                  <?php endif; ?>
                  <h3><a href="<?= esc((string) $hit['url']) ?>"><?= esc((string) $hit['title']) ?></a></h3>
                  <?php if ((string) ($hit['description'] ?? '') !== ''): ?><p><?= esc((string) $hit['description']) ?></p><?php endif; ?>
                  <a class="card-link" href="<?= esc((string) $hit['url']) ?>">Otvoriť článok</a>
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