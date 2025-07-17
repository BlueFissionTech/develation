<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\ILoopElement;

class EachElement extends Element implements ILoopElement
{
    public function run(array $vars): string
    {
        $glue = $this->getAttribute('glue');
        $results = [];

        if (isset($this->attributes['items'])) {
            $items = $this->getAttribute('items') ?? [];

            foreach ($items as $index => $item) {
                $this->block->setVar('index', $index);
                $this->block->setVar('current', $item);
                $results[] = $this->block->process();
            }
        } elseif (isset($this->attributes['iterations'])) {
            $count = (int) $this->getAttribute('iterations');
            for ($i = 0; $i < $count; $i++) {
                $this->block->setVar('index', $i);
                $results[] = $this->block->process();
            }
        } else {
            throw new \InvalidArgumentException("`each` requires either 'items' or 'iterations'.");
        }

        return implode($glue, $results);
    }
}