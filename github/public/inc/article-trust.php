<?php
declare(strict_types=1);

require_once __DIR__ . '/affiliate-ui.php';

if (!function_exists('interessa_article_updated_meta')) {
    function interessa_article_updated_meta(string $file): ?array {
        if (!is_file($file)) {
            return null;
        }

        $timestamp = @filemtime($file);
        if (!is_int($timestamp) || $timestamp <= 0) {
            return null;
        }

        return [
            'timestamp' => $timestamp,
            'date' => date('d.m.Y', $timestamp),
            'iso' => date(DATE_ATOM, $timestamp),
        ];
    }
}

if (!function_exists('interessa_article_methodology_points')) {
    function interessa_article_methodology_points(string $slug, array $meta, ?array $commerce): array {
        $category = trim((string) ($meta['category'] ?? ''));
        $points = [
            'Obsah porovnavame podla ciela, zlozenia, formy produktu a praktickeho pouzitia.',
            'Kratke vybery maju ulahcit orientaciu, nie nahradit vlastne rozhodnutie podla potrieb.',
        ];

        if ($commerce !== null) {
            $points[] = 'Produkty vo vybere vyberame podla relevancie k teme clanku a tomu, co citatel najcastejsie hlada.';
            $points[] = 'Odkazy do obchodov spravujeme centralne cez interne /go/ route, takze ich vieme aktualizovat bez zasahu do textu clanku.';
        }

        switch ($category) {
            case 'proteiny':
                $points[] = 'Pri proteinoch sledujeme typ suroviny, mnozstvo bielkovin na davku, toleranciu laktozy a pomer cena-vykon.';
                break;
            case 'kreatin':
            case 'sila':
                $points[] = 'Pri vykonnostnych doplnkoch davame doraz na formu latky, davkovanie a to, ci ma produkt oporu v beznej praxi.';
                break;
            case 'mineraly':
            case 'imunita':
                $points[] = 'Pri vitaminoch a mineraloch sledujeme najma formu, davku a realne pouzitie, nie len marketingove tvrdenia.';
                break;
            case 'probiotika-travenie':
            case 'vyziva':
                $points[] = 'Pri traveni a vyzive sledujeme zlozenie, citatelnost etikety a to, ci produkt riesi realny problem, nie len trend.';
                break;
            case 'klby-koza':
                $points[] = 'Pri kolagene a klbovej vyzive sledujeme typ kolagenu, davku na porciu a dlhodobe prakticke pouzitie.';
                break;
        }

        return array_values(array_unique($points));
    }
}

if (!function_exists('interessa_article_trust_shortlist_meta')) {
    function interessa_article_trust_shortlist_meta(?array $commerce): ?array {
        $stats = interessa_commerce_shortlist_stats($commerce);
        if ($stats === null) {
            return null;
        }

        return [
            'count' => (int) ($stats['count'] ?? 0),
            'real_packshots' => (int) ($stats['real_packshots'] ?? 0),
            'merchant_count' => (int) ($stats['merchant_count'] ?? 0),
            'editorial_visuals' => (int) ($stats['editorial_visuals'] ?? 0),
        ];
    }
}

if (!function_exists('interessa_render_article_trust_box')) {
    function interessa_render_article_trust_box(string $slug, array $meta, ?array $commerce, ?string $file): void {
        $updated = ($file !== null && $file !== '') ? interessa_article_updated_meta($file) : null;
        if ($updated === null) {
            $adminArticle = interessa_admin_article_content($slug);
            $updatedAt = trim((string) ($adminArticle['updated_at'] ?? ''));
            if ($updatedAt !== '') {
                $timestamp = strtotime($updatedAt);
                if (is_int($timestamp) || (is_numeric($timestamp) && (int) $timestamp > 0)) {
                    $timestamp = (int) $timestamp;
                    if ($timestamp > 0) {
                        $updated = [
                            'timestamp' => $timestamp,
                            'date' => date('d.m.Y', $timestamp),
                            'iso' => date(DATE_ATOM, $timestamp),
                        ];
                    }
                }
            }
        }

        $points = interessa_article_methodology_points($slug, $meta, $commerce);
        $categoryMeta = category_meta((string) ($meta['category'] ?? ''));
        $categoryTitle = trim((string) ($categoryMeta['title'] ?? ''));
        $disclosure = interessa_affiliate_disclosure_text();
        $formatLabel = interessa_article_format_label($slug, (string) ($meta['title'] ?? ''));
        $shortlistMeta = interessa_article_trust_shortlist_meta($commerce);

        echo '<section class="article-trust" aria-label="' . esc('Redakcne poznamky') . '">';
        echo '<div class="section-head">';
        echo '<h2>' . esc('Ako s clankom pracovat') . '</h2>';
        echo '<p class="meta">' . esc('Kratke vysvetlenie, ako je obsah pripraveny, ako funguju odkazy a kedy bol naposledy kontrolovany.') . '</p>';
        echo '</div>';
        echo '<div class="article-trust-grid">';

        echo '<article class="article-trust-card">';
        echo '<h3>' . esc('Ako hodnotime') . '</h3>';
        echo '<ul class="article-trust-list">';
        foreach ($points as $point) {
            echo '<li>' . esc($point) . '</li>';
        }
        echo '</ul>';
        echo '</article>';

        echo '<article class="article-trust-card">';
        echo '<h3>' . esc('Ako funguju odkazy') . '</h3>';
        echo '<p>' . esc($commerce !== null
            ? $disclosure
            : 'Aj pri informacnych clankoch zachovavame ciste interne odkazy a priebezne upratujeme strukturu tak, aby bol obsah dlhodobo udrzatelny.') . '</p>';
        echo '<p class="article-meta-inline"><strong>' . esc('Forma odkazov:') . '</strong> interne <code>/go/</code> route a ' . esc('centralna sprava partnerov.') . '</p>';
        echo '</article>';

        echo '<article class="article-trust-card">';
        echo '<h3>' . esc('Posledna kontrola') . '</h3>';
        if ($updated !== null) {
            echo '<p class="article-meta-inline"><strong>' . esc('Obsah skontrolovany:') . '</strong> ' . esc($updated['date']) . '</p>';
        }
        echo '<p class="article-meta-inline"><strong>' . esc('Format:') . '</strong> ' . esc($formatLabel) . '</p>';
        if ($categoryTitle !== '') {
            echo '<p class="article-meta-inline"><strong>' . esc('Tema:') . '</strong> ' . esc($categoryTitle) . '</p>';
        }
        if ($shortlistMeta !== null) {
            echo '<p class="article-meta-inline"><strong>' . esc('Vyber produktov:') . '</strong> ' . esc((string) $shortlistMeta['count']) . ' ' . esc(interessa_pluralize_slovak((int) $shortlistMeta['count'], 'produkt', 'produkty', 'produktov')) . '</p>';
            if (($shortlistMeta['merchant_count'] ?? 0) > 0) {
                echo '<p class="article-meta-inline"><strong>' . esc('Porovnane obchody:') . '</strong> ' . esc((string) $shortlistMeta['merchant_count']) . '</p>';
            }
            if (($shortlistMeta['editorial_visuals'] ?? 0) > 0) {
                echo '<p class="article-meta-inline">' . esc('Pri niektorych produktoch este doplname finalne obrazky baleni.') . '</p>';
            }
        }
        echo '<p class="article-meta-inline"><strong>' . esc('Slug:') . '</strong> ' . esc(canonical_article_slug($slug)) . '</p>';
        echo '</article>';

        echo '</div>';
        echo '</section>';
    }
}
