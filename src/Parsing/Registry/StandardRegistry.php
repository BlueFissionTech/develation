<?php

namespace BlueFission\Parsing\Registry;

class StandardRegistry {
    protected static array $std = [];

    public static function register($name, $tool): void {
        self::$std[$name] = $tool;
    }

    public static function get(string $name) {
        return self::$std[$name] ?? null;
    }

    public static function all(): array {
        return self::$std;
    }
}