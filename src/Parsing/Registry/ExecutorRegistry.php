<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\Parsing\Executors;
use BlueFission\Parsing\Contracts\IElementExecutor;

class ExecutorRegistry {
    protected static array $executors = [];

    public static function register(string $tag, IElementExecutor $executor): void {
        self::$executors[$tag] = $executor;
    }

    public static function get(string $tag): ?IElementExecutor {
        return self::$executors[$tag] ?? self::$executors['*'] ?? null;
    }

    public static function all(): array {
        return self::$executors;
    }

    public static function registerDefaults() {
        self::register('*', new Executors\DefaultExecutor());
    }
}
