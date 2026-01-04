<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\DevElation as Dev;

class OutputElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        Dev::do('_before', [$this]);
        $name = $this->getAttribute('name');
        // Pull the rendered section output captured earlier.
        $output = $this->parent->findOutput($name);

        $this->setContent($output);

        $output = parent::render();
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function getDescription(): string
    {
        $name = $this->getAttribute('name');

        $descriptionString = sprintf('Designate a new content section "%s"', $name);

        $this->description = $descriptionString;

        return $this->description;
    }
}
