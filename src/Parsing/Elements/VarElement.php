<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\DevElation as Dev;

class VarElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        Dev::do('_before', [$this]);
        $name = trim((string)($this->attributes['name'] ?? ''));

        if ($name === '') {
            return '';
        }

        $parsed = $this->parseScopedTransform($name);
        $path = $parsed['path'];
        $chain = $parsed['chain'];
        $requiresValue = $parsed['clone'] || $parsed['mutate'] || strpos($path, '.') !== false;

        if ($requiresValue && !$this->hasPathValue($path)) {
            throw new \RuntimeException("Cannot transform undefined value '{$path}'.");
        }

        $value = $requiresValue ? $this->getPathValue($path) : ($this->block->getVar($path) ?? null);

        if ($chain !== '') {
            $value = $this->applyScopedTransform($value, $chain);
            if ($parsed['mutate']) {
                $this->setPathValue($path, $value);
            }
        }

        $output = $this->stringify($value);
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function getDescription(): string
    {
        $name = $this->attributes['name'] ?? '';

        $descriptionString = sprintf('Echo the value of the variable `%s`.', $name);

        $this->description = $descriptionString;

        return $this->description;
    }

    protected function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string)$value;
        }

        $encoded = json_encode($value);

        return $encoded === false ? '' : $encoded;
    }
}
