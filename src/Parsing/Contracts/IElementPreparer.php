<?php

namespace BlueFission\Parsing\Contracts;

use BlueFission\Parsing\Element;

interface IElementPreparer {
    public function setContext($context = null): void;
    public function prepare(Element $element): void;
    public function supports (Element $element): bool;
}