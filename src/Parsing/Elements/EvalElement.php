<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Block;
use BlueFission\Parsing\IElementRenderer;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Parsing\Contracts\IExecutableElement;

class EvalElement extends Element implements IExecutableElement, IRenderableElement
{
    protected mixed $evaluatedValue = null;

    public function execute(): mixed
    {
        [$expression, $assignTo, $silent] = $this->parseAttributes();

        $result = null;

        if ($this->isFunctionCall($expression)) {
            $result = $this->invokeTool($expression);
        } else {
            $result = $this->useGenerator($expression);
        }

        if ($assignTo) {
            $this->block->setVar($assignTo, $result);
        }

        $this->evaluatedValue = $result;

        return $result;
    }

    public function render(): string
    {
        $this->execute();

        [$expression, $assignTo, $silent] = $this->parseAttributes();

        if ($silent || $assignTo) {
            return '';
        }

        return (string)$this->evaluatedValue;
    }

    protected function parseAttributes(): array
    {
        $attrString = trim(array_keys($this->attributes)[0] ?? '');
        $assignTo = null;
        $silent = false;

        if (strpos($attrString, '->') !== false) {
            [$expression, $assignRaw] = explode('->', $attrString, 2);
            $expression = trim($expression);
            $assignTo = trim($assignRaw);
        } else {
            $expression = $attrString;
        }

        // Check for silent='true' inline
        if (preg_match('/silent=[\'"]?true[\'"]?/i', $attrString)) {
            $silent = true;
        }

        return [$expression, $assignTo, $silent];
    }

    protected function isFunctionCall(string $expression): bool
    {
        return preg_match('/^\w+\(.*\)$/', $expression);
    }

    protected function invokeTool(string $expression): mixed
    {
        // Stub for tool resolution — this would route to a registry in real use
        $function = substr($expression, 0, strpos($expression, '('));
        $args = trim(substr($expression, strlen($function)), '()');

        $argArray = [];
        if ($args) {
            foreach (explode(',', $args) as $arg) {
                $arg = trim($arg);
                $val = $this->block->getVar($arg) ?? $arg;
                $argArray[] = $val;
            }
        }

        // Fake tool registry
        $tools = [
            'summarize' => fn($text) => "Summary of: $text",
            'classifyTone' => fn($text) => "Tone: Neutral",
        ];

        if (isset($tools[$function])) {
            return $tools[$function](...$argArray);
        }

        return "[Unknown tool: $function]";
    }

    protected function useGenerator(string $expression): mixed
    {
        // Very simple fallback generator
        return "Lorem Ipsum";
    }
}
