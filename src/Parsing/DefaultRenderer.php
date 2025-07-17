<?php

namespace BlueFission\Parsing;

class DefaultRenderer implements IElementRenderer {
    public function render(Element $element): string {
        return $element->block->content;
    }
}