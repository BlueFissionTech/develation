<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Parsing\Element;

class InvokeElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        $this->closed = true; // prevent further scope propogation

        $args = $this->attributes;

        foreach ($args as $key => $value) {
            $value = $this->getAttribute($key) ?? $value;
            $this->block->setVar($key, $value);
        }
        return $this->block->process();
    }
}