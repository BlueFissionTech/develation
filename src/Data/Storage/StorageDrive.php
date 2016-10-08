<?php 
namespace BlueFission\Data\Storage;

use BlueFission\Data\Storage\Storage;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;

class StorageDrive extends Configurable {
	protected $_bays = array();
	protected $_active_bay;

	public function add( Storage $device, $name = null ) {
		$device->activate();
		$this->_bays[$name] = $device;
	}

	public function eject( $name ) {
		unset($this->_bays[$name]);
	} 

	public function all() {
		$devices = array();
		foreach ( $this->_bays as $name=>$device ) {
			$devices[$name] = get_class($device);
		}
		return $devices;
	}

	public function use( $bay ) {
		$this->_active_bay = $bay;
	}

	public function create() {
		$this->_bays[$this->_active_bay]->create();
	}

	public function read() {
		$this->_bays[$this->_active_bay]->read();
	}

	public function update() {
		$this->_bays[$this->_active_bay]->update();
	}

	public function delete() {
		$this->_bays[$this->_active_bay]->delete();
	}

	public function clear() {
		$this->_bays[$this->_active_bay]->clear();		
	}

	public function bind($object) {
		if ( $object instanceof Dispatcher ) {
			$object->behavior( Event::CHANGE, array($this ,'_onObjectUpdate') );
		}
	}

	public function _onObjectUpdate( $event ) {
		$object = $event->_target;
		// $this->assign($object->data())
	}
}