<?php
namespace BlueFission;

use Exception;

class DevValue implements IDevValue {
	protected $_data;
	protected $_type = "";

	const MORPHING_METHOD_PREFIX = '_';

	public function __construct( $value = null ) {
		$this->_data = $value;
		if ( $this->_type ) {
			settype($this->_data, $this->_type);
		}
	}
	///
	//Variable value functions
	///////
	// ensure that a var is not null
	public function _isNotNull() {
		// return (isset($this->_data) && $this->_data !== null && $this->_data != '');
		return !$this->isNull();
	}

	// check if a var is null
	public function _isNull( ) {
		// return !$this->isNotNull();
		return ( is_null($this->_data ) );
	}

	// check if a var has an empty value
	public function _isNotEmpty( ) {
		// return ( $this->isNotNull( $this->_data ) || is_numeric( $this->_data) );
		return !$this->isEmpty( );
	}

	// check if a var has an empty value
	public function _isEmpty( ) {
		// return !$this->isNotEmpty( $this->_data );
		return ( empty($this->_data) && !is_numeric( $this->_data ) );
	}

	public function _isFalsy() {
		return ( $this->_data == false || $this->_data == 0 || $this->_data == null );
	}

	public function _isTruthy() {
		return !$this->isFalsy();
	}

	public function value() {
		return $this->_data;
	}

	public function __call( $method, $args ) {
		if ( method_exists($this, self::MORPHING_METHOD_PREFIX.$method) ) {
			$output = call_user_func_array(array($this, self::MORPHING_METHOD_PREFIX.$method), $args);
			return $output;
		} else {
			throw new Exception("Method not defined", 1);			
		}
	}

	public static function __callStatic( $method, $args ) {
		if ( method_exists(get_called_class(), self::MORPHING_METHOD_PREFIX.$method) ) {
			$class = get_called_class();
			$value = array_shift( $args );
			$var = new $class( $value );
			$output = call_user_func_array(array($var, self::MORPHING_METHOD_PREFIX.$method), $args);
			unset($var);
			return $output;
		} else {
			throw new Exception("Method not defined", 1);			
		}
	}
}