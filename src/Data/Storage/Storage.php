<?php

namespace BlueFission\Data\Storage;

use BlueFission\Val;
use BlueFission\Arr;
use BlueFission\IObj;
use BlueFission\Net\HTTP;
use BlueFission\Data\Data;
use BlueFission\Data\IData;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\State;
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
		if (Arr::is($config)) {
			$this->config($config);
		}
	} 
	
	/**
	 * Method for initializing the storage
	 */
	protected function init(): IObj
	{
		parent::init();

		$this->behavior( new StorageAction( StorageAction::READ ), [&$this, 'read'] );
		$this->behavior( new StorageAction( StorageAction::WRITE ), [&$this, 'write'] );
		$this->behavior( new StorageAction( StorageAction::DELETE ), [&$this, 'delete'] );

		$this->perform( State::DISCONNECTED );

		return $this;
	}
	
	/**
	 * Method for activating the storage
	 */
	public function activate(): IObj
	{
		// If this class is an instance of Storage, not a child class, then instantiate source
		if ( get_class($this) == 'BlueFission\Data\Storage\Storage' ) {
			$this->_source = null;
		}
		if ( Val::isNotNull($this->_source) ) {
			$this->halt( State::DISCONNECTED );
			$this->perform( [Event::ACTIVATED, State::CONNECTED ] );
		} else {
			$this->perform( Event::FAILURE, new Meta(when: Action::ACTIVATE, info: self::STATUS_FAILED_INIT) );
			$this->perform( State::FAILURE );
		}
		return $this;
	}
	
	/**
	 * Method for reading data from the storage
	 */
	protected function _read(): void
	{
		if ( get_class($this) == 'BlueFission\Data\Storage\Storage' ) {
			$this->_contents = $this->_source;
			$this->perform( Event::SUCCESS, new Meta(when: Action::READ));
		}
		
		$this->perform( Event::COMPLETE );
	}
	
	/**
	 * Method for writing data to the storage
	 */
	protected function _write(): void
	{
		if ( get_class($this) == 'BlueFission\Data\Storage\Storage' ) {
			$this->_source = $this->_contents ?? HTTP::jsonEncode($this->_data);
			$this->perform( Event::SUCCESS, new Meta(when: Action::SAVE));
		}

		$this->perform( Event::COMPLETE ); 
	}
	
	/**
	 * Method for deleting data from the storage
	 */
	
	/**
	 * Performs a delete operation on the contents of the storage.
	 * Triggers the Event::COMPLETE event.
	 * 
	 * @return IObj
	 */
	protected function _delete(): void
	{
		if ( get_class($this) == 'BlueFission\Data\Storage\Storage' ) {
			$this->_source = '';
			$this->perform( Event::SUCCESS, new Meta(when: Action::DELETE));
		}
		
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
	public function contents($data = null): mixed
	{
		if (Val::isNull($data)) return $this->_contents;
		
		$this->_contents = $data;
		$this->perform( Event::CHANGE ); 

		return null;
	}

	/**
     * Deactivates the storage class
     *
     * @return IObj $this
     */
    public function deactivate(): IObj {
        // Implement deactivation logic
        $this->perform( State::DISCONNECTING );
        if ( method_exists($this, '_disconnect') ) {
        	$this->_disconnect();
        }
        $this->_source = null;
        $this->halt([State::CONNECTED, State::DISCONNECTING ]);
        $this->perform( State::DISCONNECTED );
        return $this;
    }
}