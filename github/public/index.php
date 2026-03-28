<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/category-hubs.php';
require_once __DIR__ . '/inc/article-commerce.php';

$page_title = 'Interesa.sk - porovnania doplnkov vyzivy, proteinov a vitaminov';
$page_description = 'Prakticke porovnania, nakupne navody a odporucania pre proteiny, vitaminy, mineraly a dalsie doplnky vyzivy.';
$page_canonical = '/';
$brandOgImage = interessa_brand_image_meta('og-default', true);
$page_image = (string) ($brandOgImage['src'] ?? asset('img/brand/og-default.svg'));
$page_og_type = 'website';
$page_styles = [asset('css/home-b12.css')];
$page_schema = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'Interesa',
        'url' => absolute_url('/'),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => absolute_url('/search?q={search_term_string}'),
            'query-input' => 'required name=search_term_string',
        ],
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => 'Interesa.sk',
        'description' => $page_description,
        'url' => absolute_url('/'),
    ],
];

$homeHeroImage = interessa_build_image_meta(
    interessa_collect_asset_candidates(['img/hero/hero-1']),
    [
        'alt' => 'Zdrava vyziva a doplnky v jemnom editorial style',
        'sizes' => '(min-width: 1200px) 540px, 100vw',
        'loading' => 'eager',
        'fetchpriority' => 'high',
    ],
    'article',
    true
);

$featuredCategorySlugs = ['proteiny', 'vyziva', 'mineraly', 'sila', 'klby-koza', 'imunita'];
$featuredGuideSlugs = [
    'najlepsie-proteiny-2026',
    'kreatin-porovnanie',
    'kolagen-recenzia',
    'veganske-proteiny-top-vyber-2026',
];

$featuredCategories = [];
foreach ($featuredCategorySlugs as $slug) {
    $meta = category_meta($slug);
    $hub = interessa_category_hub($slug);
    if ($meta === null || $hub === null) {
        continue;
    }

    $featuredCategories[] = [
        'slug' => $slug,
        'title' => $meta['title'],
        'description' => $hub['description'] ?? $meta['description'],
        'focus_point' => trim((string) (($hub['focus_points'][0] ?? '') ?: ($hub['intro'] ?? ''))),
        'theme_label' => match ($slug) {
            'proteiny' => 'Start pre vyber proteinu',
            'vyziva' => 'Kazdodenny zaklad a rutina',
            'mineraly' => 'Mikroziviny a forma latok',
            'sila' => 'Vykon, kreatin a trening',
            'klby-koza' => 'Kolagen, klby a regeneracia',
            'imunita' => 'Imunita a obranyschopnost',
            default => 'Hlavna tema',
        },
        'image' => interessa_category_image_meta($slug, 'hero', true),
        'count' => count(category_articles($slug)),
        'featured_count' => count((array) ($hub['featured_guides'] ?? [])),
        'commercial_count' => count(array_filter(array_values(category_articles($slug)), static function (array $item): bool {
            return interessa_article_has_commerce((string) ($item['slug'] ?? ''));
        })),
        'full_coverage_count' => count(array_filter(array_values(category_articles($slug)), static function (array $item): bool {
            return interessa_article_has_full_packshot_coverage((string) ($item['slug'] ?? ''));
        })),
    ];
}

$homeGoalSlugs = ['chudnutie', 'sila', 'imunita', 'klby-koza'];
$homeGoalCards = [];
foreach ($homeGoalSlugs as $slug) {
    $meta = category_meta($slug);
    $hub = interessa_category_hub($slug);
    if ($meta === null || $hub === null) {
        continue;
    }

    $primaryGuide = null;
    foreach ((array) ($hub['featured_guides'] ?? []) as $guide) {
        $guideSlug = trim((string) ($guide['slug'] ?? ''));
        if ($guideSlug === '') {
            continue;
        }

        $guideMeta = article_meta($guideSlug);
        $primaryGuide = [
            'slug' => $guideSlug,
            'label' => trim((string) ($guide['label'] ?? 'Start')),
            'title' => trim((string) ($guideMeta['title'] ?? humanize_slug($guideSlug))),
        ];
        break;
    }

    $homeGoalCards[] = [
        'slug' => $slug,
        'title' => $meta['title'],
        'description' => trim((string) ($hub['intro'] ?? $meta['description'] ?? '')),
        'image' => interessa_category_image_meta($slug, 'hero', true),
        'primary_guide' => $primaryGuide,
    ];
}

$homeQuickStartPaths = [
    [
        'eyebrow' => 'Najrychlejsi start',
        'title' => 'Chcem hned porovnat proteiny podla ciela',
        'description' => 'Dobry start, ak sa chces rychlo zorientovat a hned vidiet, ktore typy proteinov davaju zmysel pre tvoj ciel.',
        'href' => article_url('najlepsie-proteiny-2026'),
        'cta' => 'Otvorit vyber',
    ],
    [
        'eyebrow' => 'Kazdodenny zaklad',
        'title' => 'Chcem rychlo pochopit zakladne doplnky vyzivy',
        'description' => 'Dobry start pre beznu rutinu: co ma zmysel brat pravidelne a co je skôr doplnok navyse.',
        'href' => article_url('doplnky-vyzivy'),
        'cta' => 'Otvorit sprievodcu',
    ],
    [
        'eyebrow' => 'Porovnanie v tabulke',
        'title' => 'Chcem ist rovno na hotove porovnanie produktov',
        'description' => 'Ak nechces citat dlhy uvod, otvor si clanok, kde mas produkty porovnane na jeden pohlad.',
        'href' => article_url('kreatin-porovnanie'),
        'cta' => 'Otvorit porovnanie',
    ],
];

$homeThemeIntentGroups = [
    [
        'title' => 'Zacni hlavnou temou',
        'description' => 'Najprv si otvor siroku oblast, v ktorej pochopis typy produktov a hlavne rozdiely.',
        'links' => ['proteiny', 'vyziva', 'mineraly'],
    ],
    [
        'title' => 'Ries konkretny ciel',
        'description' => 'Ak uz vies, co chces vyriesit, chod rovno do cielovej temy a skrat si cestu k vyberu.',
        'links' => ['chudnutie', 'imunita', 'sila', 'klby-koza'],
    ],
    [
        'title' => 'Dories detail alebo specialitu',
        'description' => 'Doplnkove temy pomozu, ked uz nechces studovat zaklad, ale hladat uzsi vyber.',
        'links' => ['kreatin', 'pre-workout', 'probiotika-travenie', 'aminokyseliny'],
    ],
];

$homeSupportThemeSlugs = ['chudnutie', 'kreatin', 'pre-workout', 'probiotika-travenie', 'aminokyseliny'];
$homeSupportThemes = [];
foreach ($homeSupportThemeSlugs as $slug) {
    $meta = category_meta($slug);
    $hub = interessa_category_hub($slug);
    if ($meta === null || $hub === null) {
        continue;
    }

    $articles = array_values(category_articles($slug));
    $commercialCount = count(array_filter($articles, static function (array $item): bool {
        return interessa_article_has_commerce((string) ($item['slug'] ?? ''));
    }));

    $homeSupportThemes[] = [
        'slug' => $slug,
        'title' => $meta['title'],
        'description' => trim((string) ($hub['intro'] ?? $meta['description'] ?? '')),
        'count' => count($articles),
        'commercial_count' => $commercialCount,
    ];
}

$featuredGuides = [];
foreach ($featuredGuideSlugs as $slug) {
    $meta = article_meta($slug);
    $categorySlug = normalize_category_slug((string) ($meta['category'] ?? ''));
    $articleFile = __DIR__ . '/content/articles/' . $slug . '.html';
    $commerceSummary = interessa_article_commerce_summary($slug);
    $featuredGuides[] = [
        'slug' => $slug,
        'title' => $meta['title'],
        'description' => interessa_article_card_description($slug, trim((string) ($meta['description'] ?? '')), 20),
        'image' => interessa_article_image_meta($slug, 'hero', true),
        'format_label' => interessa_article_format_label($slug, (string) ($meta['title'] ?? '')),
        'commerce_summary' => $commerceSummary,
        'category_meta' => $categorySlug !== '' ? category_meta($categorySlug) : null,
        'coverage_percent' => interessa_shortlist_coverage_percent($commerceSummary),
        'coverage_label' => interessa_shortlist_coverage_label($commerceSummary),
        'updated_date' => is_file($articleFile) ? date('d.m.Y', (int) @filemtime($articleFile)) : '',
    ];
}

$homeLeadSlug = 'najlepsi-protein-na-chudnutie-wpc-vs-wpi';
$homeLeadMeta = article_meta($homeLeadSlug);
$homeLeadImage = interessa_article_image_meta($homeLeadSlug, 'hero', true);
$homeLeadCommerceSummary = interessa_article_commerce_summary($homeLeadSlug);
$homeLeadCoveragePercent = interessa_shortlist_coverage_percent($homeLeadCommerceSummary);
$homeLeadCoverageLabel = interessa_shortlist_coverage_label($homeLeadCommerceSummary);
$homeLeadFile = __DIR__ . '/content/articles/' . $homeLeadSlug . '.html';
$homeLeadUpdated = is_file($homeLeadFile) ? date('d.m.Y', (int) @filemtime($homeLeadFile)) : '';
$allIndexedArticles = array_values(indexed_articles());
$recentWindow = strtotime('-60 days');
$recentArticlesCount = count(array_filter($allIndexedArticles, static function (array $item) use ($recentWindow): bool {
    $file = __DIR__ . '/content/articles/' . (string) ($item['slug'] ?? '') . '.html';
    return is_file($file) && (int) @filemtime($file) >= $recentWindow;
}));
$commercialArticleCount = count(array_filter($allIndexedArticles, static function (array $item): bool {
    return interessa_article_has_commerce((string) ($item['slug'] ?? ''));
}));
$readyShortlistGuides = [];
$comparisonReadyGuides = [];
foreach ($allIndexedArticles as $item) {
    $slug = (string) ($item['slug'] ?? '');
    $summary = interessa_article_commerce_summary($slug);
    if ($slug === '' || !is_array($summary) || (int) ($summary['count'] ?? 0) <= 0) {
        continue;
    }
    $meta = article_meta($slug);
    $categorySlug = normalize_category_slug((string) ($meta['category'] ?? ''));
    $articleFile = __DIR__ . '/content/articles/' . $slug . '.html';
    $readyShortlistGuides[] = [
        'slug' => $slug,
        'title' => $meta['title'],
        'description' => interessa_article_card_description($slug, trim((string) ($meta['description'] ?? '')), 20),
        'image' => interessa_article_image_meta($slug, 'thumb', true),
        'format_label' => interessa_article_format_label($slug, (string) ($meta['title'] ?? '')),
        'category_meta' => $categorySlug !== '' ? category_meta($categorySlug) : null,
        'coverage_percent' => interessa_shortlist_coverage_percent($summary),
        'coverage_label' => interessa_shortlist_coverage_label($summary),
        'updated_ts' => is_file($articleFile) ? (int) @filemtime($articleFile) : 0,
        'updated_date' => is_file($articleFile) ? date('d.m.Y', (int) @filemtime($articleFile)) : '',
    ];

    if (interessa_article_has_comparison_table($slug)) {
        $comparisonReadyGuides[] = [
            'slug' => $slug,
            'title' => $meta['title'],
            'description' => interessa_article_card_description($slug, trim((string) ($meta['description'] ?? '')), 18),
            'image' => interessa_article_image_meta($slug, 'thumb', true),
            'format_label' => interessa_article_format_label($slug, (string) ($meta['title'] ?? '')),
            'category_meta' => $categorySlug !== '' ? category_meta($categorySlug) : null,
            'coverage_percent' => interessa_shortlist_coverage_percent($summary),
            'updated_ts' => is_file($articleFile) ? (int) @filemtime($articleFile) : 0,
            'updated_date' => is_file($articleFile) ? date('d.m.Y', (int) @filemtime($articleFile)) : '',
        ];
    }
}
usort($readyShortlistGuides, static function (array $a, array $b): int {
    $coverageCompare = ((int) ($b['coverage_percent'] ?? 0)) <=> ((int) ($a['coverage_percent'] ?? 0));
    if ($coverageCompare !== 0) {
        return $coverageCompare;
    }
    return ((int) ($b['updated_ts'] ?? 0)) <=> ((int) ($a['updated_ts'] ?? 0));
});
$readyShortlistGuides = array_slice($readyShortlistGuides, 0, 3);
usort($comparisonReadyGuides, static function (array $a, array $b): int {
    $coverageCompare = ((int) ($b['coverage_percent'] ?? 0)) <=> ((int) ($a['coverage_percent'] ?? 0));
    if ($coverageCompare !== 0) {
        return $coverageCompare;
    }
    return ((int) ($b['updated_ts'] ?? 0)) <=> ((int) ($a['updated_ts'] ?? 0));
});
$comparisonReadyGuides = array_slice($comparisonReadyGuides, 0, 3);
$categoryCount = count(category_registry());
$guideCount = count($allIndexedArticles);

include __DIR__ . '/inc/head.php';
?>
<section class="hero">
  <div class="container hero-inner">
    <div class="hero-copy">
      <p class="hub-eyebrow">Prakticky magazin o vyzive</p>
      <h1>Vyber si doplnky a vyzivu bez chaosu a marketingoveho balastu</h1>
      <p>Interesa spaja hlavne temy, nakupne navody, recenzie a porovnania tak, aby si sa vedel rychlo dostat k rozumnemu vyberu podla ciela.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="/clanky/najlepsie-proteiny-2026">Zacat porovnanim</a>
        <a class="btn btn-ghost" href="/kategorie">Otvorit temy</a>
      </div>
    </div>

    <div class="hero-media">
      <figure class="hero-figure">
        <?= interessa_render_image($homeHeroImage, ['style' => 'aspect-ratio:16/9;object-fit:cover;']) ?>
        <figcaption>Prakticke navody, porovnania a odporucania stavane na dlhodobe pouzivanie, nie len na rychly klik.</figcaption>
      </figure>
    </div>
  </div>
</section>

<section class="container stats-strip" aria-label="Co na webe najdes">
  <article class="stats-card">
    <strong><?= esc((string) $categoryCount) ?> hlavnych tem</strong>
    <p>Zacni podla ciela a az potom ries konkretny produkt.</p>
  </article>
  <article class="stats-card">
    <strong><?= esc((string) $guideCount) ?> clankov v archive</strong>
    <p>Porovnania, navody, recenzie a top vybery napriec hlavnymi temami.</p>
  </article>
  <article class="stats-card">
    <strong><?= esc((string) $recentArticlesCount) ?> aktualizovanych za 60 dni</strong>
    <p>Obsah priebezne kontrolujeme a dorovnavame podla toho, co ludia realne najcastejsie hladaju.</p>
  </article>
  <article class="stats-card">
    <strong><?= esc((string) $commercialArticleCount) ?> clankov s odporucaniami</strong>
    <p>Na tychto strankach uz vies prejst od vysvetlenia temy priamo k porovnanym produktom a obchodom.</p>
  </article>
</section>

<?php if ($homeQuickStartPaths !== []): ?>
<section class="container home-section home-section--quickstart">
  <div class="section-head">
    <p class="hub-eyebrow">Rychly start</p>
    <h2>Tri najrychlejsie cesty cez web</h2>
    <p class="meta">Ak nechces najprv prechadzat cely web, zacni jednou z tychto troch ciest. Kazda ta rychlo dovedie k uzitocnemu clanku a rozumnejsiemu vyberu.</p>
  </div>

  <div class="quickstart-grid">
    <?php foreach ($homeQuickStartPaths as $path): ?>
      <article class="quickstart-card">
        <span class="quickstart-eyebrow"><?= esc((string) ($path['eyebrow'] ?? 'Start')) ?></span>
        <h3><a href="<?= esc((string) ($path['href'] ?? '/')) ?>"><?= esc((string) ($path['title'] ?? '')) ?></a></h3>
        <p><?= esc((string) ($path['description'] ?? '')) ?></p>
        <a class="btn btn-primary" href="<?= esc((string) ($path['href'] ?? '/')) ?>"><?= esc((string) ($path['cta'] ?? 'Otvorit')) ?></a>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($homeGoalCards !== []): ?>
<section class="container home-section">
  <div class="section-head">
    <h2>Zacni podla toho, co riesis</h2>
    <p class="meta">Vyber si ciel a otvor prvy clanok, ktory ta najrychlejsie dostane k odpovedi, nie k dalsiemu chaosu.</p>
  </div>

  <div class="hub-grid home-goals-grid">
    <?php foreach ($homeGoalCards as $goal): ?>
      <article class="hub-card home-goal-card">
        <div class="category-asset-frame category-asset-frame--goal">
          <?= interessa_render_image($goal['image'], ['class' => 'hub-card-image category-asset-image', 'alt' => $goal['title']]) ?>
        </div>
        <div class="hub-card-body">
          <span class="hub-card-icon" aria-hidden="true"><?= interessa_category_icon((string) $goal['slug']) ?></span>
          <span class="hub-card-label"><?= esc((string) $goal['title']) ?></span>
          <p><?= esc((string) $goal['description']) ?></p>
          <div class="home-goal-actions">
            <?php if (is_array($goal['primary_guide'] ?? null)): ?>
              <a class="btn btn-primary" href="<?= esc(article_url((string) $goal['primary_guide']['slug'])) ?>">
                <?= esc('Zacat: ' . (string) ($goal['primary_guide']['label'] ?? 'Start')) ?>
              </a>
            <?php endif; ?>
            <a class="btn btn-ghost" href="<?= esc(category_url((string) $goal['slug'])) ?>">Otvorit temu</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($homeThemeIntentGroups !== []): ?>
<section class="container home-section home-section--theme-system">
  <div class="section-head">
    <p class="hub-eyebrow">Ako sa zorientovat</p>
    <h2>Najprv si vyber sirsiu temu</h2>
    <p class="meta">Niektore temy sluzia ako vstup do problemu, ine su skor na uzsi detail. Tento prehlad ti pomoze zacat tam, kde to bude davat najvacsi zmysel.</p>
  </div>

  <div class="intent-lane-grid home-intent-grid">
    <?php foreach ($homeThemeIntentGroups as $group): ?>
      <article class="intent-lane-card">
        <h3><?= esc((string) ($group['title'] ?? 'Temy')) ?></h3>
        <?php if (trim((string) ($group['description'] ?? '')) !== ''): ?><p><?= esc((string) ($group['description'] ?? '')) ?></p><?php endif; ?>
        <div class="intent-lane-links">
          <?php foreach ((array) ($group['links'] ?? []) as $intentSlug): ?>
            <?php
            $intentMeta = category_meta((string) $intentSlug);
            if ($intentMeta === null) {
                continue;
            }
            ?>
            <a class="intent-link-chip" href="<?= esc(category_url((string) $intentSlug)) ?>">
              <span class="intent-link-icon" aria-hidden="true"><?= interessa_category_icon((string) $intentSlug) ?></span>
              <?= esc((string) ($intentMeta['title'] ?? humanize_slug((string) $intentSlug))) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($homeSupportThemes !== []): ?>
<section class="container home-section home-section--support-themes">
  <div class="section-head">
    <p class="hub-eyebrow">Ked uz vies, co hladat</p>
    <h2>Specializovane temy pre konkretne otazky</h2>
    <p class="meta">Tieto temy su vhodne vtedy, ked uz nechces studovat zaklad, ale riesis konkretny problem alebo detail vyberu.</p>
  </div>

  <div class="support-theme-grid">
    <?php foreach ($homeSupportThemes as $theme): ?>
      <article class="support-theme-card">
        <div class="support-theme-head">
          <span class="intent-link-icon" aria-hidden="true"><?= interessa_category_icon((string) $theme['slug']) ?></span>
          <div class="support-theme-copy">
            <h3><a href="<?= esc(category_url((string) $theme['slug'])) ?>"><?= esc((string) $theme['title']) ?></a></h3>
            <p><?= esc(interessa_trim_words((string) ($theme['description'] ?? ''), 12)) ?></p>
          </div>
        </div>
        <div class="article-card-submeta">
          <span class="article-card-subchip"><?= esc((string) ($theme['count'] ?? 0)) ?> <?= esc(interessa_pluralize_slovak((int) ($theme['count'] ?? 0), 'clanok', 'clanky', 'clankov')) ?></span>
          <?php if ((int) ($theme['commercial_count'] ?? 0) > 0): ?>
            <span class="article-card-subchip is-coverage is-partial">Vyber produktov v <?= esc((string) ($theme['commercial_count'] ?? 0)) ?> <?= esc(interessa_pluralize_slovak((int) ($theme['commercial_count'] ?? 0), 'clanku', 'clankoch', 'clankoch')) ?></span>
          <?php endif; ?>
        </div>
        <a class="card-link" href="<?= esc(category_url((string) $theme['slug'])) ?>">Otvorit temu</a>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="container home-section">
  <div class="section-head">
    <h2>Vyber si temu, v ktorej zacat</h2>
    <p class="meta">Kazda tema ta ma rychlo zorientovat, ukazat najlepsi prvy clanok a potom ta posunut k vhodnemu vyberu.</p>
  </div>

  <div class="hub-grid home-theme-grid">
    <?php foreach ($featuredCategories as $category): ?>
      <article class="hub-card theme-card theme-card--<?= esc((string) $category['slug']) ?>">
        <div class="theme-card-media category-asset-frame category-asset-frame--theme">
          <?= interessa_render_image($category['image'], ['class' => 'hub-card-image theme-card-image category-asset-image', 'alt' => $category['title']]) ?>
          <div class="theme-card-badge-row">
            <span class="theme-card-kicker">Hlavna tema</span>
            <span class="theme-card-kicker is-soft"><?= esc((string) ($category['theme_label'] ?? 'Hlavna tema')) ?></span>
          </div>
        </div>
        <div class="hub-card-body">
          <div class="theme-card-head">
            <span class="hub-card-icon" aria-hidden="true"><?= interessa_category_icon((string) $category['slug']) ?></span>
            <div class="theme-card-head-copy">
              <span class="hub-card-label">Tema pre rychly start</span>
              <h3><a href="<?= esc(category_url((string) $category['slug'])) ?>"><?= esc((string) $category['title']) ?></a></h3>
            </div>
          </div>
          <div class="article-card-meta">
            <span class="hub-card-label"><?= esc((string) $category['count']) ?> <?= esc(interessa_pluralize_slovak((int) $category['count'], 'clanok', 'clanky', 'clankov')) ?> v teme</span>
            <span class="article-card-date"><?= esc((string) $category['featured_count']) ?> <?= esc(interessa_pluralize_slovak((int) $category['featured_count'], 'klucovy clanok', 'klucove clanky', 'klucovych clankov')) ?></span>
          </div>
          <?php if ((int) ($category['commercial_count'] ?? 0) > 0): ?>
            <div class="article-card-submeta">
              <span class="article-card-subchip">Vyber produktov v <?= esc((string) $category['commercial_count']) ?> <?= esc(interessa_pluralize_slovak((int) $category['commercial_count'], 'clanku', 'clankoch', 'clankoch')) ?></span>
              <?php if ((int) ($category['full_coverage_count'] ?? 0) > 0): ?>
                <span class="article-card-subchip is-coverage is-full">Najrychlejsia cesta k vyberu v <?= esc((string) $category['full_coverage_count']) ?> <?= esc(interessa_pluralize_slovak((int) $category['full_coverage_count'], 'clanku', 'clankoch', 'clankoch')) ?></span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if (trim((string) ($category['focus_point'] ?? '')) !== ''): ?>
            <p class="theme-card-focus"><?= esc((string) $category['focus_point']) ?></p>
          <?php endif; ?>
          <p><?= esc((string) $category['description']) ?></p>
          <a class="btn" href="<?= esc(category_url((string) $category['slug'])) ?>">Otvorit kategoriu</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="container home-section">
      <div class="section-head">
        <h2>Najlepsie clanky na prvy klik</h2>
        <p class="meta">Sem chod vtedy, ked uz riesis konkretny vyber a nechces sa prehrabavat celou temou od zaciatku.</p>
      </div>

  <div class="hub-grid">
    <?php foreach ($featuredGuides as $guide): ?>
      <article class="hub-card">
        <?= interessa_render_image($guide['image'], ['class' => 'hub-card-image', 'alt' => $guide['title']]) ?>
        <div class="hub-card-body">
        <div class="article-card-meta">
          <span class="article-card-chip is-format"><?= esc((string) ($guide['format_label'] ?? 'Sprievodca')) ?></span>
          <?php if (is_array($guide['category_meta'] ?? null)): ?><span class="article-card-chip"><?= esc((string) ($guide['category_meta']['title'] ?? '')) ?></span><?php endif; ?>
          <?php if (($guide['updated_date'] ?? '') !== ''): ?><span class="article-card-date">Aktualizovane: <?= esc((string) $guide['updated_date']) ?></span><?php endif; ?>
        </div>
        <?= interessa_render_article_commerce_submeta((string) $guide['slug'], 'compact') ?>
        <?php if ((int) ($guide['coverage_percent'] ?? 0) > 0): ?>
          <div class="article-card-submeta">
            <span class="article-card-subchip is-coverage <?= (int) ($guide['coverage_percent'] ?? 0) >= 100 ? 'is-full' : 'is-partial' ?>">
              <?= esc(ucfirst((string) ($guide['coverage_label'] ?? 'vyber produktov'))) ?>
            </span>
            <span class="article-card-subchip">Realne fotky: <?= esc((string) ($guide['coverage_percent'] ?? 0)) ?>%</span>
          </div>
        <?php endif; ?>
        <h3><a href="<?= esc(article_url((string) $guide['slug'])) ?>"><?= esc((string) $guide['title']) ?></a></h3>
        <?php if ($guide['description'] !== ''): ?><p><?= esc((string) $guide['description']) ?></p><?php endif; ?>
          <a class="btn" href="<?= esc(article_url((string) $guide['slug'])) ?>"><?= esc(interessa_article_cta_label((string) $guide['slug'], (string) $guide['title'])) ?></a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php if ($readyShortlistGuides !== []): ?>
<section class="container home-section">
  <div class="section-head">
    <h2>Clanky, kde sa vies rozhodnut najrychlejsie</h2>
    <p class="meta">Vybery, v ktorych uz mas prehlad produktov a jasne odporucania pripravene na rychle rozhodnutie.</p>
  </div>

  <div class="hub-grid article-teaser-grid">
    <?php foreach ($readyShortlistGuides as $guide): ?>
      <article class="hub-card article-teaser-card">
        <a href="<?= esc(article_url((string) $guide['slug'])) ?>">
          <?= interessa_render_image((array) $guide['image'], ['class' => 'hub-card-image', 'alt' => (string) $guide['title']]) ?>
        </a>
        <div class="hub-card-body article-teaser-body">
          <div class="article-card-meta">
            <span class="article-card-chip is-format"><?= esc((string) ($guide['format_label'] ?? 'Clanok')) ?></span>
            <?php if (is_array($guide['category_meta'] ?? null)): ?><span class="article-card-chip"><?= esc((string) ($guide['category_meta']['title'] ?? '')) ?></span><?php endif; ?>
            <?php if (($guide['updated_date'] ?? '') !== ''): ?><span class="article-card-date">Aktualizovane: <?= esc((string) $guide['updated_date']) ?></span><?php endif; ?>
          </div>
          <?= interessa_render_article_commerce_submeta((string) $guide['slug'], 'compact') ?>
          <h3><a href="<?= esc(article_url((string) $guide['slug'])) ?>"><?= esc((string) $guide['title']) ?></a></h3>
          <?php if (($guide['description'] ?? '') !== ''): ?><p><?= esc((string) $guide['description']) ?></p><?php endif; ?>
          <a class="btn" href="<?= esc(article_url((string) $guide['slug'])) ?>"><?= esc(interessa_article_cta_label((string) $guide['slug'], (string) $guide['title'])) ?></a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($comparisonReadyGuides !== []): ?>
<section class="container home-section">
  <div class="section-head">
    <h2>Rychle porovnania v tabulke</h2>
    <p class="meta">Ak chces ist rovno na porovnanie produktov, tu najdes clanky, kde uz mas pripravenu prehladnu tabulku aj odporucania.</p>
  </div>

  <div class="hub-grid article-teaser-grid">
    <?php foreach ($comparisonReadyGuides as $guide): ?>
      <article class="hub-card article-teaser-card">
        <a href="<?= esc(article_url((string) $guide['slug'])) ?>">
          <?= interessa_render_image((array) $guide['image'], ['class' => 'hub-card-image', 'alt' => (string) $guide['title']]) ?>
        </a>
        <div class="hub-card-body article-teaser-body">
          <div class="article-card-meta">
            <span class="article-card-chip is-format"><?= esc((string) ($guide['format_label'] ?? 'Clanok')) ?></span>
            <?php if (is_array($guide['category_meta'] ?? null)): ?><span class="article-card-chip"><?= esc((string) ($guide['category_meta']['title'] ?? '')) ?></span><?php endif; ?>
            <?php if (($guide['updated_date'] ?? '') !== ''): ?><span class="article-card-date">Aktualizovane: <?= esc((string) $guide['updated_date']) ?></span><?php endif; ?>
          </div>
          <div class="article-card-submeta">
            <span class="article-card-subchip is-coverage is-full">Prehladna tabulka produktov</span>
            <span class="article-card-subchip">Realne fotky: <?= esc((string) ($guide['coverage_percent'] ?? 0)) ?>%</span>
          </div>
          <h3><a href="<?= esc(article_url((string) $guide['slug'])) ?>"><?= esc((string) $guide['title']) ?></a></h3>
          <?php if (($guide['description'] ?? '') !== ''): ?><p><?= esc((string) $guide['description']) ?></p><?php endif; ?>
          <a class="btn" href="<?= esc(article_url((string) $guide['slug'])) ?>">Otvorit porovnanie</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="container two-col home-story">
  <div class="content">
    <article class="lead-article">
      <header>
        <p class="hub-eyebrow">Odporucany start</p>
        <h2><?= esc($homeLeadMeta['title']) ?></h2>
        <div class="article-card-meta">
          <span class="article-card-chip is-format"><?= esc(interessa_article_format_label($homeLeadSlug, (string) $homeLeadMeta['title'])) ?></span>
          <?php $homeLeadCategory = category_meta(normalize_category_slug((string) ($homeLeadMeta['category'] ?? ''))); ?>
          <?php if ($homeLeadCategory !== null): ?><span class="article-card-chip"><?= esc((string) ($homeLeadCategory['title'] ?? '')) ?></span><?php endif; ?>
          <?php if ($homeLeadUpdated !== ''): ?><span class="article-card-date">Aktualizovane: <?= esc($homeLeadUpdated) ?></span><?php endif; ?>
        </div>
        <?= interessa_render_article_commerce_submeta($homeLeadSlug, 'full') ?>
        <?php if ($homeLeadCoveragePercent > 0): ?>
          <div class="article-card-submeta">
            <span class="article-card-subchip is-coverage <?= $homeLeadCoveragePercent >= 100 ? 'is-full' : 'is-partial' ?>">
              <?= esc(ucfirst($homeLeadCoverageLabel)) ?>
            </span>
            <span class="article-card-subchip">Realne fotky: <?= esc((string) $homeLeadCoveragePercent) ?>%</span>
          </div>
        <?php endif; ?>
        <p class="meta">Ak prave riesis chudnutie alebo chces rozumiet rozdielu medzi WPC a WPI, tu ma zmysel zacat.</p>
      </header>

      <figure class="inline-figure">
        <?= interessa_render_image($homeLeadImage, ['style' => 'object-fit:cover;']) ?>
      </figure>

      <p>Najcastejsia chyba pri kupe proteinu je, ze sa riesi znacka skor nez typ proteinu, ciel a tolerancia laktozy. Tento clanok pomaha rozlisit, kedy dava zmysel klasicky koncentrat a kedy sa oplati izolat.</p>
      <ul class="hub-checklist">
        <li>WPC byva praktickejsie pri rozpocte a kazdodennom pouzivani.</li>
        <li>WPI dava vacsi zmysel pri diete alebo citlivosti na laktozu.</li>
        <li>V clanku uz mas aj pripraveny vyber produktov a ciste CTA do obchodu.</li>
      </ul>
      <p><a class="btn btn-primary" href="<?= esc(article_url($homeLeadSlug)) ?>"><?= esc(interessa_article_cta_label($homeLeadSlug, (string) $homeLeadMeta['title'])) ?></a></p>
    </article>
  </div>

  <?php include __DIR__ . '/inc/sidebar.php'; ?>
</section>

<section class="container home-section home-trust">
  <div class="section-head">
    <h2>Preco sa na webe vies rychlo zorientovat</h2>
    <p class="meta">Interesa ma fungovat ako redakcny pomocnik pri vybere, nie ako agresivna predajna stranka.</p>
  </div>

  <div class="card-grid home-trust-grid">
    <article class="card">
      <div class="card-body">
        <h3>Ako hodnotime produkty</h3>
        <p>Pozerame sa na ciel pouzitia, zlozenie, davku, cenu na porciu a to, ci produkt dava zmysel v realnej rutine.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>Porovnavame viac obchodov</h3>
        <p>V odporucaniach nechavame priestor viacerym merchantom. GymBeam je silny partner, ale web nema stat len na jednej znacke.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>Affiliate odkazy nemenia cenu</h3>
        <p>Niektore odkazy vedu do partnerskych obchodov. Ak cez ne nakupis, web moze ziskat proviziu bez navysenia ceny pre teba.</p>
      </div>
    </article>
  </div>
</section>

<?php include __DIR__ . '/inc/footer.php'; ?>
