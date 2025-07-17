<?php

namespace BlueFission\Parsing;

use BlueFission\Parsing\Registry\TagRegistry;

/**
 * Handles the variable registry and root-level tree parsing
 */
class Root extends Element {

    public function __construct(string $input = '')
    {
        parent::__construct(TagRegistry::ROOT, $input, $input);
    }
}