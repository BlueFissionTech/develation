<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Parsing\Contracts\IExecutableElement;
use BlueFission\Parsing\Element;
use BlueFission\DevElation as Dev;

class MacroElement extends Element implements IRenderableElement, IExecutableElement
{
    public function execute(): mixed
    {
        // For macros, execution is equivalent to rendering the macro body
        // in-place. We return an empty string here since macros are intended
        // to be invoked via @invoke, not executed as standalone output.
        return $this->render();
    }

    public function render(): string
    {
        Dev::do('_before', [$this]);
        $name = $this->getAttribute('name');
        if (!$name) return '';

        // Parse the macro body once so its internal elements (vars, etc.) are
        // available when invoked later, then register it on the nearest
        // top-level scoped element (not always the absolute ROOT).
        $this->parse();

        $this->getTop()->addMacro($name, $this);

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
        $this->block->process();
        $output = $this->block->content;
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
