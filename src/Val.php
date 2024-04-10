<?php
namespace BlueFission;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Dispatches;
use BlueFission\Collections\Collection;
use Exception;

/**
 * The Val class is meant to be inherited.
 */
class Val implements IVal {
	use Dispatches {
        Dispatches::__construct as private __tConstruct;
    }

	/**
	 * @var mixed $_data
	 */
	protected $_data;

	/**
	 * @var $_constraints
	 */
	protected $_constraints = [];

	/**
	 * Capture the value of the var at a specific time
	 * @var null
	 */
	protected $_snapshot = null;

	/**
	 * @var string $_forceType
	 */
	protected $_forceType = false;

	/**
	 * @var string $type
	 */
	protected $_type = "";

	private static $_instances = null;

	/**
	 * @var string PRIVATE_PREFIX
	 */
	const PRIVATE_PREFIX = '_';

	/**
	 * Constructor to initialize value of the class
	 *
	 * @param mixed $value
	 */
	public function __construct( $value = null, bool $takeSnapshot = true, bool $cast = false ) {
		$this->__tConstruct();

		if ( $value instanceof IVal ) {
			$value = $value->val();
		}

		$this->_data = $value;
		if ( $this->_type && $this->_forceType || $cast ) {
			settype($this->_data, $this->_type);
		}

		if ( $takeSnapshot ) {
			$this->snapshot();
		}

		$this->dispatch(new Event(Event::LOAD));
	}

	/**
	 * Convert the value to the type of the var
	 *
	 * @return IVal
	 */
	public function cast(): IVal
	{
		if ( $this->_type ) {
			settype($this->_data, $this->_type);
		}

		return $this;
	}

	/**
	 * Get the datatype name of the object
	 * @return string
	 */
	public function getType() {
		return $this->_type;
	}

	/**
	 * Make, create a new instance of this class
	 * @param  mixed $value The value to set as the data member
	 * @return IVal        a new instance of the class
	 */
	public static function make($value = null): IVal
	{
		$class = get_called_class();
		$object = new $class();

		$object = ValFactory::make($object->getType(), $value);

		return $object;
	}

	/**
	 * Tag the object with a group to be tracked by the object class
	 * @param string $group The group to tag the object with
	 * @return IVal
	 */
	public function tag($group = null)
	{
		$tag = $this->getType();
		if ( $group ) {
			$tag = $group . '.' . $tag;
		}

		if ( !self::$_instances ) {
			self::$_instances = new Collection();
		}

		if ( !isset(self::$_instances[$tag]) ) {
			self::$_instances[$tag] = new Collection();
		}

		self::$_instances[$tag]->addDistinct($this);

		return $this;
	}

	public function untag($group = null)
	{
		$tag = $this->getType();
		if ( $group ) {
			$tag = $group . '.' . $tag;
		}

		if ( !self::$_instances ) {
			self::$_instances = new Collection();
		}

		if ( isset(self::$_instances[$tag]) ) {
			$key = self::$_instances[$tag]->search($this);
			self::$_instances[$tag]->remove($key);
		}

		return $this;
	}

	/**
	 * Get the group of objects tagged with the specified group
	 * @param string $group The group to get the objects from=
	 * @return Collection
	 */
	public function grp($group = null)
	{
		$tag = $this->getType();
		if ( $group ) {
			$tag = $group . '.' . $tag;
		}

		if ( !self::$_instances ) {
			self::$_instances = new Collection();
		}

		return self::$_instances[$tag];
	}



	///
	//Variable value functions
	///////
	
	/**
	 * checks if the value is set
	 *
	 * @return bool
	 */
	public function _is( ): bool
	{
		return isset($this->_data);
	}
	
	/**
	 * Check if var is a valid instance of $_type
	 *
	 * @return bool
	 */
	public function _isValid( $value = null ): bool
	{
		$var = $value ?? $this->_data;
		if ( $this->_type ) {
			switch ($this->_type) {
				case '': // redundant catch for no type set
					return true;
					break;
				case 'string':
					return is_string($var);
					break;
				case 'number':
				case 'double':
					// validates that value is numeric including zero
					return is_numeric($var);
					break;
				case 'integer':
					return is_int($var);
					break;
				case 'float':
					return is_float($var);
					break;
				case 'bool':
					return is_bool($var);
					break;
				case 'array':
					return is_array($var);
					break;
				case 'object':
					return is_object($var);
					break;
				case 'resource':
					return is_resource($var);
					break;
				case 'null':
					return is_null($var);
					break;
				case 'scalar':
					return is_scalar($var);
					break;
				default:
					return false;
					break;
			}
		}
		return true;
	}
	
	/**
	 * Ensure that a var is not null
	 *
	 * @return bool
	 */
	public function _isNotNull(): bool
	{
		return !$this->isNull();
	}

	/**
	 * Check if a var is null
	 *
	 * @return bool
	 */
	public function _isNull( ): bool
	{
		return is_null( $this->_data );
	}

	/**
	 * Check if a var doesn't have an empty value
	 *
	 * @return bool
	 */
	public function _isNotEmpty( ): bool
	{
		return !$this->isEmpty( );
	}

	/**
	 * Check if a var has an empty value
	 *
	 * @return bool
	 */
	public function _isEmpty( ): bool
	{
		return empty($this->_data) && !is_numeric( $this->_data );
	}

	/**
	 * Check if a var is falsy
	 *
	 * @return bool
	 */
	public function _isFalsy(): bool
	{
		return !$this->isTruthy();
	}

	/**
	 * Check if a var is truthy
	 *
	 * @return bool
	 */
	public function _isTruthy(): bool
	{
		return (bool)$this->_data;
	}

	/**
	 * Add a constraint to the value of the var
	 * 
	 * @param  callable $callable The function to be called on the valu
	 * @return IVal
	 */
	public function _constraint( $callable, $priority = 10 ): IVal
	{
		$this->_constraints[$priority] = $this->_constraints[$priority] ?? [];
		$this->_constraints[$priority][] = $callable;
		ksort($this->_constraints);
		
		return $this;
	}

	/**
	 * Set or return the value of the var
	 *
	 * @param mixed $value
	 *
	 * @return mixed The value of the data member `_data`
	 */
	public function val($value = null): mixed
	{
		// if ( Val::isNotNull($value) ) {
		if ( !is_null($value) ) {
    		if (Val::isValid($value)) {
    			throw new \Exception("Value is not a valid type '{$this->_type}'", 1);
    		}
    		$this->alter($value);

    		return $this;
		} else {
			// Always return a constrained value
			$value = $this->_data;
			foreach ($this->_constraints as $constraint) {
				foreach ($constraint as $callable) {
					call_user_func_array($callable, [&$value]);
				}
			}
		}

		return $value;
	}

	/**
	 * pass the value as a reference bound to $_data
	 *
	 * @param mixed $value
	 * @return IVAl
	 */
	public function ref(&$value): IVal
	{
		$this->alter($value);
		$this->_data = &$value;

		return $this;
	}

	/**
	 * Snapshot the value of the var
	 *
	 * @return IVal
	 */
	public function snapshot(): IVal
	{
		$this->_snapshot = $this->_data;

		return $this;
	}

	/**
	 * Clear the value of the snapshot
	 * 
	 */
	public function clearSnapshot(): IVal
	{
		$this->_snapshot = null;

		return $this;
	}

	/**
	 * Reset the value of the var to the snapshot
	 *
	 * @return IVal
	 */
	public function reset(): IVal
	{
		$this->_data = $this->_snapshot;

		return $this;
	}

	/**
	 * Get the change between the current value and the snapshot
	 *
	 * @return mixed
	 */
	public function delta()
	{
		return $this->_data - $this->_snapshot;
	}

	/**
	 * Magic method to get the value of the var
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get( $name ) {
		if ( 'value' === $name ) {
			return $this->val();
		}
	}

	/**
	 * Magic method to set the value of the var
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function __set( $name, $value ) {
		if ( 'value' === $name ) {
			$this->value($value);
		}
	}

	/**
	 * Set the local var to null
	 * 
	 * @return IVal
	 */
	public function clear(): IVal
	{
		$this->_data = null;

		return $this;
	}

	/**
	 * Alter the value of $_data
	 *
	 * @param mixed $value
	 * @return void
	 */
	protected function alter($value)
	{
		foreach ($this->_constraints as $constraint) {
			foreach ($constraint as $callable) {
				call_user_func($callable, $value);
			}
		}
		if ($this->_data != $value) {
			$this->dispatch(new Event(Event::CHANGE));
		}
		$this->_data = $value;
	}

	/**
	 * Magic method to call methods starting with _
	 *
	 * @param string $method
	 * @param array $args
	 * @return mixed
	 * @throws Exception
	 */
	public function __call( $method, $args )
	{
		if ( method_exists($this, self::PRIVATE_PREFIX.$method) ) {
			$output = call_user_func_array(array($this, self::PRIVATE_PREFIX.$method), $args);
			// if ($output instanceof Val) {
			// 	$output = $output->val();
			// }
			return $output;
		} else {
			// throw new Exception("Method {$method} not defined", 1);
			error_log("Method {$method} not defined in class " . get_class($this));
			return false;
		}
	}

	/**
	 * Magic method to call object as a function to access value directly
	 * 
	 * @param mixed $value
	 * @return mixed
	 */
    public function __invoke($value = null) 
    {
    	if ( !is_null($value) ) {
			$this->val($value);
		}

		$clone = clone $this;
		
		return $clone->cast()->val();
    }

	/**
	* Magic method for calling non-existent methods as static methods.
	* If a method exists starting with '_', it will be called with the first argument as the value
	* @param string $method
	* @param array $args
	* @throws Exception
	* @return mixed
	*/
	public static function __callStatic( $method, $args )
	{
		if ( method_exists(get_called_class(), self::PRIVATE_PREFIX.$method) ) {
			$class = get_called_class();
			$value = array_shift( $args );
			$object = new $class( $value, false, false );
			$output = call_user_func_array(array($object, self::PRIVATE_PREFIX.$method), $args);
			unset($object);
			if ($output instanceof IVal) {
				$output = $output->val();
			}
			return $output;
		} else {
			// throw new Exception("Method {$method} not defined", 1);
			error_log("Method {$method} not defined in class " . get_called_class());
			return false;
		}
	}

	public function __destroy()
	{
		$this->dispatch(new Event(Event::UNLOAD));
	}
}