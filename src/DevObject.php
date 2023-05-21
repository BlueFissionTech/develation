<?php
namespace BlueFission;

use BlueFission\Behavioral\Dispatcher;

class DevObject extends Dispatcher implements IDevObject
{
    /**
     * @var array
     */
    protected $_data;

    /**
     * @var string
     */
    protected $_type;

    /**
     * DevObject constructor.
     */
    public function __construct() {
        if (!isset($this->_data))
            $this->_data = [];
        
        if (!$this->_type)
            $this->_type = get_class( $this );
    }

    /**
     * @param string $field
     * @param mixed|null $value
     * @return mixed|null
     */
    public function field(string $field, $value = null) {
        if ( DevValue::isNotEmpty($value) ) {
            $this->_data[$field] = $value;
        } else {
            $value = $this->_data[$field] ?? null;
        }
        return $value;
    }

    /**
     * clear all the data of the object
     */
    public function clear()
    {
        array_walk($this->_data, function(&$value, $key) { 
			$value = null; 
		});
    }
    
    /**
     * @param string $field
     * @return mixed|null
     */
    public function __get($field)
    {
        return $this->field($field);
    }

    /**
     * @param string $field
     * @param mixed $value
     */
    public function __set($field, $value)
    {
        $this->field($field, $value);
    }

    /**
     * @param string $field
     * @return bool
     */
    public function __isset( $field )
    {
        return isset ( $this->_data[$field] );
    }

    /**
     * @param string $field
     */
    public function __unset( $field )
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
    public function __toString()
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
        return $this->_data;
    }

    /**
     * Convert the object data into a JSON string
     * 
     * @return string The object data as a JSON string
     */
    public function toJson(): string
    {
        return json_encode($this->_data);
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