<?php

namespace BlueFission\Data\Queues;

class FileQueue extends Queue implements IQueue {

	// This is highly unreliable and should only be used for testing

	const FILENAME = 'file_queue_stack.tmp';
		
	private static $_stack;
	private static $_array;

	private function __construct() {}
	
	private function __clone() {}

	private static function instance() {
		if(!self::$_stack) self::init();
		return self::$_stack;
	}
	
	private static function init() {
		// $stack = tmpfile( self::FILENAME );
		$tempfile = sys_get_temp_dir().DIRECTORY_SEPARATOR.self::FILENAME;
		// touch($tempfile);
		// $stack = fopen($tempfile, 'a+');
		$stack = $tempfile;

		self::$_stack = $stack;
	}
	
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
	
	public static function enqueue($queue, $item) {
		$stack = self::instance();
		$data = file_get_contents($stack);

		self::$_array = unserialize($data);

		self::$_array[$queue][] = $item;
		
		$data = serialize(self::$_array);

		file_put_contents($stack, $data);
	}	
}