<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/functions.php';

$q = trim((string) ($_GET['q'] ?? ''));
$page_title = ($q !== '' ? 'Hladat: ' . $q : 'Hladat clanky') . ' | Interesa';
$page_description = 'Vyhladavanie clankov na Interesa.sk';
include __DIR__ . '/inc/head.php';

function match_article_result(string $slug, array $meta, string $query): ?array {
    if ($query === '') {
        return null;
    }

    $title = $meta[0] ?? humanize_slug($slug);
    $description = $meta[1] ?? '';
    $haystack = mb_strtolower($title . ' ' . $description);
    $needle = mb_strtolower($query);
    $score = 0;

    if (mb_strpos($haystack, $needle) !== false) {
        $score += 5;
    }

    $file = __DIR__ . '/content/articles/' . $slug . '.html';
    if (is_file($file)) {
        $text = mb_strtolower(strip_tags((string) file_get_contents($file)));
        if (mb_strpos($text, $needle) !== false) {
            $score += 3;
        }
    }

    if ($score === 0) {
        return null;
    }

    return [$score, $title, $description, article_url($slug)];
}
?>
<section class="container two-col">
  <div class="content">
    <article class="card">
      <h1>Hladat</h1>
      <?php if ($q === ''): ?>
        <p class="note">Zadaj vyraz do vyhladavania.</p>
      <?php else: ?>
        <p class="note">Vysledky pre: <strong><?= esc($q) ?></strong></p>
        <div class="card-grid">
          <?php
          $hits = [];
          foreach (article_registry() as $slug => $meta) {
              $match = match_article_result($slug, $meta, $q);
              if ($match !== null) {
                  $hits[] = $match;
              }
          }
          usort($hits, static fn($a, $b) => $b[0] <=> $a[0]);
          if (!$hits) {
              echo '<p class="note">Nenasli sa ziadne clanky.</p>';
          }
          foreach ($hits as [$score, $title, $description, $url]): ?>
            <article class="card">
              <h3><a href="<?= esc($url) ?>"><?= esc($title) ?></a></h3>
              <?php if ($description !== ''): ?><div class="meta"><?= esc($description) ?></div><?php endif; ?>
              <div class="actions"><a class="btn" href="<?= esc($url) ?>">Citat</a></div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </article>
  </div>

  <aside class="sidebar" aria-label="Pravy panel">
    <?php include __DIR__ . '/inc/components/latest_articles.php'; ?>
  </aside>
</section>
<?php include __DIR__ . '/inc/footer.php';