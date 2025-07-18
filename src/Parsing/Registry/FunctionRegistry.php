<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\Parsing\Contracts\IToolFunction;

class FunctionRegistry {
    protected static array $functions = [];

    public static function register(IToolFunction $fn): void {
        self::$functions[$fn->name()] = $fn;
    }

    public static function get(string $name): ?IToolFunction {
        return self::$functions[$name] ?? null;
    }

    public static function all(): array {
        return self::$functions;
    }
}
