<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\Parsing\Contracts\IGenerator;
use BlueFission\DevElation as Dev;

class GeneratorRegistry {
    protected static ?IGenerator $generator = null;

    public static function set(IGenerator $gen): void {
        $gen = Dev::apply('_in', $gen);
        self::$generator = $gen;
        Dev::do('_after', [$gen]);
    }

    public static function get(): ?IGenerator {
        $gen = self::$generator;
        return Dev::apply('_out', $gen);
    }
}
