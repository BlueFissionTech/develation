<?php

namespace BlueFission\Data\Queues;

use SplQueue as BaseSplQueue;

class SplQueue extends Queue implements IQueue {
    private static $_queues = [];

    private function __construct() { }

    private static function instance( $queue)
    {
        if (!isset(self::$_queues[$channel])) {
            self::$_queues[$channel] = new BaseSplQueue();
        }

        return self::$_queues[$channel];
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

    public static function enqueue($item, $queue) {
        $queues = self::instance($queue);
        $queues->enqueue($item);
    }
}
