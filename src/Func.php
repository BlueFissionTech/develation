<?php

namespace BlueFission;

use BlueFission\Behavioral\Behaviors\Event;

class Func extends Val implements IVal {
	/**
	 *
	 * @var string $type is used to store the data type of the object
	 */
	protected $_type = DataTypes::CALLBACK;

	/**
	 * Constructor to initialize value of the class
	 *
	 * @param mixed $value
	 */
	public function __construct( $value = null, $snapshot = true, $cast = false ) {
		$value = is_callable( $value ) ? $value : ( ( ( $cast || $this->_forceType ) && !is_null($value)) ? Closure::fromCallable($value) : $value );
		parent::__construct($value);
	}

	/**
	 * Convert the value to the type of the var
	 *
	 * @return IVal
	 */
	public function cast(): IVal
	{
		// force $_data to be a callback
		if ( $this->_type ) {
			if ( !is_callable($this->_data) ) {
				$this->_data = function() { return null; };
			}
		} else {
			$this->_data = Closure::fromCallable($this->_data);
		}
		$this->trigger(Event::CHANGE);

		return $this;
	}

	/**
	 * Binds the callback to an object
	 * @param  object $object the object to bind to
	 * @return IVal         the func object
	 */
	public function bind( $object ): IVal
	{
		$this->_data = $this->_data->bindTo($object);

		return $this;
	}

	/**
	 * Returns a list of the arguments that the callback expects
	 * @return array the list of arguments
	 */
	public function expects(): array
	{
		try {
			$reflector = new \ReflectionFunction($this->_data);
			return $reflector->getParameters();
		} catch (Exception $e) {
			return [];
		}
	}

	/**
	 * Returns the expected return results of the callback
	 * @return mixed the return types of the callback
	 */
	public function returns(): mixed
	{
		try {
			$reflector = new \ReflectionFunction($this->_data);
			return $reflector->getReturnType();
		} catch (Exception $e) {
			return null;
		}
	}

	public function call()
	{
		$args = func_get_args();
		
		if ( !is_callable($this->_data) ) {
			$this->trigger(Event::EXCEPTION);
			throw new \Exception("The value is not callable");
		}
		return call_user_func_array($this->_data, $args);
	}

	public function __invoke( $value = null )
	{
		$args = func_get_args();
		return $this->call(...$args);
	}
}