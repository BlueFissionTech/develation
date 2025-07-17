<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IRenderableElement;

class OutputElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        $name = $this->getAttribute('name');
        $output = $this->parent->findOutput($name);
        return $output;
    }
}