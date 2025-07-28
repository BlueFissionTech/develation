<?php

namespace BlueFission\Parsing\Renderers;

use BlueFission\Parsing\Contracts\IProcessor;
use BlueFission\Parsing\Element;

class PHPProcessor implements IProcessor {
    public function process($content): string {
        if (!is_string($content)) {
            throw new \InvalidArgumentException('Content must be a string');
        }

        // potentially unsafe!
        eval($content);
    }
}