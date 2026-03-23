<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/head.php';
require_once __DIR__ . '/inc/articles.php';
require_once __DIR__ . '/inc/metrics.php';
require_once __DIR__ . '/inc/faqs.php';

$slug = $ARTICLE_SLUG ?? basename(strtok($_SERVER['REQUEST_URI'] ?? '', '?'));
$slug = preg_replace('~^clanky/|/|\.php$~','',$slug);
$info = article_get($slug);
if (!$info) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
[$title, $perex, $cat] = $info;
views_track($slug);

// Hero images (if exist)
$img_dir = __DIR__ . '/assets/img/articles/';
$hero_sizes = [1600,1200,800,600];
$available = [];
foreach ($hero_sizes as $w) {
  $p = $img_dir . $slug . '-' . $w . '.webp';
  if (is_file($p)) $available[$w] = '/assets/img/articles/'.$slug.'-'.$w.'.webp';
}

$shops = [
  ['GymBeam', '/go/'.$slug.'-gymbeam'],
  ['Aktin', '/go/'.$slug.'-aktin'],
  ['MyProtein', '/go/'.$slug.'-myprotein'],
  ['Protein.sk', '/go/'.$slug.'-proteinsk'],
  ['BioTechUSA', '/go/'.$slug.'-biotechusa'],
];

$content_file = $GLOBALS['__ARTICLE_CONTENT_FILE'] ?? (__DIR__ . '/content/articles/' . $slug . '.html');
?>
<div class="wrap" style="display:grid;grid-template-columns:1fr;gap:24px;max-width:1100px;margin:0 auto;padding:16px;">
  <article class="article">
    <header>
      <p class="eyebrow"><?= htmlspecialchars(articles_categories()[$cat][0] ?? '') ?></p>
      <h1><?= htmlspecialchars($title) ?></h1>
      <p class="lead"><?= htmlspecialchars($perex) ?></p>

      <?php if ($available): ?>
        <figure class="hero">
          <img 
            src="<?= reset($available) ?>" 
            alt="<?= htmlspecialchars($title) ?>" 
            loading="eager" decoding="async"
            srcset="<?php $out=[]; foreach($available as $w=>$url){ $out[] = $url.' '.$w.'w'; } echo htmlspecialchars(implode(', ',$out)); ?>"
            sizes="(max-width: 640px) 100vw, 1100px" />
        </figure>
      <?php endif; ?>

      <div class="cta-row">
        <?php foreach ($shops as $s): ?>
          <a class="cta" href="<?= htmlspecialchars($s[1]) ?>">Pozrieť – <?= htmlspecialchars($s[0]) ?></a>
        <?php endforeach; ?>
      </div>
    </header>

    <section class="content">
      <?php if (is_file($content_file)) { readfile($content_file); } else { echo '<p>Obsah sa pripravuje.</p>'; } ?>
    </section>

    <?php $faq = faq_for_slug($slug); if ($faq): ?>
    <section class="faq">
      <h2>Často kladené otázky</h2>
      <dl>
        <?php foreach ($faq as $qa): ?>
          <dt><?= htmlspecialchars($qa['q']) ?></dt>
          <dd><?= htmlspecialchars($qa['a']) ?></dd>
        <?php endforeach; ?>
      </dl>
      <script type="application/ld+json">
      <?= json_encode([
        '@context'=>'https://schema.org',
        '@type'=>'FAQPage',
        'mainEntity'=>array_map(function($qa){
          return ['@type'=>'Question','name'=>$qa['q'],'acceptedAnswer'=>['@type'=>'Answer','text'=>$qa['a']]];
        }, $faq)
      ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>
      </script>
    </section>
    <?php endif; ?>
  </article>

  <?php require __DIR__ . '/inc/sidebar.php'; ?>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
