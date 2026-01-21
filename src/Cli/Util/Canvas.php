<?php

namespace BlueFission\Cli\Util;

use BlueFission\Obj;
use BlueFission\Str;
use BlueFission\Val;
use BlueFission\DataTypes;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\DevElation as Dev;

class Canvas extends Obj
{
    protected $_data = [
        'width' => 0,
        'height' => 0,
        'fill' => ' ',
    ];

    protected $_types = [
        'width' => DataTypes::INTEGER,
        'height' => DataTypes::INTEGER,
        'fill' => DataTypes::STRING,
    ];

    protected array $buffer = [];

    public function __construct(int $width, int $height, string $fill = ' ')
    {
        parent::__construct();

        $width = Dev::apply('_in', $width);
        $height = Dev::apply('_in', $height);
        $fill = Dev::apply('_in', $fill);

        $this->assign([
            'width' => max(0, $width),
            'height' => max(0, $height),
            'fill' => $this->normalizeChar($fill),
        ]);

        $this->resetBuffer();
        Dev::do('_after', [$this]);
    }

    public function clear(?string $fill = null): self
    {
        Dev::do('_before', [$this, $fill]);
        if (Val::isNotNull($fill)) {
            $this->field('fill', $this->normalizeChar($fill));
        }

        $this->resetBuffer();
        $this->trigger(Event::CHANGE, new Meta(data: 'clear'));
        Dev::do('_after', [$this]);

        return $this;
    }

    public function set(int $x, int $y, string $char): self
    {
        Dev::do('_before', [$this, $x, $y, $char]);
        if (!$this->inBounds($x, $y)) {
            return $this;
        }

        $this->buffer[$y - 1][$x - 1] = $this->normalizeChar($char);
        $this->trigger(Event::CHANGE, new Meta(data: ['x' => $x, 'y' => $y]));
        Dev::do('_after', [$this]);

        return $this;
    }

    public function drawText(int $x, int $y, string $text): self
    {
        Dev::do('_before', [$this, $x, $y, $text]);
        if (!$this->inBounds($x, $y)) {
            return $this;
        }

        $chars = str_split($text);
        $cursor = $x;
        foreach ($chars as $char) {
            if ($this->inBounds($cursor, $y)) {
                $this->buffer[$y - 1][$cursor - 1] = $this->normalizeChar($char);
            }
            $cursor++;
        }

        $this->trigger(Event::CHANGE, new Meta(data: ['x' => $x, 'y' => $y]));
        Dev::do('_after', [$this]);

        return $this;
    }

    public function toLines(): array
    {
        $lines = [];
        foreach ($this->buffer as $row) {
            $lines[] = implode('', $row);
        }

        return Dev::apply('_out', $lines);
    }

    public function render(): string
    {
        Dev::do('_before', [$this]);
        $output = implode(PHP_EOL, $this->toLines());
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function diffLines(?Canvas $previous): array
    {
        Dev::do('_before', [$this, $previous]);
        $current = $this->toLines();
        if (!$previous) {
            return Dev::apply('_out', $current);
        }

        $prior = $previous->toLines();
        $diffs = [];
        $max = max(count($current), count($prior));

        for ($index = 0; $index < $max; $index++) {
            $line = $current[$index] ?? '';
            $prev = $prior[$index] ?? '';
            if ($line !== $prev) {
                $diffs[$index + 1] = $line;
            }
        }

        return Dev::apply('_out', $diffs);
    }

    public function renderDiff(?Canvas $previous): string
    {
        Dev::do('_before', [$this, $previous]);
        $diffs = $this->diffLines($previous);
        if (empty($diffs)) {
            return '';
        }

        $output = '';
        foreach ($diffs as $lineNumber => $line) {
            if (is_int($lineNumber)) {
                $output .= Screen::moveCursor(1, $lineNumber) . Screen::clearLine() . $line;
            } else {
                $output .= $line;
            }
        }

        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function width(): int
    {
        return (int)$this->field('width');
    }

    public function height(): int
    {
        return (int)$this->field('height');
    }

    protected function resetBuffer(): void
    {
        $width = max(0, (int)$this->field('width'));
        $height = max(0, (int)$this->field('height'));
        $fill = $this->normalizeChar((string)$this->field('fill'));

        $this->buffer = [];
        for ($row = 0; $row < $height; $row++) {
            $this->buffer[$row] = array_fill(0, $width, $fill);
        }
    }

    protected function inBounds(int $x, int $y): bool
    {
        return $x >= 1 && $y >= 1 && $x <= $this->width() && $y <= $this->height();
    }

    protected function normalizeChar(string $char): string
    {
        if ($char === '') {
            return ' ';
        }

        return Str::sub($char, 0, 1);
    }
}
