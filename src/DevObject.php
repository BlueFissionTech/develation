<?php
namespace BlueFission;

use BlueFission\IDevValue;
use BlueFission\DevValue;
use BlueFission\DevArray;
use BlueFission\DevValueFactory as Factory;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;

use BlueFission\Behavioral\Dispatches;

class DevObject implements IDevObject
{
    use Dispatches {
        Dispatches::__construct as private __dispatchConstruct;
    }

    /**
     * @var DevArray
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
     * DevObject constructor.
     */
    public function __construct() {
        $this->__dispatchConstruct();

        if ( !DevValue::is($this->_data) ) {
            $this->_data = new DevArray;
        } elseif ( DevArray::is($this->_data) ) {
            $this->_data = new DevArray($this->_data);
        }

        foreach ( $this->_types as $field=>$type ) {
            $item = Factory::make($type, $this->_data[$field] ?? null);
            $item->behavior(new Event( Event::CHANGE ), function($behavior, $args) {
                $this->_data->dispatch($behavior, $args);
            });

            $this->_data[$field] = $item;
        }
        
        if ( !DevValue::is($this->_type) ) {
            $this->_type = get_class( $this );
        }

        $this->_data->behavior(new Event( Event::CHANGE ), [$this, 'onDataChange']);
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
        if ( DevValue::isNotEmpty($value) ) {
            if ( $this->_lockDataType 
                && isset( $this->_data[$field] )
                && $this->_data[$field] instanceof IDevValue ) {
                if ( $this->_data[$field]->isValid($value) ) {
                    $this->_data[$field]->value($value);
                } else {
                    throw new \Exception("Invalid value for field $field");
                }
            } elseif (isset( $this->_data[$field] )
                && $this->_data[$field] instanceof IDevValue
                && $this->_data[$field]->isValid($value) ) {
                $this->_data[$field]->value($value);
            } else {
                $this->_data[$field] = $value;
            }
        } else {
            $value = $this->_data[$field] ?? null;
            if ( $value instanceof IDevValue && $this->_exposeValueObject == false ) {
                $value = $value->value();
            }
        }
        return $value;
    }

    /**
     * add field constraints to the object members
     * @param  callable $callable a function to run on the value before setting
     * @return IDevObject
     */
    public function constraint( $callable ): IDevObject
    {
        $this->_data->contraint( $callable );

        return $this;
    }

    public function exposeValueObject( bool $expose = true )
    {
        $this->_exposeValueObject = $expose;
    }

    /**
     * clear all the data of the object
     * @return void
     */
    public function clear(): void
    {
        array_walk($this->_data, function(&$value, $key) { 
            if ( $value instanceof IDevValue ) {
                $value->clear();
            } else {
                $value = null;
                $this->_data[$key] = $value;
            }
        });
    }

    /**
     * Assign values to fields in this object.
     *
     * @param  object|array  $data  The data to import into this object.
     * @throws InvalidArgumentException  If the data is not an object or associative array.
     */
    public function assign( $data )
    {
        if ( is_object( $data ) || DevArray::isAssoc( $data ) ) {
            $this->perform( State::BUSY );
            foreach ( $data as $a=>$b ) {
                $this->field($a, $b);
            }
            $this->halt( State::BUSY );
            $this->dispatch( Event::CHANGE );
        }
        else
            throw new \InvalidArgumentException( "Can't import from variable type " . gettype($data) );
    }

    /**
     * event handler for data changes
     *
     * @param  Event $behavior
     * @return void
     */
    public function onDataChange($behavior): void
    {
        $this->dispatch($behavior);
    }

    /**
     * Method to expose IDevValue members when called as methods
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call( $method, $args )
    {
        if ( method_exists($this, $method) ) {
            return call_user_func_array([$this, $method], $args);
        } elseif ( DevArray::hasKey($this->_data, $method) ) {
            $output = call_user_func_array(function() use ( $method ) {
                return $this->_data[$method];
            }, $args);
            
            return $output;
        } else {
            return false;
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

    // public function __sleep()
    // {
    //  return array_keys( $this->_data );
    // }

    // public function __wakeup()
    // {
        
    // }

    /**
     * @return string
     */
    public function __toString(): string
    {
        // return get_class( $this );
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
            if ( $value instanceof IDevValue ) {
                $array[$key] = $value->value();
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