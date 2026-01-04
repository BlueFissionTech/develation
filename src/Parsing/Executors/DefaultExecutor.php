<?php

namespace BlueFission\Parsing\Executors;

use BlueFission\Parsing\Contracts\IElementExecutor;
use BlueFission\Parsing\Element;
use BlueFission\DevElation as Dev;

/**
 * Executes operations like file includes, tools, or custom blocks
 */
class DefaultExecutor implements IElementExecutor {
    public function execute(Element $element): mixed
    {
        Dev::do('_before', [$element]);
        $result = $element->execute();
        $result = Dev::apply('_out', $result);
        Dev::do('_after', [$result, $element]);
        return $result;
    }
}
