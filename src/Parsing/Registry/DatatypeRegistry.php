<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\IVal;
use BlueFission\Val;
use BlueFission\IObj;
use BlueFission\DevElation as Dev;

class DatatypeRegistry {
    protected static array $datatypes = [];

    public static function register(string $name, string $class): void {
        $class = Dev::apply('_in', $class);
        if (!class_implements($class, IVal::class) || !class_implements($class, IObj::class)) {
            throw new \InvalidArgumentException("Class {$class} must implement ".IVal::class." or ".IObj::class);
        }

        self::$datatypes[$name] = $class;
        Dev::do('_after', [$name, $class]);
    }

    public static function get(string $name): ?string {
        $class = self::$datatypes[$name] ?? \BlueFission\Val::class;
        return Dev::apply('_out', $class);
    }

    public static function all(): array {
        return Dev::apply('_out', self::$datatypes);
    }

    public static function registerDefaults(): void
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
