<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IRenderableElement;

class CommentElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        return '';
    }
}