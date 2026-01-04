<?php

namespace BlueFission\Parsing\Renderers;

use BlueFission\Parsing\Contracts\IElementRenderer;
use BlueFission\Parsing\Element;
use BlueFission\DevElation as Dev;

class SectionRenderer implements IElementRenderer {
    public function render(Element $element): string {
        $template = $element->getParent()->getTemplate();

        if (!$template) return '';

        // Render the section and register its output for later insertion.
        Dev::do('_before', [$element, $template]);
        $output = $element->build();
        $output = Dev::apply('_out', $output);

        $element->getParent()->getTemplate()->addOutput($element->getAttribute('name'), $output);

        Dev::do('_after', [$output, $element, $template]);
        return $output;
    }
}
