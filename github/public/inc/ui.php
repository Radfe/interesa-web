<?php declare(strict_types=1);

/** Bezpečné esc */
function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/** Absolútna URL */
function site_url(string $path = '/'): string {
  $host = $_SERVER['HTTP_HOST'] ?? 'interesa.sk';
  if ($path === '' || $path[0] !== '/') $path = '/'.$path;
  return 'https://' . $host . $path;
}

/** Náhľad obrázku článku (ak chýba, placeholder) */
function article_img(string $slug): string {
  $rel = "/assets/img/articles/$slug.webp";
  return is_file($_SERVER['DOCUMENT_ROOT'].$rel) ? $rel : "/assets/img/placeholder-16x9.svg";
}

/** Hviezdičky 0–5 */
function ui_stars(float $score, ?int $count=null): string {
  $score = max(0.0, min(5.0, $score));
  $w = ($score / 5) * 100;
  $label = number_format($score, 1, ',', ' ') . ' z 5' . ($count ? " · $count hodnotení" : '');
  return '<div class="stars" role="img" aria-label="'.esc($label).'"><span class="bg">★★★★★</span><span class="fg" style="width:'.$w.'%">★★★★★</span></div>';
}

/** CTA tlačidlo do obchodu (nofollow sponsored) */
function ui_btn(string $href, string $label='Do obchodu', string $class='btn'): string {
  return '<a class="'.esc($class).'" href="'.esc($href).'" target="_blank" rel="nofollow sponsored">'.esc($label).'</a>';
}

/** Vyrenderuje tabuľku „Top produkty“ */
function render_top_products(array $TOP, string $title='Top produkty'): void {
  if (!$TOP) return;
  echo '<section class="topbox"><h2>'.esc($title).'</h2><table class="top-products"><tbody>';
  foreach ($TOP as $row) {
    $name   = $row['name']   ?? '';
    $attrs  = $row['attrs']  ?? '';
    $rating = (float)($row['rating'] ?? 0);
    $code   = $row['code']   ?? '';
    $img    = $row['image']  ?? '/assets/img/placeholder-16x9.svg';
    if (!is_file($_SERVER['DOCUMENT_ROOT'].$img)) $img = '/assets/img/placeholder-16x9.svg';
    $link   = '/go/'.rawurlencode($code);

    echo '<tr>'.
           '<td class="pimg"><img loading="lazy" src="'.esc($img).'" alt="'.esc($name).'" width="96" height="96"></td>'.
           '<td class="pinfo">'.
             '<div class="pname">'.esc($name).'</div>'.
             ($attrs ? '<div class="pattrs">'.esc($attrs).'</div>' : '').
             '<div class="rating">'.ui_stars($rating).'</div>'.
           '</td>'.
           '<td class="pcta">'.ui_btn($link, 'Do obchodu').'</td>'.
         '</tr>';
  }
  echo '</tbody></table></section>';
}
