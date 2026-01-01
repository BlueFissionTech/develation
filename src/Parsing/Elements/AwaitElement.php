<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IExecutableElement;
use BlueFission\DevElation as Dev;

class AwaitElement extends Element implements IExecutableElement
{
    public function execute(): mixed
    {
        Dev::do('_before', [$this]);
        $event = $this->getAttribute('event');
        if ($event && method_exists($this->block, 'await')) {
            $result = $this->block->await($event);
            $result = Dev::apply('_out', $result);
            Dev::do('_after', [$result, $this]);
            return $result;
        }
        Dev::do('_after', [null, $this]);
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
