<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\DevElation as Dev;

class IndexElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        Dev::do('_before', [$this]);
        $output = (string) $this->parent->getIndex();
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function getDescription(): string
    {
        $index = $this->parent->getIndex();
        $descriptionString = sprintf('Looping counter at index %d', $index);

        $this->description = $descriptionString;

        return $this->description;
    }
}
