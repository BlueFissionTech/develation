<?php
namespace BlueFission;

use ArrayAccess;

/**
 * Class DevArray
 * This class is a value object for arrays.
 * It has various array helper methods for checking array type, getting/setting values, removing duplicates etc.
 * It also implements ArrayAccess interface
 * 
 * @package BlueFission
 * @implements IDevValue
 * @implements ArrayAccess
 */
class DevArray extends DevValue implements IDevValue, ArrayAccess {
    protected $_type = "array";

    /**
     * DevArray constructor.
     * @param null|mixed $value
     */
    public function __construct( $value = null ) {
        parent::__construct( $value );
        $this->_data = $this->_toArray();
    }

    /**
     * check if the array is a hash
     * @return bool
     */
    public function _isHash( ): bool {
        $var = $this->_data;
        return ((is_array( $var )) && !is_numeric( implode( array_keys( $var ))));
    }

    /**
     * check if the array is associative
     * @return bool
     */
    public function _isAssoc( ): bool {
        return $this->_isHash();
    }

    /**
     * check if the array is numerically indexed
     * @return bool
     */
    public function _isIndexed( ): bool {
        $var = $this->_data;
        return ((is_array( $var )) && is_numeric( implode( array_keys( $var ))));
    }

    /**
     * check if the array is not empty
     * @return bool
     */
    public function _isNotEmpty( ): bool {
        $var = $this->_data;

        if ( !empty( $var ) && is_array($var) && count($var) >= 1) {
            if ( count($var) == 1 && !$this->isAssoc($var) && empty( $var[0]) ) return false;
        }
        return true;
    }

    /**
     * check if the array is empty
     * @return bool
     */
    public function _isEmpty( ): bool {
        return !$this->isNotEmpty( $this->_data);
    }

    /**
     * get value for given key in an array if it exists
     * @param mixed $key
     * @return mixed|null
     */
    public function get( $key ) {
        $var = $this->_data;
        $keys = array_keys( $var );
        if ( in_array( $key, $keys ) )
        {
            return $var[$key];
        }
    }

    /**
     * set value for given key in an array if it exists
     * @param mixed $key
     * @param mixed $value
     */
    public function set( $key, $value ) {
        $this->_data[$key] = $value;
    }

    /**
     * get the largest integer value from an array
     * @return int
     */
	public function _max( ): int {
		$array = $this->_data;
		if (sort($array)) {
			$max = (int)array_pop($array);
		}
		return $max;
	}

	/**
	 * get the lowest integer value from an array
	 * @return int
	 */
	public function _min(): int {
		$array = $this->_data;
		if (rsort($array)) {
			$max = (int)array_pop($array);
		}
		return $max;
	}

	/**
	 * outputs any value as an array element or returns value if it is an array
	 * @param bool $allow_empty
	 * @return array
	 */
	public function _toArray( bool $allow_empty = false): array {
		$value = $this->_data;
		$value_r = array();
		if (!is_string($value) || (!$value == '' || $allow_empty))
			(is_array($value)) ? $value_r = $value : $value_r[] = $value;
		return $value_r;
	}

	/**
	 * Return the data as an array
	 * @return array
	 */
	public function value(): array {
		return $this->_toArray();
	}

	/**
	 * Remove duplicate values from an array as a reference
	 * @return bool
	 */
	public function _removeDuplicates(): bool {
		$array = $this->_data;
		if (is_array($array)) {
			$hold = array();
			foreach ($array as $a=>$b) {
				if (!in_array($b, $hold, true))	{ 
					$hold[$a] = $b;
				}
			}
			$array = $hold;
			unset($hold);
			return true;
		}
		else 
			return false;
	}

	/**
	 * Case insensitive remove duplicate values from an array as a reference
	 * @return bool
	 */
	public function _iRemoveDuplicates(): bool {
		$array = $this->_data;
		if (is_array($array)) 
		{
			$hold = array();
			 foreach ($array as $a=>$b) 
			 {
				if (!in_array(strtolower($b), $hold) && !is_array($b)) 
				{ 
					$hold[$a] = strtolower($b); 
				}
			}
			$array = $hold;
			unset($hold);
			return true;
		} 
		else 
			return false;
	}

	/**
	 * Check if the offset exists in the data array
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists ( $offset ) : bool {
		return isset( $this->_data[$offset] );
	}

	/**
	 * Get the value of the offset in the data array
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet ( $offset ) : mixed {
		return $this->get( $offset );
	}

	/**
	 * Set the value of the offset in the data array
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet ( $offset, $value ) : void {
		if (is_null($offset)) {
			while (array_key_exists($offset, $this->_data) || !$offset) {
				$offset = count($this->_data);
			}
		}
		$this->set($offset, $value);
	}

	/**
	 * Unset a value at the specified offset
	 * 
	 * @param mixed $offset The offset to unset
	 * @return void
	 */
	public function offsetUnset ( $offset ) : void {
		if ( $this->offsetExists ( $offset ) )
			unset( $this->_data[$offset] );
	}

}