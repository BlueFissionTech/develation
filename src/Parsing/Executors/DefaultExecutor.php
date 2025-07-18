<?php

namespace BlueFission\Parsing\Executors;

use BlueFission\Parsing\Contracts\IElementExecutor;
use BlueFission\Parsing\Element;

/**
 * Executes operations like file includes, tools, or custom blocks
 */
class DefaultExecutor implements IElementExecutor {
    public function execute(Element $element): mixed
    {
        return $element->execute();
    }
}