<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\ILoopElement;
use BlueFission\DevElation as Dev;

class WhileElement extends Element implements ILoopElement
{
    public function run(array $vars): string
    {
        Dev::do('_before', [$vars, $this]);
        $output = '';
        while ($this->evaluate($vars)) {
            $output .= $this->block->process();
        }
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function evaluate(array $vars): bool
    {
        // Basic eval pattern for now; could be more complex
        $condition = $this->getAttribute('condition');

        $result = !!$condition; // crude fallback
        $result = Dev::apply('_out', $result);
        return $result;
    }

    public function getDescription(): string
    {
        $condition = $this->getAttribute('condition');

        $descriptionString = sprintf('While the value %s is true, loop the following block', $condition);

        $this->description = $descriptionString;

        return $this->description;
    }
}
