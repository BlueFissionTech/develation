<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\Parsing\Contracts\IProcessor;
use BlueFission\DevElation as Dev;

class ProcessorRegistry {
    protected static array $processors = [];

    public static function register(IProcessor $fn): void {
        $fn = Dev::apply('_in', $fn);
        self::$processors[$fn->name()] = $fn;
        Dev::do('_after', [$fn]);
    }

    public static function get(string $name): ?IProcessor {
        $processor = self::$processors[$name] ?? null;
        return Dev::apply('_out', $processor);
    }

    public static function all(): array {
        return Dev::apply('_out', self::$processors);
    }
}
