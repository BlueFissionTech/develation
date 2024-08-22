<?php

namespace BlueFission\Data\Queues;

use SplQueue as BaseSplQueue;

class SplQueue extends Queue implements IQueue {
    private static $queues = [];

    private function __construct() { }

    private static function instance( $queue)
    {
        if (!isset(self::$queues[$queue])) {
            self::$queues[$queue] = new BaseSplQueue();
        }

        return self::$queues[$queue];
    }

    public static function isEmpty($queue) {
        $queues = self::instance($queue);
        return $queues->isEmpty();
    }

    public static function dequeue($queue, $after = false, $until = false) {
        $queues = self::instance($queue);
        if (!$queues->isEmpty()) {
            return $queues->dequeue();
        }
        return null;
    }

    public static function enqueue($queue, $item) {
        $queues = self::instance($queue);
        $queues->enqueue($item);
    }
}
