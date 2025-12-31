<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IRenderableElement;

class OutputElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        $name = $this->getAttribute('name');
        // Pull the rendered section output captured earlier.
        $output = $this->parent->findOutput($name);

        $this->setContent($output);

        return parent::render();
    }

    public function getDescription(): string
    {
        $name = $this->getAttribute('name');

        $descriptionString = sprintf('Designate a new content section "%s"', $name);

        $this->description = $descriptionString;

        return $this->description;
    }
}
