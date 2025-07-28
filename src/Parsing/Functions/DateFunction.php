<?php

namespace BlueFission\Parsing\Functions;

use BlueFission\Parsing\Contracts\IToolFunction;

class DateFunction implements IToolFunction {
    public function name(): string {
        return 'date';
    }

    public function execute(array $args): mixed {
        $timestamp = $args[0] ?? '';
        $format = $args[1] ?? 'Y-m-d H:i:s';
        if (!is_numeric($timestamp)) {
            return 'Invalid timestamp';
        }

        $timestamp = (int)$timestamp;
        $date = date($format, $timestamp);
        if ($date === false) {
            return 'Invalid date format';
        }

        return $date;
    }
}