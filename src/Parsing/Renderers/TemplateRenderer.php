<?php

namespace BlueFission\Parsing\Renderers;

use BlueFission\Parsing\Contracts\IElementRenderer;
use BlueFission\Parsing\Element;

class TemplateRenderer implements IElementRenderer {
    public function render(Element $element): string {
        $content = $element->build();

        $element->getParent()?->setContent($content);

        return '';
    }
}