<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\Parsing\Contracts\IToolFunction;
use BlueFission\DevElation as Dev;

class FunctionRegistry {
    protected static array $functions = [];

    public static function register(IToolFunction $fn): void {
        $fn = Dev::apply('_in', $fn);
        self::$functions[$fn->name()] = $fn;
        Dev::do('_after', [$fn]);
    }

    public static function get(string $name): ?IToolFunction {
        $fn = self::$functions[$name] ?? null;
        return Dev::apply('_out', $fn);
    }

    public static function all(): array {
        return Dev::apply('_out', self::$functions);
    }
}
