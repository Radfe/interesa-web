<?php
declare(strict_types=1);

if (!function_exists('interessa_hero_prompt_registry')) {
    function interessa_hero_prompt_registry(): array {
        static $registry = null;
        if (is_array($registry)) {
            return $registry;
        }

        $file = __DIR__ . '/../content/media/article-hero-prompts.php';
        $registry = is_file($file) ? include $file : [];
        return is_array($registry) ? $registry : [];
    }
}

if (!function_exists('interessa_hero_prompt_category_subject')) {
    function interessa_hero_prompt_category_subject(string $category, string $title): string {
        $category = normalize_category_slug($category);

        return match ($category) {
            'proteiny' => 'protein supplement, shaker and clean nutrition setting',
            'kreatin', 'sila' => 'sports supplement jar, shaker and subtle gym environment',
            'klby-koza' => 'collagen supplement, scoop and wellness detail',
            'mineraly' => 'mineral supplement capsules in a clean product layout',
            'imunita' => 'vitamins, citrus fruits and healthy lifestyle detail',
            'pre-workout' => 'pre-workout supplement jar and energetic training detail',
            'probiotika-travenie' => 'probiotic capsules and soft wellness styling',
            'aminokyseliny' => 'amino acid supplement, shaker and subtle fitness context',
            'chudnutie' => 'healthy meal and subtle fitness weight loss concept',
            'vyziva' => 'nutrition supplements arranged in a clean modern layout',
            default => 'health supplement product and clean lifestyle detail',
        };
    }
}

if (!function_exists('interessa_build_hero_prompt')) {
    function interessa_build_hero_prompt(string $title, string $category): string {
        $subject = interessa_hero_prompt_category_subject($category, $title);
        return sprintf(
            'Realistic professional hero photo for "%s", %s, bright minimal background, soft pastel palette, modern health and fitness look, natural light, no text, no collage, 3:2 aspect ratio.',
            $title,
            $subject
        );
    }
}

if (!function_exists('interessa_hero_prompt_meta')) {
    function interessa_hero_prompt_meta(string $slug): array {
        $canonicalSlug = function_exists('canonical_article_slug') ? canonical_article_slug($slug) : $slug;
        $registry = interessa_hero_prompt_registry();
        $item = $registry[$canonicalSlug] ?? [];
        $meta = article_meta($canonicalSlug);

        $title = trim((string) ($item['title'] ?? ($meta['title'] ?? '')));
        if ($title === '') {
            $title = humanize_slug($canonicalSlug);
        }

        $category = normalize_category_slug((string) ($item['category'] ?? ($meta['category'] ?? '')));
        $assetPath = (string) ($item['asset_path'] ?? ('public/assets/img/articles/heroes/' . $canonicalSlug . '.webp'));
        $fileName = (string) ($item['file_name'] ?? ($canonicalSlug . '.webp'));
        $altText = (string) ($item['alt_text'] ?? $title);
        $prompt = trim((string) ($item['prompt'] ?? ''));
        if ($prompt === '') {
            $prompt = interessa_build_hero_prompt($title, $category);
        }

        return [
            'title' => $title,
            'category' => $category,
            'file_name' => $fileName,
            'asset_path' => $assetPath,
            'alt_text' => $altText,
            'prompt' => $prompt,
            'status' => (string) ($item['status'] ?? 'todo'),
        ];
    }
}
