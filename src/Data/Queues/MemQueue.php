<?php
namespace BlueFission\Data\Queues;

use Memcached;

// based on MEMQ 
// https://github.com/abhinavsingh/memq
// http://abhinavsingh.com/memq-fast-queue-implementation-using-memcached-and-php-only/

class MemQueue extends Queue implements IQueue {
		
	private static $_stack = NULL;

	const MEMQ_POOL = 'localhost:11211';
	const MEMQ_TTL = 0;
	
	private function __construct() {}
	
	private function __clone() {}
	
	private static function instance() {
		if(!self::$_stack) self::init();
		return self::$_stack;
	}
	
	private static function init() {
		$_stack = new Memcached;
		$servers = explode(",", env('MEMQ_POOL', static::MEMQ_POOL));
		foreach($servers as $server) {
			list($host, $port) = explode(":", $server);
			$_stack->addServer($host, $port);
		}
		self::$_stack = $_stack;
	}
	
	public static function is_empty($queue) {
		$stack = self::instance();
		$head = $stack->get($queue."_head");
		$tail = $stack->get($queue."_tail");
			
		if($head >= $tail || $head === FALSE || $tail === FALSE) 
			return TRUE;
		else 
			return FALSE;
	}

	public static function dequeue($queue, $after=FALSE, $until=FALSE) {
		$stack = self::instance();
		
		// if ( self::$_mode == static::FIFO ) {
		// 	$start = "_head";
		// 	$end = "_tail";
		// } elseif ( self::$_mode == static::FILO ) {
		// 	$start = "_tail";
		// 	$end = "_head";
		// }
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