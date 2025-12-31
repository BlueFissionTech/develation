<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Contracts\IExecutableElement;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Parsing\Contracts\IToolFunction;
use BlueFission\Parsing\Registry\FunctionRegistry;
use BlueFission\Parsing\Registry\GeneratorRegistry;
use BlueFission\Parsing\Element;

class FunctionElement extends Element implements IExecutableElement, IRenderableElement
{
    public function render(): string
    {
        $result = $this->execute();

        // Check for silent attribute
        $silent = $this->getAttribute('silent') ?? 'false';
        if (filter_var($silent, FILTER_VALIDATE_BOOLEAN)) {
            return '';
        }

        return $result;
    }

    public function execute(): mixed
    {
        $rawExpr = array_keys($this->attributes)[0] ?? '';

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
