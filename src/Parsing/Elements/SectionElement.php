<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IRenderableElement;

class SectionElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        $sectionName = $this->getAttribute('name');
        if ($sectionName) {
            $this->parent->addSection($sectionName, $this);
        }
        return '';
    }

    public function build(): string
    {
        return parent::render();
    }
}