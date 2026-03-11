<?php
declare(strict_types=1);

if (!function_exists('interessa_affiliate_cta_html')) {
    require_once __DIR__ . '/../affiliate-ui.php';
}

if (!function_exists('cta_button')) {
    function cta_button(string $slug, string $label = 'Kupit', array $attrs = []): string {
        $class = trim((string) ($attrs['class'] ?? 'btn btn-cta')) ?: 'btn btn-cta';
        return interessa_affiliate_cta_html(['code' => $slug], [
            'label' => $label,
            'class' => $class,
        ]);
    }
}