<?php

namespace BlueFission\Parsing\Registry;

use BlueFission\Parsing\Renderers;
use BlueFission\Parsing\Contracts\IElementRenderer;
use BlueFission\DevElation as Dev;

class RendererRegistry {
    protected static array $renderers = [];

    public static function register(string $tag, IElementRenderer $renderer): void {
        $renderer = Dev::apply('_in', $renderer);
        self::$renderers[$tag] = $renderer;
        Dev::do('_after', [$tag, $renderer]);
    }

    public static function get(string $tag): ?IElementRenderer {
        $renderer = self::$renderers[$tag] ?? self::$renderers['*'] ?? null;
        return Dev::apply('_out', $renderer);
    }

    public static function all(): array {
        return Dev::apply('_out', self::$renderers);
    }

    public static function registerDefaults() {
        self::register('*', new Renderers\DefaultRenderer());
        self::register('template', new Renderers\TemplateRenderer());
        self::register('section', new Renderers\SectionRenderer());
        self::register('output', new Renderers\OutputRenderer());
    }
}
