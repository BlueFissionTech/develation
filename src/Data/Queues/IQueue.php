<?php

namespace BlueFission\Data\Queues;

/**
 * Interface IQueue defines the basic operations for a queue data structure.
 *
 * @package BlueFission\Data\Queues
 */
interface IQueue
{
    /**
     * Check if the queue is empty.
     *
     * @param string $queue The queue name.
     *
     * @return bool Returns true if the queue is empty, otherwise false.
     */
    public static function isEmpty($queue);

    /**
     * Remove an item from the front of the queue.
     *
     * @param string $queue The queue name.
     * @param int|bool $after The item or offset after which dequeuing starts.
     * @param int|bool $until The item or offset at which dequeuing stops.
     *
     * @return mixed Returns the removed item.
     */
    public static function dequeue($queue, $after = false, $until = false);

    /**
     * Add an item to the back of the queue.
     *
     * @param string $queue The queue name.
     * @param mixed $item  The item to be added to the queue.
     *
     * @return mixed A backend-specific identifier or success value.
     */
    public static function enqueue($queue, $item);
}
