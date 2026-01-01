<?php

namespace BlueFission\Parsing\Renderers;

use BlueFission\Parsing\Contracts\IElementRenderer;
use BlueFission\Parsing\Element;
use BlueFission\DevElation as Dev;

class OutputRenderer implements IElementRenderer {
    public function render(Element $element): string {
        $template = $element->getParent();

        if (!$template) return '';

        // Output elements just surface already-captured output.
        Dev::do('_before', [$element, $template]);
        $output = $element->render();
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $element, $template]);

        return $output;
    }
}
