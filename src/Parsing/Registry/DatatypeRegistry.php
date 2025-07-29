<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\IVal;
use BlueFission\Val;
use BlueFission\IObj;

class DatatypeRegistry {
    protected static array $datatypes = [];

    public static function register(string $name, string $class): void {
        if (!is_a($class, IVal::class) || !is_a($class, IObj::class)) {
            throw new \InvalidArgumentException("Class {$class} must implement ".IVal::class." or ".IObj::class);
        }

        self::$datatypes[$name] = $class;
    }

    public static function get(string $name): ?IVal {
        return self::$datatypes[$name] ?? \BlueFission\Val::class;
    }

    public static function all(): array {
        return self::$datatypes;
    }

    public static function defaults(): void
    {
        $map = [
            'text' => \BlueFission\Str::class,
            'number' => \BlueFission\Num::class,
            'flag' => \BlueFission\Flag::class,
            'value' => \BlueFission\Val::class,
            'val' => \BlueFission\Val::class,
            'list' => \BlueFission\Arr::class,
            'date' => \BlueFission\Date::class,
            'object' => \BlueFission\Obj::class,
            'macro' => \BlueFission\Func::class,
        ];

        foreach ($map as $name => $class) {
            self::register($name, $class);
        }
    }
}
