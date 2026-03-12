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
            'Kratke shortlisty maju zjednodusit orientaciu, nie nahradit vlastne rozhodnutie podla potrieb.',
        ];

        if ($commerce !== null) {
            $points[] = 'Produkty v nakupnych boxoch vyberame podla relevancie k teme clanku a citatelskemu zameru.';
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

        echo '<section class="article-trust" aria-label="Redakcne poznamky">';
        echo '<div class="section-head">';
        echo '<h2>Ako s clankom pracovat</h2>';
        echo '<p class="meta">Kratke vysvetlenie, ako je obsah pripraveny, ako funguju odkazy a kedy bol naposledy kontrolovany.</p>';
        echo '</div>';
        echo '<div class="article-trust-grid">';

        echo '<article class="article-trust-card">';
        echo '<h3>Ako hodnotime</h3>';
        echo '<ul class="article-trust-list">';
        foreach ($points as $point) {
            echo '<li>' . esc($point) . '</li>';
        }
        echo '</ul>';
        echo '</article>';

        echo '<article class="article-trust-card">';
        echo '<h3>Ako funguju odkazy</h3>';
        echo '<p>' . esc($commerce !== null
            ? $disclosure
            : 'Aj pri informacnych clankoch zachovavame ciste interne odkazy a priebezne upratujeme strukturu tak, aby bol obsah dlhodobo udrzatelny.') . '</p>';
        echo '<p class="article-meta-inline"><strong>Forma odkazov:</strong> interne <code>/go/</code> route a centralna sprava partnerov.</p>';
        echo '</article>';

        echo '<article class="article-trust-card">';
        echo '<h3>Posledna kontrola</h3>';
        if ($updated !== null) {
            echo '<p class="article-meta-inline"><strong>Obsah skontrolovany:</strong> ' . esc($updated['date']) . '</p>';
        }
        if ($categoryTitle !== '') {
            echo '<p class="article-meta-inline"><strong>Tema:</strong> ' . esc($categoryTitle) . '</p>';
        }
        echo '<p class="article-meta-inline"><strong>Slug:</strong> ' . esc(canonical_article_slug($slug)) . '</p>';
        echo '</article>';

        echo '</div>';
        echo '</section>';
    }
}
