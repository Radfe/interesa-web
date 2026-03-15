<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/category-hubs.php';
require_once __DIR__ . '/../inc/article-commerce.php';

$page_title = 'Kategorie doplnkov vyzivy, porovnani a navodov | Interesa';
$page_description = 'Temy, cez ktore sa rychlo dostanes od zakladnej orientacie k najlepsim clankom, porovnaniam a odporucaniam.';
$page_canonical = '/kategorie';
$page_image = asset('img/brand/og-default.svg');
$page_og_type = 'website';
$hubs = interessa_category_hubs();

$primarySlugs = ['proteiny', 'vyziva', 'mineraly', 'sila', 'klby-koza', 'imunita'];
$secondarySlugs = ['chudnutie', 'kreatin', 'pre-workout', 'probiotika-travenie', 'aminokyseliny', 'doplnkove-prislusenstvo'];

$primaryHubs = [];
foreach ($primarySlugs as $slug) {
    if (isset($hubs[$slug])) {
        $primaryHubs[$slug] = $hubs[$slug];
    }
}

$secondaryHubs = [];
foreach ($secondarySlugs as $slug) {
    if (isset($hubs[$slug])) {
        $secondaryHubs[$slug] = $hubs[$slug];
    }
}

$intentGroups = [
    [
        'title' => 'Chcem sa rychlo zorientovat',
        'description' => 'Najprv otvor siroku temu, v ktorej pochopis rozdiely a vyberies spravny typ produktu.',
        'links' => ['proteiny', 'vyziva', 'mineraly'],
    ],
    [
        'title' => 'Riesim konkretny ciel',
        'description' => 'Ak uz vies, co ta trapi alebo co chces zlepsit, chod rovno do cielovej temy.',
        'links' => ['chudnutie', 'imunita', 'klby-koza', 'sila'],
    ],
    [
        'title' => 'Hladam detail alebo specialitu',
        'description' => 'Tieto temy su vhodne vtedy, ked uz mas zaklad a potrebujes riesit uzsi vyber alebo doplnok.',
        'links' => ['kreatin', 'pre-workout', 'probiotika-travenie', 'aminokyseliny', 'doplnkove-prislusenstvo'],
    ],
];

$hubPrimaryGuide = [];
foreach ($hubs as $slug => $hub) {
    $primaryGuide = null;
    foreach ((array) ($hub['featured_guides'] ?? []) as $guide) {
        $guideSlug = trim((string) ($guide['slug'] ?? ''));
        if ($guideSlug === '') {
            continue;
        }

        $guideMeta = article_meta($guideSlug);
        $guideSummary = interessa_article_commerce_summary($guideSlug);
        $primaryGuide = [
            'slug' => $guideSlug,
            'label' => trim((string) ($guide['label'] ?? 'Start')) ?: 'Start',
            'title' => trim((string) ($guideMeta['title'] ?? humanize_slug($guideSlug))),
            'has_commerce' => is_array($guideSummary) && (int) ($guideSummary['count'] ?? 0) > 0,
            'coverage_state' => interessa_article_commerce_coverage_state($guideSlug),
        ];
        break;
    }

    $hubPrimaryGuide[$slug] = $primaryGuide;
}

$hubArticleCount = [];
$hubCommercialCount = [];
$hubFullCoverageCount = [];
$totalCommercialArticles = 0;
$totalFullCoverageArticles = 0;
foreach (array_keys($hubs) as $slug) {
    $articles = array_values(category_articles($slug));
    $hubArticleCount[$slug] = count($articles);
    $hubCommercialCount[$slug] = count(array_filter($articles, static function (array $item): bool {
        return interessa_article_has_commerce((string) ($item['slug'] ?? ''));
    }));
    $hubFullCoverageCount[$slug] = count(array_filter($articles, static function (array $item): bool {
        return interessa_article_has_full_packshot_coverage((string) ($item['slug'] ?? ''));
    }));
    $totalCommercialArticles += (int) ($hubCommercialCount[$slug] ?? 0);
    $totalFullCoverageArticles += (int) ($hubFullCoverageCount[$slug] ?? 0);
}
$activeCommercialHubs = count(array_filter($hubCommercialCount, static fn(int $count): bool => $count > 0));

$page_schema = [
    breadcrumb_schema([
        ['name' => 'Domov', 'url' => '/'],
        ['name' => 'Kategorie', 'url' => '/kategorie'],
    ]),
    [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'Kategorie',
        'description' => $page_description,
        'url' => absolute_url('/kategorie'),
    ],
];

include __DIR__ . '/../inc/head.php';
?>
<section class="container home-section">
  <article class="card hub-hero-card categories-overview">
    <p class="hub-eyebrow">Prehlad tem</p>
    <h1>Kategorie</h1>
    <p class="lead">Kategorie na Interese sluzia ako vstup do temy. Pomozu ti rychlo sa zorientovat, otvorit spravny clanok a az potom riesit konkretny vyber produktu.</p>
    <div class="stats-strip categories-stats" aria-label="Prehlad kategorii">
      <article class="stats-card">
        <strong><?= count($primaryHubs) ?></strong>
        <p>hlavnych tem, v ktorych sa oplati zacat</p>
      </article>
      <article class="stats-card">
        <strong><?= count($secondaryHubs) ?></strong>
        <p>specializovanych tem pre detailnejsie otazky</p>
      </article>
      <article class="stats-card">
        <strong><?= count($hubs) ?></strong>
        <p>tem pripravenych na navody, porovnania a recenzie</p>
      </article>
      <article class="stats-card">
        <strong><?= esc((string) $totalCommercialArticles) ?></strong>
        <p>clankov, kde sa dostanes aj k odporucaniam</p>
      </article>
      <article class="stats-card">
        <strong><?= esc((string) $activeCommercialHubs) ?></strong>
        <p>tem, kde uz vies prejst aj k vyberu</p>
      </article>
      <article class="stats-card">
        <strong><?= esc((string) $totalFullCoverageArticles) ?></strong>
        <p>clankov s realnymi fotkami produktov</p>
      </article>
    </div>
    <div class="hero-cta">
      <a class="btn btn-ghost" href="/clanky?commercial=1">Otvorit clanky s odporucaniami</a>
      <a class="btn btn-ghost" href="/clanky?coverage=full">Pozriet porovnania s realnymi fotkami</a>
    </div>
  </article>
</section>

<section class="container home-section">
  <div class="section-head">
    <h2>Ako sa v 12 temach nestratit</h2>
    <p class="meta">Nie kazda tema ma rovnaku ulohu. Tento prehlad ti pomoze pochopit, kde sa oplati zacat a co je skor doplnkova cesta.</p>
  </div>

  <div class="card-grid home-trust-grid categories-system-grid">
    <article class="card">
      <div class="card-body">
        <h3>Hlavne vstupne temy</h3>
        <p>Toto su siroke temy, kde dava najvacsi zmysel zacat: proteiny, vyziva, mineraly, imunita, sila a klby.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>Cielove temy</h3>
        <p>Sem chod vtedy, ked uz neriesis cely trh, ale konkretny problem alebo vysledok, napr. chudnutie alebo vykon.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>Detailne a podporne temy</h3>
        <p>Tieto temy doplnaju hlavne temy webu. Pomozu ti, ked uz vies, co hladas a potrebujes uzsi vyber.</p>
      </div>
    </article>
  </div>
</section>

<?php if ($intentGroups !== []): ?>
<section class="container home-section">
  <div class="section-head">
    <h2>Vyber si temu podla toho, co prave riesis</h2>
    <p class="meta">Namiesto prezerania celeho zoznamu si vyber zamer a chod rovno na temu, ktora dava najvacsi zmysel.</p>
  </div>

  <div class="intent-lane-grid">
    <?php foreach ($intentGroups as $group): ?>
      <article class="intent-lane-card">
        <h3><?= esc((string) ($group['title'] ?? 'Intent')) ?></h3>
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

<section class="container home-section">
  <div class="section-head">
    <h2>Hlavne temy, v ktorych sa oplati zacat</h2>
        <p class="meta">Najsilnejsie smery webu, na ktorych stoja hlavne porovnania, sprievodcovia a produktove vybery.</p>
  </div>

  <div class="hub-grid">
    <?php foreach ($primaryHubs as $slug => $hub): ?>
      <?php $guideCount = count((array) ($hub['featured_guides'] ?? [])); ?>
      <?php $articleCount = (int) ($hubArticleCount[$slug] ?? 0); ?>
      <?php $commercialCount = (int) ($hubCommercialCount[$slug] ?? 0); ?>
      <?php $fullCoverageCount = (int) ($hubFullCoverageCount[$slug] ?? 0); ?>
      <?php $primaryGuide = $hubPrimaryGuide[$slug] ?? null; ?>
      <article class="hub-card hub-card--primary">
        <div class="category-asset-frame category-asset-frame--theme">
          <?= interessa_render_image(interessa_category_image_meta($slug, 'hero', true), ['class' => 'hub-card-image category-asset-image', 'alt' => $hub['title']]) ?>
        </div>
        <div class="hub-card-body">
          <span class="hub-card-icon" aria-hidden="true"><?= interessa_category_icon((string) $slug) ?></span>
          <div class="article-card-submeta">
            <span class="article-card-subchip is-coverage is-full">Hlavna vstupna tema</span>
          </div>
          <div class="article-card-meta">
            <span class="hub-card-label"><?= esc((string) $guideCount) ?> klucove clanky</span>
            <span class="article-card-chip"><?= esc((string) $articleCount) ?> <?= esc(interessa_pluralize_slovak($articleCount, 'clanok', 'clanky', 'clankov')) ?></span>
          </div>
          <?php if ($commercialCount > 0): ?>
            <div class="article-card-submeta">
              <span class="article-card-subchip">Vyber produktov v <?= esc((string) $commercialCount) ?> <?= esc(interessa_pluralize_slovak($commercialCount, 'clanku', 'clankoch', 'clankoch')) ?></span>
              <?php if ($fullCoverageCount > 0): ?>
              <span class="article-card-subchip is-coverage is-full">Najrychlejsie sa zorientujes v <?= esc((string) $fullCoverageCount) ?> <?= esc(interessa_pluralize_slovak($fullCoverageCount, 'clanku', 'clankoch', 'clankoch')) ?></span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if (is_array($primaryGuide) && !empty($primaryGuide['has_commerce'])): ?>
            <div class="article-card-submeta">
              <?php if (($primaryGuide['coverage_state'] ?? '') === 'full'): ?>
                <span class="article-card-subchip is-coverage is-full">V uvodnom clanku uz najdes rychly prehlad aj vyber</span>
              <?php else: ?>
                <span class="article-card-subchip is-coverage is-partial">V uvodnom clanku uz najdes odporucane produkty</span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <h3><a href="<?= esc(category_url($slug)) ?>"><?= esc((string) $hub['title']) ?></a></h3>
          <p><?= esc((string) ($hub['description'] ?? '')) ?></p>
          <div class="home-goal-actions">
            <?php if (is_array($primaryGuide)): ?>
              <a class="btn btn-primary" href="<?= esc(article_url((string) ($primaryGuide['slug'] ?? ''))) ?>"><?= esc('Zacat: ' . (string) ($primaryGuide['label'] ?? 'Start')) ?></a>
            <?php endif; ?>
            <a class="btn btn-ghost" href="<?= esc(category_url($slug)) ?>">Otvorit kategoriu</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="container home-section home-section--secondary-hubs">
  <div class="section-head">
    <h2>Detailnejsie temy a doplnkove cesty</h2>
    <p class="meta">Temy pre konkretne typy doplnkov, uzsie problemy a hlbsie prepojenie obsahu napriec webom.</p>
  </div>

  <div class="hub-grid">
    <?php foreach ($secondaryHubs as $slug => $hub): ?>
      <?php $guideCount = count((array) ($hub['featured_guides'] ?? [])); ?>
      <?php $articleCount = (int) ($hubArticleCount[$slug] ?? 0); ?>
      <?php $commercialCount = (int) ($hubCommercialCount[$slug] ?? 0); ?>
      <?php $fullCoverageCount = (int) ($hubFullCoverageCount[$slug] ?? 0); ?>
      <?php $primaryGuide = $hubPrimaryGuide[$slug] ?? null; ?>
      <article class="hub-card hub-card--support">
        <div class="category-asset-frame category-asset-frame--theme">
          <?= interessa_render_image(interessa_category_image_meta($slug, 'hero', true), ['class' => 'hub-card-image category-asset-image', 'alt' => $hub['title']]) ?>
        </div>
        <div class="hub-card-body">
          <span class="hub-card-icon" aria-hidden="true"><?= interessa_category_icon((string) $slug) ?></span>
          <div class="article-card-submeta">
            <span class="article-card-subchip">Doplnkova tema</span>
          </div>
          <div class="article-card-meta">
            <span class="hub-card-label"><?= esc((string) $guideCount) ?> klucove clanky</span>
            <span class="article-card-chip"><?= esc((string) $articleCount) ?> <?= esc(interessa_pluralize_slovak($articleCount, 'clanok', 'clanky', 'clankov')) ?></span>
          </div>
          <?php if ($commercialCount > 0): ?>
            <div class="article-card-submeta">
              <span class="article-card-subchip">Vyber produktov v <?= esc((string) $commercialCount) ?> <?= esc(interessa_pluralize_slovak($commercialCount, 'clanku', 'clankoch', 'clankoch')) ?></span>
              <?php if ($fullCoverageCount > 0): ?>
                <span class="article-card-subchip is-coverage is-full">Najrychlejsie sa zorientujes v <?= esc((string) $fullCoverageCount) ?> <?= esc(interessa_pluralize_slovak($fullCoverageCount, 'clanku', 'clankoch', 'clankoch')) ?></span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if (is_array($primaryGuide) && !empty($primaryGuide['has_commerce'])): ?>
            <div class="article-card-submeta">
              <?php if (($primaryGuide['coverage_state'] ?? '') === 'full'): ?>
                <span class="article-card-subchip is-coverage is-full">V uvodnom clanku uz najdes rychly prehlad aj vyber</span>
              <?php else: ?>
                <span class="article-card-subchip is-coverage is-partial">V uvodnom clanku uz najdes odporucane produkty</span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <h3><a href="<?= esc(category_url($slug)) ?>"><?= esc((string) $hub['title']) ?></a></h3>
          <p><?= esc((string) ($hub['description'] ?? '')) ?></p>
          <div class="home-goal-actions">
            <?php if (is_array($primaryGuide)): ?>
              <a class="btn btn-primary" href="<?= esc(article_url((string) ($primaryGuide['slug'] ?? ''))) ?>"><?= esc('Zacat: ' . (string) ($primaryGuide['label'] ?? 'Start')) ?></a>
            <?php endif; ?>
            <a class="btn btn-ghost" href="<?= esc(category_url($slug)) ?>">Otvorit temu</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="container home-section">
  <div class="section-head">
    <h2>Ako cez kategorie najrychlejsie prejst k vyberu</h2>
    <p class="meta">Jednoducha cesta od sirokej temy ku konkretnemu clanku a potom k vhodnemu produktu.</p>
  </div>

  <div class="card-grid home-trust-grid">
    <article class="card">
      <div class="card-body">
        <h3>1. Zacni sirsou temou</h3>
        <p>Najprv otvor hlavnu kategoriu podla ciela, napr. proteiny, sila a vykon alebo vitaminy a mineraly.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>2. Prejdi klucove clanky</h3>
        <p>Kazda tema ma vlastny vyber clankov, ktore maju byt vstupom do temy, nie len dalsim zoznamom odkazov.</p>
      </div>
    </article>
    <article class="card">
      <div class="card-body">
        <h3>3. Az potom ries produkt</h3>
        <p>Produktove boxy a CTA davaju najvacsi zmysel az v momente, ked citatel rozumie typu produktu a svojmu cielu.</p>
      </div>
    </article>
  </div>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>
