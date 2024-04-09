<?php

namespace BlueFission\Data\Queues;

use BlueFission\Data\FileSystem;

/**
 * Class DiskQueue
 * This class implements the IQueue interface and serves as a disk based queue for message storage and processing
 */
class DiskQueue extends Queue implements IQueue {
	
	/**
	 * Directory name for the disk-based queue
	 * @var string
	 */
	const DIRNAME = 'php_temp_stack_dir';
	
	/**
	 * Filename prefix for messages stored in the disk-based queue
	 * @var string
	 */
	const FILENAME = 'message_';
	
	/**
	 * The instance of the disk-based queue
	 * @var string
	 */
	private static $_stack;
	
	/**
	 * The array of messages in the disk-based queue
	 * @var array
	 */
	private static $_array;

	/**
	 * Get the instance of the disk-based queue
	 * @return string
	 */
	private static function instance() {
		if(!self::$_stack) self::init();
		return self::$_stack;
	}
	
	/**
	 * Initialize the disk-based queue by creating a directory and file system object
	 * @return void
	 */
	private static function init() {
		$tempfile = sys_get_temp_dir();
		
		$stack = $tempfile.DIRECTORY_SEPARATOR.self::DIRNAME;

		$fs = new FileSystem(array('root'=>$tempfile, 'mode'=>'a+', 'filter'=>'file', 'doNotConfirm'=>true));
	    
	    if (file_exists($stack) && !is_dir($stack)) { 
	    	unlink($stack); 
	    }

	    if (!is_dir($stack)) {
	    	$fs->mkdir(self::DIRNAME);
	    }
	    
	    self::$_stack = $stack; 
	}
	
	/**
	 * Check if the disk-based queue is empty
	 * @param  string  $queue
	 * @return boolean
	 */
	public static function isEmpty($queue) {
		$stack = self::instance();

		$fs = new FileSystem(array('root'=>$stack, 'mode'=>'r', 'filter'=>'file', 'doNotConfirm'=>true, 'lock'=>true));
		$fs->dirname = $queue;

		$array = $fs->listDir();

		if (!is_array($array)) return true;

		return count( $array ) ? false : true;
	}

	/**
	 * Dequeue messages from the disk-based queue
	 * @param  string  $queue
	 * @param  integer $after
	 * @param  integer $until
	 * @return mixed
	 */
	public static function dequeue($queue, $after=false, $until=false) {
		$stack = self::instance();

		$fs = new FileSystem(array('root'=>$stack, 'mode'=>'a+', 'filter'=>'file', 'doNotConfirm'=>true, 'lock'=>true));
		$fs->dirname = $queue;
		$array = $fs->listDir();

		if (  $array == false ) return false;

		if ( self::$_mode == static::FILO ) {
			$array = array_reverse($array);
		}

		$message = null;

		if($after === false && $until === false) {
			foreach ( $array as $file ) {
				// $fp = fopen("/tmp/lock.txt", "r");

				// $fs->filename = $file;
				$fs->open($file);

				if ( $fs->isLocked() ) {
					$fs->read();
					$message = $fs->contents();
					$fs->delete();
					$fs->close();
				}
				return unserialize($message);
			}
		} elseif($after !== false && $until === false) {
			$until = self::tail($array);
		}

		$items = array();
		for($i=$after+1; $i<=$until; $i++)  {
			$file = self::FILENAME . $i;
			$fs->filename = $file;
			$fs->open();

			if ( $fs->isLocked() ) {
				$fs->read();
				$message = $fs->contents();
				$fs->delete();
				$fs->close();
				$items[] = unserialize($message);
				$message = null;
			}
		}
		return $items;
	}
	
	/**
	 * Adds a new item to the specified queue.
	 *
	 * @param string $queue The name of the queue to add the item to.
	 * @param mixed $item The item to be added to the queue.
	 *
	 * @return void
	 */
	public static function enqueue($queue, $item) {
		$stack = self::instance();

		$fs = new FileSystem(array('root'=>$stack, 'mode'=>'x', 'filter'=>'file', 'doNotConfirm'=>true, 'lock'=>true));

		$fs->dirname = $queue;
	    $fs->mkdir();

		$message = serialize($item);

		$tail = self::tail($queue);
		$fs->contents($message);
		while (!$fs->isLocked()) {
			$fs->basename = self::FILENAME . str_pad( $tail, 11, '0', STR_PAD_LEFT);
			$fs->open();
			$tail++;
			if ($tail > 99999999999 ) $tail = 0;
		}

		$fs->write();
		$fs->close();
	}

	/**
	 * Retrieves the last item added to the specified queue.
	 *
	 * @param string $queue The name of the queue to retrieve the last item from.
	 *
	 * @return int The last item's index in the queue.
	 */
	private static function tail($queue) {
		$stack = self::instance();

		$fs = new FileSystem(array('root'=>$stack, 'mode'=>'r', 'filter'=>'file', 'doNotConfirm'=>true, 'lock'=>true));
		$fs->dirname = $queue;
		$array = $fs->listDir();

		if (!is_array($array) || count($array) < 1) return 1;
		// rsort($array);
		$last = end($array);

		$tail = str_replace(array($stack, self::FILENAME, $queue,DIRECTORY_SEPARATOR), array('','','',''), $last);

		return (int)$tail;
	}

}