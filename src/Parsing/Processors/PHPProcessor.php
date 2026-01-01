<?php

namespace BlueFission\Parsing\Processors;

use BlueFission\Parsing\Contracts\IProcessor;
use BlueFission\Parsing\Element;
use BlueFission\DevElation as Dev;

class PHPProcessor implements IProcessor {
    public function process($content): string {
        if (!is_string($content)) {
            throw new \InvalidArgumentException('Content must be a string');
        }

        $content = Dev::apply('_in', $content);
        Dev::do('_before', [$content]);
        ob_start(); // Start output buffering
        eval($content); // Execute the PHP code
        $output = ob_get_clean(); // Capture the output
        $output = is_string($output) ? $output : ''; // Ensure a string is returned
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output]);
        return $output;
    }
}
