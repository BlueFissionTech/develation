<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\Parsing\Validators;
use BlueFission\Parsing\Contracts\IValidator;

class ValidatorRegistry {
    protected static array $validators = [];

    public static function register(string $tag, IValidator $validator): void {
        self::$validators[$tag] = $validator;
    }

    public static function get(string $tag): ?IValidator {
        return self::$validators[$tag] ?? self::$validators['*'] ?? null;
    }

    public static function all(): array {
        return self::$validators;
    }

    public static function registerDefaults() {
        self::register('notEmpty', new Validators\NotEmptyValidator());
    }
}
