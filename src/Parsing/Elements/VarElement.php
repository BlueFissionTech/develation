<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IRenderableElement;

class VarElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        $var = array_keys($this->attributes)[0];
        return (string) ($this->block->getVar($var) ?? '');
    }
}