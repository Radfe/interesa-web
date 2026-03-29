<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/category-hubs.php';
require_once __DIR__ . '/article-commerce.php';

$slug = $category_landing_slug ?? '';
$hub = interessa_category_hub($slug);
if ($slug === '' || $hub === null) {
    http_response_code(404);
    require dirname(__DIR__) . '/404.php';
    return;
}

$categoryHero = interessa_category_image_meta($slug, 'hero', true);
$categorySeo = interessa_category_seo_meta($slug);
$page_title = trim((string) ($categorySeo['meta_title'] ?? '')) !== '' ? (string) $categorySeo['meta_title'] : ($hub['title'] . ' | Interesa');
$page_description = trim((string) ($categorySeo['meta_description'] ?? '')) !== '' ? (string) $categorySeo['meta_description'] : (string) $hub['description'];
$page_canonical = category_url($slug);
$page_image = $categoryHero['src'] ?? asset('img/brand/og-default.svg');
$page_og_type = 'website';

$featuredGuides = is_array($hub['featured_guides'] ?? null) ? $hub['featured_guides'] : [];
$featuredSlugs = [];
$itemList = [];
$primaryGuideSlug = '';
$primaryCommercialGuideSlug = '';
foreach ($featuredGuides as $guide) {
    $guideSlug = trim((string) ($guide['slug'] ?? ''));
    if ($guideSlug === '') {
        continue;
    }

    if ($primaryGuideSlug === '') {
        $primaryGuideSlug = $guideSlug;
    }
    if ($primaryCommercialGuideSlug === '' && interessa_article_has_commerce($guideSlug)) {
        $primaryCommercialGuideSlug = $guideSlug;
    }

    $featuredSlugs[] = $guideSlug;
    $meta = article_meta($guideSlug);
    $itemList[] = [
        '@type' => 'ListItem',
        'position' => count($itemList) + 1,
        'url' => absolute_url(article_url($guideSlug)),
        'name' => $guide['title'] ?? $meta['title'],
    ];
}

$categoryArticles = array_values(category_articles($slug));
$articleCount = count($categoryArticles);
$featuredCount = count($featuredGuides);
$recentWindow = strtotime('-60 days');
$recentCount = 0;
$commercialCount = 0;
$fullCoverageCount = 0;
$formatCounts = [];
foreach ($categoryArticles as $articleItem) {
    $articleSlug = (string) ($articleItem['slug'] ?? '');
    $articleTitle = (string) ($articleItem['title'] ?? '');
    $formatLabel = interessa_article_format_label($articleSlug, $articleTitle);
    if (interessa_article_has_commerce($articleSlug)) {
        $commercialCount++;
    }
    if (interessa_article_has_full_packshot_coverage($articleSlug)) {
        $fullCoverageCount++;
    }
    $articleFile = dirname(__DIR__) . '/content/articles/' . $articleSlug . '.html';
    if (is_file($articleFile) && (int) @filemtime($articleFile) >= $recentWindow) {
        $recentCount++;
    }
    if ($formatLabel !== '') {
        $formatCounts[$formatLabel] = ($formatCounts[$formatLabel] ?? 0) + 1;
    }
}
arsort($formatCounts);
$topFormats = array_slice($formatCounts, 0, 4, true);
$extraArticles = array_values(array_filter($categoryArticles, static function (array $item) use ($featuredSlugs): bool {
    return !in_array((string) ($item['slug'] ?? ''), $featuredSlugs, true);
}));
$articleTopicKey = static function (array $item): string {
    $slugValue = strtolower((string) ($item['slug'] ?? ''));
    $descriptionValue = strtolower((string) ($item['description'] ?? ''));
    $haystack = $slugValue . ' ' . $descriptionValue;
    $map = [
        'horcik' => ['horcik', 'magnezium', 'magnesium'],
        'vitamin-d' => ['vitamin-d', 'vitamin d', ' d3', 'd3 ', 'd3+', 'k2'],
        'vitamin-c' => ['vitamin-c', 'vitamin c'],
        'zinok' => ['zinok', 'zinek', 'zinc'],
        'multivitamin' => ['multivitamin'],
        'omega-3' => ['omega-3', 'omega 3'],
        'beta-glukan' => ['beta-glukan', 'beta glukan'],
        'probiotika' => ['probiotika', 'probiotic'],
        'kolagen' => ['kolagen', 'collagen'],
        'kreatin' => ['kreatin', 'creatine', 'creapure', 'monohydrate', 'hcl'],
        'proteiny' => ['protein', 'whey', 'isolate', 'clear', 'vegan'],
    ];

    foreach ($map as $topicKey => $needles) {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return $topicKey;
            }
        }
    }

    $firstChunk = strtok($slugValue, '-');
    return is_string($firstChunk) ? trim($firstChunk) : '';
};
$extraArticlesTotal = count($extraArticles);
$maxExtraArticles = $articleCount > 24 ? 12 : 18;
$topicLimit = $articleCount > 36 ? 1 : 2;
$extraArticlePreview = [];
$extraPreviewTopicCounts = [];
$extraPreviewSeen = [];
foreach ($extraArticles as $item) {
    $itemSlug = (string) ($item['slug'] ?? '');
    if ($itemSlug === '') {
        continue;
    }

    $topicKey = $articleTopicKey($item);
    if ($topicKey !== '' && (($extraPreviewTopicCounts[$topicKey] ?? 0) >= $topicLimit)) {
        continue;
    }

    $extraArticlePreview[] = $item;
    $extraPreviewSeen[$itemSlug] = true;
    if ($topicKey !== '') {
        $extraPreviewTopicCounts[$topicKey] = ($extraPreviewTopicCounts[$topicKey] ?? 0) + 1;
    }

    if (count($extraArticlePreview) >= $maxExtraArticles) {
        break;
    }
}
if (count($extraArticlePreview) < min($extraArticlesTotal, $maxExtraArticles)) {
    foreach ($extraArticles as $item) {
        $itemSlug = (string) ($item['slug'] ?? '');
        if ($itemSlug === '' || isset($extraPreviewSeen[$itemSlug])) {
            continue;
        }

        $extraArticlePreview[] = $item;
        $extraPreviewSeen[$itemSlug] = true;
        if (count($extraArticlePreview) >= $maxExtraArticles) {
            break;
        }
    }
}
$extraArticles = $extraArticlePreview;
$hiddenExtraArticles = max(0, $extraArticlesTotal - count($extraArticles));
$hasDedicatedArticleImage = static function (?array $image, string $slug): bool {
    return is_array($image)
        && (string) ($image['kind'] ?? '') === 'article'
        && (string) ($image['entity'] ?? '') === $slug
        && (string) ($image['source_type'] ?? '') !== 'placeholder';
};
$renderArticleFallbackMedia = static function (string $href, string $slug, string $formatLabel, string $tagLabel): string {
    $visualLabel = trim($tagLabel) !== '' ? $tagLabel : 'Clanok';
    $secondary = trim($formatLabel) !== '' ? $formatLabel : 'Prakticky clanok';

    return '<a class="hub-card-visual-fallback" href="' . esc($href) . '">'
        . '<span class="hub-card-icon" aria-hidden="true">' . interessa_category_icon($slug) . '</span>'
        . '<span class="hub-card-fallback-copy">'
        . '<span class="hub-card-label">' . esc($visualLabel) . '</span>'
        . '<strong>' . esc($secondary) . '</strong>'
        . '</span>'
        . '</a>';
};
$readyArticles = [];
foreach ($categoryArticles as $item) {
    $itemSlug = (string) ($item['slug'] ?? '');
    $summary = interessa_article_commerce_summary($itemSlug);
    if ($itemSlug === '' || !is_array($summary) || (int) ($summary['count'] ?? 0) <= 0) {
        continue;
    }
    $item['_coverage_percent'] = interessa_shortlist_coverage_percent($summary);
    $item['_coverage_label'] = interessa_shortlist_coverage_label($summary);
    $readyArticles[] = $item;
}
usort($readyArticles, static function (array $a, array $b): int {
    $coverageCompare = ((int) ($b['_coverage_percent'] ?? 0)) <=> ((int) ($a['_coverage_percent'] ?? 0));
    if ($coverageCompare !== 0) {
        return $coverageCompare;
    }
    $aFile = dirname(__DIR__) . '/content/articles/' . (string) ($a['slug'] ?? '') . '.html';
    $bFile = dirname(__DIR__) . '/content/articles/' . (string) ($b['slug'] ?? '') . '.html';
    return ((int) @filemtime($bFile)) <=> ((int) @filemtime($aFile));
});
$readyArticles = array_slice($readyArticles, 0, 3);
$crossThemePaths = interessa_cross_theme_paths($slug);
$primaryCommercialGuideCoverage = $primaryCommercialGuideSlug !== ''
    ? interessa_article_commerce_coverage_state($primaryCommercialGuideSlug)
    : null;
$comparisonReadyArticles = [];
foreach ($categoryArticles as $item) {
    $itemSlug = (string) ($item['slug'] ?? '');
    if ($itemSlug === '' || !interessa_article_has_comparison_table($itemSlug)) {
        continue;
    }

    $summary = interessa_article_commerce_summary($itemSlug);
    $item['_coverage_percent'] = interessa_shortlist_coverage_percent($summary);
    $comparisonReadyArticles[] = $item;
}
usort($comparisonReadyArticles, static function (array $a, array $b): int {
    $coverageCompare = ((int) ($b['_coverage_percent'] ?? 0)) <=> ((int) ($a['_coverage_percent'] ?? 0));
    if ($coverageCompare !== 0) {
        return $coverageCompare;
    }
    $aFile = dirname(__DIR__) . '/content/articles/' . (string) ($a['slug'] ?? '') . '.html';
    $bFile = dirname(__DIR__) . '/content/articles/' . (string) ($b['slug'] ?? '') . '.html';
    return ((int) @filemtime($bFile)) <=> ((int) @filemtime($aFile));
});
$comparisonReadyArticles = array_slice($comparisonReadyArticles, 0, 2);
$primaryGuide = null;
$supportingGuides = [];
$topSectionSlugs = [];
foreach ($featuredGuides as $guide) {
    $guideSlug = trim((string) ($guide['slug'] ?? ''));
    if ($guideSlug === '' || isset($topSectionSlugs[$guideSlug])) {
        continue;
    }

    if ($primaryGuide === null) {
        $primaryGuide = $guide;
        $topSectionSlugs[$guideSlug] = true;
        continue;
    }

    if (count($supportingGuides) >= 3) {
        break;
    }

    $supportingGuides[] = $guide;
    $topSectionSlugs[$guideSlug] = true;
}
$quickDecisionLimit = ($primaryGuideSlug !== '' && $primaryGuideSlug === $primaryCommercialGuideSlug) ? 2 : 3;
$quickDecisionEntries = [];
$quickDecisionSeen = $topSectionSlugs;
foreach ($comparisonReadyArticles as $item) {
    $itemSlug = (string) ($item['slug'] ?? '');
    if ($itemSlug === '' || isset($quickDecisionSeen[$itemSlug])) {
        continue;
    }

    $item['_decision_label'] = 'Porovnanie';
    $item['_decision_cta'] = 'Otvorit porovnanie';
    $quickDecisionEntries[] = $item;
    $quickDecisionSeen[$itemSlug] = true;
    if (count($quickDecisionEntries) >= $quickDecisionLimit) {
        break;
    }
}
if (count($quickDecisionEntries) < $quickDecisionLimit) {
    foreach ($readyArticles as $item) {
        $itemSlug = (string) ($item['slug'] ?? '');
        if ($itemSlug === '' || isset($quickDecisionSeen[$itemSlug])) {
            continue;
        }

        $item['_decision_label'] = 'Odporucany vyber';
        $item['_decision_cta'] = interessa_article_cta_label($itemSlug, (string) ($item['title'] ?? ''));
        $quickDecisionEntries[] = $item;
        $quickDecisionSeen[$itemSlug] = true;
        if (count($quickDecisionEntries) >= $quickDecisionLimit) {
            break;
        }
    }
}
$crossThemeIsHelpful = $crossThemePaths !== [] && (count($supportingGuides) < 3 || count($extraArticles) > 0);

$page_schema = [
    breadcrumb_schema([
        ['name' => 'Domov', 'url' => '/'],
        ['name' => 'Kategorie', 'url' => '/kategorie'],
        ['name' => $hub['title'], 'url' => $page_canonical],
    ]),
    [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $hub['title'],
        'description' => $page_description,
        'url' => absolute_url($page_canonical),
    ],
];

if ($itemList !== []) {
    $page_schema[] = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Klucove clanky: ' . $hub['title'],
        'itemListElement' => $itemList,
    ];
}

include dirname(__DIR__) . '/inc/head.php';
?>
<section class="container two-col">
  <div class="content">
    <article class="card hub-hero-card">
      <?php if ($categoryHero !== null): ?>
        <figure class="hub-hero-media category-asset-frame category-asset-frame--hero">
          <?= interessa_render_image($categoryHero, ['class' => 'hub-card-image category-asset-image category-hero-image', 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
        </figure>
      <?php endif; ?>
      <div class="hub-heading-row">
        <span class="hub-icon-badge" aria-hidden="true"><?= interessa_category_icon($slug) ?></span>
        <h1><?= esc($hub['title']) ?></h1>
      </div>
      <p class="lead"><?= esc($hub['intro']) ?></p>
      <p class="meta">
        <?= esc((string) $articleCount) ?> <?= esc(interessa_pluralize_slovak($articleCount, 'clanok', 'clanky', 'clankov')) ?> v teme
        <?php if ($commercialCount > 0): ?>
          • vyber produktov v <?= esc((string) $commercialCount) ?> <?= esc(interessa_pluralize_slovak($commercialCount, 'clanku', 'clankoch', 'clankoch')) ?>
        <?php endif; ?>
      </p>
      <div class="hero-cta">
        <?php if ($primaryGuideSlug !== ''): ?>
          <a class="btn btn-primary" href="<?= esc(article_url($primaryGuideSlug)) ?>">Zacat hlavnym clankom</a>
        <?php endif; ?>
        <?php if ($primaryCommercialGuideSlug !== ''): ?>
          <a class="btn btn-ghost" href="<?= esc(article_url($primaryCommercialGuideSlug)) ?>">
            <?= esc($primaryCommercialGuideCoverage === 'full' ? 'Prejst na porovnanie a vyber' : 'Prejst na odporucane produkty') ?>
          </a>
        <?php else: ?>
          <a class="btn btn-ghost" href="/clanky/?category=<?= esc($slug) ?>&amp;commercial=1">Clanky s odporucaniami</a>
        <?php endif; ?>
      </div>
    </article>

    <?php if ($primaryGuide !== null): ?>
      <section class="card">
        <div class="section-head">
          <h2>Zacni tu</h2>
          <p class="meta">Najrychlejsia cesta, ako sa v tejto teme zorientovat a prejst k spravnemu vyberu.</p>
        </div>
        <?php
        $guideSlug = trim((string) ($primaryGuide['slug'] ?? ''));
        $meta = article_meta($guideSlug);
        $title = trim((string) ($primaryGuide['title'] ?? $meta['title']));
        $description = interessa_article_card_description($guideSlug, trim((string) ($primaryGuide['description'] ?? $meta['description'])), 24);
        $label = trim((string) ($primaryGuide['label'] ?? 'Hlavny guide'));
        $guideImage = interessa_article_image_meta($guideSlug, 'thumb', true);
        $guideFile = dirname(__DIR__) . '/content/articles/' . $guideSlug . '.html';
        $guideDate = is_file($guideFile) ? date('d.m.Y', (int) @filemtime($guideFile)) : '';
        $formatLabel = interessa_article_format_label($guideSlug, $title);
        $hasDedicatedImage = $hasDedicatedArticleImage($guideImage, $guideSlug);
        ?>
        <article class="hub-card">
          <?php if ($hasDedicatedImage): ?>
            <a href="<?= esc(article_url($guideSlug)) ?>">
              <?= interessa_render_image($guideImage, ['class' => 'hub-card-image', 'alt' => $title]) ?>
            </a>
          <?php else: ?>
            <?= $renderArticleFallbackMedia(article_url($guideSlug), $slug, $formatLabel, $label) ?>
          <?php endif; ?>
          <div class="hub-card-body">
            <div class="article-card-meta">
              <span class="article-card-chip is-format"><?= esc($formatLabel) ?></span>
              <span class="hub-card-label"><?= esc($label) ?></span>
              <?php if ($guideDate !== ''): ?><span class="article-card-date"><?= esc($guideDate) ?></span><?php endif; ?>
            </div>
            <?= interessa_render_article_commerce_submeta($guideSlug, 'compact') ?>
            <h3><a href="<?= esc(article_url($guideSlug)) ?>"><?= esc($title) ?></a></h3>
            <?php if ($description !== ''): ?><p><?= esc($description) ?></p><?php endif; ?>
            <a class="btn btn-primary" href="<?= esc(article_url($guideSlug)) ?>"><?= esc(interessa_article_cta_label($guideSlug, $title)) ?></a>
          </div>
        </article>
      </section>
    <?php elseif ($featuredGuides === []): ?>
      <section class="card">
        <p class="note"><?= esc((string) ($hub['empty_message'] ?? 'Tato kategoria sa este doplna. Zatial tu coskoro pribudnu odporucane clanky.')) ?></p>
      </section>
    <?php endif; ?>

    <?php if ($quickDecisionEntries !== []): ?>
      <section class="card">
        <div class="section-head">
          <h2>Rychle rozhodnutie</h2>
          <p class="meta">Ak chces ist co najrychlejsie k porovnaniu alebo odporucanym produktom, zacni jednym z tychto clankov.</p>
        </div>
        <div class="hub-grid article-related-grid">
          <?php foreach ($quickDecisionEntries as $item): ?>
            <?php
            $itemSlug = (string) ($item['slug'] ?? '');
            $itemTitle = function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake((string) ($item['title'] ?? '')) : (string) ($item['title'] ?? '');
            $itemDescription = interessa_article_card_description($itemSlug, trim((string) ($item['description'] ?? '')), 18);
            $itemImage = interessa_article_image_meta($itemSlug, 'thumb', true);
            $itemFile = dirname(__DIR__) . '/content/articles/' . $itemSlug . '.html';
            $itemDate = is_file($itemFile) ? date('d.m.Y', (int) @filemtime($itemFile)) : '';
            $formatLabel = interessa_article_format_label($itemSlug, $itemTitle);
            $hasDedicatedImage = $hasDedicatedArticleImage($itemImage, $itemSlug);
            $decisionLabel = trim((string) ($item['_decision_label'] ?? 'Rychla volba'));
            $decisionCta = trim((string) ($item['_decision_cta'] ?? 'Otvorit clanok'));
            ?>
            <article class="hub-card article-teaser-card">
              <?php if ($hasDedicatedImage): ?>
                <a href="<?= esc(article_url($itemSlug)) ?>">
                  <?= interessa_render_image($itemImage, ['class' => 'hub-card-image', 'alt' => $itemTitle]) ?>
                </a>
              <?php else: ?>
                <?= $renderArticleFallbackMedia(article_url($itemSlug), $slug, $formatLabel, $decisionLabel) ?>
              <?php endif; ?>
              <div class="hub-card-body article-teaser-body">
                <div class="article-card-meta">
                  <span class="article-card-chip is-format"><?= esc($formatLabel) ?></span>
                  <span class="hub-card-label"><?= esc($decisionLabel) ?></span>
                  <?php if ($itemDate !== ''): ?><span class="article-card-date"><?= esc($itemDate) ?></span><?php endif; ?>
                </div>
                <?= interessa_render_article_commerce_submeta($itemSlug, 'compact') ?>
                <h3><a href="<?= esc(article_url($itemSlug)) ?>"><?= esc($itemTitle) ?></a></h3>
                <?php if ($itemDescription !== ''): ?><p><?= esc($itemDescription) ?></p><?php endif; ?>
                <a class="card-link" href="<?= esc(article_url($itemSlug)) ?>"><?= esc($decisionCta) ?></a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($supportingGuides !== []): ?>
      <section class="card">
        <div class="section-head">
          <h2>Dalsie dobre starty v teme</h2>
          <p class="meta">Doplnujuce cesty pre iny ciel alebo hlbsie zorientovanie sa v teme.</p>
        </div>
        <div class="hub-grid article-related-grid">
          <?php foreach ($supportingGuides as $guide): ?>
            <?php
            $guideSlug = trim((string) ($guide['slug'] ?? ''));
            if ($guideSlug === '') {
                continue;
            }
            $meta = article_meta($guideSlug);
            $title = trim((string) ($guide['title'] ?? $meta['title']));
            $description = interessa_article_card_description($guideSlug, trim((string) ($guide['description'] ?? $meta['description'])), 20);
            $label = trim((string) ($guide['label'] ?? 'Start'));
            $formatLabel = interessa_article_format_label($guideSlug, $title);
            ?>
            <article class="hub-card article-teaser-card">
              <div class="hub-card-body article-teaser-body">
                <div class="article-card-meta">
                  <span class="article-card-chip is-format"><?= esc($formatLabel) ?></span>
                  <span class="hub-card-label"><?= esc($label) ?></span>
                </div>
                <?= interessa_render_article_commerce_submeta($guideSlug, 'compact') ?>
                <h3><a href="<?= esc(article_url($guideSlug)) ?>"><?= esc($title) ?></a></h3>
                <?php if ($description !== ''): ?><p><?= esc($description) ?></p><?php endif; ?>
                <a class="card-link" href="<?= esc(article_url($guideSlug)) ?>"><?= esc(interessa_article_cta_label($guideSlug, $title)) ?></a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($crossThemeIsHelpful): ?>
      <section class="card">
        <div class="section-head">
          <h2>Suvisiace temy</h2>
          <p class="meta">Dalsi krok, ked uz mas tuto temu zorientovanu a chces plynulo pokracovat dalej.</p>
        </div>
        <ul class="article-list">
          <?php foreach ($crossThemePaths as $path): ?>
            <li>
              <a href="<?= esc((string) ($path['href'] ?? '/')) ?>"><?= esc((string) ($path['title'] ?? 'Dalsia tema')) ?></a>
              <?php if (trim((string) ($path['description'] ?? '')) !== ''): ?> <span class="meta"><?= esc((string) ($path['description'] ?? '')) ?></span><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>

    <?php if ($extraArticles !== []): ?>
      <section class="card">
        <div class="section-head">
          <h2>Dalsie clanky v teme</h2>
          <p class="meta">
            Doplnujuce clanky pre hlbsie prestudovanie temy.
            <?php if ($hiddenExtraArticles > 0): ?>
              Zobrazujeme len vyber <strong><?= esc((string) count($extraArticles)) ?></strong> z <strong><?= esc((string) $extraArticlesTotal) ?></strong>, aby listing nebol zahlteny opakujucimi sa variaciami.
            <?php endif; ?>
          </p>
        </div>
        <ul class="article-list">
          <?php foreach ($extraArticles as $item): ?>
            <?php
            $itemSlug = (string) ($item['slug'] ?? '');
            $itemTitle = function_exists('interessa_fix_mojibake') ? interessa_fix_mojibake((string) ($item['title'] ?? '')) : (string) ($item['title'] ?? '');
            $itemDescription = interessa_article_card_description($itemSlug, trim((string) ($item['description'] ?? '')), 16);
            ?>
            <li>
              <a href="<?= esc(article_url($itemSlug)) ?>"><?= esc($itemTitle) ?></a>
              <?php if ($itemDescription !== ''): ?> <span class="meta"><?= esc($itemDescription) ?></span><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <?php if ($hiddenExtraArticles > 0): ?>
          <p class="meta" style="margin-top:1rem">Ak chces vidiet vsetky clanky v tejto teme, otvor <a href="/clanky/?category=<?= esc($slug) ?>">kompletny archiv temy</a>.</p>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </div>

  <?php $sidebarContextCategorySlug = $slug; ?>
  <?php include dirname(__DIR__) . '/inc/sidebar.php'; ?>
</section>
<?php include dirname(__DIR__) . '/inc/footer.php'; ?>
