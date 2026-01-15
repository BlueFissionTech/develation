<?php
namespace BlueFission\Cli\Util;

use BlueFission\Obj;
use BlueFission\Str;

class Table extends Obj
{
    public static function render(array $headers, array $rows, array $options = []): string
    {
        $padding = isset($options['padding']) ? (int)$options['padding'] : 1;
        $align = $options['align'] ?? [];

        $rowCount = count($rows);
        $colCount = count($headers);
        for ($i = 0; $i < $rowCount; $i++) {
            $colCount = max($colCount, count($rows[$i]));
        }

        $headers = self::normalizeRow($headers, $colCount);
        $normalizedRows = [];
        foreach ($rows as $row) {
            $normalizedRows[] = self::normalizeRow($row, $colCount);
        }

        $widths = array_fill(0, $colCount, 0);
        for ($i = 0; $i < $colCount; $i++) {
            $widths[$i] = max($widths[$i], Str::len(Ansi::strip((string)$headers[$i])));
        }
        foreach ($normalizedRows as $row) {
            for ($i = 0; $i < $colCount; $i++) {
                $widths[$i] = max($widths[$i], Str::len(Ansi::strip((string)$row[$i])));
            }
        }

        $lines = [];
        $lines[] = self::borderLine($widths, $padding);
        if ($headers) {
            $lines[] = self::rowLine($headers, $widths, $padding, $align);
            $lines[] = self::borderLine($widths, $padding);
        }
        foreach ($normalizedRows as $row) {
            $lines[] = self::rowLine($row, $widths, $padding, $align);
        }
        $lines[] = self::borderLine($widths, $padding);

        return implode(PHP_EOL, $lines);
    }

    protected static function normalizeRow(array $row, int $colCount): array
    {
        $values = array_values($row);
        for ($i = count($values); $i < $colCount; $i++) {
            $values[] = '';
        }
        return $values;
    }

    protected static function borderLine(array $widths, int $padding): string
    {
        $parts = [];
        foreach ($widths as $width) {
            $parts[] = str_repeat('-', $width + ($padding * 2));
        }
        return '+' . implode('+', $parts) . '+';
    }

    protected static function rowLine(array $row, array $widths, int $padding, array $align): string
    {
        $parts = [];
        foreach ($row as $index => $cell) {
            $text = (string)$cell;
            $visible = strlen(Ansi::strip($text));
            $width = $widths[$index];
            $space = max(0, $width - $visible);
            $alignment = $align[$index] ?? 'left';

            if ($alignment === 'right') {
                $text = str_repeat(' ', $space) . $text;
            } elseif ($alignment === 'center') {
                $left = (int)floor($space / 2);
                $right = $space - $left;
                $text = str_repeat(' ', $left) . $text . str_repeat(' ', $right);
            } else {
                $text = $text . str_repeat(' ', $space);
            }

            $parts[] = str_repeat(' ', $padding) . $text . str_repeat(' ', $padding);
        }

        return '|' . implode('|', $parts) . '|';
    }
}
