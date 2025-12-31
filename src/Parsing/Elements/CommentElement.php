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

    public function getDescription(): string
    {
        $descriptionString = sprintf(
            'Comment Element: %s',
            Str::truncate($this->getRaw(), 30)
        );

        $this->description = $descriptionString;

        return $this->description;
    }
}