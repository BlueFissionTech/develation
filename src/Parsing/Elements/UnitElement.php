<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IRenderableElement;

class UnitElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        $unitName = $this->getAttribute('name');
        if ($unitName) {
            $this->parent->addUnit($unitName, $this);
        }
        return '';
    }

    public function build(): string
    {
        return parent::render();
    }
}