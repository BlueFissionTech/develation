<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IRenderableElement;

class VarElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        $name = $this->attributes['name'] ?? '';

        return (string) ($this->block->getVar($name) ?? '');
    }

    public function getDescription(): string
    {
        $name = $this->attributes['name'] ?? '';

        $descriptionString = sprintf('Echo the value of the variable `%s`.', $name);

        $this->description = $descriptionString;

        return $this->description;
    }
}