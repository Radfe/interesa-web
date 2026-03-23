<?php

declare(strict_types=1);

require_once __DIR__ . '/../public/inc/agent-status.php';

$args = [];
foreach (array_slice($_SERVER['argv'] ?? [], 1) as $arg) {
    if (!str_starts_with($arg, '--')) {
        continue;
    }
    [$key, $value] = array_pad(explode('=', substr($arg, 2), 2), 2, '');
    $args[$key] = $value;
}

$status = interessa_agent_status_read();

if (($args['branch'] ?? '') !== '') {
    $status['Current branch'] = $args['branch'];
}
if (($args['current-task'] ?? '') !== '') {
    $status['Current task'] = $args['current-task'];
}
if (($args['next-task'] ?? '') !== '') {
    $status['Next planned task'] = $args['next-task'];
}
if (($args['last-completed'] ?? '') !== '') {
    $status['Last completed task'] = $args['last-completed'];
}
if (($args['files'] ?? '') !== '') {
    $status['Files currently modified'] = array_values(array_filter(array_map('trim', explode(',', $args['files']))));
}
if (($args['progress'] ?? '') !== '') {
    $status['Overall progress'] = array_values(array_filter(array_map('trim', explode('|', $args['progress']))));
}

interessa_agent_status_write($status);
echo "AGENT_STATUS.md updated." . PHP_EOL;
