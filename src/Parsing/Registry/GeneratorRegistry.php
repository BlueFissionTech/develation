<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\Parsing\Contracts\IGenerator;

class GeneratorRegistry {
    protected static ?IGenerator $generator = null;

    public static function set(IGenerator $gen): void {
        self::$generator = $gen;
    }

    public static function get(): ?IGenerator {
        return self::$generator;
    }
}
