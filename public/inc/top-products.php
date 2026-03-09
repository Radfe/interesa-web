<?php
declare(strict_types=1);

/**
 * Render “Top produkty” v článku.
 *
 * Očakáva pole $TOP_PRODUCTS = [
 *   [
 *     'name'   => 'Názov',
 *     'subtitle' => 'Krátky benefit',
 *     'rating' => 4.7,                // 0–5 (float)
 *     'img'    => '/assets/img/products/mg-bisglycinate.webp'  // alebo absolútna URL
 *     'code'   => 'horcik-ktory-je-najlepsi-a-preco-aktin',    // affiliate kód
 *     'url'    => 'https://.../produkt'                        // Fallback URL, ak kód chýba
 *   ],
 *   ...
 * ];
 *
 * CTA logika:
 * - ak $GO_LINKS[$code] EXISTUJE => použije sa /go/<code> (rel="nofollow sponsored")
 * - ak NEEXISTUJE a je 'url' => použije sa 'url' (rel="nofollow") + odznak “bez affiliate”
 * - ak nie je nič => tlačidlo disabled
 */

if (!function_exists('interessa_render_top_products')) {
  function interessa_render_top_products(array $TOP_PRODUCTS, string $title = 'Top produkty'): void {
    // Načítaj mapu affiliate odkazov (z CSV/ PHP)
    $GO_LINKS = [];
    @include __DIR__ . '/go-links.php';
    if (isset($GO_LINKS) && is_array($GO_LINKS) && !$GO_LINKS) {
      // ak include nenaplnil, skús funkciu
      if (function_exists('interessa_go_links')) {
        $GO_LINKS = interessa_go_links();
      }
    }

    echo '<section class="topbox">';
    echo '<h2>' . htmlspecialchars($title, ENT_QUOTES) . '</h2>';
    echo '<table class="top-products">';
    foreach ($TOP_PRODUCTS as $row) {
      $name     = trim((string)($row['name'] ?? ''));
      $subtitle = trim((string)($row['subtitle'] ?? ''));
      $rating   = (float)($row['rating'] ?? 0);
      $img      = trim((string)($row['img'] ?? ''));
      $code     = trim((string)($row['code'] ?? ''));
      $url      = trim((string)($row['url'] ?? '')); // fallback

      if ($img === '') $img = '/assets/img/placeholder-16x9.svg';

      // CTA rozhodnutie
      $href     = '';
      $rel      = 'nofollow';
      $badge    = '';
      if ($code !== '' && !empty($GO_LINKS[$code])) {
        $href = '/go/' . rawurlencode($code);
        $rel  = 'nofollow sponsored';
      } elseif ($url !== '') {
        $href = $url;
        $badge = '<span class="muted" style="font-size:12px;margin-left:8px">bez affiliate</span>';
      }

      echo '<tr>';
      echo '  <td class="pimg"><img src="' . htmlspecialchars($img, ENT_QUOTES) . '" alt="' . htmlspecialchars($name, ENT_QUOTES) . '" loading="lazy"></td>';
      echo '  <td>';
      echo '    <div class="pname">' . htmlspecialchars($name, ENT_QUOTES) . '</div>';
      if ($subtitle !== '') {
        echo '  <div class="pattrs">' . htmlspecialchars($subtitle, ENT_QUOTES) . '</div>';
      }
      // hviezdičky
      if ($rating > 0) {
        $w = max(0, min(100, round(($rating/5)*100)));
        echo '  <div class="stars"><span class="stars-bg">★★★★★</span><span class="stars-fg" style="width:'.$w.'%">★★★★★</span></div>';
      }
      echo '  </td>';

      echo '  <td class="pcta">';
      if ($href !== '') {
        echo '<a class="btn" href="' . htmlspecialchars($href, ENT_QUOTES) . '" target="_blank" rel="' . $rel . '">Do obchodu</a>' . $badge;
      } else {
        echo '<button class="btn" style="opacity:.5" disabled>Čoskoro</button>';
      }
      echo '  </td>';
      echo '</tr>';
    }
    echo '</table>';
    echo '</section>';
  }
}
