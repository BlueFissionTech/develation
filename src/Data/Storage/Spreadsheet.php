<?php
namespace BlueFission\Data\Storage;

class Spreadsheet extends Storage implements IData {
	private $_index;

	protected $_config = array( 
		'location'=>'', 
		'name'=>'' 
	);

	public function activate( ) {
		$path = $this->config('location') ? $this->config('location') : sys_get_temp_dir();
		
		$name = $this->config('name') ? (string)$this->config('name') : DevString::random();
			
		if (!$this->config('name'))	{
			$file = tempnam($path, $name);		
		}

		$data = file( $file );

		if ( $data ) {
			$spreadsheet = array();
			foreach ( $data as $row ) {
				$spreadsheet[] = str_getcsv( $row );
			}
			$this->_source = $spreadsheet;
			$this->_index = 0;
		}

		if ( !$this->_source ) 
			$this->status( self::STATUS_FAILED_INIT );
		else
			$this->status( self::STATUS_SUCCESSFUL_INIT );
	}

	public function write() {
		$source = $this->_source;
		$status = self::STATUS_FAILED;
		$data = DevValue::isNull($this->_contents) ? HTTP::jsonEncode($this->_fields) : $this->_contents; 
		
		$source->empty();
		$source->contents( $data );
		$source->write();				
		
		$status = self::STATUS_SUCCESS;
		
		$this->status( $status );	
	}
	
	public function read() {	
		if ( $this->_index 
		$row = $this->_source[ $this->_index ];
		$this->loadArray( $row );

		return $value;
	}
	
	public function delete() {
		unset ( $this->_source[ $this->_index] );
	}

	public function index( $index = null ) {
		if ( $index && $this->inbounds() ) {
			$this->_index = $index;
		}
		return $this->_index;
	}

	private function inbounds() {
		return ( $this->_index <= count( $this->_source ) && $this->_index >= 0 );
	}

	public function next() {
		if ( $this->inbounds() )
			$this->_index++;
	}

	public function previous() {
		if ( $this->inbounds() )
			$this->_index--;
	}
}