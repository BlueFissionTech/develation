<?php

namespace BlueFission\System;

use BlueFission\Arr;
use BlueFission\Data\FileSystem;
use BlueFission\Flag;
use BlueFission\Str;
use BlueFission\DevElation as Dev;

class CommandLocator
{
    protected static $_cache = [];

    public static function find(string $command, array $options = []): ?string
    {
        $command = Dev::apply('_in', $command);
        $options = Dev::apply('_options', $options);
        $options = Arr::merge([
            'paths' => [],
            'env_path' => null,
            'use_shell' => true,
            'cache' => true,
        ], $options);
        $options = Arr::make($options);

        $command = Str::trim((string)$command);
        if ($command === '') {
            return null;
        }

        if (self::optionEnabled($options, 'cache', true) && Arr::hasKey(self::$_cache, $command)) {
            return self::$_cache[$command];
        }

        if (self::isAbsolutePath($command)) {
            $resolved = self::resolvePath($command);
            return self::remember($command, $resolved, $options->val());
        }

        $paths = Arr::toArray($options['paths'] ?? []);
        $envPath = $options['env_path'] ?? null;
        if (!Str::is($envPath) || Str::make((string)$envPath)->trim()->isEmpty()) {
            $envPath = getenv('PATH') ?: '';
        }

        $searchPaths = Arr::make($paths)
            ->merge(Str::make((string)$envPath)->split(PATH_SEPARATOR))
            ->val();
        $extensions = self::extensions();
        $hasExtension = self::hasExtension($command);

        foreach ($searchPaths as $path) {
            $path = Str::make((string)$path)->trim();
            if ($path->isEmpty()) {
                continue;
            }

            $candidateBase = $path->trim(DIRECTORY_SEPARATOR)->append(DIRECTORY_SEPARATOR)->append($command)->val();
            $result = self::matchExecutable($candidateBase, $extensions, $hasExtension);
            if ($result) {
                return self::remember($command, $result, $options->val());
            }
        }

        if (self::optionEnabled($options, 'use_shell', true)) {
            $result = self::shellLocate($command);
            return self::remember($command, $result, $options->val());
        }

        return self::remember($command, null, $options->val());
    }

    public static function isWindows(): bool
    {
        return (new Machine())->getOS() === 'Windows';
    }

    protected static function remember(string $command, ?string $value, array $options): ?string
    {
        $value = Dev::apply('_out', $value);
        if (self::optionEnabled(Arr::make($options), 'cache', true)) {
            self::$_cache[$command] = $value;
        }

        if ($value !== null) {
            Dev::do('_found', [$command, $value]);
        }

        return $value;
    }

    protected static function resolvePath(string $path): ?string
    {
        if (!FileSystem::fileExists($path)) {
            return null;
        }

        $real = realpath($path);
        return $real ? $real : $path;
    }

    protected static function isAbsolutePath(string $command): bool
    {
        if (Str::has($command, '://')) {
            return true;
        }

        if (self::isWindows()) {
            if (Str::matchPattern($command, '/^[A-Za-z]:\\\\/')) {
                return true;
            }

            return Str::sub($command, 0, 2) === '\\\\';
        }

        return Str::sub($command, 0, 1) === '/';
    }

    protected static function extensions(): array
    {
        if (!self::isWindows()) {
            return [''];
        }

        $pathext = getenv('PATHEXT') ?: '.EXE;.BAT;.CMD;.COM';
        $extensions = Str::make($pathext)->split(';')->val();
        $normalized = Arr::make();

        foreach ($extensions as $ext) {
            $ext = Str::make((string)$ext)->trim();
            if ($ext->isEmpty()) {
                continue;
            }
            if (!$ext->startsWith('.')) {
                $ext->prepend('.');
            }
            $normalized[] = $ext->val();
        }

        return $normalized->val();
    }

    protected static function hasExtension(string $command): bool
    {
        $extension = pathinfo($command, PATHINFO_EXTENSION);
        return $extension !== '';
    }

    protected static function matchExecutable(string $candidateBase, array $extensions, bool $hasExtension): ?string
    {
        if ($hasExtension || Arr::count($extensions) === 0) {
            return self::resolvePath($candidateBase);
        }

        foreach ($extensions as $extension) {
            $candidate = $candidateBase . $extension;
            $resolved = self::resolvePath($candidate);
            if ($resolved) {
                return $resolved;
            }
        }

        return null;
    }

    protected static function shellLocate(string $command): ?string
    {
        $command = escapeshellarg($command);
        if (self::isWindows()) {
            $output = shell_exec("where $command");
        } else {
            $output = shell_exec("command -v $command 2>/dev/null");
            if (!$output) {
                $output = shell_exec("which $command 2>/dev/null");
            }
        }

        if (Str::isEmpty($output)) {
            return null;
        }

        $lines = Str::make($output)->split(PHP_EOL)->val();
        foreach ($lines as $line) {
            $line = Str::trim((string)$line);
            if ($line === '') {
                continue;
            }
            $resolved = self::resolvePath($line);
            if ($resolved) {
                return $resolved;
            }
        }

        return null;
    }

    protected static function optionEnabled(Arr $options, string $key, bool $default = false): bool
    {
        if (!$options->hasKey($key)) {
            return $default;
        }

        return Flag::parseBool($options[$key], $default);
    }
}
