<?php

namespace BlueFission;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Dispatches;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Collections\Collection;
use Exception;

/**
 * The Val class is meant to be inherited.
 */
class Val implements IVal, IDispatcher {
	use Dispatches {
        Dispatches::__construct as private __tConstruct;
    }

	/**
	 * @var mixed $data
	 */
	protected $data;

	/**
	 * @var $constraints
	 */
	protected $constraints = [];

	/**
	 * Capture the value of the var at a specific time
	 * @var null
	 */
	protected $snapshot = null;

	/**
	 * @var string $forceType
	 */
	protected $forceType = false;

	/**
	 * @var string $type
	 */
	protected $type = DataTypes::GENERIC;

	private static $instances = null;

	private static $last = null;

	private static $slots = [];

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

		$this->data = $value;
		if ( $this->type && $this->forceType || $cast ) {
			settype($this->data, $this->type->value);
		}

		if ( $takeSnapshot ) {
			$this->snapshot();
		}

		$this->trigger(Event::LOAD);
	}

	/**
	 * Check the value of the var against multiple functions
	 *
	 * @return bool
	 */
	public function _check($functions = null): bool
	{
		$functions = is_array($functions) && !is_callable($functions, true) ? $functions : [$functions];

		$valid = false;

		foreach ($functions as $function) {
			if ( is_string($function) && method_exists($this, $function) ) {
				$valid = $this->$function();
			} elseif ( is_string($function) && method_exists($this, '_'.$function) ) {
				$valid = $this->{'_'.$function}();
			} elseif ( is_string($function) && function_exists($function) ) {
				$valid = $function($this->data);
			} elseif ( is_callable($function) ) {
				$valid = call_user_func($function, $this->data);
			} else {
				$valid = false;
			}

			if ( !$valid ) {
				break;
			}
		}

		return $valid;
	}

	/**
	 * Convert the value to the type of the var
	 *
	 * @return IVal
	 */
	public function cast(): IVal
	{
		try {
			if ( $this->type && DataTypes::GENERIC !== $this->type ) {
				settype($this->data, $this->type->value);
				$this->trigger(Event::CHANGE);
			}
		} catch (Exception $e) {
			$this->trigger(Event::ERROR);
			error_log("Can't cast value to type '{$this->type->value}'");
		}

		return $this;
	}

	/**
	 * Get the datatype name of the object
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type->value;
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
	 * Create a new instance of the class
	 * @param  mixed $value The value to set as the data member
	 * @return IVal        a new instance of the class
	 */
	public static function grab(): mixed
	{
		$value = self::$last;

		return $value;
	}

	/**
	 * Use the last instance of the class
	 * @return IVal
	 */
	public static function use(): IVal
	{
		$value = self::grab();

		return self::make($value);
	}

	/**
	 * Slot a function and bind it into the object
	 * @param string $name The name of the slot
	 * @param callable $callable The function to be slotted
	 * @return IVal returns the object
	 */
	public static function slot(string $name, callable $callable): IVal
	{
		$this->slots[$name] = $callable->bindTo($this, $this);
	}

	/**
	 * Tag the object with a group to be tracked by the object class
	 * @param string $group The group to tag the object with
	 * @return IVal
	 */
	public function tag($group = null): IVal
	{
		$tag = $this->getType();
		if ( $group ) {
			$tag = $group . '.' . $tag;
		}

		if ( !self::$instances ) {
			self::$instances = new Collection();
		}

		if ( !isset(self::$instances[$tag]) ) {
			self::$instances[$tag] = new Collection();
		}

		self::$instances[$tag]->addDistinct($this);

		return $this;
	}

	/**
	 * Untag the object from the group
	 * @param string $group The group to untag the object from
	 * @return IVal
	 */
	public function untag($group = null): IVal
	{
		$tag = $this->getType();
		if ( $group ) {
			$tag = $group . '.' . $tag;
		}

		if ( !self::$instances ) {
			self::$instances = new Collection();
		}

		if ( isset(self::$instances[$tag]) ) {
			$key = self::$instances[$tag]->search($this);
			self::$instances[$tag]->remove($key);
		}

		return $this;
	}

	/**
	 * Get the group of objects tagged with the specified group
	 * @param string $group The group to get the objects from=
	 * @return Collection
	 */
	public static function grp($group = null): Collection
	{
		$class = get_called_class();
		$object = new $class();

		$tag = $object->getType() ?? 'generic';
		unset($object);

		if ( $group ) {
			$tag = $group . '.' . $tag;
		}

		if ( !self::$instances ) {
			self::$instances = new Collection();
		}

		if ( !isset(self::$instances[$tag]) ) {
			self::$instances[$tag] = new Collection();
		}

		return self::$instances[$tag];
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
		return isset($this->data);
	}
	
	/**
	 * Check if var is a valid instance of $type
	 *
	 * @return bool
	 */
	public function _isValid( $value = null ): bool
	{
		$var = $value ?? $this->data;
		if ( $this->type ) {
			switch ($this->type) {
				case DataTypes::GENERIC: // redundant catch for no type set
					return true;
					break;
				case DataTypes::STRING:
					return is_string($var);
					break;
				case DataTypes::NUMBER:
				case DataTypes::DOUBLE:
					// validates that value is numeric including zero
					return is_numeric($var);
					break;
				case DataTypes::INTEGER:
					return is_int($var);
					break;
				case DataTypes::FLOAT:
					return is_float($var);
					break;
				case DataTypes::BOOLEAN:
				case 'bool':
					return is_bool($var);
					break;
				case DataTypes::ARRAY:
					return is_array($var);
					break;
				case DataTypes::OBJECT:
					return is_object($var);
					break;
				case DataTypes::RESOURCE:
					return is_resource($var);
					break;
				case DataTypes::NULL:
					return is_null($var);
					break;
				case DataTypes::SCALAR:
					return is_scalar($var);
					break;
				case DataTypes::CALLBACK:
					return is_callback($var);
					break;
				case DataTypes::DATETIME:
					return $var instanceof DateTime || strtotime($var) !== false;
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
		return is_null( $this->data );
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
		return empty($this->data) && !is_numeric( $this->data );
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
	 * Execute a callback function if the stored value is true
	 *
	 * @param callable $callback The callback function to execute
	 *
	 * @return IVal The instance of the Flag class
	 */
	public function then( $callback ) {
		if ( $this->data ) {
			$callback( $this );
		}

		return $this;
	}

	/**
	 * Execute a callback function if the stored value is false
	 *
	 * @param callable $callback The callback function to execute
	 *
	 * @return IVal The instance of the Flag class
	 */
	public function otherwise( $callback ) {
		if ( !$this->data ) {
			$callback( $this );
		}

		return $this;
	}

	/**
	 * Combine the stored boolean value with another boolean value using the AND operator
	 *
	 * @param mixed $value The boolean value to combine with the stored value
	 *
	 * @return IVal The instance of the Flag class
	 */
	public function and( $value ) {
		if ( $value instanceof IVal ) {
			$value = $value->val();
		}

		$this->data = $this->data && $value;
		return $this;
	}

	/**
	 * Combine the stored boolean value with another boolean value using the OR operator
	 *
	 * @param mixed $value The boolean value to combine with the stored value
	 *
	 * @return IVal The instance of the Flag class
	 */
	public function or( $value ) {
		if ( $value instanceof IVal ) {
			$value = $value->val();
		}

		$this->data = $this->data || $value;
		return $this;
	}

	/**
	 * Negate the stored boolean value
	 *
	 * @return IVal The instance of the Flag class
	 */
	public function not() {
		$this->data = !$this->data;
		return $this;
	}

	/**
	 * Combine the stored boolean value with another boolean value using the XOR operator
	 *
	 * @param mixed $value The boolean value to combine with the stored value
	 *
	 * @return IVal The instance of the Flag class
	 */
	public function xor( $value ) {
		if ( $value instanceof IVal ) {
			$value = $value->val();
		}

		$this->data = $this->data xor $value;
		return $this;
	}

	/**
	 * Combine the stored boolean value with another boolean value using the NOR operator
	 *
	 * @param mixed $value The boolean value to combine with the stored value
	 *
	 * @return IVal The instance of the Flag class
	 */
	public function nor( $value ) {
		if ( $value instanceof IVal ) {
			$value = $value->val();
		}

		$this->data = !($this->data || $value);
		return $this;
	}

	/**
	 * Check if a var is truthy
	 *
	 * @return bool
	 */
	public function _isTruthy(): bool
	{
		return (bool)$this->data;
	}

	/**
	 * Add a constraint to the value of the var
	 * 
	 * @param  callable $callable The function to be called on the valu
	 * @return IVal
	 */
	public function _constraint( $callable, $priority = 10 ): IVal
	{
		$this->constraints[$priority] = $this->constraints[$priority] ?? [];
		$this->constraints[$priority][] = $callable;
		ksort($this->constraints);
		
		return $this;
	}

	/**
	 * Set or return the value of the var
	 *
	 * @param mixed $value
	 *
	 * @return mixed The value of the data member `data`
	 */
	public function val($value = null): mixed
	{
		// if ( Val::isNotNull($value) ) {
		if ( !is_null($value) ) {
    		if (!Val::isValid($value)) {
    			$this->trigger(Event::EXCEPTION);
    			throw new \Exception("Value is not a valid type '{$this->type->value}'", 1);
    		}
    		$this->alter($value);

    		return $this;
		} else {
			// Always return a constrained value
			$value = $this->data;
			foreach ($this->constraints as $constraint) {
				foreach ($constraint as $callable) {
					call_user_func_array($callable, [&$value]);
				}
			}
		}

		return $value;
	}

	/**
	 * pass the value as a reference bound to $data
	 *
	 * @param mixed $value
	 * @return IVal
	 */
	public function ref(&$value): IVal
	{
		$this->alter($value);

		$value = $this->data;

		$this->data = &$value;

		return $this;
	}

	/**
	 * Snapshot the value of the var
	 *
	 * @return IVal
	 */
	public function snapshot(): IVal
	{
		$this->snapshot = $this->data;

		return $this;
	}

	/**
	 * Clear the value of the snapshot
	 * 
	 */
	public function clearSnapshot(): IVal
	{
		$this->snapshot = null;

		return $this;
	}

	/**
	 * Reset the value of the var to the snapshot
	 *
	 * @return IVal
	 */
	public function reset(): IVal
	{
		$this->data = $this->snapshot;
		$this->trigger(Event::CHANGE);

		return $this;
	}

	/**
	 * Get the change between the current value and the snapshot
	 *
	 * @return mixed
	 */
	public function delta()
	{
		return $this->data - $this->snapshot;
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
			$this->val($value);
		}
	}

	/**
	 * Set the local var to null
	 * 
	 * @return IVal
	 */
	public function clear(): IVal
	{
		$this->data = null;
		$this->trigger([Event::CLEAR_DATA, Event::CHANGE]);

		return $this;
	}

	/**
	 * Alter the value of $data
	 *
	 * @param mixed $value
	 * @return void
	 */
	protected function alter($value)
	{
		foreach ($this->constraints as $constraint) {
			foreach ($constraint as $callable) {
				call_user_func_array($callable, [&$value]);
			}
		}
		if ($this->data != $value) {
			$this->trigger(Event::CHANGE);
		}
		$this->data = $value;
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
			$output = call_user_func_array([$this, self::PRIVATE_PREFIX.$method], $args);

			$this->trigger(Event::ACTION_PERFORMED);
			
			return $output;
		} else {
			if ( in_array($method, $this->slots) ) {
				$this->trigger(Event::ACTION_PERFORMED);
				return call_user_func_array($this->slots[$method], $args);
			}

			// throw new Exception("Method {$method} not defined", 1);
			error_log("Method {$method} not defined in class " . get_class($this));
			$this->trigger([Event::ACTION_FAILED, Event::ERROR]);

			return null;
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
		$class = get_called_class();
		
		if ( method_exists($class, self::PRIVATE_PREFIX.$method) ) {
			$value = array_shift( $args );

			self::$last = $value;
			
			$object = new $class( $value, false, false );
			$output = call_user_func_array([$object, self::PRIVATE_PREFIX.$method], $args);
			unset($object);
			if ($output instanceof IVal) {
				$output = $output->val();
			}
			return $output;
		} 

		// throw new Exception("Method {$method} not defined", 1);
		error_log("Method {$method} not defined in class " . get_called_class());
		return false;
	}

	public function __destroy()
	{
		$this->dispatch(new Event(Event::UNLOAD));
	}
}