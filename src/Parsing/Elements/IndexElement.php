<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IRenderableElement;

class IndexElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        return (string) $this->parent->getIndex();
    }

    public function getDescription(): string
    {
        $index = $this->parent->getIndex();
        $descriptionString = sprintf('Looping counter at index %d', $index);

        $this->description = $descriptionString;

        return $this->description;
    }
}