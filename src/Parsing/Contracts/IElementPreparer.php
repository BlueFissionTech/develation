<?php

namespace BlueFission\Parsing\Contracts;

use BlueFission\Parsing\Element;

interface IElementPreparer {
    public function ready($data = null): void;
    public function prepare(Element $element): void;
}
