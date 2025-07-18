<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IExecutableElement;

class AwaitElement extends Element implements IExecutableElement
{
    public function execute(): mixed
    {
        $event = $this->getAttribute('event');
        if ($event && method_exists($this->block, 'await')) {
            return $this->block->await($event);
        }
        return null;
    }
}