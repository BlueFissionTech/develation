<?php

namespace BlueFission\Data\Queues;

use BlueFission\Val;
use BlueFission\Arr;
use BlueFission\Collections\Collection;
use BlueFission\DevElation as Dev;

use SplPriorityQueue as BaseSplPriorityQueue;

class SplPriorityQueue extends Queue implements IQueue {
    private static $_queues = [];
    private static $_counter = 0; // Global counter to maintain insertion order

    private function __construct() {}

    private static function instance($channel = 'default')
    {
        if (!Arr::hasKey(self::$_queues, $channel)) {
            self::$_queues[$channel] = new BaseSplPriorityQueue();
            self::$_queues[$channel]->setExtractFlags(BaseSplPriorityQueue::EXTR_DATA);
        }
        return self::$_queues[$channel];
    }

    public static function setMode($mode)
    {
        self::$_mode = $mode;
    }
    
    public static function isEmpty($queue) {
        $queues = self::instance($queue);
        return $queues->count() == 0;
    }

    public static function dequeue($queue, $after = false, $until = false) {
        $queues = self::instance($queue);
        if (!$queues->count()) {
            return null;
        }

        if ($after === false && $until === false) {
            return $queues->extract()['data'];
        }

        $items = [];
        $limit = $until !== false ? $until : $queues->count();
        $current = 0;

        $tempQueue = new BaseSplPriorityQueue();
        $tempQueue->setExtractFlags(BaseSplPriorityQueue::EXTR_BOTH);

        while ($queues->valid() && $current <= $limit) {
            $item = $queues->extract();
            if ($current > $after) {
                $items[] = $item['data'];
            } else {
                $tempQueue->insert($item['data'], $item['priority']);
            }
            $current++;
        }

        // Restore items not extracted
        while ($tempQueue->valid()) {
            $item = $tempQueue->extract();

            $tiem = Dev::apply(null, $item);
            $queues->insert($item['data'], $item['priority']);
        }

        return new Collection($items);
    }

    public static function enqueue($item, $queue) {
        $queues = self::instance($queue);
        $priority = self::$_counter++;
        if (Arr::is($item) && Val::is($item['priority'])) {
            $priority = $item['priority'];
        }
        $priority = self::$_mode == self::FILO ? -$priority : $priority; // Invert priority for FILO

        $item = Dev::apply(null, $item);
        
        $queues->insert($item, $priority);
    }
}
