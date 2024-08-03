<?php

namespace BlueFission;

use BlueFission\IVal;
use BlueFission\Val;
use BlueFission\Arr;
use BlueFission\ValFactory as Factory;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;

use BlueFission\Behavioral\Behaves;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Behavioral\IBehavioral;

class Obj implements IObj, IDispatcher, IBehavioral
{
    use Behaves {
        Behaves::__construct as private __behavesConstruct;
    }

    /**
     * @var Arr
     */
    protected $_data;

    /**
     * @var array
     */
    protected $_types = [];

    /**
     * @var string
     */
    protected $_type;

    /**
     * @var bool
     */
    protected $_exposeValueObject = false;

    /**
     * @var bool
     */
    protected $_lockDataType = false;

    /**
     * Obj constructor.
     */
    public function __construct() {
        $this->__behavesConstruct();

        if ( !Val::is($this->_data) ) {
            $this->_data = new Arr();
        } elseif ( Arr::is($this->_data) ) {
            $this->_data = Arr::use();
        }

        foreach ( $this->_types as $field=>$type ) {
            $item = Factory::make($type, $this->_data[$field] ?? null);
            
            $this->_data[$field] = $item;
            $this->_data->echo($item, [Event::CHANGE]);
        }
        
        if ( !Val::is($this->_type) ) {
            $this->_type = get_class( $this );
        }

        $this->echo($this->_data, [Event::CHANGE]);
        $this->trigger(Event::LOAD);
    }

    /**
     * Sets one of the object fields by name
     * 
     * @param string $field
     * @param mixed|null $value
     * @return mixed|null
     */
    public function field(string $field, $value = null): mixed
    {
        if ( Val::isNotEmpty($value) ) {
            if ( $this->_lockDataType 
                && isset( $this->_data[$field] )
                && $this->_data[$field] instanceof IVal ) {
                if ( $this->_data[$field]->isValid($value) ) {
                    $this->_data[$field]->val($value);
                } else {
                    $this->trigger(Event::EXCEPTION);
                    throw new \Exception("Invalid value for field $field");
                }
            } elseif (isset( $this->_data[$field] )
                && $this->_data[$field] instanceof IVal
                && $this->_data[$field]->isValid($value) ) {
                $this->_data[$field]->val($value);
            } else {
                $this->_data[$field] = $value;
            }

            return $this;
        } else {
            $value = $this->_data[$field] ?? null;
            if ( $value instanceof IVal && $this->_exposeValueObject == false ) {
                $value = $value->val();
            }
        }
        return $value;
    }

    /**
     * add field constraints to the object members
     * @param  callable $callable a function to run on the value before setting
     * @return IObj
     */
    public function constraint( callable $callable ): IObj
    {
        $this->_data->contraint( $callable );

        return $this;
    }

    /**
     * Sets whether the value object should be returned as the value or the object
     * 
     * @param  bool $expose
     * @return IObj
     */
    public function exposeValueObject( bool $expose = true ): IObj
    {
        $this->_exposeValueObject = $expose;

        return $this;
    }

    /**
     * clear all the data of the object
     * @return IObj
     */
    public function clear(): IObj
    {
        foreach ( $this->_data as $key => &$value ) {
            if ( $value instanceof IVal ) {
                $value->clear();
            } else {
                $value = null;
                $this->_data[$key] = $value;
            }
        };

        $this->trigger(Event::CLEAR_DATA);
        return $this;
    }

    /**
     * Assign values to fields in this object.
     *
     * @param  object|array  $data  The data to import into this object.
     * @return IObj
     * @throws InvalidArgumentException  If the data is not an object or associative array.
     */
    public function assign( $data ): IObj
    {
        if ( is_object( $data ) || Arr::isAssoc( $data ) ) {
            $this->dispatch( State::BUSY );
            foreach ( $data as $a=>$b ) {
                $this->field($a, $b);
            }
            $this->halt( State::BUSY );
        }
        else
            throw new \InvalidArgumentException( "Can't import from variable type " . gettype($data) );

        return $this;
    }

    /**
     * Method to expose IVal members when called as methods
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call( $method, $args )
    {
        if ( method_exists($this, $method) ) {
            $this->trigger(Event::ACTION_PERFORMED);

            return call_user_func_array([$this, $method], $args);
        } elseif ( Arr::hasKey($this->_data, $method) ) {
            $output = call_user_func_array(function() use ( $method ) {
                return $this->_data[$method];
            }, $args);
            
            $this->trigger(Event::ACTION_PERFORMED);

            return $output;
        } else {
            $this->trigger([Event::ACTION_FAILED, Event::EXCEPTION]);
            throw new \Exception("Method $method does not exist");
        }
    }
    
    /**
     * @param string $field
     * @return mixed|null
     */
    public function __get($field): mixed
    {
        return $this->field($field);
    }

    /**
     * @param string $field
     * @param mixed $value
     *
     * @return void
     */
    public function __set($field, $value): void
    {
        $this->field($field, $value);
    }

    /**
     * @param string $field
     * @return bool
     */
    public function __isset( $field ): bool
    {
        return isset ( $this->_data[$field] );
    }

    /**
     * @param string $field
     * @return void
     */
    public function __unset( $field ): void
    {
        unset ( $this->_data[$field] );
    }

    public function __sleep()
    {
        return ['_data', '_types', '_type', '_exposeValueObject', '_lockDataType'];
    }

    public function __wakeup()
    {
        $this->__construct();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->_type;
    }  

     /**
     * Convert the object data into an array
     * 
     * @return array The object data as an array
     */
    public function toArray(): array
    {
        $array = $this->_data->toArray();
        foreach ( $array as $key => $value ) {
            if ( $value instanceof IVal ) {
                $array[$key] = $value->val();
            }
        }
        return $array;
    }

    /**
     * Convert the object data into a JSON string
     * 
     * @return string The object data as a JSON string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Serialize the object data
     * 
     * @return string The serialized object data
     */
    public function serialize(): string
    {
        return serialize($this->_data);
    }

    /**
     * Unserialize the object data
     * 
     * @param string $data The serialized object data
     * @return void
     */
    public function unserialize($data): void
    {
        $this->_data = unserialize($data);
    }
}