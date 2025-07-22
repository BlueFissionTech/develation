<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IRenderableElement;

class CurrentElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        $var = $this->parent->getCurrent() . ($this->raw ? '.' . $this->raw : '');
        
        $value = $this->getNestedValue($var);

        return (string)$value;
    }
}