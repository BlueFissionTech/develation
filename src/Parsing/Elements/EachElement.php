<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\ILoopElement;

class EachElement extends Element implements ILoopElement
{
    protected int $index = 0;
    protected string $current = '';

    public function run(array $vars): string
    {
        $glue = $this->getAttribute('glue') ?: '';
        $results = [];

        $this->parse();

        if (isset($this->attributes['items'])) {
            $current = $this->attributes['items'];
            $items = $this->getAttribute('items') ?? [];
            foreach ($items as $index => $item) {
                $this->index = $index;
                $this->current = $current.'.'.$index;
                $this->block->process();

                $results[] = $this->block->content;
            }
        } elseif (isset($this->attributes['iterations'])) {
            $count = (int) $this->getAttribute('iterations');
            for ($i = 0; $i < $count; $i++) {
                $this->index = $i;
                $this->block->setVar('index', $i);
                $this->block->process();
                $results[] = $this->block->content;
            }
        } else {
            throw new \InvalidArgumentException("`each` requires either 'items' or 'iterations'.");
        }

        return implode($glue, $results);
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getCurrent(): string
    {
        return $this->current;
    }
}