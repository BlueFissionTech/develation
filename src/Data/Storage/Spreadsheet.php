<?php

namespace BlueFission\Data\Storage;

use BlueFission\IObj;

/**
 * Class Spreadsheet
 * 
 * This class represents a spreadsheet as a data storage.
 * 
 * @package BlueFission\Data\Storage
 * @implements IData
 */
class Spreadsheet extends Storage implements IData {
	/**
	 * @var int $index The current index of the spreadsheet data.
	 */
	private $index;

	/**
	 * @var array $config The configuration of the spreadsheet.
	 * @property string 'location' The location of the spreadsheet.
	 * @property string 'name' The name of the spreadsheet.
	 */
	protected $config = [
		'location'=>'', 
		'name'=>'' 
	];

	/**
	 * Activates the spreadsheet, reading its data into memory.
	 *
	 * @return IObj
	 */
	public function activate(): IObj
	{
		$path = $this->config('location') ? $this->config('location') : sys_get_temp_dir();
		
		$name = $this->config('name') ? (string)$this->config('name') : Str::rand();
			
		if (!$this->config('name'))	{
			$file = tempnam($path, $name);		
		}

		$data = file( $file );

		if ( $data ) {
			$spreadsheet = [];
			foreach ( $data as $row ) {
				$spreadsheet[] = str_getcsv( $row );
			}
			$this->source = $spreadsheet;
			$this->index = 0;
		}

		if ( !$this->source ) 
			$this->status( self::STATUS_FAILED_INIT );
		else
			$this->status( self::STATUS_SUCCESSFUL_INIT );

		return $this;
	}

	/**
	 * Writes the data of the spreadsheet.
	 *
	 * @return IObj
	 */
	public function write(): IObj
	{
		$source = $this->source;
		$status = self::STATUS_FAILED;
		$data = Val::isNull($this->contents) ? HTTP::jsonEncode($this->fields) : $this->contents; 
		
		$source->empty();
		$source->contents( $data );
		$source->write();				
		
		$status = self::STATUS_SUCCESS;
		
		$this->status( $status );

		return $this;
	}
	
	/**
	 * Reads the current data of the spreadsheet.
	 *
	 * @return IObj
	 */
	public function read(): IObj
	{	
		if ( $this->index ) {
			$row = $this->source[ $this->index ];
			$this->loadArray( $row );
		}

		return $this;
	}
	
/**
	 * Removes the current element at the index in the source array
	 * 
	 * @return IObj
	 */
	public function delete(): IObj
	{
		unset ( $this->source[ $this->index] );

		return $this;
	}

	/**
	 * Gets or sets the current index of the source array
	 * 
	 * @param int|null $index  The index to set to
	 * 
	 * @return int The current index
	 */
	public function index( $index = null )
	{
		if ( $index && $this->inbounds() ) {
			$this->index = $index;
		}
		
		return $this->index;
	}

	/**
	 * Checks if the current index is within bounds of the source array
	 * 
	 * @return bool  True if within bounds, false otherwise
	 */
	private function inbounds() {
		return ( $this->index <= count( $this->source ) && $this->index >= 0 );
	}

	/**
	 * Increments the current index
	 * 
	 * @return void
	 */
	public function next() {
		if ( $this->inbounds() )
			$this->index++;
	}

	/**
	 * Decrements the current index
	 * 
	 * @return void
	 */
	public function previous() {
		if ( $this->inbounds() )
			$this->index--;
	}
}