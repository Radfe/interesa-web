<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';

$page_title = 'Kĺby a koža | Interesa';
$page_description = 'Kolagén a kĺbová výživa. Porovnania, recenzie a ako vybrať čo funguje.';
$page_canonical = '/kategorie/klby-koza';
$page_image = asset('img/og-default.jpg');
$page_og_type = 'website';
$page_schema = [
    breadcrumb_schema([
        ['name' => 'Domov', 'url' => '/'],
        ['name' => 'Kategórie', 'url' => '/kategorie'],
        ['name' => 'Kĺby a koža', 'url' => '/kategorie/klby-koza'],
    ]),
    [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'Kĺby a koža',
        'description' => $page_description,
        'url' => absolute_url('/kategorie/klby-koza'),
    ],
];
include __DIR__ . '/../inc/head.php';
?>
<section class="container two-col">
  <div class="content">
    <article class="card">
      <h1>Kĺby a koža</h1>
      <p>Najdôležitejšie informácie o kolagéne a kĺbovej výžive na jednom mieste.</p>
      <h2>Odporúčané články</h2>
      <ul class="article-list">
        <li><a href="/clanky/kolagen">Kolagén – základ</a></li>
        <li><a href="/clanky/kolagen-na-klby-porovnanie">Kolagén na kĺby – porovnanie</a></li>
        <li><a href="/clanky/kolagen-recenzia">Kolagén – recenzia</a></li>
        <li><a href="/clanky/klby-a-kolagen">Kĺby a kolagén</a></li>
      </ul>
    </article>
  </div>
  <?php include __DIR__ . '/../inc/sidebar.php'; ?>
</section>
<?php include __DIR__ . '/../inc/footer.php'; ?>