<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\Parsing\Preparers;
use BlueFission\Parsing\Contracts\IElementPreparer;
use BlueFission\DevElation as Dev;

class PreparerRegistry {
    protected static array $preparers = [];

    public static function register(IElementPreparer $preparer, ?array $supports = null): void {
        $preparer = Dev::apply('_in', $preparer);
        if ($supports !== null) {
            $preparer->setsSupported($supports);
        }

        self::$preparers[] = $preparer;
        Dev::do('_after', [$preparer, $supports]);
    }

    public static function all(): array {
        return Dev::apply('_out', self::$preparers);
    }

    public static function registerDefaults() {
        self::register(new Preparers\VariablePreparer());
        self::register(new Preparers\PathPreparer());
        self::register(new Preparers\HierarchyPreparer());
        self::register(new Preparers\EventBubblePreparer());
    }
}
