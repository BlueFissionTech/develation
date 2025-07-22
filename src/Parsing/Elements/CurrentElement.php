<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\ILoopElement;
use BlueFission\Parsing\Contracts\IRenderableElement;

class CurrentElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        if (!$this->parent || !($this->parent instanceof ILoopElement)) {
            return '';
        }

        $var = $this?->parent?->getCurrent() . ($this->raw ? '.' . $this->raw : '');
        
        $value = $this->getNestedValue($var);

        return (string)$value;
    }
}