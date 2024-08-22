<?php
namespace BlueFission\Data\Datasource;

use BlueFission\Num;
use BlueFission\IObj;
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
	 * @var int $index
	 */
	private $index;
	
	/**
	 * The collection of data being managed by the Datasource.
	 *
	 * @var array $collection
	 */
	private $collection;

	/**
	 * Datasource constructor.
	 *
	 * @param null $config
	 */
	public function __construct( $config = null ) {
		parent::__construct( $config = null );
		$index = -1;
	}

	/**
	 * Reads the current data record from the collection.
	 *
	 * @return IObj
	 */
	public function read(): IObj
	{
		$this->assign( $this->collection[ $this->index ] );

		return $this;
	}
	
	/**
	 * Writes the current data record to the collection.
	 *
	 * @return IObj
	 */
	public function write(): IObj
	{
		$this->collection[ $this->index ] = $this->data;

		return $this;
	}
	
	/**
	 * Deletes the current data record from the collection.
	 *
	 * @return IObj
	 */
	public function delete(): IObj
	{
		unset ( $this->collection[ $this->index ] );

		return $this;
	}
	
	/**
	 * Returns the contents of the current data record.
	 *
	 * @return string
	 */
	public function contents() {
		return serialize( $this->data );
	}

	/**
	 * Sets the current index in the collection of data.
	 *
	 * @param int $index
	 * @return int
	 */
	public function index( $index = 0 ) {
		if ( $index && $this->inbounds( $index ) ) {
			$this->index = $index;
		}
		return $this->index;
	}

	/**
	 * Check if the specified index is within the bounds of the data collection.
	 *
	 * @param int|null $index
	 * @return bool
	 */
	private function inbounds( $index = null ) {
		$index = Num::isValid($index) ? $index : $this->index;
		return ( $index <= count( $this->collection ) && $index >= 0 );
	}

	/**
	 * Increments the current index in the collection of data.
	 *
	 * @return void
	 */
	public function next() {
		if ( $this->inbounds() )
			$this->index++;
	}
	
	/**
	 * Decrements the internal index by 1, if the index is within the bounds of the collection
	 * 
	 * @return void
	 */
	public function previous() {
		if ( $this->inbounds() )
			$this->index--;
	}
}