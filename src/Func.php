<?php

namespace BlueFission;

use BlueFission\Behavioral\Behaviors\Event;
use Closure;
use Exception;

class Func extends Val implements IVal
{
    /**
     *
     * @var string $type is used to store the data type of the object
     */
    protected $_type = DataTypes::CALLABLE;

    /**
 * Constructor that accepts any callable. If it's not callable and casting is enabled,
 * attempts to convert it to a Closure.
 *
 * @param mixed $value The value to store (expected to be callable)
 * @param bool $snapshot Whether to track the original value
 * @param bool $cast Whether to cast non-callables into closures
 */
    public function __construct($value = null, $snapshot = true, $cast = false)
    {
        $value = is_callable($value, true) ? $value : ((($cast || $this->_forceType) && !is_null($value)) ? \Closure::fromCallable($value) : $value);
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
        if ($this->_type) {
            if (!is_callable($this->_data)) {
                $this->_data = function () { return null; };
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
    public function bind($object, $scope = null): IVal
    {
        $this->_data = $this->_data->bindTo($object, $scope);

        return $this;
    }
    /**
     * Returns an array of ReflectionParameter objects for the callback.
     *
     * @return array<int, \ReflectionParameter>
     */
    public function expects(): array
    {
        if (is_string($this->_data) || is_array($this->_data)) {
            $this->_data = \Closure::fromCallable($this->_data);
        }

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

        if (!is_callable($this->_data)) {
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



    /**
     * Allows the object to be called like a function.
     *
     * @param mixed ...$args Arguments to pass to the stored callable
     * @return mixed
     */
    public function __invoke($value = null)
    {
        $args = func_get_args();
        return $this->call(...$args);
    }
}
