<?php

declare(strict_types=1);

const INTERESSA_AGENT_STATUS_FILE = __DIR__ . '/../../AGENT_STATUS.md';

function interessa_agent_status_default(): array {
    return [
        'Current branch' => 'codex/feature/admin-system',
        'Current task' => 'Implementing AI status tracking and dashboard',
        'Next planned task' => 'Add quick-create flows for products and affiliate codes in admin',
        'Last completed task' => 'Stabilized frontend text system and added admin dashboard with hero backlog',
        'Files currently modified' => [],
        'Overall progress' => [],
    ];
}

function interessa_agent_status_read(?string $path = null): array {
    $path ??= INTERESSA_AGENT_STATUS_FILE;
    $status = interessa_agent_status_default();

    if (!is_file($path)) {
        return $status;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if (!is_array($lines)) {
        return $status;
    }

    $currentKey = null;
    foreach ($lines as $line) {
        $trimmed = trim((string) $line);
        if ($trimmed === '' || $trimmed === '# AGENT STATUS') {
            continue;
        }

        if (str_ends_with($trimmed, ':')) {
            $currentKey = rtrim($trimmed, ':');
            if (in_array($currentKey, ['Files currently modified', 'Overall progress'], true)) {
                $status[$currentKey] = [];
            } elseif (!array_key_exists($currentKey, $status)) {
                $status[$currentKey] = '';
            }
            continue;
        }

        if ($currentKey === null) {
            continue;
        }

        if (str_starts_with($trimmed, '- ')) {
            $status[$currentKey] ??= [];
            if (!is_array($status[$currentKey])) {
                $status[$currentKey] = [];
            }
            $status[$currentKey][] = trim(substr($trimmed, 2));
            continue;
        }

        $status[$currentKey] = $trimmed;
    }

    return $status;
}

function interessa_agent_status_write(array $status, ?string $path = null): void {
    $path ??= INTERESSA_AGENT_STATUS_FILE;
    $base = interessa_agent_status_default();
    $status = array_merge($base, $status);

    $lines = ['# AGENT STATUS', ''];
    foreach ([
        'Current branch',
        'Current task',
        'Next planned task',
        'Last completed task',
        'Files currently modified',
        'Overall progress',
    ] as $key) {
        $lines[] = $key . ':';
        $value = $status[$key] ?? '';
        if (is_array($value)) {
            foreach ($value as $item) {
                $item = trim((string) $item);
                if ($item !== '') {
                    $lines[] = '- ' . $item;
                }
            }
        } else {
            $lines[] = trim((string) $value);
        }
        $lines[] = '';
    }

    file_put_contents($path, implode(PHP_EOL, $lines));
}
