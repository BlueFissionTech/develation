<?php

namespace BlueFission\Parsing;

use BlueFission\Parsing\Registry\TagRegistry;

/**
 * Handles the variable registry and root-level tree parsing
 */
class Root extends Element {

    public function __construct(string $input = '', $open = null, $close = null)
    {
        parent::__construct(TagRegistry::ROOT, $input, $input);
        
        if ($open) {
            $this->block->open = $open;
        }
        if ($close) {
            $this->block->close = $close;
        }
    }
}