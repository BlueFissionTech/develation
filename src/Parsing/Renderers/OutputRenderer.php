<?php

namespace BlueFission\Parsing\Renderers;

use BlueFission\Parsing\Contracts\IElementRenderer;
use BlueFission\Parsing\Element;

class OutputRenderer implements IElementRenderer {
    public function render(Element $element): string {
        $template = $element->getParent();

        if (!$template) return '';

        echo "Output\n";

        $output = $element->render();

        // $element->getParent()->addOutput($element->getAttribute('name'), $output);

        return $output;
    }
}