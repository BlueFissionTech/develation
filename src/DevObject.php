<?php
namespace BlueFission;

use BlueFission\DevArray as Array;
use BlueFission\DevValue as Value;
use BlueFission\IDevValue;

use BlueFission\Behavioral\Behaviors\Event;

use BlueFission\Behavioral\Dispatcher;

class DevObject extends Dispatcher implements IDevObject
{
    /**
     * @var DevArray
     */
    protected $_data;

    /**
     * @var string
     */
    protected $_type;

    /**
     * @var bool
     */
    protected $_exposeValueObject = true;

    /**
     * DevObject constructor.
     */
    public function __construct() {
        parent::__construct();

        if ( !Value::is($this->_data) ) {
            $this->_data = new Array;
        }
        
        if ( !Value::is($this->_type) ) {
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
    public function field(string $field, $value = null): mixed|null
    {
        if ( Value::isNotEmpty($value) ) {
            if (isset( $this->_data[$field] 
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
            }
		});
    }

    /**
     * event handler for data changes
     *
     * @param  Event $event
     * @return void
     */
    public function onDataChange($behavior): void
    {
        $this->dispatch($behavior);
    }
    
    /**
     * @param string $field
     * @return mixed|null
     */
    public function __get($field): mixed|null
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