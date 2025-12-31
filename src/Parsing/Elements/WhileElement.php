<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\ILoopElement;

class WhileElement extends Element implements ILoopElement
{
    public function run(array $vars): string
    {
        $output = '';
        while ($this->evaluate($vars)) {
            $output .= $this->block->process();
        }
        return $output;
    }

    public function evaluate(array $vars): bool
    {
        // Basic eval pattern for now; could be more complex
        $condition = $this->getAttribute('condition');

        return !!$condition; // crude fallback
    }

    public function getDescription(): string
    {
        $condition = $this->getAttribute('condition');

        $descriptionString = sprintf('While the value %s is true, loop the following block', $condition);

        $this->description = $descriptionString;

        return $this->description;
    }
}