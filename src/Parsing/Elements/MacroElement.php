<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Parsing\Element;

class MacroElement extends Element implements IMacroElement, IRenderableElement
{
    public function render(): string
    {
        $name = $this->getAttribute('name');
        if (!$name) return '';

        $macros = $this->block->field('macros') ?? [];
        $macros[$name] = $this;
        $this->block->field('macros', $macros);

        return '';
    }

    public function invoke(array $args = []): string
    {
        foreach ($args as $key => $value) {
            $this->block->setVar($key, $value);
        }
        return $this->block->process();
    }
}