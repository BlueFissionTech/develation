<?php

declare(strict_types=1);

require_once __DIR__ . '/support.php';

use BlueFission\Arr;
use BlueFission\Num;
use BlueFission\Str;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, 'This fixture requires the CLI SAPI.' . PHP_EOL);
    exit(1);
}

$size = filter_var($argv[1] ?? '256', FILTER_VALIDATE_INT);
$iterations = filter_var($argv[2] ?? '500', FILTER_VALIDATE_INT);

if ($size === false || $size < 1 || $size > 4096 || $iterations === false || $iterations < 1 || $iterations > 100000) {
    fwrite(STDERR, 'Usage: php examples/benchmark-primitives.php <size:1-4096> <iterations:1-100000>' . PHP_EOL);
    exit(1);
}

$numbers = range(1, $size);
$source = '  ' . str_repeat('A', $size) . '  ';
$cases = [
    'arr_sum' => [fn () => Arr::sum($numbers), intdiv($size * ($size + 1), 2)],
    'str_normalize' => [fn () => Str::make($source)->trim()->lower()->val(), str_repeat('a', $size)],
    'num_clamp' => [fn () => Num::clamp(7, 0, 10), 7],
];

$samples = [];
foreach ($cases as $name => [$run, $expected]) {
    if ($run() !== $expected) {
        throw new RuntimeException("Incorrect benchmark fixture result: {$name}");
    }

    for ($warmup = 0; $warmup < 100; $warmup++) {
        $run();
    }

    $times = [];
    for ($sample = 0; $sample < 5; $sample++) {
        $start = hrtime(true);
        for ($iteration = 0; $iteration < $iterations; $iteration++) {
            $run();
        }
        $times[] = (hrtime(true) - $start) / $iterations;
    }
    sort($times, SORT_NUMERIC);
    $samples[$name] = [
        'median_ns_per_call' => $times[2],
        'samples_ns_per_call' => $times,
    ];
}

$opcache = function_exists('opcache_get_status') ? opcache_get_status(false) : false;
$optionalExtensions = [];
foreach (['curl', 'sqlite3', 'mysqli', 'redis', 'memcached', 'mongodb', 'parallel', 'pcntl', 'Zend OPcache'] as $extension) {
    $optionalExtensions[$extension] = extension_loaded($extension)
        ? (phpversion($extension) ?: 'loaded (version unavailable)')
        : null;
}

echo json_encode([
    'php' => PHP_VERSION,
    'sapi' => PHP_SAPI,
    'os_family' => PHP_OS_FAMILY,
    'opcache_enable_cli' => ini_get('opcache.enable_cli'),
    'opcache_jit' => ini_get('opcache.jit'),
    'opcache_jit_buffer_size' => ini_get('opcache.jit_buffer_size'),
    'opcache_loaded' => extension_loaded('Zend OPcache'),
    'jit_enabled' => is_array($opcache) ? ($opcache['jit']['enabled'] ?? false) : null,
    'optional_extension_versions' => $optionalExtensions,
    'size' => $size,
    'iterations' => $iterations,
    'results' => $samples,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
