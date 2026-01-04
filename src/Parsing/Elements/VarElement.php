<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\DevElation as Dev;

class VarElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        Dev::do('_before', [$this]);
        $name = $this->attributes['name'] ?? '';

        $output = (string) ($this->block->getVar($name) ?? '');
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function getDescription(): string
    {
        $name = $this->attributes['name'] ?? '';

        $descriptionString = sprintf('Echo the value of the variable `%s`.', $name);

        $this->description = $descriptionString;

        return $this->description;
    }
}
