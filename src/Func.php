<?php

namespace BlueFission;

use BlueFission\Behavioral\Behaviors\Event;

/**
 * Func
 *
 * Callable value object with optional signature metadata and support for
 * runtime-bound closures. It exposes reflection-friendly helper methods around
 * existing callables rather than acting as a full compiler.
 */
class Func extends Val implements IVal {
	/**
	 *
	 * @var string $type is used to store the data type of the object
	 */
	protected $_type = DataTypes::CALLABLE;

	protected array $_signature = [];
	protected $_returnType = null;
	protected $_compiled = null;

	/**
	 * Constructor to initialize value of the class
	 *
	 * @param mixed $value
	 */
	public function __construct( $value = null, $snapshot = true, $cast = false ) {
		$value = is_callable( $value, true ) ? $value : ( ( ( $cast || $this->_forceType ) && !is_null($value)) ? \Closure::fromCallable($value) : $value );
		parent::__construct($value);
	}

	/**
	 * Returns if the function is callable
	 * @return boolean
	 */
	public function _isCallable(): bool
	{
		return is_callable($this->_data);
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
	 * Define the expected parameter signature metadata.
	 *
	 * @param array<int, string|array<string, mixed>> $params
	 * @return self
	 */
	public function signature(array $params): self
	{
	    $this->_signature = array_map(function($param) {
	        return is_array($param) ? $param : ['name' => $param];
	    }, $params);

	    return $this;
	}

	/**
	 * Define the expected return type metadata.
	 *
	 * @param string $type
	 * @return self
	 */
	public function type(string $type): self
	{
	    $this->_returnType = $type;
	    return $this;
	}

    /**
     * Set the callable body from a closure or trusted string body.
     *
     * @param callable|string $logic
     * @return self
     */
    public function body(callable|string $logic): self
    {
        if (is_callable($logic)) {
            $this->_data = \Closure::fromCallable($logic);
        } elseif (is_string($logic)) {
            // Create closure from string-based body using eval (only for trusted input).
            $paramList = implode(', ', array_map(function ($param) {
                $name = (string) ($param['name'] ?? '');

                if (Str::startsWith($name, '$')) {
                    return $name;
                }

                return '$' . $name;
            }, $this->_signature));
            $return = $this->_returnType ? ": {$this->_returnType}" : '';
            $code = "return function($paramList)$return { $logic };";

	        $this->_data = eval($code);
	    }

	    return $this;
	}

	/**
	 * Restore a previously compiled callable if one has been stored.
	 *
	 * @return self
	 */
	public function compile(): self
	{
	    if (!$this->_data && $this->_compiled) {
	        $this->_data = $this->_compiled;
	    }
	    return $this;
	}

	/**
	 * Return the explicit parameter metadata if one was provided.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function parameters(): array
	{
	    return $this->_signature;
	}

	/**
	 * Binds the callback to an object
	 * @param  object $object the object to bind to
	 * @return IVal         the func object
	 */
	public function bind( $object, $scope = null ): IVal
	{
		$this->_data = $this->_data->bindTo($object, $scope);

		return $this;
	}

	/**
	 * Returns a list of the arguments that the callback expects
	 * @return array the list of arguments
	 */
	public function expects(): array
	{
		if ($this->_signature) {
	        return $this->_signature;
	    }

		if (is_callable($this->_data)) {
			$reflector = null;
			if (is_array($this->_data)) {
				$reflector = new \ReflectionMethod($this->_data[0], $this->_data[1]);
			} elseif (is_string($this->_data) && Str::pos($this->_data, '::') !== false) {
				$reflector = new \ReflectionMethod($this->_data);
			} else {
	        	$reflector = new \ReflectionFunction($this->_data);
			}
	        return array_map(fn($p) => ['name' => $p->getName(), 'type' => (string)$p->getType()], $reflector->getParameters());
	    }

	    return [];
	}

	/**
	 * Returns the expected return results of the callback
	 * @return mixed the return types of the callback
	 */
	public function returns(): mixed
	{
		if ($this->_returnType) return $this->_returnType;

	    if (is_callable($this->_data)) {
			$reflector = null;
			if (is_array($this->_data)) {
				$reflector = new \ReflectionMethod($this->_data[0], $this->_data[1]);
			} elseif (is_string($this->_data) && Str::pos($this->_data, '::') !== false) {
				$reflector = new \ReflectionMethod($this->_data);
			} else {
	        	$reflector = new \ReflectionFunction($this->_data);
			}
	        return (string)$reflector->getReturnType();
	    }

	    return null;
	}

	/**
	 * Invoke the callable with argument forwarding and reference preservation.
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function call()
	{
		$args = func_get_args();
		
		if ( !is_callable($this->_data) ) {
			$this->trigger(Event::EXCEPTION);
			throw new \Exception("The value is not callable");
		}

		// make sure arguments that are pass by reference are honored
		$ref_args = [];
		foreach ($this->expects() as $i => $param) {
			$ref_args[$i] = &$args[$i];
		}

		// call the function
		return call_user_func_array($this->_data, $ref_args);
	}

	public function __invoke( $value = null )
	{
		$args = func_get_args();
		return $this->call(...$args);
	}
}
