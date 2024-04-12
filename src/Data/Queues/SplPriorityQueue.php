<?php

namespace BlueFission\Data\Queues;

use SplPriorityQueue;

class SplPriorityQueue implements IQueue {
    private $queue;

    public function __construct() {
        $this->queue = new SplPriorityQueue();
        $this->queue->setExtractFlags(SplPriorityQueue::EXTR_DATA);
    }

    public static function isEmpty($queue) {
        return $queue->count() == 0;
    }

    public static function dequeue($queue, $after = false, $until = false) {
        if (!$queue->isEmpty()) {
            return $queue->extract();
        }
        return null;
    }

    public static function enqueue($queue, $item) {
        // Assuming $item is an array ['data' => $data, 'priority' => $priority]
        if (is_array($item) && isset($item['priority'])) {
        	$queue->insert($item['data'], $item['priority']);
        } else {
        	$queue->insert($item, 0);
        }
    }
}
