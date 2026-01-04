<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\DevElation as Dev;

class CommentElement extends Element implements IRenderableElement
{
    public function render(): string
    {
        Dev::do('_before', [$this]);
        $output = '';
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
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
