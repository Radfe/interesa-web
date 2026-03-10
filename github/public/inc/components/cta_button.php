<?php
declare(strict_types=1);
/**
 * Interesa – inc/components/cta_button.php (kompletný súbor)
 * CTA komponent pre affiliate prekliky cez /go/<slug>.
 * Bez zásahu do CSS – výzor rieši existujúce štýly.
 */

if (!function_exists('esc') || !function_exists('site_url')) {
  require_once __DIR__ . '/../functions.php';
}

/**
 * CTA tlačidlo – vracia HTML <a>.
 */
function cta_button(string $slug, string $label = 'Kúpiť', array $attrs = []): string {
  $href = site_url('/go/' . $slug);

  $baseAttrs = [
    'class' => 'btn btn-cta',
    'href'  => $href,
    'rel'   => 'nofollow sponsored noopener',
    'target'=> '_blank',
    'data-slug' => $slug,
  ];

  $final = array_merge($baseAttrs, $attrs);
  $htmlAttrs = '';
  foreach ($final as $k => $v) {
    $htmlAttrs .= ' ' . esc((string)$k) . '="' . esc((string)$v) . '"';
  }

  return '<a' . $htmlAttrs . '>' . esc($label) . '</a>';
}
