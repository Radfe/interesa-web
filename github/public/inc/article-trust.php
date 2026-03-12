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

        echo '<section class="article-trust" aria-label="' . esc('Redak?n? pozn?mky') . '">';
        echo '<div class="section-head">';
        echo '<h2>' . esc('Ako s ?l?nkom pracova?') . '</h2>';
        echo '<p class="meta">' . esc('Kr?tke vysvetlenie, ako je obsah pripraven?, ako funguj? odkazy a kedy bol naposledy kontrolovan?.') . '</p>';
        echo '</div>';
        echo '<div class="article-trust-grid">';

        echo '<article class="article-trust-card">';
        echo '<h3>' . esc('Ako hodnot?me') . '</h3>';
        echo '<ul class="article-trust-list">';
        foreach ($points as $point) {
            echo '<li>' . esc($point) . '</li>';
        }
        echo '</ul>';
        echo '</article>';

        echo '<article class="article-trust-card">';
        echo '<h3>' . esc('Ako funguj? odkazy') . '</h3>';
        echo '<p>' . esc($commerce !== null
            ? $disclosure
            : 'Aj pri informa?n?ch ?l?nkoch zachov?vame ?ist? intern? odkazy a priebe?ne upratujeme ?trukt?ru tak, aby bol obsah dlhodobo udr?ate?n?.') . '</p>';
        echo '<p class="article-meta-inline"><strong>' . esc('Forma odkazov:') . '</strong> interne <code>/go/</code> route a ' . esc('centr?lna spr?va partnerov.') . '</p>';
        echo '</article>';

        echo '<article class="article-trust-card">';
        echo '<h3>' . esc('Posledn? kontrola') . '</h3>';
        if ($updated !== null) {
            echo '<p class="article-meta-inline"><strong>' . esc('Obsah skontrolovan?:') . '</strong> ' . esc($updated['date']) . '</p>';
        }
        if ($categoryTitle !== '') {
            echo '<p class="article-meta-inline"><strong>' . esc('T?ma:') . '</strong> ' . esc($categoryTitle) . '</p>';
        }
        echo '<p class="article-meta-inline"><strong>' . esc('Slug:') . '</strong> ' . esc(canonical_article_slug($slug)) . '</p>';
        echo '</article>';

        echo '</div>';
        echo '</section>';
    }
}
