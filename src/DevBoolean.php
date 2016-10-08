<?php
namespace BlueFission;

class DevBoolean extends DevValue implements IDevValue {
	
	protected $_type = "boolean";

	public function __construct( $value = null ) {
		$value = $value ? true : false;
		parent::__construct( $value );
	}

	// return the opposite value of a boolean variable
	public function _opposite() {
		$bool = $this->_value;
	    return (!($bool === true));
	}
}