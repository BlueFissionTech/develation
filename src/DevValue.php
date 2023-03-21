<?php
namespace BlueFission;

use Exception;

/**
 * The DevValue class is meant to be inherited.
 */
class DevValue implements IDevValue {
	/**
	 * @var mixed $_data
	 */
	protected $_data;

	/**
	 * @var string $_type
	 */
	protected $_type = "";

	const PRIVATE_PREFIX = '_';

	/**
	 * Constructor to initialize value of the class
	 *
	 * @param mixed $value
	 */
	public function __construct( $value = null ) {
		$this->_data = $value;
		if ( $this->_type ) {
			settype($this->_data, $this->_type);
		}
	}
	///
	//Variable value functions
	///////
	
	/**
	 * Ensure that a var is not null
	 *
	 * @return boolean
	 */
	public function _isNotNull() {
		// return (isset($this->_data) && $this->_data !== null && $this->_data != '');
		return !$this->isNull();
	}

	/**
	 * Check if a var is null
	 *
	 * @return boolean
	 */
	public function _isNull( ) {
		// return !$this->isNotNull();
		return ( is_null($this->_data ) );
	}

	/**
	 * Check if a var doesn't have an empty value
	 *
	 * @return boolean
	 */
	public function _isNotEmpty( ) {
		// return ( $this->isNotNull( $this->_data ) || is_numeric( $this->_data) );
		return !$this->isEmpty( );
	}

	/**
	 * Check if a var has an empty value
	 *
	 * @return boolean
	 */
	public function _isEmpty( ) {
		// return !$this->isNotEmpty( $this->_data );
		return ( empty($this->_data) && !is_numeric( $this->_data ) );
	}

	/**
	 * Check if a var is falsy
	 *
	 * @return boolean
	 */
	public function _isFalsy() {
		return !$this->isTruthy();
		// return ( $this->_data == false || $this->_data == 0 || $this->_data == null );
	}

	/**
	 * Check if a var is truthy
	 *
	 * @return boolean
	 */
	public function _isTruthy() {
		return (bool)$this->_data;
		// return !$this->isFalsy();
	}

	/**
	 * Return the value of the var
	 *
	 * @return mixed The value of the data member `_data`
	 */
	public function value() {
		return $this->_data;
	}

	/**
	 * Magic method to call methods starting with _
	 *
	 * @param string $method
	 * @param array $args
	 * @return mixed
	 * @throws Exception
	 */
	public function __call( $method, $args ) {
		if ( method_exists($this, self::PRIVATE_PREFIX.$method) ) {
			$output = call_user_func_array(array($this, self::PRIVATE_PREFIX.$method), $args);
			return $output;
		} else {
			// throw new Exception("Method {$method} not defined", 1);
			error_log("Method {$method} not defined in class " . get_class($self));
			return false;
		}
	}

	/**
	* Magic method for calling non-existent methods as static methods.
	* If a method exists starting with '_', it will be called with the first argument as the value
	* @param string $method
	* @param array $args
	* @throws Exception
	* @return mixed
	*/
	public static function __callStatic( $method, $args ) {
		if ( method_exists(get_called_class(), self::PRIVATE_PREFIX.$method) ) {
			$class = get_called_class();
			$value = array_shift( $args );
			$var = new $class( $value );
			$output = call_user_func_array(array($var, self::PRIVATE_PREFIX.$method), $args);
			unset($var);
			return $output;
		} else {
			// throw new Exception("Method {$method} not defined", 1);
			error_log("Method {$method} not defined in class " . get_called_class());
			return false;
		}
	}
}