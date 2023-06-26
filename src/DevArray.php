<?php
namespace BlueFission;

use BlueFission\Behavioral\Behaviors\Event;
use ArrayAccess;
use Countable;
use IteratorAggregate;

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
class DevArray extends DevValue implements IDevValue, ArrayAccess, Countable, IteratorAggregate {
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
     * Check if value is an array
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function _is( ): bool
    {
		return is_array( $this->_data );
	}

	/**
	 * Checks if value exists in array
	 * 
	 * @param  mixed  $value the value to find
	 * @return bool        true if value is found
	 */
	public function _has( mixed $value ): bool
	{
		return in_array($value, $this->_data);
	}

	/**
	 * Checks if key is registered in the array
	 * 
	 * @param  string|int  $key the key to search for
	 * @return bool      true if found
	 */
	public function _hasKey( string|int $key ): bool
	{
		return array_key_exists($key, $this->_data);
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
     * gets the count length of the array
     * 
     * @return int
     */
    public function _count( ): int {
		$var = $this->_data;
		return count( $var );
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
        $this->dispatch(new Event(Event::CHANGE));
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
		$value_r = [];
		if (!is_string($value) || (!$value == '' || $allow_empty)) {
			(is_array($value)) ? $value_r = $value : ((is_null($value)) ? $value_r : $value_r[] = $value);
		}
		return $value_r;
	}

	/**
	 * Display representation of the array as a string
	 *
	 * @return string
	 */
	public function __toString(): string {
		return print_r(array_slice($this->_data, 0, 10), true);
	}

	/**
     * Convert the array data a JSON string
     * 
     * @return string The array data as a JSON string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

	/**
	 * Merges any number of arrays / parameters recursively with the local $_data array
	 * Replaces entries with string keys with values from latter arrays.
	 * If the entry or the next value to be assigned is an array, then it automagically treats both arguments as an array.
	 * Numeric entries are appended, not replaced, but only if they are unique
	 * @param array ...$arrays
	 * 
	 * @return array
	 */
	public function _merge( ...$arrays ): array {
		$array = $this->_data;
		foreach ($arrays as $arg) {
			if (is_array($arg)) {
				foreach ($arg as $key=>$value) {
					if (is_array($value) && isset($array[$key]) && is_array($array[$key])) {
						$array[$key] = $this->_merge($array[$key], $value);
					}
					else if (is_numeric($key) && !in_array($value, $array)) {
						$array[] = $value;
					}
					else {
						$array[$key] = $value;
					}
				}
			}
		}

		return $this;
	}

	/**
	 * Appends other arrays to local $_data array
	 * @param array ...$arrays
	 * 
	 * @return DevArray
	 */
	public function _append( ...$arrays ): DevArray
	{
		$array = $this->_data;
		foreach ($arrays as $arg) {
			if (is_array($arg)) {
				foreach ($arg as $key=>$value) {
					if (is_numeric($key) && !in_array($value, $array)) {
						$array[] = $value;
					}
				}
			}
		}
		$this->alter($array);

		return $this;
	}

	/**
	 * get intersection between the $_data and the argument array
	 * @param array $array
	 *
	 * @return array
	 */
	public function _intersect( array $array ): array {
		return array_intersect($this->_data, $array);
	}

	/**
	 * get difference between the $_data and the argument array
	 * @param array $array
	 *
	 * @return array
	 */
	public function _diff( array $array ): array {
		return array_diff($this->_data, $array);
	}

	/**
	 * get the keys of the $_data array
	 * @return array
	 */
	public function _keys(): array {
		return array_keys($this->_data);
	}
		
	/**
	 * Return the data as an array
	 * @return array
	 */
	public function value($value = null): array {
		return $this->_toArray();
	}

	/**
	 * Return a count of the base array
	 * @return int the number of elements in $_data
	 */
	public function count(): int
	{
		return count($this->_data);
	}

	/**
	 * Remove duplicate values from an array as a reference
	 * @return DevArray
	 */
	public function _removeDuplicates(): DevArray
	{
		$array = $this->_data;
		$hold = [];
		foreach ($array as $a=>$b) {
			if (!in_array($b, $hold, true))	{ 
				$hold[$a] = $b;
			}
		}
		$array = $hold;
		unset($hold);
		$this->alter($array);

		return $this;
	}

	/**
	 * Case insensitive remove duplicate values from an array as a reference
	 * @return DevArray
	 */
	public function _iRemoveDuplicates(): DevArray
	{
		$array = $this->_data;
		$hold = [];
		 foreach ($array as $a=>$b) 
		 {
			if (!in_array(strtolower($b), $hold) && !is_array($b)) 
			{ 
				$hold[$a] = strtolower($b); 
			}
		}
		$array = $hold;
		unset($hold);
		$this->alter($array);

		return $this;
		
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
	}<?php
namespace BlueFission;

use BlueFission\Behavioral\Behaviors\Event;
use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;

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
class DevArray extends DevValue implements IDevValue, ArrayAccess, Countable, IteratorAggregate {
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
     * Check if value is an array
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function _is( ): bool
    {
		return is_array( $this->_data );
	}

	/**
	 * Checks if value exists in array
	 * 
	 * @param  mixed  $value the value to find
	 * @return bool        true if value is found
	 */
	public function _has( mixed $value ): bool
	{
		return in_array($value, $this->_data);
	}

	/**
	 * Checks if key is registered in the array
	 * 
	 * @param  string|int  $key the key to search for
	 * @return bool      true if found
	 */
	public function _hasKey( string|int $key ): bool
	{
		return array_key_exists($key, $this->_data);
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
     * gets the count length of the array
     * 
     * @return int
     */
    public function _count( ): int {
		$var = $this->_data;
		return count( $var );
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
        $this->dispatch(new Event(Event::CHANGE));
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
		$value_r = [];
		if (!is_string($value) || (!$value == '' || $allow_empty)) {
			(is_array($value)) ? $value_r = $value : ((is_null($value)) ? $value_r : $value_r[] = $value);
		}
		return $value_r;
	}

	/**
	 * Display representation of the array as a string
	 *
	 * @return string
	 */
	public function __toString(): string {
		return print_r(array_slice($this->_data, 0, 10), true);
	}

	/**
     * Convert the array data a JSON string
     * 
     * @return string The array data as a JSON string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

	/**
	 * Merges any number of arrays / parameters recursively with the local $_data array
	 * Replaces entries with string keys with values from latter arrays.
	 * If the entry or the next value to be assigned is an array, then it automagically treats both arguments as an array.
	 * Numeric entries are appended, not replaced, but only if they are unique
	 * @param array ...$arrays
	 * 
	 * @return array
	 */
	public function _merge( ...$arrays ): array {
		$array = $this->_data;
		foreach ($arrays as $arg) {
			if (is_array($arg)) {
				foreach ($arg as $key=>$value) {
					if (is_array($value) && isset($array[$key]) && is_array($array[$key])) {
						$array[$key] = $this->_merge($array[$key], $value);
					}
					else if (is_numeric($key) && !in_array($value, $array)) {
						$array[] = $value;
					}
					else {
						$array[$key] = $value;
					}
				}
			}
		}

		return $this;
	}

	/**
	 * Appends other arrays to local $_data array
	 * @param array ...$arrays
	 * 
	 * @return DevArray
	 */
	public function _append( ...$arrays ): DevArray
	{
		$array = $this->_data;
		foreach ($arrays as $arg) {
			if (is_array($arg)) {
				foreach ($arg as $key=>$value) {
					if (is_numeric($key) && !in_array($value, $array)) {
						$array[] = $value;
					}
				}
			}
		}
		$this->alter($array);

		return $this;
	}

	/**
	 * get intersection between the $_data and the argument array
	 * @param array $array
	 *
	 * @return array
	 */
	public function _intersect( array $array ): array {
		return array_intersect($this->_data, $array);
	}

	/**
	 * get difference between the $_data and the argument array
	 * @param array $array
	 *
	 * @return array
	 */
	public function _diff( array $array ): array {
		return array_diff($this->_data, $array);
	}

	/**
	 * get the keys of the $_data array
	 * @return array
	 */
	public function _keys(): array {
		return array_keys($this->_data);
	}
		
	/**
	 * Return the data as an array
	 * @return array
	 */
	public function value($value = null): array {
		return $this->_toArray();
	}

	/**
	 * Return a count of the base array
	 * @return int the number of elements in $_data
	 */
	public function count(): int
	{
		return count($this->_data);
	}

	/**
	 * Remove duplicate values from an array as a reference
	 * @return DevArray
	 */
	public function _removeDuplicates(): DevArray
	{
		$array = $this->_data;
		$hold = [];
		foreach ($array as $a=>$b) {
			if (!in_array($b, $hold, true))	{ 
				$hold[$a] = $b;
			}
		}
		$array = $hold;
		unset($hold);
		$this->alter($array);

		return $this;
	}

	/**
	 * Case insensitive remove duplicate values from an array as a reference
	 * @return DevArray
	 */
	public function _iRemoveDuplicates(): DevArray
	{
		$array = $this->_data;
		$hold = [];
		 foreach ($array as $a=>$b) 
		 {
			if (!in_array(strtolower($b), $hold) && !is_array($b)) 
			{ 
				$hold[$a] = strtolower($b); 
			}
		}
		$array = $hold;
		unset($hold);
		$this->alter($array);

		return $this;
		
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

	public function getIterator() : Traversable {
        return new \ArrayIterator($this->_data);
    }
}
}