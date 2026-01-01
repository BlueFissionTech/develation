<?php

namespace BlueFission\Parsing;

use BlueFission\Str;
use BlueFission\DevElation as Dev;

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
        $this->name = Dev::apply('_in', $name);
        $this->pattern = Dev::apply('_in', $pattern);
        $this->unifiedPattern = Dev::apply('_in', $unifiedPattern);
        $this->attributes = Dev::apply('_attributes', $attributes);
        $this->interface = Dev::apply('_in', $interface);
        $this->renderer = Dev::apply('_in', $renderer);
        $this->class = Dev::apply('_in', $class);
    }

    public function token(): string
    {
        $token = '__'.Str::slugify($this->name, '_').'__';

        return Dev::apply('_out', $token);
    }
}
