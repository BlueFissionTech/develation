<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IExecutableElement;

class ExecElement extends Element implements IExecutableElement
{
    public function execute(): mixed
    {
        $type = $this->getAttribute('type');
        $content = $this->getContent();

        return null;
    }

    public function render(): string
    {
    	return '';
    }
}