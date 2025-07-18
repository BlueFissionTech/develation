<?php

namespace BlueFission\Parsing;

use BlueFission\Str;

/**
 * Tag types used as tokens by the parser
 */
class TagDefinition {
    public string $name;
    public string $pattern;
    public ?string $unifiedPattern;
    public array $attributes;
    public ?string $interface;
    public ?string $renderer;
    public ?string $class;

    public function __construct(
        string $name,
        string $pattern,
        ?string $unifiedPattern = null,
        array $attributes = [],
        ?string $interface = null,
        ?string $class = null,
        ?string $renderer = null
    ) {
        $this->name = $name;
        $this->pattern = $pattern;
        $this->unifiedPattern = $unifiedPattern;
        $this->attributes = $attributes;
        $this->interface = $interface;
        $this->renderer = $renderer;
        $this->class = $class;
    }

    public function token(): string
    {
        $token = '__'.Str::slugify($this->name, '_').'__';

        return $token;
    }
}
