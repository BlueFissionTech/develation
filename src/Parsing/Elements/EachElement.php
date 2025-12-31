<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\ILoopElement;
use BlueFission\Str;

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
                $this->block->setContent($this->getRaw());
                $this->index = $index;
                $this->current = $current.'.'.$index;

                $this->block->process();

                $results[] = $this->block->content;
            }
        } elseif (isset($this->attributes['iterations'])) {
            $count = (int) $this->getAttribute('iterations');
            for ($i = 0; $i < $count; $i++) {
                $this->block->setContent($this->getRaw());
                $this->index = $i;
                $this->block->setVar('index', $i);
                $this->block->process();
                $results[] = $this->block->content;
            }
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

    public function getDescription(): string
    {
        $descriptionString = sprintf('Evaluate the block for each item in the list %s.', $this->attributes['items'] ?? 'N/A');

        $this->description = $descriptionString;

        return $this->description;
    }
}