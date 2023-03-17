<?php
namespace BlueFission\Data\Queues;

use Memcached;

/**
 * Class MemQueue
 * 
 * This class is an implementation of a queue using Memcached and based on MEMQ (https://github.com/abhinavsingh/memq)
 * 
 * @link http://abhinavsingh.com/memq-fast-queue-implementation-using-memcached-and-php-only/
 */
class MemQueue extends Queue implements IQueue
{
	/**
	 * Stores the Memcached instance
	 *
	 * @var Memcached|null
	 */
	private static $_stack = NULL;

	/**
	 * The default pool to be used for Memcached
	 */
	const MEMQ_POOL = 'localhost:11211';

	/**
	 * The default time-to-live value for items in the queue
	 */
	const MEMQ_TTL = 0;
	
	/**
	 * Prevents creating an instance of the class
	 */
	private function __construct() {}
	
	/**
	 * Prevents cloning of the class instance
	 */
	private function __clone() {}
	
	/**
	 * Returns the Memcached instance
	 *
	 * @return Memcached
	 */
	private static function instance() {
		if(!self::$_stack) self::init();
		return self::$_stack;
	}
	
	/**
	 * Initializes the Memcached instance
	 *
	 * @return void
	 */
	private static function init() {
		$_stack = new Memcached;
		$servers = explode(",", env('MEMQ_POOL', static::MEMQ_POOL));
		foreach($servers as $server) {
			list($host, $port) = explode(":", $server);
			$_stack->addServer($host, $port);
		}
		self::$_stack = $_stack;
	}
	
	/**
	 * Determines if the queue is empty
	 *
	 * @param string $queue
	 * @return bool
	 */
	public static function is_empty($queue) {
		$stack = self::instance();
		$head = $stack->get($queue."_head");
		$tail = $stack->get($queue."_tail");
			
		if($head >= $tail || $head === FALSE || $tail === FALSE) 
			return TRUE;
		else 
			return FALSE;
	}

	/**
	 * Dequeues an item from the queue
	 *
	 * @param string $queue
	 * @param int|bool $after
	 * @param int|bool $until
	 * @return mixed
	 */
	public static function dequeue($queue, $after=FALSE, $until=FALSE) {
		$stack = self::instance();
		
		if($after === FALSE && $until === FALSE) {
			if ( self::$_mode == static::FIFO ) {
				$tail = $stack->get($queue."_tail");

				if(($id = $stack->increment($queue."_head")) === FALSE) {
					return FALSE;
				}
			
				if($id <= $tail) {
					$output = $stack->get($queue."_".($id-1));
					$stack->delete($queue."_".($id-1));
					return $output;
				}
				else {
					$stack->decrement($queue."_head");
					return FALSE;
				} 
			} elseif ( self::$_mode == static::FILO ) {
				$head = $stack->get($queue."_head");
				if(($id = $stack->decrement($queue."_tail")) === FALSE) {
					return FALSE;
				}
			
				if($id >= $head) {
					$output = $stack->get($queue."_".($id-1));
					$stack->delete($queue."_".($id-1));
					return $output;
				}
				else {
					$stack->increment($queue."_tail");
					return FALSE;
				} 
				
			}
		}
		else if($after !== FALSE && $until === FALSE) {
			$until = $stack->get($queue."_tail");	
		}
		
		$item_keys = array();
		for($i=$after+1; $i<=$until; $i++) 
			$item_keys[] = $queue."_".$i;
		$null = NULL;
		
		return $stack->getMulti($item_keys, $null, Memcached::GET_PRESERVE_ORDER); 
	}
	
	/**
	 * Enqueue a new item in the specified queue
	 *
	 * @param string $queue The name of the queue to add the item to
	 * @param mixed $item The item to add to the queue
	 *
	 * @return int|bool The ID of the added item or FALSE on failure
	 */
	public static function enqueue($queue, $item) {
		$stack = self::instance();
		
		$id = $stack->increment($queue."_tail");
		if($id === FALSE) {
			if($stack->add($queue."_tail", 1, self::MEMQ_TTL) === FALSE) {
				$id = $stack->increment($queue."_tail");
				if($id === FALSE) 
					return FALSE;
			}
			else {
				$id = 1;
				$stack->add($queue."_head", $id, self::MEMQ_TTL);
			}
		}
		
		if($stack->add($queue."_".$id, $item, self::MEMQ_TTL) === FALSE) 
			return FALSE;
		
		return $id;
	}
	
}