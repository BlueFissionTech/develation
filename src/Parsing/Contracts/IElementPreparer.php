<?php

namespace BlueFission\Parsing\Contracts;

use BlueFission\Parsing\Element;

interface IElementPreparer {
    public function ready($data = null): void;
    public function prepare(Element $element): void;
    public function supports (Element $element): bool;
    public function getData(): mixed;
}