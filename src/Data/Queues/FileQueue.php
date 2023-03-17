<?php

namespace BlueFission\Data\Queues;

/**
 * Class FileQueue
 * 
 * @package BlueFission\Data\Queues
 * @implements IQueue
 */
class FileQueue extends Queue implements IQueue {

	/**
	 * A constant for the file name for the file queue stack
	 */
	const FILENAME = 'file_queue_stack.tmp';
		
	/**
	 * @var string $_stack 
	 */
	private static $_stack;

	/**
	 * @var array $_array 
	 */
	private static $_array;

	/**
	 * Prevents the class from being instantiated
	 */
	private function __construct() {}
	
	/**
	 * Prevents the class from being cloned
	 */
	private function __clone() {}

	/**
	 * Returns the instance of the stack
	 * 
	 * @return string
	 */
	private static function instance() {
		if(!self::$_stack) self::init();
		return self::$_stack;
	}
	
	/**
	 * Initializes the stack
	 */
	private static function init() {
		$tempfile = sys_get_temp_dir().DIRECTORY_SEPARATOR.self::FILENAME;
		$stack = $tempfile;

		self::$_stack = $stack;
	}
	
	/**
	 * Check if a given queue is empty
	 * 
	 * @param string $queue 
	 * @return boolean
	 */
	public static function is_empty($queue) {
		$stack = self::instance();

		$count = filesize($stack);
		if ( $count < 1 )
			return true;

		$data = file_get_contents(self::$stack);

		self::$_array = unserialize($data);

		$count = count(self::$_array[$queue]);

		return $count ? false : true;
	}

	/**
	 * Dequeue an item from a given queue
	 * 
	 * @param string $queue 
	 * @param int $after 
	 * @param int $until 
	 * @return array|null
	 */
	public static function dequeue($queue, $after=false, $until=false) {
		$stack = self::instance();
		$data = file_get_contents($stack);

		self::$_array = unserialize($data);
		if ( self::$_mode == static::FILO && is_array(self::$_array)) {
			self::$_array = array_reverse(self::$_array);
		}

		if($after === false && $until === false) {
			return is_array(self::$_array) ? array_pop( self::$_array[$queue] ) : null;
		} elseif($after !== false && $until === false) {
			$until = count(self::$_array)-1;
		}
		$items = array_slice ( self::$_array[$queue], $after, $until, true );

		$data = serialize(self::$_array);

		file_put_contents($stack, $data);

		return $items;
	}
	
	/**
	 * Enqueue an item to the given queue
	 * 
	 * @param string $queue The name of the queue
	 * @param mixed $item The item to be added to the queue
	 * 
	 * @return void
	 */
	public static function enqueue($queue, $item) {
		$stack = self::instance();
		$data = file_get_contents($stack);

		self::$_array = unserialize($data);

		self::$_array[$queue][] = $item;
		
		$data = serialize(self::$_array);

		file_put_contents($stack, $data);
	}

}