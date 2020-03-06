<?php
namespace BlueFission\Collections;

use ArrayAccess;
use ArrayObject;
use BlueFission\DevValue;
use BlueFission\Behavioral\Behaviors\Configurable;

class Group extends Collection implements ICollection, ArrayAccess {
	
	public function type( $type = null ) {
		if ( DevValue::isNull($type) ) {
			return $this->_type;
		}
		$this->_type = $type;
	}

	private function convert( $value ) {
		if ( $this->_type && ! $value instanceof $this->_type ) {
			if ( is_array($value) && is_subclass_of($this->_type, '\BlueFission\Behavioral\Configurable') ) {
				$object = new $this->_type();
				$object->assign($value);
				$value = $object;
			} elseif ( is_subclass_of($this->_type, '\BlueFission\DevValue') ) {
				$value = new $this->_type($value);
			} else {
				// $value = settype($value, $this->_type);
			}
		}
		return $value;
	}

	public function get( $key ) {
		$value = parent::get( $key );
		return $this->convert( $value );
	}

	public function first()	{
		$value = parent::first();
		return $this->convert( $value );
	}

	public function last() {
		$value = parent::last();
		return $this->convert( $value );
	}
}