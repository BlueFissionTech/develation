<?php
namespace BlueFission\Data\Storage;

use BlueFission\DevString;
use BlueFission\Data\IData;
use BlueFission\Data\FileSystem;
use BlueFission\Net\HTTP;

class Disk extends Storage implements IData
{
	protected $_config = array( 
		'location'=>'', 
		'name'=>'' 
	);
		
	public function __construct( $config = null ) {
		parent::__construct( $config );
	}
	
	public function activate( ) {
		$path = $this->config('location') ? $this->config('location') : sys_get_temp_dir();
		
		$name = $this->config('name') ? (string)$this->config('name') : DevString::random();
			
		if (!$this->config('name'))	{
			$file = tempnam($path, $name);		
		}

		$filesystem = new FileSystem( array('mode'=>'c') );
		if ( $filesystem->open($file) )
			$this->_source = $filesystem;

		if ( !$this->_source ) 
			$this->status( self::STATUS_FAILED_INIT );
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
		$source = $this->_source;
		$source->read();

		$value = $source->contents();
		if ( function_exists(json_decode))
		{
			$value = json_decode($value);
			$this->loadArray($value);
		}	
		return $value;
	}
	
	public function delete() {
		$source = $this->_source;
		$source->delete();
	}
}