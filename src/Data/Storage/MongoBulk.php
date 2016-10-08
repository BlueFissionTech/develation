<?php

namespace BlueFission\Data\Storage;

use BlueFission\Data\IData;
use BlueFission\Collections\Group;
use BlueFission\DevValue;
use BlueFission\DevArray;
use BlueFission\Behavioral\Behaviors\Event;

class MongoBulk extends Mongo implements IData {
	
	private $_rows;

	public function __construct( $config = null )
	{
		parent::__construct( $config );
		$this->_rows = new Group();
		$this->_rows->type('\BlueFission\Data\Storage\Mongo');
	}

	public function read() {
		parent::read();
		$res = array();
		if (method_exists('mysqli_result', 'fetch_all')) # Compatibility layer with PHP < 5.3
			$res = $this->_result->fetch_all( MYSQLI_ASSOC );
		else {
			if ($this->_result) {
				$res = $this->_result->toArray();
			}
		}

		$this->_rows = new Group( $res );
		$this->_rows->type('\BlueFission\Data\Storage\Mongo');
	}

	public function write() {
		$db = $this->_source;

		if ($db) {
			$collection = $this->config('name');

			$db->config('collection', $collection);
			// $db->config('ignore_null', $this->config('ignore_null'));
			$success = $db->query($this->_rows->toArray());

			if ($success) {
				$affected_row = $db->last_row();
				$this->_last_row_affected = $affected_row;
			}

			$status = $success ? self::STATUS_SUCCESS : ( $db->status() ? $db->status() : self::STATUS_FAILED );
			$this->status( $status );
			if (!$success)
				return false;
		}
	}

	public function result()
	{
		return $this->_rows;
	}

	public function each() {
		return $this->_rows->each();
	}

	public function limit($start = 0, $end = '') 
	{
		$this->_row_start = $start;
		$this->_row_end = $end;
	}

	public function contents($data = null)
	{
		if ( DevValue::isNull($data)) {
			return $this->_rows->current() ? $this->_rows->current() : parent::contents();
		} elseif (is_array($data) && !DevArray::isAssoc($data)) {
			$this->_rows = new Group( $data );
		} elseif ( DevArray::isAssoc($data) ) {
			parent::contents($data);
		}
		
		$this->perform( Event::CHANGE ); 
	}
}