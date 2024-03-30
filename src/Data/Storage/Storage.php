<?php
namespace BlueFission\Data\Storage;

use BlueFission\DevValue;
use BlueFission\Data\Data;
use BlueFission\Data\IData;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Data\Storage\Behaviors\StorageAction;

/**
 * Class Storage
 * 
 * @package BlueFission\Data\Storage
 * 
 * This class represents the basic structure for data storage.
 */
class Storage extends Data implements IData
{
    /**
     * @var mixed The contents of the storage
     */
	protected $_contents;
	
	/**
	 * @var mixed The source of the storage
	 */
	protected $_source;
	
	/**
	 * @var array The configuration data for the storage
	 */
	protected $_config = [];

	/**
	 * @var string A constant string indicating a successful operation
	 */
	const STATUS_SUCCESS = 'Success.';
	
	/**
	 * @var string A constant string indicating a failed operation
	 */
	const STATUS_FAILED = 'Failed.';
	
	/**
	 * @var string A constant string indicating a failed init() operation for the storage
	 */
	const STATUS_FAILED_INIT = 'Could not init() storage.';
	
	/**
	 * @var string A constant string indicating a successful init() operation for the storage
	 */
	const STATUS_SUCCESSFUL_INIT = 'Storage is activated.';
	
	/**
	 * @var string A constant string indicating the name field of the storage
	 */
	const NAME_FIELD = 'name';
	
	/**
	 * @var string A constant string indicating the location field of the storage
	 */
	const PATH_FIELD = 'location';
	
	/**
	 * Storage constructor.
	 * 
	 * @param array|null $config The configuration data for the storage
	 */
	public function __construct( $config = null )
	{
		parent::__construct();
		if (is_array($config)) {
			$this->config($config);
		}
	} 
	
	/**
	 * Method for initializing the storage
	 */
	protected function init() 
	{
		parent::init();

		$this->behavior( new StorageAction( StorageAction::READ ), array(&$this, 'read') );
		$this->behavior( new StorageAction( StorageAction::WRITE ), array(&$this, 'write') );
		$this->behavior( new StorageAction( StorageAction::DELETE ), array(&$this, 'delete') );
	}
	
	/**
	 * Method for activating the storage
	 */
	public function activate() { 
		if ( $this->_source )
			$this->perform( Event::ACTIVATED );
	}
	
	/**
	 * Method for reading data from the storage
	 */
	public function read() { 
		
		$this->perform( Event::COMPLETE );
	}
	
	/**
	 * Method for writing data to the storage
	 */
	public function write() { $this->perform( Event::COMPLETE ); }
	
	/**
	 * Method for deleting data from the storage
	 */
	
	/**
	 * Performs a delete operation on the contents of the storage.
	 * Triggers the Event::COMPLETE event.
	 * 
	 * @return void
	 */
	public function delete() { 
		$this->perform( Event::COMPLETE ); 
	}
	
	/**
	 * Gets or sets the contents of the storage.
	 * Triggers the Event::CHANGE event if the contents are set.
	 * 
	 * @param mixed $data The data to be set as the contents of the storage.
	 * 
	 * @return mixed The contents of the storage if $data is not set, otherwise void.
	 */
	public function contents($data = null)
	{
		if (DevValue::isNull($data)) return $this->_contents;
		
		$this->_contents = $data;
		$this->perform( Event::CHANGE ); 
	}

}