<?php
namespace BlueFission\Collections;

use ArrayAccess;
use ArrayObject;
use ArrayIterator;
use IteratorAggregate;
use BlueFission\DevValue;
use BlueFission\DevArray;

class Collection extends DevArray implements ICollection, ArrayAccess, IteratorAggregate {
	protected $_value;
	protected $_type = "";
	protected $_iterator;

	public function __construct( $value = null ) {
		parent::__construct( $value );
		if ( empty( $value ) )
		{
			$this->_value = new ArrayObject( );
		}
		else
		{
			$this->_value = new ArrayObject( DevArray::toArray() );
		}

        $this->_iterator = new ArrayIterator($this->_value);	
	}

	public function get( $key ) {
		if (!is_scalar($key) && !is_null($key)) {
			throw new InvalidArgumentException('Label must be scalar');
		}
		if ($this->has( $key )) {
			return $this->_value[$key];
		}
		else 
			return null;		
	}

	public function toArray( bool $allow_empty = false ) {
		$value = $this->_value->getArrayCopy();
		return $value;
	}

	public function has( $key ) {
		if (!is_scalar($key) && !is_null($key)) {
			throw new InvalidArgumentException('Label must be scalar');
		}
		// return is_object($this->_value) ? property_exists( $this->_value, $key ) : array_key_exists( $key, $this->_value );
		return $this->_value->offsetExists($key);
	}
	public function add( $object, $key = null ) {
		if (!is_scalar($key) && !is_null($key)) {
			throw new InvalidArgumentException('Label must be scalar');
		}
		$this->_value[$key] = $object;
	}
	public function first()	{
		$array = $this->_value->getArrayCopy();
		$array = array_reverse ( $array );
		return end ( $array );
	}
	public function last() {
		return end( $this->_value );
	}
	public function contents() {
		return $this->_value->getArrayCopy();
	}
	public function remove( $key ) {
		if (!is_scalar($key) && !is_null($key)) {
			throw new InvalidArgumentException('Label must be scalar');
		}
		if ( isset($this->_value[$key]) )
			unset( $this->_value[$key]);
	}
	public function clear() {
		unset( $this->_value );
		$this->_value = new ArrayObject();
	}

	public function count() {
		return $this->_value->count();
	}

	public function each() {
		if ( $this->valid() ) {
			$row = $this->current();
			$this->next();
			return $row;
		} else {
			$this->rewind();
			return false;
		}
	}

	public function serialize() {
        return serialize($this->_value);
    }

    public function unserialize($data) {
        $this->_value = unserialize($data);
    }

    // Array Access
    public function offsetExists ( $offset ) {
		return $this->has( $offset );
    }
	public function offsetGet ( $offset ) {
		return $this->get( $offset );
	}
	public function offsetSet ( $offset, $value ) {
		$this->add( $value, $offset );
	}
	public function offsetUnset ( $offset ) {
		$this->remove( $offset );
	}

	// Iteration
	public function rewind() {
		$this->_iterator->rewind();
	}

	public function current() {
		return $this->get( $this->_iterator->key() );
	}

	public function key() {
		return $this->_iterator->key();
	}

	public function next() {
		return $this->_iterator->next();
	}

	public function valid() {
		return $this->has($this->_iterator->key());
	}

	public function getIterator() {
        $this->_iterator = new ArrayIterator($this->_value);
        return $this->_iterator;
    }

    public function contains( $value ) {
    	return in_array($value, $this->_value->getArrayCopy());
    }

    public function rand() {
 		// Doesn't work for associative key based arrays
    	// $rand = 0;
    	// if ( function_exists('mt_rand') ) {
    	// 	$rand = mt_rand(0, $this->_value->count() - 1)];
    	// } else {
    	// 	$rand = array_rand( $this->_value->getArrayCopy() );
    	// }

    	$rand = array_rand( $this->_value->getArrayCopy() );

    	return $rand;
    }
}