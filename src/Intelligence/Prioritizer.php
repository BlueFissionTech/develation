<?php

// Queue manager

class Prioritizer {

	private $level = 3;
	private $consciousness = 1;
	private $filters = array();

	public function next() {
		$item = $queue::dequeue();
		if ( $this->review($item) )
		{
			$this->inform();
		}

	}

	private function review( $item ) {
		if ( isset($item->level) && $item->level >= $this->level ) {
			return false;
		}
		if ( isset($item->consciousness) && $item->consciousness >= $this->consciousness ) {
			return false;
		}
		if ( isset($item->description) && $this->relevance($item->description) ) {
			return false;
		}

		return true;
	}
}