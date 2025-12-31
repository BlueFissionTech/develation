<?php

namespace BlueFission\Parsing\Renderers;

use BlueFission\Parsing\Contracts\IElementRenderer;
use BlueFission\Parsing\Element;

class OutputRenderer implements IElementRenderer {
    public function render(Element $element): string {
        $template = $element->getParent();

        if (!$template) return '';

        // Output elements just surface already-captured output.
        $output = $element->render();

        return $output;
    }
}
