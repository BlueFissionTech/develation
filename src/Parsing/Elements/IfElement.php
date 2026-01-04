<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IConditionElement;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\DevElation as Dev;

class IfElement extends Element implements IConditionElement, IRenderableElement
{
    public function evaluate(): bool
    {
        Dev::do('_before', [$this]);
        $left = $this->getAttribute('var') ?? null;
        $right = $this->getAttribute('equals') ??
                 $this->getAttribute('not_equals') ??
                 $this->getAttribute('gt') ??
                 $this->getAttribute('lt') ??
                 $this->getAttribute('gte') ??
                 $this->getAttribute('lte') ?? null;

        $operator = match(true) {
            isset($this->attributes['not_equals']) => '!=',
            isset($this->attributes['gt']) => '>',
            isset($this->attributes['lt']) => '<',
            isset($this->attributes['gte']) => '>=',
            isset($this->attributes['lte']) => '<=',
            default => '==',
        };

        $result = match($operator) {
            '!=' => $left != $right,
            '>' => $left > $right,
            '<' => $left < $right,
            '>=' => $left >= $right,
            '<=' => $left <= $right,
            default => $left == $right,
        };

        $result = Dev::apply('_out', $result);
        Dev::do('_after', [$result, $this]);
        return $result;
    }

    public function getDescription(): string
    {
        $left = $this->getAttribute('var') ?? null;
        $right = $this->getAttribute('equals') ??
                 $this->getAttribute('not_equals') ??
                 $this->getAttribute('gt') ??
                 $this->getAttribute('lt') ??
                 $this->getAttribute('gte') ??
                 $this->getAttribute('lte') ?? null;

        $operator = match(true) {
            isset($this->attributes['not_equals']) => '!=',
            isset($this->attributes['gt']) => '>',
            isset($this->attributes['lt']) => '<',
            isset($this->attributes['gte']) => '>=',
            isset($this->attributes['lte']) => '<=',
            default => '==',
        };

        $descriptionString = sprintf('Evaluate if %s %s %s', $left, $operator, $right);

        $this->description = $descriptionString;

        return $this->description;
    }
}
