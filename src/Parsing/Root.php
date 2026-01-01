<?php

namespace BlueFission\Parsing;

use BlueFission\Parsing\Registry\TagRegistry;
use BlueFission\DevElation as Dev;

/**
 * Handles the variable registry and root-level tree parsing
 */
class Root extends Element {

    public function __construct(string $input = '', $open = null, $close = null)
    {
        $input = Dev::apply('_in', $input);
        Dev::do('_before', [$input, $open, $close]);
        parent::__construct(TagRegistry::ROOT, $input, $input);
        
        if ($open) {
            $this->block->open = $open;
        }
        if ($close) {
            $this->block->close = $close;
        }
        Dev::do('_after', [$this]);
    }
}
