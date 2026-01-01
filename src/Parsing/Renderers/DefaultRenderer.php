<?php

namespace BlueFission\Parsing\Renderers;

use BlueFission\Parsing\Contracts\IElementRenderer;
use BlueFission\Parsing\Element;
use BlueFission\DevElation as Dev;

class DefaultRenderer implements IElementRenderer {
    public function render(Element $element): string {
        Dev::do('_before', [$element]);
        $output = $element->render();
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $element]);
        return $output;
    }
}
