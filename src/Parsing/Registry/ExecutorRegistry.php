<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\Parsing\Executors;
use BlueFission\Parsing\Contracts\IElementExecutor;
use BlueFission\DevElation as Dev;

class ExecutorRegistry {
    protected static array $executors = [];

    public static function register(string $tag, IElementExecutor $executor): void {
        $executor = Dev::apply('_in', $executor);
        self::$executors[$tag] = $executor;
        Dev::do('_after', [$tag, $executor]);
    }

    public static function get(string $tag): ?IElementExecutor {
        $executor = self::$executors[$tag] ?? self::$executors['*'] ?? null;
        return Dev::apply('_out', $executor);
    }

    public static function all(): array {
        return Dev::apply('_out', self::$executors);
    }

    public static function registerDefaults() {
        self::register('*', new Executors\DefaultExecutor());
    }
}
