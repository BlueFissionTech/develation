<?php

declare(strict_types=1);

use BlueFission\Data\Log;
use BlueFission\Str;

$autoload = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if (!is_file($autoload)) {
    fwrite(STDERR, 'Install dependencies with `composer install` before running examples.' . PHP_EOL);
    exit(1);
}

require_once $autoload;

function bf_example_runtime_path(string $path = ''): string
{
    $root = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.localappdata' . DIRECTORY_SEPARATOR . 'examples';

    if (!is_dir($root)) {
        mkdir($root, 0775, true);
    }

    if ($path === '') {
        return $root;
    }

    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

    return $root . DIRECTORY_SEPARATOR . $path;
}

function bf_example_logger(string $name): Log
{
    return new Log([
        'storage' => Log::FILE,
        'file' => bf_example_runtime_path($name . '.log'),
        'instant' => true,
    ]);
}

function bf_example_input(string $key, string $default = ''): string
{
    return Str::trim((string)($_POST[$key] ?? $default));
}

function bf_example_id(string $prefix): string
{
    return $prefix . '_' . Str::uuid4();
}

function bf_example_html(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
