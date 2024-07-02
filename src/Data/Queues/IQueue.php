<?php
namespace BlueFission\Data\Queues;

/**
 * Interface IQueue defines the basic operations for a queue data structure.
 *
 * @package BlueFission\Data\Queues
 */
interface IQueue {
	/**
	 * Check if the queue is empty.
	 *
	 * @param array $queue The queue data structure.
	 *
	 * @return bool Returns true if the queue is empty, otherwise false.
	 */
	public static function isEmpty($queue);

	/**
	 * Remove an item from the front of the queue.
	 *
	 * @param array $queue The queue data structure.
	 * @param bool $after  Determines if the item should be removed after a certain condition.
	 * @param bool $until  Determines if the item should be removed until a certain condition.
	 *
	 * @return mixed Returns the removed item.
	 */
	public static function dequeue($queue, $after=false, $until=false);
	
	/**
	 * Add an item to the back of the queue.
	 *
	 * @param array $queue The queue data structure.
	 * @param mixed $item  The item to be added to the queue.
	 *
	 * @return void
	 */
	public static function enqueue($queue, $item);
}
