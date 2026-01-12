<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Registry\ValidatorRegistry;
use BlueFission\Parsing\Contracts\ILoopElement;
use BlueFission\DevElation as Dev;

class UntilElement extends Element implements ILoopElement
{
    public function run(array $vars): string
    {
        Dev::do('_before', [$vars, $this]);

        $output = '';
        $max = $this->getAttribute('max')
            ?? $this->getAttribute('limit')
            ?? $this->getAttribute('attempts')
            ?? 10;
        $max = is_numeric($max) ? (int)$max : 10;
        if ($max < 1) {
            $max = 1;
        }

        $this->parse();

        $attempts = 0;
        do {
            $attempts++;
            $this->block->setContent($this->getRaw());
            $this->block->process();
            $output = $this->block->content;
        } while (!$this->evaluate() && $attempts < $max);

        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this, $attempts]);
        return $output;
    }

    public function evaluate(): bool
    {
        // Basic eval pattern for now; could be more complex
        $validator = $this->getAttribute('validator');
        if (!$validator) {
            return false;
        }

        try {
            $validatorObj = ValidatorRegistry::get($validator);
            if (!$validatorObj) {
                return false;
            }
            $result = $validatorObj->validate($this);
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
