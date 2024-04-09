<?php

namespace BlueFission\Data\Queues;

/**
 * Class Queue implements IQueue and manages the queue data structure.
 */
class Queue implements IQueue {

	/**
	 * @var ArrayObject $_stack stores the queue data.
	 */
	private static $_stack;

	/**
	 * @var int $_mode stores the queue mode (FILO or FIFO).
	 */
	public static $_mode;

	/**
	 * FILO constant for last in, first out mode.
	 */
	const FILO = 1;

	/**
	 * FIFO constant for first in, first out mode.
	 */
	const FIFO = 2;

	/**
	 * Private constructor to prevent instantiation of the class.
	 */
	private function __construct() {}

	/**
	 * Private clone method to prevent duplication of the class.
	 */
	private function __clone() {}

	/**
	 * Method to get an instance of the class.
	 *
	 * @return ArrayObject $_stack the instance of the queue.
	 */
	private static function instance() {
		if(!self::$_stack) self::init();
		return self::$_stack;
	}

	/**
	 * Initializes the queue data structure.
	 */
	private static function init() {
		$stack = new \ArrayObject;
		self::$_stack = $stack;
	}

	/**
	 * Determines if the queue is empty.
	 *
	 * @param string $queue the name of the queue.
	 *
	 * @return bool true if the queue is empty, false otherwise.
	 */
	public static function isEmpty($queue) {
		$stack = self::instance();
		$count = isset($stack[$queue]) && count($stack[$queue]);
		return $count ? false : true;
	}

	/**
	 * Dequeues an item from the queue.
	 *
	 * @param string $queue the name of the queue.
	 * @param int $after_id the item after which to start dequeuing.
	 * @param int $till_id the item until which to dequeue.
	 *
	 * @return mixed the item that was dequeued.
	 */
	public static function dequeue($queue, $after_id=false, $till_id=false) {
		$stack = self::instance();
		if($after_id === false && $till_id === false) {
			if ( self::$_mode == static::FIFO ) {
				$item = array_shift( $stack[$queue] );
			} elseif ( self::$_mode == static::FILO ) {
				$item = array_pop( $stack[$queue] );
			}
			return $item;
		} elseif ($after_id !== false && $till_id === false) {
			$till_id = count($stack[$queue])-1;
		}
		$items = array_slice ( $stack[$queue], $after_id, $till_id, true );
		return $items;
	}
	
	/**
	 * Adds an item to the specified queue.
	 *
	 * @param string $queue The name of the queue to add the item to.
	 * @param mixed $item The item to add to the queue.
	 *
	 * @return void
	 */
	public static function enqueue($queue, $item) {
		$stack = self::instance();
		$stack[$queue][] = $item;
	}

	/**
	 * Sets the mode of the queue (FILO or FIFO).
	 *
	 * @param int $mode The mode of the queue (either FILO or FIFO).
	 *
	 * @return void
	 */
	public static function setMode( $mode ) {
		self::$_mode = $mode;
	}

}