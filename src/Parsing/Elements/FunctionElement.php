<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Contracts\IExecutableElement;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Parsing\Contracts\IToolFunction;
use BlueFission\Parsing\Registry\FunctionRegistry;
use BlueFission\Parsing\Registry\GeneratorRegistry;
use BlueFission\Parsing\Element;
use BlueFission\Arr;
use BlueFission\Flag;
use BlueFission\Str;
use BlueFission\DevElation as Dev;

class FunctionElement extends Element implements IExecutableElement, IRenderableElement
{
    public function render(): string
    {
        Dev::do('_before', [$this]);
        $result = $this->execute();
        $result = Dev::apply('_out', $result);

        // Check for silent attribute
        $silent = Flag::make($this->getAttribute('silent') ?? 'false')->parseBool();
        if ($silent) {
            Dev::do('_after', ['', $this]);
            return '';
        }

        Dev::do('_after', [$result, $this]);
        return $result;
    }

    public function execute(): mixed
    {
        Dev::do('_before', [$this]);
        $rawExpr = Arr::make($this->attributes)->keys()->shift() ?? '';
        $rawExpr = Dev::apply('_in', $rawExpr);

        // Check for assignment syntax -> varName
        $assignTo = null;
        if ($match = Str::make($rawExpr)->matchPattern('/->\s*(\w+)/')) {
            $assignTo = $match[1];
            $rawExpr = Str::make($rawExpr)
                ->replace($match[0], '')
                ->trim()
                ->val();
        }

        // Check if function-style (contains parens)
        if ($parts = Str::make($rawExpr)->matchPattern('/^(\w+)\((.*?)\)$/')) {
            $funcName = $parts[1];
            $args = Str::make($parts[2])
                ->split(',')
                ->map(fn ($arg) => Str::trim((string)$arg))
                ->val();

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
        $rawExpr = Arr::make($this->attributes)->keys()->shift() ?? '';
        if ($match = Str::make($rawExpr)->matchPattern('/->\s*(\w+)/')) {
            $name = $match[1];
        }

        $descriptionString = sprintf('Define a function named `%s`', $name);

        $this->description = $descriptionString;

        return $this->description;
    }
}
