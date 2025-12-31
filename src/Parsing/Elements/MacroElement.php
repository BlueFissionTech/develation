<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Parsing\Contracts\IExecutableElement;
use BlueFission\Parsing\Element;

class MacroElement extends Element implements IRenderableElement, IExecutableElement
{
    public function render(): string
    {
        $name = $this->getAttribute('name');
        if (!$name) return '';

        $this->getRoot()->addMacro($name, $this);

        return '';
    }

    public function invoke(array $args = []): string
    {
        $this->closed = true; // prevent further scope propogation

        foreach ($args as $key => $value) {
            $this->block->setVar($key, $value);
        }
        return $this->block->process();
    }

    public function getDescription(): string
    {
        $name = $this->getAttribute('name');

        $descriptionString = sprintf('Define a code block macro named `%s`', $name);

        $this->description = $descriptionString;

        return $this->description;
    }
}