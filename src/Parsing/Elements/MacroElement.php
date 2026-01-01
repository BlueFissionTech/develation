<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Parsing\Contracts\IExecutableElement;
use BlueFission\Parsing\Element;
use BlueFission\DevElation as Dev;

class MacroElement extends Element implements IRenderableElement, IExecutableElement
{
    public function render(): string
    {
        Dev::do('_before', [$this]);
        $name = $this->getAttribute('name');
        if (!$name) return '';

        $this->getRoot()->addMacro($name, $this);

        $output = '';
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function invoke(array $args = []): string
    {
        Dev::do('_before', [$args, $this]);
        $this->closed = true; // prevent further scope propogation

        foreach ($args as $key => $value) {
            $this->block->setVar($key, $value);
        }
        $output = $this->block->process();
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function getDescription(): string
    {
        $name = $this->getAttribute('name');

        $descriptionString = sprintf('Define a code block macro named `%s`', $name);

        $this->description = $descriptionString;

        return $this->description;
    }
}
