<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\DevElation as Dev;

class StandardRegistry {
    protected static array $std = [];

    public static function register($name, $tool): void {
        $tool = Dev::apply('_in', $tool);
        self::$std[$name] = $tool;
        Dev::do('_after', [$name, $tool]);
    }

    public static function get(string $name) {
        $tool = self::$std[$name] ?? null;
        return Dev::apply('_out', $tool);
    }

    public static function all(): array {
        return Dev::apply('_out', self::$std);
    }
}
