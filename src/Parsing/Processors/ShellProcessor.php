<?php

namespace BlueFission\Parsing\Processors;

use BlueFission\Parsing\Contracts\IProcessor;
use BlueFission\Parsing\Element;
use BlueFission\System\System;

class ShellProcessor implements IProcessor {
    public function process($content): string {
        if (!is_string($content)) {
            throw new \InvalidArgumentException('Content must be a string');
        }
        
        $result = (new System)->run($content);
        return is_string($result) ? $result : 'Execution completed successfully';
    }
}