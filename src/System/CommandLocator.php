<?php

namespace BlueFission\System;

use BlueFission\Arr;
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

        $command = Str::trim((string)$command);
        if ($command === '') {
            return null;
        }

        if (!empty($options['cache']) && Arr::hasKey(self::$_cache, $command)) {
            return self::$_cache[$command];
        }

        if (self::isAbsolutePath($command)) {
            $resolved = self::resolvePath($command);
            return self::remember($command, $resolved, $options);
        }

        $paths = Arr::toArray($options['paths'] ?? []);
        $envPath = $options['env_path'] ?? null;
        if (!Str::is($envPath) || Str::trim((string)$envPath) === '') {
            $envPath = getenv('PATH') ?: '';
        }

        $searchPaths = Arr::merge($paths, Str::make((string)$envPath)->split(PATH_SEPARATOR)->val());
        $extensions = self::extensions();
        $hasExtension = self::hasExtension($command);

        foreach ($searchPaths as $path) {
            $path = Str::trim((string)$path);
            if ($path === '') {
                continue;
            }

            $candidateBase = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $command;
            $result = self::matchExecutable($candidateBase, $extensions, $hasExtension);
            if ($result) {
                return self::remember($command, $result, $options);
            }
        }

        if (!empty($options['use_shell'])) {
            $result = self::shellLocate($command);
            return self::remember($command, $result, $options);
        }

        return self::remember($command, null, $options);
    }

    public static function isWindows(): bool
    {
        return (new Machine())->getOS() === 'Windows';
    }

    protected static function remember(string $command, ?string $value, array $options): ?string
    {
        $value = Dev::apply('_out', $value);
        if (!empty($options['cache'])) {
            self::$_cache[$command] = $value;
        }

        if ($value !== null) {
            Dev::do('_found', [$command, $value]);
        }

        return $value;
    }

    protected static function resolvePath(string $path): ?string
    {
        if (!file_exists($path)) {
            return null;
        }

        $real = realpath($path);
        return $real ? $real : $path;
    }

    protected static function isAbsolutePath(string $command): bool
    {
        if (Str::contains($command, '://')) {
            return true;
        }

        if (self::isWindows()) {
            if (preg_match('/^[A-Za-z]:\\\\/', $command)) {
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
        $normalized = [];

        foreach ($extensions as $ext) {
            $ext = Str::trim((string)$ext);
            if ($ext === '') {
                continue;
            }
            if (Str::sub($ext, 0, 1) !== '.') {
                $ext = '.' . $ext;
            }
            $normalized[] = $ext;
        }

        return $normalized;
    }

    protected static function hasExtension(string $command): bool
    {
        $extension = pathinfo($command, PATHINFO_EXTENSION);
        return $extension !== '';
    }

    protected static function matchExecutable(string $candidateBase, array $extensions, bool $hasExtension): ?string
    {
        if ($hasExtension || count($extensions) === 0) {
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

        if (!$output) {
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
}
