<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Parsing\Element;
use BlueFission\DevElation as Dev;

class InvokeElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        Dev::do('_before', [$this]);
        $this->closed = true; // prevent further scope propogation

        $args = $this->attributes;

        foreach ($args as $key => $value) {
            $value = $this->getAttribute($key) ?? $value;
            $this->block->setVar($key, $value);
        }

        $output = $this->block->process();
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function getDescription(): string
    {
        $descriptionString = sprintf('Inovke code');

        $this->description = $descriptionString;

        return $this->description;
    }
}
