<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\Parsing\Contracts\IProcessor;

class ProcessorRegistor {
    protected static array $processors = [];

    public static function register(IProcessor $fn): void {
        self::$processors[$fn->name()] = $fn;
    }

    public static function get(string $name): ?IProcessor {
        return self::$processors[$name] ?? null;
    }

    public static function all(): array {
        return self::$processors;
    }
}