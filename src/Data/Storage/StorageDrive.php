<?php 
namespace BlueFission\Data\Storage;

use BlueFission\IObj;
use BlueFission\Data\Storage\Storage;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Configurable;
use BlueFission\Behavioral\IConfigurable;

/**
 * Class StorageDrive
 *
 * @package BlueFission\Data\Storage
 */
class StorageDrive implements IConfigurable {
	use Configurable;
	/**
	 * An array of storage devices in the drive
	 *
	 * @var array
	 */
	protected $bays = [];
	/**
	 * The active bay in the drive
	 *
	 * @var
	 */
	protected $activeBay;

	/**
	 * Add a storage device to the drive
	 *
	 * @param Storage $device
	 * @param null $name
	 */
	public function add( Storage $device, $name = null ): IObj
	{
		$device->activate();
		$this->bays[$name] = $device;

		return $this;
	}

	/**
	 * Eject a storage device from the drive
	 *
	 * @param $name
	 */
	public function eject( $name ): IObj
	{
		unset($this->bays[$name]);

		return $this;
	} 

	/**
	 * Get all storage devices in the drive
	 *
	 * @return array
	 */
	public function all(): array
	{
		$devices = [];
		foreach ( $this->bays as $name => $device ) {
			$devices[$name] = get_class($device);
		}
		return $devices;
	}

	/**
	 * Select a bay in the drive
	 *
	 * @param $bay
	 */
	public function use( $bay ): IObj
	{
		$this->activeBay = $bay;

		return $this;
	}

	/**
	 * Create a new data in the active storage device
	 */
	public function create(): IObj
	{
		$this->bays[$this->activeBay]->create();

		return $this;
	}

	/**
	 * Read data from the active storage device
	 */
	public function read(): IObj
	{
		$this->bays[$this->activeBay]->read();

		return $this;
	}

	/**
	 * Update data in the active storage device
	 */
	public function update(): IObj
	{
		$this->bays[$this->activeBay]->update();

		return $this;
	}

	/**
	 * Delete data from the active storage device
	 */
	public function delete(): IObj
	{
		$this->bays[$this->activeBay]->delete();

		return $this;
	}

	/**
	 * Clear data from the active storage device
	 */
	public function clear(): IObj
	{
		$this->bays[$this->activeBay]->clear();

		return $this;
	}

	/**
	 * Bind an object to the storage drive
	 *
	 * @param $object
	 */
	public function bind($object): IObj
	{
		if ( $object instanceof IDispatcher ) {
			$object->behavior( Event::CHANGE, array($this ,'_onObjectUpdate') );
		}

		return $this;
	}

	/**
	 * This method handles the update event of an object.
	 * 
	 * @param $event - The event object
	 */
	public function _onObjectUpdate( $event )
	{
		$object = $event->target;
	}
}