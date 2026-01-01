<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\ILoopElement;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\DevElation as Dev;

class CurrentElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        Dev::do('_before', [$this]);
        if (!$this->parent || !($this->parent instanceof ILoopElement)) {
            return '';
        }

        $var = $this?->parent->getCurrent() . ($this->raw ? '.' . $this->raw : '');
        
        $value = $this->getNestedValue($var);

        $output = (string)$value;
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function getDescription(): string
    {
        $current = $this->parent->getCurrent();
        $descriptionString = sprintf('Looping with current item `%d`', $current);

        $this->description = $descriptionString;

        return $this->description;
    }
}
