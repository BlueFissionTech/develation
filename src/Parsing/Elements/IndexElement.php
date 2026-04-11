<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Str;
use BlueFission\DevElation as Dev;

class IndexElement extends Element implements IRenderableElement
{
    protected function getLoopContext(): ?\BlueFission\Parsing\Contracts\ILoopElement
    {
        $parent = $this->parent;

        while ($parent) {
            if ($parent instanceof \BlueFission\Parsing\Contracts\ILoopElement) {
                return $parent;
            }

            $parent = $parent->getParent();
        }

        return null;
    }

    public function render(): string
    {
        Dev::do('_before', [$this]);
        $index = $this->getScopeVariable('index');

        if ($index === null) {
            $index = $this->getLoopContext()?->getIndex();
        }

        $output = Str::is($index) || is_numeric($index) ? (string)$index : '';
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function getDescription(): string
    {
        $index = $this->getLoopContext()?->getIndex() ?? 0;
        $descriptionString = sprintf('Looping counter at index %d', $index);

        $this->description = $descriptionString;

        return $this->description;
    }
}
