<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$slug = preg_replace('~[^a-z0-9\-_]+~i', '', (string) ($_GET['slug'] ?? ''));
$category = category_meta($slug);
if ($slug === '' || $category === null) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    return;
}

$hub = category_hub_data($slug);
$featuredArticles = $hub['featured_articles'] ?? [];
$articles = $hub['articles'] ?? [];
$relatedCategories = $hub['related_categories'] ?? [];
$articleCount = (int) ($hub['article_count'] ?? 0);
$page_title = $category['title'] . ' | Interesa';
$page_description = $category['description'];
$page_type = 'CollectionPage';
include __DIR__ . '/inc/head.php';
?>
<section class="container two-col">
  <div class="content content-stack">
    <nav class="breadcrumbs" aria-label="Breadcrumb">
      <a href="/">Domov</a>
      <span aria-hidden="true">/</span>
      <a href="/kategorie/">Kategórie</a>
      <span aria-hidden="true">/</span>
      <span><?= esc($category['title']) ?></span>
    </nav>

    <article class="lead-article category-hero">
      <span class="eyebrow"><?= esc((string) ($hub['eyebrow'] ?? 'Obsahový hub')) ?></span>
      <h1><?= esc($category['title']) ?></h1>
      <p class="lead"><?= esc($category['description']) ?></p>
      <p class="category-hero-copy"><?= esc((string) ($hub['intro'] ?? $category['description'])) ?></p>
      <div class="category-meta-strip">
        <span><?= $articleCount > 0 ? $articleCount . ' článkov v clustri' : (!empty($featuredArticles) ? 'Téma prepája súvisiace clustre' : 'Cluster práve rozširujeme') ?></span>
        <span>Praktické porovnania, FAQ a ďalšie kroky</span>
      </div>
      <div class="article-actions compact-actions">
        <a class="btn btn-primary" href="/search?q=<?= rawurlencode($category['title']) ?>">Vyhľadať v téme</a>
        <a class="btn btn-ghost" href="/clanky/">Prejsť celý archív článkov</a>
      </div>
    </article>

    <?php if (!empty($hub['questions'])): ?>
      <section class="article-summary-card category-summary-card">
        <div>
          <span class="eyebrow">Začni tu</span>
          <h2>Najčastejšie otázky v tejto téme</h2>
          <p class="section-intro">Ak si v kategórii prvýkrát, toto sú najlepšie body, podľa ktorých sa zorientuješ najrýchlejšie.</p>
        </div>
        <ul class="summary-list category-question-list">
          <?php foreach ($hub['questions'] as $question): ?>
            <li><span><?= esc($question) ?></span></li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>

    <?php if ($featuredArticles): ?>
      <section class="content-stack">
        <div class="section-heading section-heading-tight">
          <div>
            <span class="eyebrow">Odporúčaný štart</span>
            <h2>Najlepšie články, od ktorých sa oplatí začať</h2>
            <p class="section-intro">Vybrané články, ktoré dajú najlepší kontext skôr, než začneš riešiť konkrétny produkt alebo značku.</p>
          </div>
        </div>
        <div class="grid-cards related-grid category-featured-grid">
          <?php foreach ($featuredArticles as $item): ?>
            <article class="post-card post-card-compact">
              <a href="<?= esc($item['url']) ?>">
                <img class="thumb" loading="lazy" decoding="async" src="<?= esc($item['image']) ?>" alt="<?= esc($item['title']) ?>">
              </a>
              <div class="post-card-body">
                <a class="chip" href="<?= esc(category_url($category['slug'])) ?>"><?= esc($category['title']) ?></a>
                <h3><a href="<?= esc($item['url']) ?>"><?= esc($item['title']) ?></a></h3>
                <?php if (($item['description'] ?? '') !== ''): ?><p><?= esc($item['description']) ?></p><?php endif; ?>
                <a class="card-link" href="<?= esc($item['url']) ?>">Otvoriť článok</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($articles): ?>
      <section class="content-stack">
        <div class="section-heading section-heading-tight">
          <div>
            <span class="eyebrow">Všetok obsah v téme</span>
            <h2>Ďalšie články a porovnania v tejto kategórii</h2>
            <p class="section-intro">Keď už máš základný prehľad, tu nájdeš ďalšie články, ktoré rozširujú detail alebo riešia konkrétnejší problém.</p>
          </div>
        </div>
        <div class="grid-cards article-card-grid">
          <?php foreach ($articles as $item): ?>
            <article class="post-card">
              <a href="<?= esc(article_url($item['slug'])) ?>">
                <img class="thumb" loading="lazy" decoding="async" src="<?= esc(article_img($item['slug'])) ?>" alt="<?= esc($item['title']) ?>">
              </a>
              <div class="post-card-body">
                <a class="chip" href="<?= esc(category_url($category['slug'])) ?>"><?= esc($category['title']) ?></a>
                <h3><a href="<?= esc(article_url($item['slug'])) ?>"><?= esc($item['title']) ?></a></h3>
                <?php if ($item['description'] !== ''): ?><p><?= esc($item['description']) ?></p><?php endif; ?>
                <a class="btn btn-ghost" href="<?= esc(article_url($item['slug'])) ?>">Čítať článok</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php elseif (!$featuredArticles): ?>
      <article class="card legal-card">
        <h2>Obsah pre túto kategóriu pripravujeme</h2>
        <p>Najbližší krok je doplniť sériu článkov, ktoré pokryjú výber, porovnania aj časté otázky.</p>
      </article>
    <?php endif; ?>
  </div>

  <aside class="sidebar" aria-label="Pravý panel">
    <?php include __DIR__ . '/inc/components/latest_articles.php'; ?>

    <article class="ad-card info-panel">
      <h3>Čo riešiť ako prvé</h3>
      <ul class="sidebar-link-list">
        <?php foreach (array_slice((array) ($hub['questions'] ?? []), 0, 3) as $question): ?>
          <li><?= esc($question) ?></li>
        <?php endforeach; ?>
      </ul>
    </article>

    <?php if ($relatedCategories): ?>
      <article class="ad-card related-categories-card">
        <h3>Súvisiace kategórie</h3>
        <ul class="sidebar-link-list related-category-list">
          <?php foreach ($relatedCategories as $related): ?>
            <li>
              <a href="<?= esc($related['url']) ?>"><?= esc($related['title']) ?></a>
              <span><?= (int) $related['count'] ?> článkov</span>
            </li>
          <?php endforeach; ?>
        </ul>
      </article>
    <?php endif; ?>

    <article class="ad-card">
      <h3>Heureka vyhľadávanie</h3>
      <div class="heureka-affiliate-searchpanel" data-trixam-positionid="67512" data-trixam-codetype="iframe" data-trixam-linktarget="top"></div>
    </article>
  </aside>
</section>
<?php include __DIR__ . '/inc/footer.php';