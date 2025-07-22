<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\Parsing\Preparers;
use BlueFission\Parsing\Contracts\IElementPreparer;

class PreparerRegistry {
    protected static array $preparers = [];

    public static function register(IElementPreparer $preparer, ?array $supports = null): void {
        if ($supports !== null) {
            $preparer->supports($supports);
        }

        self::$preparers[] = $preparer;
    }

    public static function all(): array {
        return self::$preparers;
    }

    public static function registerDefaults() {
        self::register(new Preparers\VariablePreparer());
        self::register(new Preparers\PathPreparer());
        self::register(new Preparers\HierarchyPreparer());
        self::register(new Preparers\EventBubblePreparer());
    }
}