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

    public function getDescription(): string
    {
        $descriptionString = sprintf(
            'Awaits the occurrence of a specific event before proceeding. Use the "event" attribute to specify the event name to wait for.'
        );

        $this->description = $descriptionString;

        return $this->description;
    }
}