<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Contracts\ILoopElement;
use BlueFission\Str;
use BlueFission\DevElation as Dev;

class EachElement extends Element implements ILoopElement
{
    protected int $index = 0;
    protected string $current = '';

    public function run(array $vars): string
    {
        Dev::do('_before', [$vars, $this]);
        $glue = $this->getAttribute('glue') ?: '';
        $results = [];

        $this->parse();

        if (isset($this->attributes['items'])) {
            $current = $this->attributes['items'];
            $items = $this->getAttribute('items') ?? [];
            $items = Dev::apply('_in', $items);
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

        $output = implode($glue, $results);
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
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
