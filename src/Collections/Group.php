<?php
namespace BlueFission\Collections;

use ArrayAccess;
use ArrayObject;
use BlueFission\Val;
use BlueFission\Obj;
use BlueFission\Behavioral\Behaviors\Configurable;

/**
 * Class Group
 *
 * Collection of values that can be manipulated as a group.
 *
 * @package BlueFission\Collections
 * @implements ICollection
 * @implements ArrayAccess
 */
class Group extends Collection implements ICollection, ArrayAccess {
	
	/**
	 * Type of objects to store in the group.
	 *
	 * @var null|string
	 */
	protected $_type = null;

	/**
	 * Get or set the type of objects stored in the group.
	 *
	 * @param null|string $type
	 * @return null|string
	 */
	public function type( $type = null ) {
		if ( Val::isNull($type) ) {
			return $this->_type;
		}
		$this->_type = $type;
	}

	/**
	 * Convert a value to the type specified by the group.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private function convert( $value ) {
		if ( $this->_type && !($value instanceof $this->_type) ) {
			try {
				$object = new $this->_type();
			} catch ( Exception $e ) {
				$object = null;
			}
			
			if (
				is_array($value) && 
				( is_a($object, Obj::class) || is_subclass_of($object, Obj::class) ) 
			) {
				$object->assign($value);
				$value = $object;
			} elseif ( is_a($object, Val::class || is_subclass_of($object, Val::class) ) ) {
				$value = new $this->_type($value);
			}
		}
		return $value;
	}

	/**
	 * Get the value stored at the specified key.
	 *
	 * @param mixed $key
	 * @return mixed
	 */
	public function get( $key ) {
		$value = parent::get( $key );
		return $this->convert( $value );
	}

	/**
	 * Get the first value in the group.
	 *
	 * @return mixed
	 */
	public function first()	{
		$value = parent::first();
		return $this->convert( $value );
	}

	/**
	 * Get the last value in the group.
	 *
	 * @return mixed
	 */
	public function last() {
		$value = parent::last();
		return $this->convert( $value );
	}
}