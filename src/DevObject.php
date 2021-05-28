<?php
namespace BlueFission;

class DevObject implements IDevObject
{
	protected $_data;
	protected $_type;

	public function __construct() {
		if (!isset($this->_data))
			$this->_data = [];
		
		if (!$this->_type)
			$this->_type = get_class( $this );
	}

	public function field($field, $value = null) {
		if ( DevValue::isNotEmpty($value) ) {
			$this->_data[$field] = $value;
		} else {
			$value = $this->_data[$field] ?? null;
		}
		return $value;
	}

	public function clear()
	{
		array_walk($this->_data, function(&$value, $key) { 
			$value = null; 
		});
	}
	
	public function __get($field)
	{
		return $this->field($field);
	}

	public function __set($field, $value)
	{
		$this->field($field, $value);
	}

	public function __isset( $field )
	{
		return isset ( $this->_data[$field] );
	}

	public function __unset( $field )
	{
		unset ( $this->_data[$field] );
	}

	// public function __sleep()
	// {
	// 	return array_keys( $this->_data );
	// }

	// public function __wakeup()
	// {
		
	// }

	public function __toString()
	{
		// return get_class( $this );
		return $this->_type;
	}	
}