<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\IExecutableElement;
use BlueFission\DevElation as Dev;

class ExecElement extends Element implements IExecutableElement
{
    public function execute(): mixed
    {
        Dev::do('_before', [$this]);
        $type = $this->getAttribute('type');
        $content = $this->getContent();
        $content = Dev::apply('_in', $content);

        Dev::do('_after', [$type, $content, $this]);
        return null;
    }

    public function render(): string
    {
        Dev::do('_before', [$this]);
        $output = '';
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }
}
