<?php

declare(strict_types=1);

if (!function_exists('interessa_icon_svg')) {
    function interessa_icon_svg(string $name, array $attrs = []): string {
        $icons = [
            'leaf' => '<path d="M19 4c-6.5 0-11 4-11 10 0 3.2 2 6 5.4 7.5C16.8 16.5 19 11.4 19 4Z" /><path d="M8 14c2.5-.4 5.2-2 8-4.8" />',
            'dumbbell' => '<path d="M3 10v4" /><path d="M6 9v6" /><path d="M18 9v6" /><path d="M21 10v4" /><path d="M6 12h12" />',
            'capsule' => '<path d="M9.5 6.5a3.5 3.5 0 0 1 5 0l3 3a3.5 3.5 0 1 1-5 5l-3-3a3.5 3.5 0 0 1 0-5Z" /><path d="m10 14 4-4" />',
            'shield' => '<path d="M12 3 5.5 6v5.2c0 4.3 2.7 8.3 6.5 9.8 3.8-1.5 6.5-5.5 6.5-9.8V6L12 3Z" /><path d="m9.5 12 1.8 1.8 3.7-4" />',
            'bolt' => '<path d="M13 2 6 13h4l-1 9 7-11h-4l1-9Z" />',
            'rings' => '<circle cx="8" cy="12" r="3" /><circle cx="16" cy="9" r="3" /><circle cx="16" cy="15" r="3" /><path d="M10.5 10.5 13 9" /><path d="M10.5 13.5 13 15" />',
            'flame' => '<path d="M12 3c1.8 2 3 4 3 6.2 0 1.5-.8 2.8-2 3.8.2-2-1-3.4-2.5-4.8C8.8 10 8 11.8 8 13.8 8 17.2 10.7 20 14 20s6-2.8 6-6.2C20 9.3 16.8 5.4 12 3Z" />',
            'molecule' => '<circle cx="7" cy="12" r="2" /><circle cx="15" cy="7" r="2" /><circle cx="17" cy="16" r="2" /><path d="M8.8 10.8 13.2 8.2" /><path d="M8.9 13 15 15.2" /><path d="M15.7 8.9 16.5 14" />',
            'grid' => '<rect x="4" y="4" width="6" height="6" rx="1.2" /><rect x="14" y="4" width="6" height="6" rx="1.2" /><rect x="4" y="14" width="6" height="6" rx="1.2" /><rect x="14" y="14" width="6" height="6" rx="1.2" />',
        ];

        $body = $icons[$name] ?? $icons['grid'];
        $defaults = [
            'class' => 'ui-icon',
            'viewBox' => '0 0 24 24',
            'fill' => 'none',
            'stroke' => 'currentColor',
            'stroke-width' => '1.8',
            'stroke-linecap' => 'round',
            'stroke-linejoin' => 'round',
            'aria-hidden' => 'true',
            'focusable' => 'false',
        ];
        $finalAttrs = array_merge($defaults, $attrs);
        $parts = [];
        foreach ($finalAttrs as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            $parts[] = htmlspecialchars((string) $key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '="' . htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        return '<svg ' . implode(' ', $parts) . '>' . $body . '</svg>';
    }
}

if (!function_exists('interessa_category_icon_name')) {
    function interessa_category_icon_name(string $slug): string {
        return match ($slug) {
            'proteiny' => 'leaf',
            'vyziva' => 'leaf',
            'mineraly' => 'capsule',
            'imunita' => 'shield',
            'sila', 'kreatin' => 'dumbbell',
            'klby-koza' => 'rings',
            'pre-workout' => 'flame',
            'probiotika', 'probiotika-travenie' => 'bolt',
            'aminokyseliny' => 'molecule',
            'doplnkove-prislusenstvo' => 'grid',
            'chudnutie' => 'bolt',
            default => 'grid',
        };
    }
}

if (!function_exists('interessa_category_icon')) {
    function interessa_category_icon(string $slug, array $attrs = []): string {
        return interessa_icon_svg(interessa_category_icon_name($slug), $attrs);
    }
}