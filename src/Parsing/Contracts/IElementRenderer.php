<?php

namespace BlueFission\Parsing\Contracts;

use BlueFission\Parsing\Element;

interface IElementRenderer {
    public function render(Element $element): string;
}
