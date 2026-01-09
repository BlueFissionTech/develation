<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Parsing\Element;
use BlueFission\DevElation as Dev;

class InvokeElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        Dev::do('_before', [$this]);
        $this->closed = true; // prevent further scope propogation

        // Parse the raw invocation expression to extract macro name + named args.
        $match = $this->getMatch();
        $argString = '';
        if (preg_match('/@invoke\\((.*)\\)/s', $match, $m)) {
            $argString = trim($m[1]);
        }

        $macroName = null;

        // First token (quoted or unquoted) is treated as the macro name.
        if ($argString !== '') {
            if (preg_match('/^([\'\"])(.*)\\1/', $argString, $nameMatch)) {
                $macroName = $nameMatch[2];
                $argString = ltrim(substr($argString, strlen($nameMatch[0])));
            } elseif (preg_match('/^([^\\s,]+)/', $argString, $nameMatch)) {
                $macroName = $nameMatch[1];
                $argString = ltrim(substr($argString, strlen($nameMatch[0])));
            }
        }

        // Fallback to attribute-based name if needed.
        if (!$macroName) {
            $macroName = $this->getAttribute('name');
        }

        // Look up the macro on the nearest "top" scoped element.
        $macroElement = $macroName ? $this->getTop()->getMacro($macroName) : null;
        if (!$macroElement instanceof MacroElement) {
            $output = '';
            $output = Dev::apply('_out', $output);
            Dev::do('_after', [$output, $this]);
            return $output;
        }

        // Parse remaining key=value pairs as arguments.
        $args = [];
        if ($argString !== '') {
            if (preg_match_all('/([a-zA-Z_][a-zA-Z0-9_-]*)\\s*=\\s*(\"[^\"]*\"|\\\'[^\\\']*\\\'|\\[[^\\]]*\\]|[^\\s]+)/', $argString, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $key = $m[1];
                    $rawVal = $m[2];
                    // Treat macro arguments as literal values by default:
                    // strip quotes/brackets but do not resolve as scoped vars.
                    $val = trim($rawVal, "\"'");
                    $args[$key] = $val;
                }
            }
        }

        $output = $macroElement->invoke($args);
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function getDescription(): string
    {
        $descriptionString = sprintf('Inovke code');

        $this->description = $descriptionString;

        return $this->description;
    }
}
