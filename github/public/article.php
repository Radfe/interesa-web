<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/metrics.php';

$slug = preg_replace('~[^a-z0-9\-_]+~i', '', (string) ($_GET['slug'] ?? ''));
$file = article_file($slug);
if ($slug === '' || !is_file($file)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    return;
}

views_track($slug);
$meta = article_meta($slug);
$category = $meta['category'] !== '' ? category_meta($meta['category']) : null;
$relatedArticles = related_articles($slug, 3);
$readingTime = article_reading_time($slug);
$outline = article_outline($slug, 6);
$faqItems = article_faq_items($slug);
$updatedIso = $meta['mtime'] > 0 ? date('c', (int) $meta['mtime']) : '';
$updatedLabel = $meta['mtime'] > 0 ? date('d.m.Y', (int) $meta['mtime']) : '';
$page_title = $meta['title'] . ' | Interesa';
$page_description = $meta['description'] !== '' ? $meta['description'] : 'Praktický článok a porovnanie na Interesa.';
$page_type = 'Article';
$page_image = $meta['image'];
$page_section = $category['title'] ?? '';
$page_published = $updatedIso;
$page_modified = $updatedIso;
$page_schema_extra = [];
if ($faqItems) {
    $page_schema_extra[] = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(
            static fn(array $item): array => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ],
            $faqItems
        ),
    ];
}
include __DIR__ . '/inc/head.php';
?>
<section class="container two-col">
  <div class="content content-stack">
    <nav class="breadcrumbs" aria-label="Breadcrumb">
      <a href="/">Domov</a>
      <span aria-hidden="true">/</span>
      <a href="/clanky/">Články</a>
      <?php if ($category !== null): ?>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(category_url($category['slug'])) ?>"><?= esc($category['title']) ?></a>
      <?php endif; ?>
      <span aria-hidden="true">/</span>
      <span><?= esc($meta['title']) ?></span>
    </nav>

    <article class="lead-article article-shell">
      <?php if ($category !== null): ?>
        <a class="chip" href="<?= esc(category_url($category['slug'])) ?>"><?= esc($category['title']) ?></a>
      <?php endif; ?>

      <h1><?= esc($meta['title']) ?></h1>
      <?php if ($meta['description'] !== ''): ?><p class="lead"><?= esc($meta['description']) ?></p><?php endif; ?>

      <div class="article-meta-strip">
        <?php if ($updatedLabel !== ''): ?><span>Aktualizované <?= esc($updatedLabel) ?></span><?php endif; ?>
        <span><?= (int) $readingTime ?> min čítania</span>
        <span>Redakčný obsah s transparentným označením affiliate odkazov</span>
      </div>

      <?php if ($outline): ?>
        <section class="article-summary-card">
          <div>
            <span class="eyebrow">Rýchla orientácia</span>
            <h2>V článku nájdeš</h2>
            <p class="section-intro">Najdôležitejšie body, cez ktoré sa vieš rýchlo dostať k praktickému záveru.</p>
          </div>

          <ul class="summary-list">
            <?php foreach ($outline as $item): ?>
              <li>
                <?php if ($item['id'] !== ''): ?>
                  <a href="#<?= esc($item['id']) ?>"><?= esc($item['text']) ?></a>
                <?php else: ?>
                  <span><?= esc($item['text']) ?></span>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>

          <p class="disclosure-note">Ak článok obsahuje odporúčané odkazy, môžu byť affiliate. Na cene pre návštevníka sa nič nemení.</p>
        </section>
      <?php endif; ?>

      <div class="article-body">
        <?= article_body_html($slug) ?>
      </div>

      <section class="article-actions">
        <div>
          <h2>Pokračovať v téme</h2>
          <p>Ak si chceš porovnať ďalšie varianty alebo prejsť širší kontext, pokračuj do súvisiacej kategórie alebo ďalších článkov.</p>
        </div>
        <div class="action-row">
          <?php if ($category !== null): ?>
            <a class="btn btn-primary" href="<?= esc(category_url($category['slug'])) ?>">Prejsť do kategórie</a>
          <?php endif; ?>
          <a class="btn btn-ghost" href="/clanky/">Ďalšie články</a>
        </div>
      </section>

      <?php if ($relatedArticles): ?>
        <section class="related-reading">
          <div class="section-heading section-heading-tight">
            <div>
              <span class="eyebrow">Súvisiace čítanie</span>
              <h2>Na túto tému nadväzujú aj ďalšie praktické články</h2>
              <p class="section-intro">Ak chceš lepší kontext pred výberom produktu, toto sú najlepšie ďalšie kroky.</p>
            </div>
          </div>

          <div class="grid-cards related-grid">
            <?php foreach ($relatedArticles as $item): ?>
              <article class="post-card post-card-compact">
                <a href="<?= esc($item['url']) ?>">
                  <img class="thumb" src="<?= esc($item['image']) ?>" alt="<?= esc($item['title']) ?>" loading="lazy" decoding="async">
                </a>
                <div class="post-card-body">
                  <a class="chip" href="<?= esc($item['category_url']) ?>"><?= esc($item['category_title']) ?></a>
                  <h3><a href="<?= esc($item['url']) ?>"><?= esc($item['title']) ?></a></h3>
                  <?php if ($item['description'] !== ''): ?><p><?= esc($item['description']) ?></p><?php endif; ?>
                  <a class="card-link" href="<?= esc($item['url']) ?>">Otvoriť článok</a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>
    </article>
  </div>

  <aside class="sidebar" aria-label="Pravý panel">
    <?php include __DIR__ . '/inc/components/latest_articles.php'; ?>

    <article class="ad-card info-panel">
      <h3>Ako čítať odporúčania</h3>
      <p>Najprv porovnaj typ produktu a cieľ použitia. Až potom rieš značku, príchuť alebo cenu.</p>
    </article>

    <article class="ad-card">
      <h3>Heureka vyhľadávanie</h3>
      <div class="heureka-affiliate-searchpanel" data-trixam-positionid="67512" data-trixam-codetype="iframe" data-trixam-linktarget="top"></div>
    </article>

    <article class="ad-card">
      <h3>Top ponuky</h3>
      <div class="heureka-affiliate-category" data-trixam-positionid="40746" data-trixam-categoryid="5526" data-trixam-codetype="iframe" data-trixam-linktarget="top"></div>
    </article>
  </aside>
</section>
<?php include __DIR__ . '/inc/footer.php';