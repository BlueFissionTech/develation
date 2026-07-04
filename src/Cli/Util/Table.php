<?php
namespace BlueFission\Cli\Util;

use BlueFission\Arr;
use BlueFission\Num;
use BlueFission\Obj;
use BlueFission\Str;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\DevElation as Dev;

class Table extends Obj
{
    public static function render(array $headers, array $rows, array $options = []): string
    {
        $table = new self();
        return $table->renderTable($headers, $rows, $options);
    }

    public function renderTable(array $headers, array $rows, array $options = []): string
    {
        $headers = Dev::apply('_in', $headers);
        $rows = Dev::apply('_in', $rows);
        $options = Dev::apply('_in', $options);
        Dev::do('_before', [$headers, $rows, $options, $this]);

        $options = Arr::make($options);
        $padding = $options->hasKey('padding') ? (int)$options['padding'] : 1;
        $align = Arr::make($options->hasKey('align') ? (array)$options['align'] : []);

        $rowCount = Arr::count($rows);
        $colCount = Arr::count($headers);
        for ($i = 0; $i < $rowCount; $i++) {
            $colCount = (int)Num::max($colCount, Arr::count($rows[$i]));
        }

        $headers = self::normalizeRow($headers, $colCount);
        $normalizedRows = Arr::make();
        foreach ($rows as $row) {
            $normalizedRows->push(self::normalizeRow($row, $colCount));
        }

        $widths = array_fill(0, $colCount, 0);
        for ($i = 0; $i < $colCount; $i++) {
            $widths[$i] = (int)Num::max($widths[$i], Str::len(Ansi::strip((string)$headers[$i])));
        }
        foreach ($normalizedRows as $row) {
            for ($i = 0; $i < $colCount; $i++) {
                $widths[$i] = (int)Num::max($widths[$i], Str::len(Ansi::strip((string)$row[$i])));
            }
        }

        $lines = Arr::make();
        $lines->push(self::borderLine($widths, $padding));
        if ($headers) {
            $lines->push(self::rowLine($headers, $widths, $padding, $align));
            $lines->push(self::borderLine($widths, $padding));
        }
        foreach ($normalizedRows as $row) {
            $lines->push(self::rowLine($row, $widths, $padding, $align));
        }
        $lines->push(self::borderLine($widths, $padding));

        $output = $lines->join(PHP_EOL)->val();
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    protected static function normalizeRow(array $row, int $colCount): array
    {
        $values = Arr::make($row)->values();
        for ($i = $values->count(); $i < $colCount; $i++) {
            $values->push('');
        }
        return $values->val();
    }

    protected static function borderLine(array $widths, int $padding): string
    {
        $parts = Arr::make();
        foreach ($widths as $width) {
            $parts->push(Str::make('-')
                ->repeat(Num::make($padding)->times(2)->plus($width)->int())
                ->val()
            );
        }
        return Str::make('+')->append($parts->join('+'))->append('+')->val();
    }

    protected static function rowLine(array $row, array $widths, int $padding, Arr $align): string
    {
        $parts = Arr::make();
        foreach ($row as $index => $cell) {
            $text = (string)$cell;
            $visible = Str::len(Ansi::strip($text));
            $width = $widths[$index];
            $space = (int)Num::max(0, Num::make($width)->minus($visible)->val());
            $alignment = $align->hasKey($index) ? $align[$index] : 'left';

            if ($alignment === 'right') {
                $text = Str::make(' ')->repeat($space)->append($text)->val();
            } elseif ($alignment === 'center') {
                $left = Num::make($space)->by(2)->int();
                $right = Num::make($space)->minus($left)->int();
                $text = Str::make(' ')
                    ->repeat($left)
                    ->append($text)
                    ->append(Str::make(' ')->repeat($right))
                    ->val();
            } else {
                $text = Str::make($text)->append(Str::make(' ')->repeat($space))->val();
            }

            $parts->push(Str::make(' ')
                ->repeat($padding)
                ->append($text)
                ->append(Str::make(' ')->repeat($padding))
                ->val()
            );
        }

        return Str::make('|')->append($parts->join('|'))->append('|')->val();
    }
}
