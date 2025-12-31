<?php

namespace BlueFission\Parsing\Renderers;

use BlueFission\Parsing\Contracts\IElementRenderer;
use BlueFission\Parsing\Element;

class SectionRenderer implements IElementRenderer {
    public function render(Element $element): string {
        $template = $element->getParent()->getTemplate();

        if (!$template) return '';

        $output = $element->build();

        $element->getParent()->getTemplate()->addOutput($element->getAttribute('name'), $output);

        return $output;
    }
}