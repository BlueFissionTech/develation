<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\Parsing\Validators;
use BlueFission\Parsing\Contracts\IValidator;
use BlueFission\DevElation as Dev;

class ValidatorRegistry {
    protected static array $validators = [];

    public static function register(string $tag, IValidator $validator): void {
        $validator = Dev::apply('_in', $validator);
        self::$validators[$tag] = $validator;
        Dev::do('_after', [$tag, $validator]);
    }

    public static function get(string $tag): ?IValidator {
        $validator = self::$validators[$tag] ?? self::$validators['*'] ?? null;
        return Dev::apply('_out', $validator);
    }

    public static function all(): array {
        return Dev::apply('_out', self::$validators);
    }

    public static function registerDefaults() {
        self::register('notEmpty', new Validators\NotEmptyValidator());
    }
}
