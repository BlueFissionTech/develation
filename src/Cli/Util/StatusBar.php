<?php

namespace BlueFission\Cli\Util;

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
        $items = $this->arrayValue($this->field('items'));
        $items[$label] = $value;
        $this->field('items', $items);
        $this->trigger(Event::CHANGE, new Meta(data: ['label' => $label]));

        return $this;
    }

    public function remove(string $label): self
    {
        $items = $this->arrayValue($this->field('items'));
        unset($items[$label]);
        $this->field('items', $items);
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
        $items = $this->arrayValue($this->field('items'));
        $parts = [];
        foreach ($items as $label => $value) {
            $parts[] = $label . ': ' . $value;
        }

        $separator = (string)$this->field('separator');
        $line = implode($separator, $parts);

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
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof \BlueFission\Arr) {
            return $value->val();
        }

        return [];
    }

    protected function fitWidth(string $line, int $width): string
    {
        $length = Str::len($line);
        if ($length === $width) {
            return $line;
        }

        if ($length < $width) {
            return $line . str_repeat(' ', $width - $length);
        }

        if ($width <= 3) {
            return Str::sub($line, 0, $width);
        }

        return Str::sub($line, 0, $width - 3) . '...';
    }
}
