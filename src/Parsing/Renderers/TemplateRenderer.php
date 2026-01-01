<?php

namespace BlueFission\Parsing\Renderers;

use BlueFission\Parsing\Contracts\IElementRenderer;
use BlueFission\Parsing\Element;
use BlueFission\DevElation as Dev;

class TemplateRenderer implements IElementRenderer {
    public function render(Element $element): string {
        // Build template content but defer final output to a post-processing phase.
        Dev::do('_before', [$element]);
        $content = $element->build();
        $content = Dev::apply('_out', $content);

        $element->getParent()?->setContent($content);

        Dev::do('_after', [$content, $element]);
        return '';
    }
}
