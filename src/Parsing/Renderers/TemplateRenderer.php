<?php

namespace BlueFission\Parsing\Renderers;

use BlueFission\Parsing\Contracts\IElementRenderer;
use BlueFission\Parsing\Element;

class TemplateRenderer implements IElementRenderer {
    public function render(Element $element): string {
        // Build template content but defer final output to a post-processing phase.
        $content = $element->build();

        $element->getParent()?->setContent($content);

        return '';
    }
}
