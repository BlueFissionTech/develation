<?php

namespace BlueFission\Cli\Util;

use BlueFission\Arr;
use BlueFission\Obj;
use BlueFission\Str;
use BlueFission\DataTypes;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\DevElation as Dev;

class StatusBar extends Obj
{
    protected $_data = [
        'items' => [],
        'separator' => ' | ',
        'width' => 0,
    ];

    protected $_types = [
        'items' => DataTypes::ARRAY,
        'separator' => DataTypes::STRING,
        'width' => DataTypes::INTEGER,
    ];

    public function set(string $label, string $value): self
    {
        $label = Dev::apply('_in', $label);
        $value = Dev::apply('_in', $value);
        $items = Arr::make($this->arrayValue($this->field('items')));
        $items[$label] = $value;
        $this->field('items', $items->val());
        $this->trigger(Event::CHANGE, new Meta(data: ['label' => $label]));

        return $this;
    }

    public function remove(string $label): self
    {
        $items = Arr::make($this->arrayValue($this->field('items')));
        unset($items[$label]);
        $this->field('items', $items->val());
        $this->trigger(Event::CHANGE, new Meta(data: ['label' => $label]));

        return $this;
    }

    public function clear(): self
    {
        $this->field('items', []);
        $this->trigger(Event::CHANGE, new Meta(data: 'clear'));
        return $this;
    }

    public function render(?int $width = null): string
    {
        Dev::do('_before', [$this, $width]);
        $items = Arr::make($this->arrayValue($this->field('items')));
        $parts = Arr::make();
        $items->each(function ($value, $label) use ($parts) {
            $parts->push(Str::make((string)$label)->append(': ')->append($value)->val());
        });

        $separator = (string)$this->field('separator');
        $line = $parts->join($separator)->val();

        $targetWidth = $width ?? (int)$this->field('width');
        if ($targetWidth > 0) {
            $line = $this->fitWidth($line, $targetWidth);
        }

        $line = Dev::apply('_out', $line);
        $this->trigger(Event::PROCESSED, new Meta(data: $line));
        Dev::do('_after', [$line, $this]);
        return $line;
    }

    protected function arrayValue($value): array
    {
        if (Arr::is($value)) {
            return $value;
        }

        if ($value instanceof \BlueFission\Arr) {
            return $value->val();
        }

        return [];
    }

    protected function fitWidth(string $line, int $width): string
    {
        $line = Str::make($line);
        $length = $line->size();
        if ($length === $width) {
            return $line->val();
        }

        if ($length < $width) {
            return $line->append(Str::make(' ')->repeat($width - $length))->val();
        }

        if ($width <= 3) {
            return $line->sub(0, $width);
        }

        return Str::make($line->sub(0, $width - 3))->append('...')->val();
    }
}
