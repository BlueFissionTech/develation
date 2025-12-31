<?php

namespace BlueFission\Parsing\Renderers;

use BlueFission\Parsing\Contracts\IElementRenderer;
use BlueFission\Parsing\Element;

class OutputRenderer implements IElementRenderer {
    public function render(Element $element): string {
        $template = $element->getParent();

        if (!$template) return '';

        $output = $element->render();

        return $output;
    }
}