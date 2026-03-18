<?php

declare(strict_types=1);

$dryRun = in_array('--dry-run', $argv, true);

$concurrently = DIRECTORY_SEPARATOR === '\\'
    ? 'node_modules\\.bin\\concurrently.cmd'
    : 'node_modules/.bin/concurrently';

$processes = [
    'php artisan serve',
    'php artisan queue:listen --tries=1 --timeout=0',
    'npm run dev',
];

$names = ['server', 'queue', 'vite'];
$colors = ['#93c5fd', '#c4b5fd', '#fdba74'];

if (function_exists('pcntl_fork')) {
    array_splice($processes, 2, 0, 'php artisan pail --timeout=0');
    array_splice($names, 2, 0, 'logs');
    array_splice($colors, 2, 0, '#fb7185');
} else {
    fwrite(STDOUT, "[dev-runner] Skipping 'php artisan pail' because the pcntl extension is unavailable.\n");
}

$parts = [$concurrently];

if ($colors !== []) {
    $parts[] = '-c';
    $parts[] = implode(',', $colors);
}

foreach ($processes as $process) {
    $parts[] = $process;
}

$parts[] = '--names=' . implode(',', $names);
$parts[] = '--kill-others';

$command = implode(' ', array_map(static fn (string $part): string => escapeshellarg($part), $parts));

if ($dryRun) {
    fwrite(STDOUT, $command . PHP_EOL);
    exit(0);
}

passthru($command, $exitCode);

exit($exitCode);
