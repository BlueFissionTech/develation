<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\ILoopElement;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Str;
use BlueFission\DevElation as Dev;

class CurrentElement extends Element implements IRenderableElement
{
    protected function getLoopContext(): ?ILoopElement
    {
        $parent = $this->parent;

        while ($parent) {
            if ($parent instanceof ILoopElement) {
                return $parent;
            }

            $parent = $parent->getParent();
        }

        return null;
    }

    protected function getCurrentPath(): string
    {
        $match = $this->getMatch();

        if (preg_match('/^\{@current\.?(.*?)\}$/', $match, $matches)) {
            return Str::trim((string)($matches[1] ?? ''));
        }

        if (preg_match('/^\{\.(.*?)\}$/', $match, $matches)) {
            return Str::trim((string)($matches[1] ?? ''));
        }

        return '';
    }

    public function render(): string
    {
        Dev::do('_before', [$this]);
        $path = $this->getCurrentPath();
        $value = $this->getScopeVariable('current');

        if ($value === null) {
            $value = $this->getLoopContext()?->getScopeVariable('current');
        }

        if ($value === null) {
            return '';
        }

        if ($path !== '') {
            $value = $this->getPathValue("current.{$path}");
        }

        $output = (string)$value;
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function getDescription(): string
    {
        $current = $this->getLoopContext()?->getCurrent() ?? '';
        $descriptionString = sprintf('Looping with current item `%d`', $current);

        $this->description = $descriptionString;

        return $this->description;
    }
}
