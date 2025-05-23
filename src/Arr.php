<?php
namespace BlueFission;

use BlueFission\Behavioral\Behaviors\Event;
use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Class Arr
 * This class is a value object for arrays.
 * It has various array helper methods for checking array type, getting/setting values, removing duplicates etc.
 * It also implements ArrayAccess interface
 * 
 * @package BlueFission
 * @implements IVal
 * @implements ArrayAccess
 */

 ## Coding Standards (Internal Summary)

//  Class properties use snake_case, public methods use camelCase.
//  This makes it easy to tell variables apart from methods at a glance.
 
//  Always type hint where possible (int, bool, array, etc.).
//  This helps prevent bugs by telling PHP and other developers what kind of data to expect.
 
//  Avoid inline logical negation like !$this->is(...) more than once in a method.
//  Instead, check once at the top of the method and return early. This keeps the code cleaner and easier to read.
 
//  Trigger Event::CHANGE on any method that mutates _data.
//  Whenever you change the data (adding, removing, updating), fire this event so other parts of the system can respond.
 
//  Ensure PHPDoc for all public methods with @param, @return, and if applicable, @throws.
//  This documents what the method does, what it needs, what it gives back, and what errors it might throw — very helpful for team members and IDEs.
 
//  Avoid commented-out legacy code. Use version control for history.
//  Don’t leave old code commented in the file — Git keeps track of the past. Keep the file clean.
 
//  Validate $_data using $this->is() before processing.
//  Always make sure the value is actually an array before doing array stuff like looping or modifying. This prevents unexpected errors.


class Arr extends Val implements IVal, ArrayAccess, Countable, IteratorAggregate {
    protected $_type = DataTypes::ARRAY;

    protected $_forceType = false;

    /**
     * Arr constructor.
     * @param null|mixed $value
     */
    public function __construct( $value = null, bool $snapshot = true, $cast = true ) {
        parent::__construct( $value, $snapshot, $cast );

        if ($cast) {
    		$this->_data = $this->toArray();
    		$this->trigger(Event::CHANGE);
        }
    }

    /**
	 * Convert the value to the type of the var
	 *
	 * @return IVal
	 */
	public function cast(): IVal
	{
		if ( $this->_type ) {
			$this->_data = $this->toArray();
		}

		return $this;
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
		if (!$this->is($this->_data)) {
			return false;
		}

		return in_array($value, $this->_data);
	}

	/**
	 * Searches for a value in the array
	 * 
	 * @param  mixed  $value the value to search for
	 * @return bool        true if value is found
	 */
	public function _search( mixed $value ): bool
	{
		if (!$this->is($this->_data)) {
			return false;
		}

		return array_search($value, $this->_data);
	}

	/**
	 * Checks if key is registered in the array
	 * 
	 * @param  string|int  $key the key to search for
	 * @return bool      true if found
	 */
	public function _hasKey( string|int $key ): bool
	{
		if (!$this->is($this->_data)) {
			return false;
		}

		return array_key_exists($key, $this->_data);
	}

	/**
	 * Shifts the first element off of the data array
	 * @return mixed the first element
	 */
	public function _shift( ): mixed
	{
		if (!$this->is($this->_data)) {
			return false;
		}

		return array_shift($this->_data);
	}

	/**
	 * Unshifts the first element onto the data array
	 */
	public function _unshift( mixed $value ): IVal
	{
		if (!$this->is($this->_data)) {
			return $this;
		}

		array_unshift($this->_data, $value);

		return $this;
	}

	/**
	 * Pops the last element off of the data array
	 * @return mixed the last element
	 */
	public function _pop( ): mixed
	{
		if (!$this->is($this->_data)) {
			return false;
		}

		return array_pop($this->_data);
	}
	
    /**
     * check if the array is a hash
     * @return bool
     */
    public function _isHash( ): bool {
    	if (!$this->is($this->_data)) {
			return false;
		}
        $var = $this->_data;

        return !(is_numeric( implode( array_keys( $var ))));
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
    	if (!$this->is($this->_data)) {
			return false;
		}
        $var = $this->_data;

        return (is_numeric( implode( array_keys( $var ))));
    }

    /**
     * check if the array is not empty
     * @return bool
     */
    public function _isNotEmpty( ): bool {
    	if (!$this->is($this->_data)) {
			return false;
		}

        $var = $this->_data;

        if ( !empty( $var ) && count($var) >= 1) {
            if ( count($var) == 1 && !$this->isAssoc($var) && empty( $var[0]) ) return false;
        } elseif ( empty( $var ) || count($var) < 1 ) {
			return false;
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
    public function _size( ): int {
    	if (!$this->is($this->_data)) {
			return false;
		}
		$var = $this->_data;

		return count( $var );
	}

    /**
     * get value for given key in an array if it exists
     * @param mixed $key
     * @return mixed|null
     */
    public function get(mixed $key ) {
    	if (!$this->is($this->_data)) {
			return false;
		}
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
    public function set(mixed $key, mixed $value ) {
    	if (!$this->is($this->_data)) {
			return false;
		}

        $this->_data[$key] = $value;
        $this->trigger( Event::CHANGE );
    }

    /**
     * Gets a slice of the array
     *
     * @param int $offset
     * @param int|null $length
     * @return IVal
     */
    public function _slice(int $offset, int $length = null): array
    {
		if (!$this->is($this->_data)) {
			return $this;
		}
		
		$array = $this->_data;
		$array = array_slice($array, $offset, $length);

		return $array;
	}


    /**
     * Sort the array
     * @param callable|null $callable
     * @return IVal
    */
   public function _sort( callable $callable = null ): IVal
    {
    	if (!$this->is($this->_data)) {
    		return $this;
    	}

    	if ($callable) {
			usort($this->_data, $callable);
		} else {
			sort($this->_data);
		}

        return $this;
    }

    /**
     * get the largest integer value from an array
     * @return int
     */
	public function _max( ): int {
		if (!$this->is($this->_data)) {
			return false;
		}
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
		if (!$this->is($this->_data)) {
			return false;
		}
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
	 * outputs any value as an array element or returns value if it is an array
	 * @param bool $allow_empty
	 * @return array
	 */
	public function _rand( ): mixed {
		if (!$this->is($this->_data)) {
			return false;
		}

		return $this->_data[array_rand($this->_data)];
	}

	/**
	 * Display representation of the array as a string
	 *
	 * @return string
	 */
	public function __toString(): string {
		if (!$this->is($this->_data)) {
			return false;
		}

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
	 * @return IVal
	 */
	public function _merge( ...$arrays ): IVal {
		if (!$this->is($this->_data)) {
			return $this;
		}
		$array = $this->_data;
		foreach ($arrays as $arg) {
			if ( $arg instanceof Arr ) {
				$arg = $arg->toArray();
			}

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
	 * @return IVal
	 */
	public function _append( ...$arrays ): IVal
	{
		if (!$this->is($this->_data)) {
			return $this;
		}
		$array = $this->_data;
		foreach ($arrays as $arg) {
			if ( $arg instanceof Arr ) {
				$arg = $arg->toArray();
			}

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
	 * Prepend one or more elements to the beginning of an array
	 */
	public function _prepend( ...$arrays ): IVal
	{
		if (!$this->is($this->_data)) {
			return $this;
		}
		$array = $this->_data;
		foreach ($arrays as $arg) {
			if (is_array($arg)) {
				foreach ($arg as $key=>$value) {
					if (is_numeric($key) && !in_array($value, $array)) {
						array_unshift($array, $value);
					}
				}
			}
		}
		$this->alter($array);

		return $this;
	}


/**
 * Pushes a value to the end of the internal array.
 *
 * @param mixed $var Value to push
 * @return IVal
 */
public function push($var): IVal
	{
		if (!$this->is($this->_data)) {
			return $this;
		}

		$array = $this->_data;
		array_push($array, $var);

		$this->alter($array);

		return $this;
	}

	/**
	 * Iterate through the array with a callback
	 */
	public function _each( callable $callback ): IVal
	{
		if (!$this->is($this->_data)) {
			return $this;
		}

		$array = $this->_data;
		foreach ($array as $key=>$value) {
			$callback($value, $key);
		}

		return $this;
	}


	/**
	 * Remove a value from the array
	 * @param mixed $value
	 * @return IVal
	 */
	public function _remove( mixed $value ): IVal
	{
		if (!$this->is($this->_data)) {
			return $this;
		}

		$array = $this->_data;
		$key = array_search($value, $array);
		if ($key !== false) {
			unset($array[$key]);
		}

		$this->alter($array);

		return $this;
	}

	/**
	 * Remove a value from the array by offset or key
	 * @param mixed $offset
	 * @return IVal
	 */
	public function _delete( mixed $offset ): IVal
	{
		if (!$this->is($this->_data)) {
			return $this;
		}

		$array = $this->_data;
		if (array_key_exists($offset, $array)) {
			unset($array[$offset]);
		}

		$this->alter($array);

		return $this;
	}


	/**
	 * get intersection between the $_data and the argument array
	 * @param array $array
	 *
	 * @return Arr
	 */
	public function _intersect( array|Arr $array ): Arr {
		if ( !is_array($array) ) {
			$array = $array->toArray();
		}

		if (!$this->is($this->_data)) {
			return Arr::make();
		}

		$array = array_intersect($this->_data, $array);

		return Arr::make($array);
	}

	/**
	 * get difference between the $_data and the argument array
	 * @param array $array
	 *
	 * @return Arr
	 */
	public function _diff( array|Arr $array ): Arr {
		if ( !is_array($array) ) {
			$array = $array->toArray();
		}

		if (!$this->is($this->_data)) {
			return Arr::make();
		}

		$array = array_diff($this->_data, $array);

		return Arr::make($array);
	}

	/**
	 * flip the keys and values of the $_data array
	 * @return Arr
	 */
	public function _flip(): Arr {
		if (!$this->is($this->_data)) {
			return Arr::make();
		}

		$array = array_flip($this->_data);

		return Arr::make($array);
	}

	/**
	 * get the keys of the $_data array
	 * @return Arr
	 */
	public function _keys(): Arr {
		if (!$this->is($this->_data)) {
			return Arr::make();
		}
		$keys = array_keys($this->_data);

		return Arr::make($keys);
	}
		
	/**
	 * Return a count of the base array
	 * @return int the number of elements in $_data
	 */
	public function count(): int
	{
		if (!$this->is($this->_data)) {
			return 0;
		}
		return count($this->_data);
	}

	/**
	 * Remove duplicate values from an array as a reference
	 * @return IVal
	 */
	public function _unique(): IVal
	{
		if (!$this->is($this->_data)) {
			return $this;
		}
		$array = $this->_data;

		$array = array_unique($array, SORT_STRING);

		$this->alter($array);

		return $this;
	}

	/**
	 * Case-insensitive remove duplicate values from an array as a reference
	 * @return IVal
	 */
	public function _iUnique(): IVal
	{
		if (!$this->is($this->_data)) {
			return $this;
		}
		$array = $this->_data;
		$hold = [];
		 foreach ($array as $a=>$b) 
		 {
			if (!in_array(strtolower($b), $hold) && !is_array($b)) 
			{ 
				$hold[$a] = strtolower($b); 
			}
		}

		$array = array_intersect_key($array, $hold);

		unset($hold);
		$this->alter($array);

		return $this;
	}

	/**
	 * Get the change between the current value and the snapshot
	 *
	 * @return mixed
	 */
	public function delta()
	{
		return $this->distance($this->_snapshot);
	}

	public function _distance(array $array2): float {
		if (!$this->is($this->_data)) {
			return $this;
		}

		$array1 = $this->_data;

	    $keys = array_unique(array_merge(array_keys($array1), array_keys($array2)));
	    $distance = 0;

	    foreach ($keys as $key) {
	        $value1 = $array1[$key] ?? 0;
	        $value2 = $array2[$key] ?? 0;
	        $distance += abs($value1 - $value2);
	    }

	    return $distance;
	}

	/**
	 * Check if the offset exists in the data array
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists ( $offset ) : bool {
		if (!$this->is($this->_data)) {
			return false;
		}

		return isset( $this->_data[$offset] );
	}

	/**
	 * Get the value of the offset in the data array
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet ( $offset ) : mixed {
		if (!$this->is($this->_data)) {
			return null;
		}

		return $this->get( $offset );
	}

	/**
	 * Set the value of the offset in the data array
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet ( $offset, $value ) : void {
		if (!$this->is($this->_data)) {
			return;
		}

		if (is_null($offset)) {
			while (array_key_exists($offset, $this->_data) || !$offset) {
				$offset = count($this->_data);
			}
		}

		$this->set($offset, $value);
	}

	/**
 * Move the internal pointer to the next element and return it.
 *
 * @return mixed|null
 */
public function next() {

		try {
			return next($this->_data);
		} catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * Unset a value at the specified offset
	 * 
	 * @param mixed $offset The offset to unset
	 * @return void
	 */
	public function offsetUnset ( $offset ) : void {
		if (!$this->is($this->_data)) {
			return;
		}

		if ( $this->offsetExists ( $offset ) ) {
			unset( $this->_data[$offset] );
		}
	}

/**
 * Get an iterator for the internal array.
 *
 * @return Traversable
 */
public function getIterator(): Traversable {
	return new \ArrayIterator($this->_data);
    }

    /**
     * Magic method for handling static calls.
     * Overrides to specially handle the 'count' method due to Countable interface.
     *
     * @param string $method The name of the method being called.
     * @param array $args The arguments passed to the method.
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic($method, $args)
    {
        if (strtolower($method) === 'count' && empty($args)) {
            // Instantiate the object to access its non-static context.
            $object = new static();
            // Return the count result directly.
            return $object->count();
        }

        // Fallback to parent handling for all other methods.
        return parent::__callStatic($method, $args);
    }
}