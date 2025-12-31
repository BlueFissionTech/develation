<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Registry\ValidatorRegistry;
use BlueFission\Parsing\Contracts\ILoopElement;

class UntilElement extends Element implements ILoopElement
{
    public function run(): string
    {
        $output = '';
        while ($this->evaluate()) {
            $output .= $this->block->process();
        }
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