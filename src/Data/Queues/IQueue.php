<?php
namespace BlueFission\Data\Queues;

interface IQueue {

	public static function is_empty($queue);

	public static function dequeue($queue, $after=false, $until=false);
	
	public static function enqueue($queue, $item);
}