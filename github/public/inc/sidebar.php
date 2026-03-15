<?php
declare(strict_types=1);

require_once __DIR__ . '/category-hubs.php';
require_once __DIR__ . '/article-commerce.php';

$sidebarCategorySlug = '';
if (isset($sidebarContextCategorySlug) && is_string($sidebarContextCategorySlug) && trim($sidebarContextCategorySlug) !== '') {
    $sidebarCategorySlug = normalize_category_slug($sidebarContextCategorySlug);
}

$sidebarGuideLinks = [];
$sidebarCommercialLinks = [];
if ($sidebarCategorySlug !== '') {
    $sidebarHub = interessa_category_hub($sidebarCategorySlug);
    foreach ((array) ($sidebarHub['featured_guides'] ?? []) as $guide) {
        $guideSlug = trim((string) ($guide['slug'] ?? ''));
        if ($guideSlug === '') {
            continue;
        }

        $guideMeta = article_meta($guideSlug);
        $sidebarGuideLinks[] = [
            'href' => article_url($guideSlug),
            'label' => (string) ($guideMeta['title'] ?? humanize_slug($guideSlug)),
            'note' => trim((string) ($guide['description'] ?? '')),
        ];
    }

    foreach (array_values(category_articles($sidebarCategorySlug)) as $item) {
        $itemSlug = trim((string) ($item['slug'] ?? ''));
        if ($itemSlug === '') {
            continue;
        }

        $summary = interessa_article_commerce_summary($itemSlug);
        if (!is_array($summary) || (int) ($summary['count'] ?? 0) <= 0) {
            continue;
        }

        $itemMeta = article_meta($itemSlug);
        $itemFile = dirname(__DIR__) . '/content/articles/' . $itemSlug . '.html';
        $sidebarCommercialLinks[] = [
            'href' => article_url($itemSlug),
            'label' => (string) ($itemMeta['title'] ?? humanize_slug($itemSlug)),
            'full_coverage' => interessa_article_has_full_packshot_coverage($itemSlug),
            'coverage_percent' => interessa_shortlist_coverage_percent($summary),
            'note' => interessa_article_has_full_packshot_coverage($itemSlug)
                ? 'Mas tam aj rychly prehlad a odporucane produkty.'
                : 'Clanok uz obsahuje odporucane produkty a prakticky vyber.',
            'updated_ts' => is_file($itemFile) ? (int) @filemtime($itemFile) : 0,
        ];
    }

    usort($sidebarCommercialLinks, static function (array $a, array $b): int {
        $fullCoverageCompare = ((int) (!empty($b['full_coverage']))) <=> ((int) (!empty($a['full_coverage'])));
        if ($fullCoverageCompare !== 0) {
            return $fullCoverageCompare;
        }

        $coverageCompare = ((int) ($b['coverage_percent'] ?? 0)) <=> ((int) ($a['coverage_percent'] ?? 0));
        if ($coverageCompare !== 0) {
            return $coverageCompare;
        }

        return ((int) ($b['updated_ts'] ?? 0)) <=> ((int) ($a['updated_ts'] ?? 0));
    });

    $sidebarCommercialLinks = array_slice($sidebarCommercialLinks, 0, 3);
}

if ($sidebarGuideLinks === []) {
    $sidebarGuideLinks = [
        ['href' => '/clanky/najlepsie-proteiny-2026', 'label' => 'Najlepsie proteiny 2026', 'note' => 'Rychly vstup do proteinov podla ciela, typu a rozpoctu.'],
        ['href' => '/clanky/kreatin-porovnanie', 'label' => 'Kreatin: porovnanie a vyber', 'note' => 'Najkratsia cesta ku vyberu kreatinu bez zbytocneho chaosu.'],
        ['href' => '/clanky/veganske-proteiny-top-vyber-2026', 'label' => 'Veganske proteiny', 'note' => 'Vyber rastlinnych proteinov, ak nechces mliecnu alternativu.'],
        ['href' => '/clanky/kolagen-recenzia', 'label' => 'Kolagen: recenzia a vyber', 'note' => 'Prehlad kolagenov podla pouzitia, nie len marketingu.'],
    ];
}

$sidebarGuideTitle = $sidebarCategorySlug !== '' ? 'Zacat v tejto teme' : 'Top nakupne navody';
?>
<aside class="sidebar" aria-label="Pravy panel">
  <?php $latestArticlesContextCategorySlug = $sidebarCategorySlug; ?>
  <?php include __DIR__ . '/components/latest_articles.php'; ?>

  <section class="widget">
    <h3>Zacat podla ciela</h3>
    <ul class="list">
      <li><a href="/kategorie/proteiny">Proteiny a regeneracia</a></li>
      <li><a href="/kategorie/sila">Sila, kreatin a vykon</a></li>
      <li><a href="/kategorie/mineraly">Vitaminy a mineraly</a></li>
      <li><a href="/kategorie/klby-koza">Klby, koza a kolagen</a></li>
    </ul>
  </section>

  <section class="widget">
    <h3><?= esc($sidebarGuideTitle) ?></h3>
    <ul class="list">
      <?php foreach ($sidebarGuideLinks as $link): ?>
        <li>
          <a href="<?= esc((string) ($link['href'] ?? '/clanky/')) ?>"><?= esc((string) ($link['label'] ?? 'Clanok')) ?></a>
          <?php if (trim((string) ($link['note'] ?? '')) !== ''): ?><br><span class="muted"><?= esc((string) ($link['note'] ?? '')) ?></span><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <?php if ($sidebarCommercialLinks !== []): ?>
    <section class="widget">
      <h3>Kde sa dostanes k vyberu najrychlejsie</h3>
      <ul class="list">
        <?php foreach ($sidebarCommercialLinks as $link): ?>
          <li>
            <a href="<?= esc((string) ($link['href'] ?? '/clanky/')) ?>"><?= esc((string) ($link['label'] ?? 'Clanok')) ?></a><br>
            <span class="muted"><?= esc((string) ($link['note'] ?? 'Clanok uz obsahuje odporucane produkty.')) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

  <section class="widget">
    <h3>Ako funguju odkazy</h3>
    <p class="muted">Niektore odkazy vedu na partnerske obchody. Ak cez ne nakupis, web moze ziskat proviziu bez navysenia ceny pre teba. Odkazy priebezne kontrolujeme tak, aby smerovali na relevantne produkty.</p>
  </section>
</aside>
