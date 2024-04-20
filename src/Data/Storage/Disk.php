<?php
namespace BlueFission\Data\Storage;

use BlueFission\Val;
use BlueFission\Str;
use BlueFission\Arr;
use BlueFission\IObj;
use BlueFission\Net\HTTP;
use BlueFission\Data\IData;
use BlueFission\Data\FileSystem;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Action;

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
			'root'=>realpath($path)
		] );

		$filesystem->basename = $name;
		$filesystem
		->when(Event::CONNECTED, (function ($b, $m) use ($filesystem) {
			$this->_source = $filesystem;
			$this->status( self::STATUS_SUCCESSFUL_INIT );
		})->bindTo($this, $this))
		->when(Event::FAILURE, (function ($b, $m) {
			if ( $m->when == Action::CONNECT ) {
				$this->status( self::STATUS_FAILED_INIT );
				return;
			}
			error_log('Failed: ' . $m->info);
		})->bindTo($this, $this))
		->when(Event::ERROR, (function ($b, $m) {
			if ( $m->when == Action::CONNECT ) {
				$this->status( self::STATUS_FAILED_INIT );
				return;
			}
			error_log('Error: ' . $m->info);
		})->bindTo($this, $this))
		->open();

		return parent::activate();
	}
	
	/**
	 * Writes data to disk storage object.
	 * 
	 * @return void
	 */
	protected function _write(): void
	{
		$source = $this->_source;
		$status = self::STATUS_FAILED;
		if (!$source) {
			$this->status( $status );
			return;
		}

		$data = Val::isEmpty($this->_contents) ? HTTP::jsonEncode($this->_data->val()) : $this->_contents;

		$source->flush()->contents( $data )->write();
		
		$status = self::STATUS_SUCCESS;
		
		$this->status( $status );
	}
	
	/**
	 * Reads data from disk storage object.
	 * 
	 * @return void 
	 */
	protected function _read(): void
	{	
		$source = $this->_source;
		if (!$source) {
			$this->status( $status );

			return;
		}
		$source->read();

		$value = $source->contents();
		if ( function_exists('json_decode'))
		{
			$value = json_decode($value, true);
			$this->contents($value);
			$this->assign((array)$value);
		}
	}
	
	/**
	 * Delete the stored data in the underlying source
	 *
	 * @return void
	 */
	protected function _delete(): void
	{
		$source = $this->_source;
		if (!$source) {
			$this->status( $status );
			return;
		}

		$source->delete();
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