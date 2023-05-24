<?php
namespace BlueFission\Data\Storage;

use BlueFission\DevValue;
use BlueFission\DevString;
use BlueFission\Data\IData;
use BlueFission\Data\FileSystem;
use BlueFission\Net\HTTP;

class Disk extends Storage implements IData
{
	/**
	 * Holds the configuration data for the disk storage object.
	 * 
	 * @var array 
	 */
	protected $_config = array( 
		'location'=>'', 
		'name'=>'' 
	);
		
	/**
	 * Constructor method for the disk storage object.
	 * 
	 * @param array|null $config 
	 */
	public function __construct( $config = null ) {
		parent::__construct( $config );
	}
	
	/**
	 * Activates the disk storage object.
	 * 
	 * @return void
	 */
	public function activate( ) {
		$path = $this->config('location') ? $this->config('location') : sys_get_temp_dir();
		
		$name = $this->config('name') ? (string)$this->config('name') : '';
			
		if (!$this->config('name'))	{
			$file = tempnam($path, 'store_');
		} else {
			$file = $path.DIRECTORY_SEPARATOR.$name;
		}

		$filesystem = new FileSystem( array('mode'=>'c+','filter'=>'file') );
		if ( $filesystem->open($file) ) {
			$this->_source = $filesystem;
		}

		if ( !$this->_source )  {
			$this->status( self::STATUS_FAILED_INIT );
		} else {
			$this->status( self::STATUS_SUCCESSFUL_INIT );
		}
	}
	
	/**
	 * Writes data to disk storage object.
	 * 
	 * @return void
	 */
	public function write() {
		$source = $this->_source;
		$status = self::STATUS_FAILED;
		$data = DevValue::isEmpty($this->_contents) ? HTTP::jsonEncode($this->_data) : $this->_contents; 
		
		$source->flush();
		$source->contents( $data );
		$source->write();				
		
		$status = self::STATUS_SUCCESS;
		
		$this->status( $status );
	}
	
	/**
	 * Reads data from disk storage object.
	 * 
	 * @return mixed 
	 */
	public function read() {	
		$source = $this->_source;
		$source->read();

		$value = $source->contents();
		if ( function_exists('json_decode'))
		{
			$value = json_decode($value, true);
			$this->contents($value);
			$this->assign((array)$value);
		}	
		return $value;
	}
	
	/**
	 * Delete the stored data in the underlying source
	 *
	 * @return void
	 */
	public function delete() {
		$source = $this->_source;
		$source->delete();
	}

	/**
	 * Close the connection to the underlying source and call parent destructor
	 *
	 * @return void
	 */
	public function __destruct() {
		if (isset($this->_source))
			$this->_source->close();
		parent::__destruct();
	}

}