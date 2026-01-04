<?php

namespace BlueFission\Parsing\Processors;

use BlueFission\Parsing\Contracts\IProcessor;
use BlueFission\Parsing\Element;
use BlueFission\System\System;
use BlueFission\DevElation as Dev;

class ShellProcessor implements IProcessor {
    public function process($content): string {
        if (!is_string($content)) {
            throw new \InvalidArgumentException('Content must be a string');
        }
        
        $content = Dev::apply('_in', $content);
        Dev::do('_before', [$content]);
        $result = (new System)->run($content);
        $result = is_string($result) ? $result : 'Execution completed successfully';
        $result = Dev::apply('_out', $result);
        Dev::do('_after', [$result]);
        return $result;
    }
}
