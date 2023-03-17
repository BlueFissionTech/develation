<?php 
namespace BlueFission\Data\Storage;

use BlueFission\Data\Storage\Storage;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;

/**
 * Class StorageDrive
 *
 * @package BlueFission\Data\Storage
 */
class StorageDrive extends Configurable {
	/**
	 * An array of storage devices in the drive
	 *
	 * @var array
	 */
	protected $_bays = array();
	/**
	 * The active bay in the drive
	 *
	 * @var
	 */
	protected $_active_bay;

	/**
	 * Add a storage device to the drive
	 *
	 * @param Storage $device
	 * @param null $name
	 */
	public function add( Storage $device, $name = null ) {
		$device->activate();
		$this->_bays[$name] = $device;
	}

	/**
	 * Eject a storage device from the drive
	 *
	 * @param $name
	 */
	public function eject( $name ) {
		unset($this->_bays[$name]);
	} 

	/**
	 * Get all storage devices in the drive
	 *
	 * @return array
	 */
	public function all() {
		$devices = array();
		foreach ( $this->_bays as $name=>$device ) {
			$devices[$name] = get_class($device);
		}
		return $devices;
	}

	/**
	 * Select a bay in the drive
	 *
	 * @param $bay
	 */
	public function use( $bay ) {
		$this->_active_bay = $bay;
	}

	/**
	 * Create a new data in the active storage device
	 */
	public function create() {
		$this->_bays[$this->_active_bay]->create();
	}

	/**
	 * Read data from the active storage device
	 */
	public function read() {
		$this->_bays[$this->_active_bay]->read();
	}

	/**
	 * Update data in the active storage device
	 */
	public function update() {
		$this->_bays[$this->_active_bay]->update();
	}

	/**
	 * Delete data from the active storage device
	 */
	public function delete() {
		$this->_bays[$this->_active_bay]->delete();
	}

	/**
	 * Clear data from the active storage device
	 */
	public function clear() {
		$this->_bays[$this->_active_bay]->clear();		
	}

	/**
	 * Bind an object to the storage drive
	 *
	 * @param $object
	 */
	public function bind($object) {
		if ( $object instanceof Dispatcher ) {
			$object->behavior( Event::CHANGE, array($this ,'_onObjectUpdate') );
		}
	}

	/**
	 * This method handles the update event of an object.
	 * 
	 * @param $event - The event object
	 */
	public function _onObjectUpdate( $event ) {
		$object = $event->_target;
		// $this->assign($object->data())
	}
}