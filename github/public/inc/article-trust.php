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
            'Obsah porovnávame podľa cieľa, zloženia, formy produktu a praktického použitia.',
            'Krátke shortlisty majú zjednodušiť orientáciu, nie nahradiť vlastné rozhodnutie podľa potrieb.',
        ];

        if ($commerce !== null) {
            $points[] = 'Produkty v nákupných boxoch vyberáme podľa relevancie k téme článku a čitateľskému zámeru.';
            $points[] = 'Odkazy do obchodov spravujeme centrálne cez interné /go/ route, takže ich vieme aktualizovať bez zásahu do textu článku.';
        }

        switch ($category) {
            case 'proteiny':
                $points[] = 'Pri proteínoch sledujeme typ suroviny, množstvo bielkovín na dávku, toleranciu laktózy a pomer cena-výkon.';
                break;
            case 'kreatin':
            case 'sila':
                $points[] = 'Pri výkonnostných doplnkoch dávame dôraz na formu látky, dávkovanie a to, či má produkt oporu v bežnej praxi.';
                break;
            case 'mineraly':
            case 'imunita':
                $points[] = 'Pri vitamínoch a mineráloch sledujeme najmä formu, dávku a reálne použitie, nie len marketingové tvrdenia.';
                break;
            case 'probiotika-travenie':
            case 'vyziva':
                $points[] = 'Pri trávení a výžive sledujeme zloženie, čitateľnosť etikety a to, či produkt rieši reálny problém, nie len trend.';
                break;
            case 'klby-koza':
                $points[] = 'Pri kolagéne a kĺbovej výžive sledujeme typ kolagénu, dávku na porciu a dlhodobé praktické použitie.';
                break;
        }

        return array_values(array_unique($points));
    }
}

if (!function_exists('interessa_render_article_trust_box')) {
    function interessa_render_article_trust_box(string $slug, array $meta, ?array $commerce, string $file): void {
        $updated = interessa_article_updated_meta($file);
        $points = interessa_article_methodology_points($slug, $meta, $commerce);
        $categoryMeta = category_meta((string) ($meta['category'] ?? ''));
        $categoryTitle = trim((string) ($categoryMeta['title'] ?? ''));
        $disclosure = interessa_affiliate_disclosure_text();

        echo '<section class="article-trust" aria-label="Redakčné poznámky">';
        echo '<div class="section-head">';
        echo '<h2>Ako s článkom pracovať</h2>';
        echo '<p class="meta">Krátke vysvetlenie, ako je obsah pripravený, ako fungujú odkazy a kedy bol naposledy kontrolovaný.</p>';
        echo '</div>';
        echo '<div class="article-trust-grid">';

        echo '<article class="article-trust-card">';
        echo '<h3>Ako hodnotíme</h3>';
        echo '<ul class="article-trust-list">';
        foreach ($points as $point) {
            echo '<li>' . esc($point) . '</li>';
        }
        echo '</ul>';
        echo '</article>';

        echo '<article class="article-trust-card">';
        echo '<h3>Ako fungujú odkazy</h3>';
        echo '<p>' . esc($commerce !== null
            ? $disclosure
            : 'Aj pri informačných článkoch zachovávame čisté interné odkazy a priebežne upratujeme štruktúru tak, aby bol obsah dlhodobo udržateľný.') . '</p>';
        echo '<p class="article-meta-inline"><strong>Forma odkazov:</strong> interné <code>/go/</code> route a centrálna správa partnerov.</p>';
        echo '</article>';

        echo '<article class="article-trust-card">';
        echo '<h3>Posledná kontrola</h3>';
        if ($updated !== null) {
            echo '<p class="article-meta-inline"><strong>Obsah skontrolovaný:</strong> ' . esc($updated['date']) . '</p>';
        }
        if ($categoryTitle !== '') {
            echo '<p class="article-meta-inline"><strong>Téma:</strong> ' . esc($categoryTitle) . '</p>';
        }
        echo '<p class="article-meta-inline"><strong>Slug:</strong> ' . esc(canonical_article_slug($slug)) . '</p>';
        echo '</article>';

        echo '</div>';
        echo '</section>';
    }
}