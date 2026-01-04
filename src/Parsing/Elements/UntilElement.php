<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Registry\ValidatorRegistry;
use BlueFission\Parsing\Contracts\ILoopElement;
use BlueFission\DevElation as Dev;

class UntilElement extends Element implements ILoopElement
{
    public function run(): string
    {
        Dev::do('_before', [$this]);
        $output = '';
        while ($this->evaluate()) {
            $output .= $this->block->process();
        }
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function evaluate(): bool
    {
        // Basic eval pattern for now; could be more complex
        $validator = $this->getAttribute('validator');

        try {
            $result = ValidatorRegistry::get($validator)->validate($this);
        } catch (\Exception $e) {
            return false;
        }

        $result = Dev::apply('_out', $result);
        return $result;
    }

    public function getDescription(): string
    {
        $validator = $this->getAttribute('validator');

        $descriptionString = sprintf('Re-generate the content of this element until it is valid against `%s`', $validator);

        $this->description = $descriptionString;

        return $this->description;
    }
}
