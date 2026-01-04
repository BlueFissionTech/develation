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
}