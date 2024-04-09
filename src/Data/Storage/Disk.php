<?php
namespace BlueFission\Data\Storage;

use BlueFission\Val;
use BlueFission\Str;
use BlueFission\Arr;
use BlueFission\IObj;
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
	protected $_config = [ 
		'location'=>'', 
		'name'=>'' 
	];
		
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
	 * @return IObj
	 */
	public function activate( ): IObj
	{
		$path = $this->config('location') ?? sys_get_temp_dir();
		
		$name = $this->config('name') ?? '';
			
		if (!$name)	{
			$name = basename(tempnam($path, 'store_'));
		}

		$filesystem = new FileSystem( [
			'mode'=>'c+',
			'filter'=>'file',
			'root'=>realpath($path),
		] );

		if ( $filesystem->open($name) ) {
			$this->_source = $filesystem;
			$this->status( self::STATUS_SUCCESSFUL_INIT );
		} else {
			$this->status( self::STATUS_FAILED_INIT );
		}

		parent::activate();

		return $this;
	}
	
	/**
	 * Writes data to disk storage object.
	 * 
	 * @return IObj
	 */
	public function write(): IObj
	{
		$source = $this->_source;
		$status = self::STATUS_FAILED;
		if (!$source) {
			$this->status( $status );

			return $this;
		}

		$data = Val::isEmpty($this->_contents) ? HTTP::jsonEncode($this->_data->val()) : $this->_contents;
		
		$source->flush();
		$source->contents( $data );
		$source->write();				
		
		$status = self::STATUS_SUCCESS;
		
		$this->status( $status );

		return $this;
	}
	
	/**
	 * Reads data from disk storage object.
	 * 
	 * @return IObj 
	 */
	public function read(): IObj
	{	
		$source = $this->_source;
		if (!$source) {
			$this->status( $status );

			return $this;
		}
		$source->read();

		$value = $source->contents();
		if ( function_exists('json_decode'))
		{
			$value = json_decode($value, true);
			$this->contents($value);
			$this->assign((array)$value);
		}

		return $this;
	}
	
	/**
	 * Delete the stored data in the underlying source
	 *
	 * @return IObj
	 */
	public function delete(): IObj
	{
		$source = $this->_source;
		if (!$source) {
			$this->status( $status );
			return $this;
		}

		$source->delete();

		return $this;
	}

	/**
	 * Close the connection to the underlying source and call parent destructor
	 *
	 * @return void
	 */
	public function __destruct() {
		if (Val::is($this->_source)) {
			$this->_source->close();
		}
		parent::__destruct();
	}

}