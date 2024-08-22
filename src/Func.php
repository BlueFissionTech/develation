<?php

namespace BlueFission;

use BlueFission\Behavioral\Behaviors\Event;

class Func extends Val implements IVal {
	/**
	 *
	 * @var string $type is used to store the data type of the object
	 */
	protected $type = DataTypes::CALLABLE;

	/**
	 * Constructor to initialize value of the class
	 *
	 * @param mixed $value
	 */
	public function __construct( $value = null, $snapshot = true, $cast = false ) {
		$value = is_callable( $value, true ) ? $value : ( ( ( $cast || $this->forceType ) && !is_null($value)) ? \Closure::fromCallable($value) : $value );
		parent::__construct($value);
	}

	/**
	 * Returns if the function is callable
	 * @return boolean
	 */
	public function _isCallable(): bool
	{
		return is_callable($this->data);
	}

	/**
	 * Convert the value to the type of the var
	 *
	 * @return IVal
	 */
	public function cast(): IVal
	{
		// force $data to be a callback
		if ( $this->type ) {
			if ( !is_callable($this->data) ) {
				$this->data = function() { return null; };
			}
		} else {
			$this->data = Closure::fromCallable($this->data);
		}
		$this->trigger(Event::CHANGE);

		return $this;
	}

	/**
	 * Binds the callback to an object
	 * @param  object $object the object to bind to
	 * @return IVal         the func object
	 */
	public function bind( $object, $scope = null ): IVal
	{
		$this->data = $this->data->bindTo($object, $scope);

		return $this;
	}

	/**
	 * Returns a list of the arguments that the callback expects
	 * @return array the list of arguments
	 */
	public function expects(): array
	{
		if ( is_string($this->data) || is_array($this->data) ) {
			$this->data = \Closure::fromCallable($this->data);
		}

		try {
			$reflector = new \ReflectionFunction($this->data);
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
			$reflector = new \ReflectionFunction($this->data);
			return $reflector->getReturnType();
		} catch (Exception $e) {
			return null;
		}
	}

	public function call()
	{
		$args = func_get_args();
		
		if ( !is_callable($this->data) ) {
			$this->trigger(Event::EXCEPTION);
			throw new \Exception("The value is not callable");
		}

		// make sure arguments that are pass by reference are honored
		$ref_args = [];
		foreach ($this->expects() as $i => $param) {
			$ref_args[$i] = &$args[$i];
		}

		// call the function
		return call_user_func_array($this->data, $ref_args);
	}

	public function __invoke( $value = null )
	{
		$args = func_get_args();
		return $this->call(...$args);
	}
}