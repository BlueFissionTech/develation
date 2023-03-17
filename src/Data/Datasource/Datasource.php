<?php
namespace BlueFission\Data\Datasource;

use BlueFission\DevNumber;
use BlueFission\Data\Data;
use BlueFission\Data\IData;

/**
 * Class Datasource
 *
 * The Datasource class extends the base Data class and implements the IData interface.
 * It provides an abstract representation of a data source and implements basic read, write, delete and navigation operations.
 *
 * @package BlueFission\Data\Datasource
 *
 */
class Datasource extends Data implements IData {
	/**
	 * The current index in the collection of data.
	 *
	 * @var int $_index
	 */
	private $_index;
	
	/**
	 * The collection of data being managed by the Datasource.
	 *
	 * @var array $_collection
	 */
	private $_collection;

	/**
	 * Datasource constructor.
	 *
	 * @param null $config
	 */
	public function __construct( $config = null ) {
		parent::__construct( $config = null );
		$_index = -1;
	}

	/**
	 * Reads the current data record from the collection.
	 *
	 * @return void
	 */
	public function read() {
		$this->assign( $this->_collection[ $this->_index ] );
	}
	
	/**
	 * Writes the current data record to the collection.
	 *
	 * @return void
	 */
	public function write() {
		$this->_collection[ $this->_index ] = $this->_data;
	}
	
	/**
	 * Deletes the current data record from the collection.
	 *
	 * @return void
	 */
	public function delete() {
		unset ( $this->_collection[ $this->_index ] );
	}
	
	/**
	 * Returns the contents of the current data record.
	 *
	 * @return string
	 */
	public function contents() {
		return serialize( $this->_data );
	}

	/**
	 * Sets the current index in the collection of data.
	 *
	 * @param int $index
	 * @return int
	 */
	public function index( $index = 0 ) {
		if ( $index && $this->inbounds( $index ) ) {
			$this->_index = $index;
		}
		return $this->_index;
	}

	/**
	 * Check if the specified index is within the bounds of the data collection.
	 *
	 * @param int|null $index
	 * @return bool
	 */
	private function inbounds( $index = null ) {
		$index = DevNumber::isValid($index) ? $index : $this->_index;
		return ( $index <= count( $this->_collection ) && $index >= 0 );
	}

	/**
	 * Increments the current index in the collection of data.
	 *
	 * @return void
	 */
	public function next() {
		if ( $this->inbounds() )
			$this->_index++;
	}
	
	/**
	 * Decrements the internal index by 1, if the index is within the bounds of the collection
	 * 
	 * @return void
	 */
	public function previous() {
		if ( $this->inbounds() )
			$this->_index--;
	}
}