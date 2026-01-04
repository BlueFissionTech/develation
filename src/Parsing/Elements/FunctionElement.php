<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Contracts\IExecutableElement;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Parsing\Contracts\IToolFunction;
use BlueFission\Parsing\Registry\FunctionRegistry;
use BlueFission\Parsing\Registry\GeneratorRegistry;
use BlueFission\Parsing\Element;
use BlueFission\DevElation as Dev;

class FunctionElement extends Element implements IExecutableElement, IRenderableElement
{
    public function render(): string
    {
        Dev::do('_before', [$this]);
        $result = $this->execute();
        $result = Dev::apply('_out', $result);

        // Check for silent attribute
        $silent = $this->getAttribute('silent') ?? 'false';
        if (filter_var($silent, FILTER_VALIDATE_BOOLEAN)) {
            Dev::do('_after', ['', $this]);
            return '';
        }

        Dev::do('_after', [$result, $this]);
        return $result;
    }

    public function execute(): mixed
    {
        Dev::do('_before', [$this]);
        $rawExpr = array_keys($this->attributes)[0] ?? '';
        $rawExpr = Dev::apply('_in', $rawExpr);

        // Check for assignment syntax -> varName
        $assignTo = null;
        if (preg_match('/->\s*(\w+)/', $rawExpr, $match)) {
            $assignTo = $match[1];
            $rawExpr = trim(str_replace($match[0], '', $rawExpr));
        }

        // Check if function-style (contains parens)
        if (preg_match('/^(\w+)\((.*?)\)$/', $rawExpr, $parts)) {
            $funcName = $parts[1];
            $args = array_map('trim', explode(',', $parts[2]));

            $function = FunctionRegistry::get($funcName);
            $result = $function
                ? $function->execute($args)
                : GeneratorRegistry::get()?->generate($this);
        } else {
            // Use the generator directly (e.g., {=summary})
            $result = GeneratorRegistry::get()?->generate($this);
        }

        if ($assignTo) {
            $this->block->setVar($assignTo, $result);
        }

        Dev::do('_after', [$result, $this]);
        return $result;
    }

    public function getDescription(): string
    {
        $name = 'undefined';
        if (preg_match('/->\s*(\w+)/', $rawExpr, $match)) {
            $name = $match[1];
            // $rawExpr = trim(str_replace($match[0], '', $rawExpr));
        }

        $descriptionString = sprintf('Define a function named `%s`', $name);

        $this->description = $descriptionString;

        return $this->description;
    }
}
