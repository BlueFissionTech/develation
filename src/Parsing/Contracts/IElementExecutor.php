<?php

namespace BlueFission\Parsing\Contracts;

use BlueFission\Parsing\Element;

interface IElementExecutor {
    public function execute(Element $element): mixed;
}
