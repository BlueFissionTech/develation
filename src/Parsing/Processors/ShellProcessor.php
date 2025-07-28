<?php

namespace BlueFission\Parsing\Renderers;

use BlueFission\Parsing\Contracts\IProcessor;
use BlueFission\Parsing\Element;
use BlueFission\System\System;

class ShellProcessor implements IProcessor {
    public function process($content): string {
        if (!is_string($content)) {
            throw new \InvalidArgumentException('Content must be a string');
        }

        (new System)->run($content);
    }
}