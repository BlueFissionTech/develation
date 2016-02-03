<?php
namespace BlueFission\Data\Storage;

use BlueFission\DevValue;
use BlueFission\Data\Data;
use BlueFission\Data\IData;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Data\Storage\Behaviors\StorageAction;

class Storage extends Data implements IData
{
	protected $_contents;
	protected $_source;
	
	const STATUS_SUCCESS = 'Success.';
	const STATUS_FAILED = 'Failed.';
	const STATUS_FAILED_INIT = 'Could not init() storage.';
	
	const NAME_FIELD = 'name';
	const PATH_FIELD = 'location';
	
	public function __construct( $config = null )
	{
		parent::__construct();
		if (is_array($config))
			$this->config($config);
	} 
	
	protected function init() 
	{
		parent::init();

		$this->behavior( new StorageAction( StorageAction::READ ), array(&$this, 'read') );
		$this->behavior( new StorageAction( StorageAction::WRITE ), array(&$this, 'write') );
		$this->behavior( new StorageAction( StorageAction::DELETE ), array(&$this, 'delete') );
	}
	
	public function activate() { 
		if ( $this->_source )
			$this->perform( Event::ACTIVATED );
	}
	public function read() { 
		
		$this->perform( Event::COMPLETE );
	}
	public function write() { $this->perform( Event::COMPLETE ); }
	public function delete() { $this->perform( Event::COMPLETE ); }
	
	public function contents($data = null)
	{
		if (DevValue::isNull($data)) return $this->_contents;
		
		$this->_contents = $data;
		$this->perform( BlueFission\Event::CHANGE ); 
	}
}