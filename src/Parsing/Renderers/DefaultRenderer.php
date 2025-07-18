<?php

namespace BlueFission\Parsing\Renderers;

use BlueFission\Parsing\Contracts\IElementRenderer;
use BlueFission\Parsing\Element;

class DefaultRenderer implements IElementRenderer {
    public function render(Element $element): string {
        return $element->render();
    }
}