<?php
namespace BlueFission\Tests\Support;

final class TestEnvironment
{
    public static function isNetworkEnabled(): bool
    {
        $value = strtolower((string)getenv('DEV_ELATION_NETWORK_TESTS'));
        if ($value === '') {
            $value = strtolower((string)getenv('RUN_NETWORK_TESTS'));
        }
        return in_array($value, ['1', 'true', 'yes'], true);
    }

    public static function mysqlConfig(): ?array
    {
        $host = getenv('DEV_ELATION_MYSQL_HOST');
        $user = getenv('DEV_ELATION_MYSQL_USER');
        $pass = getenv('DEV_ELATION_MYSQL_PASS');
        $db = getenv('DEV_ELATION_MYSQL_DB');
        $port = getenv('DEV_ELATION_MYSQL_PORT') ?: '3306';

        if ($host === false || $user === false || $db === false) {
            return null;
        }

        return [
            'host' => $host,
            'user' => $user,
            'pass' => $pass === false ? '' : $pass,
            'db' => $db,
            'port' => (int)$port,
        ];
    }

    public static function mongoUri(): ?string
    {
        $uri = getenv('DEV_ELATION_MONGO_URI');
        return $uri === false ? null : $uri;
    }

    public static function mongoConfig(): ?array
    {
        $uri = self::mongoUri();
        if (!$uri) {
            return null;
        }

        $parts = parse_url($uri);
        if ($parts === false) {
            return null;
        }

        $db = isset($parts['path']) ? ltrim($parts['path'], '/') : '';

        return [
            'host' => $parts['host'] ?? 'localhost',
            'port' => isset($parts['port']) ? (int)$parts['port'] : 27017,
            'user' => $parts['user'] ?? '',
            'pass' => $parts['pass'] ?? '',
            'db' => $db,
        ];
    }

    public static function memcachedConfig(): ?array
    {
        $host = getenv('DEV_ELATION_MEMCACHED_HOST');
        $port = getenv('DEV_ELATION_MEMCACHED_PORT') ?: '11211';
        if ($host === false) {
            return null;
        }

        return [
            'host' => $host,
            'port' => (int)$port,
        ];
    }

    public static function tempDir(string $prefix): string
    {
        $base = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        $path = $base . DIRECTORY_SEPARATOR . $prefix . '_' . uniqid('', true);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }

    public static function removeDir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $target = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($target)) {
                self::removeDir($target);
            } else {
                @unlink($target);
            }
        }

        @rmdir($path);
    }
}
