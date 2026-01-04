<?php

namespace BlueFission\Parsing\Processors;

use BlueFission\Parsing\Contracts\IProcessor;
use BlueFission\Parsing\Element;

class PHPProcessor implements IProcessor {
    public function process($content): string {
        if (!is_string($content)) {
            throw new \InvalidArgumentException('Content must be a string');
        }

        ob_start(); // Start output buffering
        eval($content); // Execute the PHP code
        $output = ob_get_clean(); // Capture the output
        return is_string($output) ? $output : ''; // Ensure a string is returned
    }
}