<?php

namespace BlueFission\Data\Storage;

use BlueFission\Connections\Database\MongoLink;

class Mongo extends Storage {

	// private $_database;
	private $_collection;

	protected $_config = array( 
		'location'=>'',
		'name'=>'',
	);
	
	public function __construct( $config = null )
	{
		parent::__construct( $config );
	}
	
	public function activate( )
	{
		$this->_source = new MongoLink();
		if ($this->config('location'))
			$this->_source->database( $this->config('location') );

		if ($this->config('name'))
			$this->_collection = $this->config('name');

		if ( !$this->_source ) 
			$this->status( self::STATUS_FAILED_INIT );
	}

	public function read() {
		$db = $this->_source;

		if ($db) {
			$collection = $this->config('name');

			$db->config('collection', $collection);

			$success = $db->find($collection, $this->_data);

			$this->_result = $success;

			$this->assign($success->toArray()[0]);

			$status = $success ? self::STATUS_SUCCESS : ( $db->status() ? $db->status : self::STATUS_FAILED );
			$this->status( $status );
			if (!$success)
				return false;
		}
	}

	public function delete() {
		$db = $this->_source;

		if ($db) {
			$collection = $this->config('name');

			$db->config('collection', $collection);

			$success = $db->delete($collection, $this->_data);

			$this->_result = $success;

			$status = $success ? self::STATUS_SUCCESS : ( $db->status() ? $db->status : self::STATUS_FAILED );
			$this->status( $status );
			if (!$success)
				return false;
		}
	}

	public function write() {
		$db = $this->_source;

		if ($db) {
			$collection = $this->config('name');

			$db->config('collection', $collection);
			// $db->config('ignore_null', $this->config('ignore_null'));
			$success = $db->query($this->_data);

			if ($success) {
				$affected_row = $db->last_row();
				$this->_last_row_affected = $affected_row;
			}

			$status = $success ? self::STATUS_SUCCESS : ( $db->status() ? $db->status : self::STATUS_FAILED );
			$this->status( $status );
			if (!$success)
				return false;
		}
		// $this->status( $result ? self::STATUS_SUCCESS : self::STATUS_FAILED );
	}

	public function mapReduce($map, $reduce, $output) {
		$db = $this->_source;

		return $db->mapReduce($map, $reduce, $output);
	}

	public function contents( $data = null )
	{
		$data = parent::contents($data);
		if ( $data ) { // if something was assigned
			$data = ($this->_result) ? $this->_result : $this->data();

			return $data;
		}
	}
}