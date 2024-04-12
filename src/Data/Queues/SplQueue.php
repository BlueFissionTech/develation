<?php

namespace BlueFission\Data\Queues;

use SplQueue;

class SplQueue implements IQueue {
    private $queue;

    public function __construct() {
        $this->queue = new SplQueue();
    }

    public static function isEmpty($queue) {
        return $queue->isEmpty();
    }

    public static function dequeue($queue, $after = false, $until = false) {
        if (!$queue->isEmpty()) {
            return $queue->dequeue();
        }
        return null;
    }

    public static function enqueue($queue, $item) {
        $queue->enqueue($item);
    }
}
