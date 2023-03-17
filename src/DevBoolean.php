<?php
namespace BlueFission;

/**
* DevBoolean class extends DevValue and implements IDevValue
* This class is used to handle boolean values
*/
class DevBoolean extends DevValue implements IDevValue {
	
	/**
	* @var string $_type The type of the value stored in the object, in this case "boolean"
	*/
	protected $_type = "boolean";

	/**
	* Constructor for DevBoolean class
	*
	* @param mixed $value The value that needs to be stored in the object
	*
	* @return void
	*/
	public function __construct( $value = null ) {
		$value = $value ? true : false;
		parent::__construct( $value );
	}

	/**
	* _opposite function returns the opposite value of a boolean variable
	*
	* @return bool The opposite value of the boolean stored in the object
	*/
	public function _opposite() {
		$bool = $this->_data;
	    return (!($bool === true));
	}
}
