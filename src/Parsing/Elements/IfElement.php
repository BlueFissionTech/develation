<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IConditionElement;
use BlueFission\Parsing\Contracts\IRenderableElement;

class IfElement extends Element implements IConditionElement, IRenderableElement
{
    public function evaluate(): bool
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

        $result = match($operator) {
            '!=' => $left != $right,
            '>' => $left > $right,
            '<' => $left < $right,
            '>=' => $left >= $right,
            '<=' => $left <= $right,
            default => $left == $right,
        };

        return $result;
    }
}