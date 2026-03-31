<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\ILoopElement;
use BlueFission\Parsing\Contracts\IRenderableElement;
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
            return trim($matches[1] ?? '');
        }

        if (preg_match('/^\{\.(.*?)\}$/', $match, $matches)) {
            return trim($matches[1] ?? '');
        }

        return '';
    }

    protected function resolveCurrentValue(mixed $value, string $path = ''): mixed
    {
        if (!$path) {
            return $value;
        }

        foreach (explode('.', $path) as $part) {
            if (is_array($value) && array_key_exists($part, $value)) {
                $value = $value[$part];
            } elseif (is_object($value) && property_exists($value, $part)) {
                $value = $value->$part;
            } else {
                return null;
            }
        }

        return $value;
    }

    public function render(): string
    {
        Dev::do('_before', [$this]);
        $loop = $this->getLoopContext();
        if (!$loop) {
            return '';
        }

        $path = $this->getCurrentPath();
        $value = $this->resolveCurrentValue($loop->getScopeVariable('current'), $path);

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
