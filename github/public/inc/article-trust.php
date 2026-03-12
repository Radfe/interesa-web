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
            'Obsah porovnĂˇvame podÄľa cieÄľa, zloĹľenia, formy produktu a praktickĂ©ho pouĹľitia.',
            'KrĂˇtke shortlisty majĂş zjednoduĹˇiĹĄ orientĂˇciu, nie nahradiĹĄ vlastnĂ© rozhodnutie podÄľa potrieb.',
        ];

        if ($commerce !== null) {
            $points[] = 'Produkty v nĂˇkupnĂ˝ch boxoch vyberĂˇme podÄľa relevancie k tĂ©me ÄŤlĂˇnku a ÄŤitateÄľskĂ©mu zĂˇmeru.';
            $points[] = 'Odkazy do obchodov spravujeme centrĂˇlne cez internĂ© /go/ route, takĹľe ich vieme aktualizovaĹĄ bez zĂˇsahu do textu ÄŤlĂˇnku.';
        }

        switch ($category) {
            case 'proteiny':
                $points[] = 'Pri proteĂ­noch sledujeme typ suroviny, mnoĹľstvo bielkovĂ­n na dĂˇvku, toleranciu laktĂłzy a pomer cena-vĂ˝kon.';
                break;
            case 'kreatin':
            case 'sila':
                $points[] = 'Pri vĂ˝konnostnĂ˝ch doplnkoch dĂˇvame dĂ´raz na formu lĂˇtky, dĂˇvkovanie a to, ÄŤi mĂˇ produkt oporu v beĹľnej praxi.';
                break;
            case 'mineraly':
            case 'imunita':
                $points[] = 'Pri vitamĂ­noch a minerĂˇloch sledujeme najmĂ¤ formu, dĂˇvku a reĂˇlne pouĹľitie, nie len marketingovĂ© tvrdenia.';
                break;
            case 'probiotika-travenie':
            case 'vyziva':
                $points[] = 'Pri trĂˇvenĂ­ a vĂ˝Ĺľive sledujeme zloĹľenie, ÄŤitateÄľnosĹĄ etikety a to, ÄŤi produkt rieĹˇi reĂˇlny problĂ©m, nie len trend.';
                break;
            case 'klby-koza':
                $points[] = 'Pri kolagĂ©ne a kÄşbovej vĂ˝Ĺľive sledujeme typ kolagĂ©nu, dĂˇvku na porciu a dlhodobĂ© praktickĂ© pouĹľitie.';
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

        echo '<section class="article-trust" aria-label="RedakÄŤnĂ© poznĂˇmky">';
        echo '<div class="section-head">';
        echo '<h2>Ako s ÄŤlĂˇnkom pracovaĹĄ</h2>';
        echo '<p class="meta">KrĂˇtke vysvetlenie, ako je obsah pripravenĂ˝, ako fungujĂş odkazy a kedy bol naposledy kontrolovanĂ˝.</p>';
        echo '</div>';
        echo '<div class="article-trust-grid">';

        echo '<article class="article-trust-card">';
        echo '<h3>Ako hodnotĂ­me</h3>';
        echo '<ul class="article-trust-list">';
        foreach ($points as $point) {
            echo '<li>' . esc($point) . '</li>';
        }
        echo '</ul>';
        echo '</article>';

        echo '<article class="article-trust-card">';
        echo '<h3>Ako fungujĂş odkazy</h3>';
        echo '<p>' . esc($commerce !== null
            ? $disclosure
            : 'Aj pri informaÄŤnĂ˝ch ÄŤlĂˇnkoch zachovĂˇvame ÄŤistĂ© internĂ© odkazy a priebeĹľne upratujeme ĹˇtruktĂşru tak, aby bol obsah dlhodobo udrĹľateÄľnĂ˝.') . '</p>';
        echo '<p class="article-meta-inline"><strong>Forma odkazov:</strong> internĂ© <code>/go/</code> route a centrĂˇlna sprĂˇva partnerov.</p>';
        echo '</article>';

        echo '<article class="article-trust-card">';
        echo '<h3>PoslednĂˇ kontrola</h3>';
        if ($updated !== null) {
            echo '<p class="article-meta-inline"><strong>Obsah skontrolovanĂ˝:</strong> ' . esc($updated['date']) . '</p>';
        }
        if ($categoryTitle !== '') {
            echo '<p class="article-meta-inline"><strong>TĂ©ma:</strong> ' . esc($categoryTitle) . '</p>';
        }
        echo '<p class="article-meta-inline"><strong>Slug:</strong> ' . esc(canonical_article_slug($slug)) . '</p>';
        echo '</article>';

        echo '</div>';
        echo '</section>';
    }
}