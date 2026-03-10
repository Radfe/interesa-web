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

$articles = category_articles($slug);
$page_title = $category['title'] . ' | Interesa';
$page_description = $category['description'];
$page_canonical = category_url($category['slug']);
$page_image = asset('img/og-default.jpg');
$page_og_type = 'website';
$page_schema = [
    breadcrumb_schema([
        ['name' => 'Domov', 'url' => '/'],
        ['name' => 'Kategórie', 'url' => '/kategorie'],
        ['name' => $category['title'], 'url' => $page_canonical],
    ]),
    [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $category['title'],
        'description' => $category['description'],
        'url' => absolute_url($page_canonical),
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => 'Interesa',
            'url' => absolute_url('/'),
        ],
    ],
];

include __DIR__ . '/inc/head.php';
?>
<section class="container two-col">
  <div class="content">
    <article class="card">
      <h1><?= esc($category['title']) ?></h1>
      <p class="meta"><?= esc($category['description']) ?></p>

      <?php if (!$articles): ?>
        <p class="note">V tejto kategórii zatiaľ nemáme žiadne články.</p>
      <?php else: ?>
        <div class="grid-cards">
          <?php foreach ($articles as $item): ?>
            <article class="post-card">
              <a href="<?= esc(article_url($item['slug'])) ?>">
                <img class="thumb" loading="lazy" decoding="async" src="<?= esc(article_img($item['slug'])) ?>" alt="<?= esc($item['title']) ?>">
              </a>
              <a class="chip" href="<?= esc(category_url($category['slug'])) ?>"><?= esc($category['title']) ?></a>
              <h3><a href="<?= esc(article_url($item['slug'])) ?>"><?= esc($item['title']) ?></a></h3>
              <?php if ($item['description'] !== ''): ?><p class="meta"><?= esc($item['description']) ?></p><?php endif; ?>
              <a class="btn" href="<?= esc(article_url($item['slug'])) ?>">Čítať</a>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </article>
  </div>

  <?php include __DIR__ . '/inc/sidebar.php'; ?>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>