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

	/**
	 * Check if the boolean value is true
	 *
	 * @return bool If the boolean value is true
	 */
	public function _isTrue(): bool {
	    return $this->_data === true;
	}

	/**
	 * Check if the boolean value is false
	 *
	 * @return bool If the boolean value is false
	 */
	public function _isFalse(): bool {
	    return $this->_data === false;
	}

	/**
	 * Returns the boolean representation of a given value
	 *
	 * @param mixed $value The value to cast to a boolean
	 *
	 * @return bool The boolean representation of the given value
	 */
	public static function _toBoolean($value): bool {
	    return (bool) $value;
	}
	/**
	 * Check if the stored value is empty
	 *
	 * @return bool True if the stored value is empty, false otherwise
	 */
	public function _isEmpty(): bool {
	    return !isset($this->_data);
	}

	/**
	 * Convert the stored boolean value to an integer
	 *
	 * @return int The stored boolean value as an integer (1 for true, 0 for false)
	 */
	public function _toInt(): int {
	    return (int) $this->_data;
	}

	/**
	 * Convert the stored boolean value to a string
	 *
	 * @return string The stored boolean value as a string ("true" for true, "false" for false)
	 */
	public function _toString(): string {
	    return $this->_data ? "true" : "false";
	}
}
