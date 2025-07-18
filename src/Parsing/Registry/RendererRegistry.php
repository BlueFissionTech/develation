<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\Parsing\Renderers;
use BlueFission\Parsing\Contracts\IElementRenderer;

class RendererRegistry {
    protected static array $renderers = [];

    public static function register(string $tag, IElementRenderer $renderer): void {
        self::$renderers[$tag] = $renderer;
    }

    public static function get(string $tag): ?IElementRenderer {
        return self::$renderers[$tag] ?? self::$renderers['*'] ?? null;
    }

    public static function all(): array {
        return self::$renderers;
    }

    public static function registerDefaults() {
        self::register('*', new Renderers\DefaultRenderer());
    }
}
