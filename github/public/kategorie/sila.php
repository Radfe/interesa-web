<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';

$page_title = 'Sila a výkon | Interesa';
$page_description = 'Kreatín, pre-workout a regenerácia. Ako ich používať a čo vybrať.';
$page_canonical = '/kategorie/sila';
$page_image = asset('img/og-default.jpg');
$page_og_type = 'website';
$page_schema = [
    breadcrumb_schema([
        ['name' => 'Domov', 'url' => '/'],
        ['name' => 'Kategórie', 'url' => '/kategorie'],
        ['name' => 'Sila a výkon', 'url' => '/kategorie/sila'],
    ]),
    [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'Sila a výkon',
        'description' => $page_description,
        'url' => absolute_url('/kategorie/sila'),
    ],
];
include __DIR__ . '/../inc/head.php';
?>
<section class="container two-col">
  <div class="content">
    <article class="card">
      <h1>Sila a výkon</h1>
      <p>Kategória pre kreatín, predtréningovky a doplnky, ktoré dávajú zmysel pri výkone.</p>
      <h2>Odporúčané články</h2>
      <ul class="article-list">
        <li><a href="/clanky/kreatin-porovnanie">Kreatín: porovnanie a výber</a></li>
        <li><a href="/clanky/kedy-brat-kreatin-a-kolko">Kedy brať kreatín a koľko</a></li>
        <li><a href="/clanky/kreatin-monohydrat-vs-hcl">Kreatín monohydrát vs HCl</a></li>
        <li><a href="/clanky/pre-workout">Pre-workout</a></li>
        <li><a href="/clanky/pre-workout-ako-vybrat">Ako vybrať pre-workout</a></li>
      </ul>
    </article>
  </div>
  <?php include __DIR__ . '/../inc/sidebar.php'; ?>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>