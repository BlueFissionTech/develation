<?php

namespace BlueFission;

/**
 * Class DevNumber
 *
 * DevNumber class that extends the DevValue class and implements the IDevValue interface.
 * It is used to handle numbers and provide additional functionality, such as checking if a value is valid, 
 * calculating percentages, and automatically casting values to int or double as needed.
 *
 */
class DevNumber extends DevValue implements IDevValue {
    /**
     * @var string $_type The type of number, "integer" or "double"
     */
    protected $_type = "double";

    /**
	 * @var string $_format The format of the number
	 */
    protected $_format = "";

    /**
     * @var string $_decimals The decimals of the number
     */
    protected $_precision = 2;

    /**
	 * @var string $_decimal The decimal separator
	 */
    protected $_decimal = ".";

    /**
     * @var string $_thousands The thousands separator
     */
    protected $_thousands = ",";

    /**
     * DevNumber constructor.
     *
     * @param mixed|null $value The value to set, if any
     */
    public function __construct( $value = null ) {
		parent::__construct($value);

        $this->_data = $value;
        if ( $this->_type && $this->_forceType == true ) {
            $clone = $this->_data;
            settype($clone, $this->_type);
            $remainder = $clone % 1;
            $this->_type = $remainder ? $this->_type : "integer";
            settype($this->_data, $this->_type);
        }
    }

    /**
     * Check if the value is a valid number
     *
     * @param bool $allowZero Whether to allow zero values
     *
     * @return bool If the value is a valid number
     */
    public function _is(bool $allowZero = true): bool
    {
        $number = $this->_data;
        return (is_numeric($number) && ((DevValue::isNotEmpty($number) && $number != 0) || $allowZero));
    }    

	/**
	 * Sets string formatting for the output of the number
	 *
	 * @param string $format The format to use
	 *
	 * @return DevNumber
	 */
	public function _format(string $format): DevNumber 
	{
		$this->_format = $format;

		return $this;
	}

	/**
	 * Sets the number of decimals to use
	 *
	 * @param int $precision The number of decimals to use
	 *
	 * @return DevNumber
	 */

	public function _precision(int $precision): DevNumber 
	{
		$this->_precision = $precision;

		return $this;
	}

	/**
	 * Sets the decimal separator
	 *
	 * @param string $decimal The decimal separator to use
	 *
	 * @return DevNumber
	 */

	public function _decimal(string $decimal): DevNumber
	{
		$this->_decimal = $decimal;

		return $this;
	}

	/**
	 * Sets the thousands separator
	 *
	 * @param string $thousands The thousands separator to use
	 *
	 * @return DevNumber
	 */
	public function _thousands(string $thousands): DevNumber
	{
		$this->_thousands = $thousands;

		return $this;
	}
    
    /**
     * Adds the numbers to the current value
     *
     * @param mixed $value The value to add
     *
     * @return DevNumber
     */
    public function _add(): DevNumber
    {
    	$values = func_get_args();
		$number = $this->_data;
		if (!DevNumber::isValid($number)) $number = 0;

		foreach ($values as $value) {
			if (!DevNumber::isValid($value)) $value = 0;
			$number += $value;
		}

		$this->alter($number);

		return $this;
	}

	/**
	 * Subtracts the numbers from the current value
	 *
	 * @param mixed $value The value to subtract
	 *
	 * @return DevNumber
	 */
	public function _subtract(): DevNumber
	{
		$values = func_get_args();
		$number = $this->_data;
		if (!DevNumber::isValid($number)) $number = 0;

		foreach ($values as $value) {
			if (!DevNumber::isValid($value)) $value = 0;
			$number -= $value;
		}

		$this->alter($number);

		return $this;
	}

	/**
	 * Multiplies the numbers to the current value
	 *
	 * @param mixed $value The value to multiply
	 *
	 * @return DevNumber
	 */
	public function _multiply(): DevNumber
	{
		$values = func_get_args();
		$number = $this->_data;
		if (!DevNumber::isValid($number)) $number = 0;

		foreach ($values as $value) {
			if (!DevNumber::isValid($value)) $value = 0;
			$number *= $value;
		}

		$this->alter($number);

		return $this;
	}

	/**
	 * Divides the numbers to the current value
	 *
	 * @param mixed $value The value to divide
	 *
	 * @return DevNumber
	 */
	public function _divide(): DevNumber
	{
		$values = func_get_args();
		$number = $this->_data;
		if (!DevNumber::isValid($number)) $number = 0;

		foreach ($values as $value) {
			if (!DevNumber::isValid($value)) $value = 0;
			if ($value != 0) {
				$number /= $value;
			}
		}

		$this->alter($number);

		return $this;
	}

    /**
     * Calculate the ratio between two values
     *
     * @param mixed $part The part of the whole
     * @param bool $percent Whether to return the percentage or the raw ratio
     *
     * @return float The ratio between two values
     */
    public function _percentage(float $part = 0, bool $percent = false): float
    {
        $whole = $this->_data;
        if (!DevNumber::isValid($part)) $part = 0;
        if (!DevNumber::isValid($whole)) $whole = 1;

        $ratio = $whole/($part * 100);

        return $ratio*(($percent) ? 100 : 1);
    }

    /**
     * Round the number to a specified number of decimal places
     *
     * @param int $precision The number of decimal places to round to
     *
     * @return DevNumber
     */
    public function _round(int $precision = 0): DevNumber
    {
        $value = round($this->_data, $precision);

        $this->alter($value);

        return $this;
    }

    /**
     * Get the absolute value of the number
     *
     * @return DevNumber
     */
    public function _abs(): DevNumber
    {
        $value = abs($this->_data);

        $this->alter($value);

        return $this;
    }

    /**
     * Get the square of the number
     *
     * @return DevNumber
     */
    public function _square(): DevNumber
    {
        $value = $this->pow(2)->value();

        $this->alter($value);

        return $this;
    }

 	/**
 	 * Increase the value of the number by $power
 	 *
 	 * @param int $power The power to raise the number to
 	 *
 	 * @return DevNumber
 	 */
 	public function _pow($power): DevNumber
 	{
        $value = pow($this->_data, $power);

        $this->alter($value);

        return $this;
    }

    /**
     * Get the square root of the number
     *
     * @return DevNumber
     */
    public function _squareRoot(): DevNumber 
    {
        $value = sqrt($this->_data);

        $this->alter($value);

        return $this;
    }

    /**
     * Get the logarithm of the number in a specified base
     *
     * @param float $base The base of the logarithm
     *
     * @return DevNumber
     */
    public function _log(float $base = M_E): DevNumber
    {
        $value = log($this->_data, $base);

        $this->alter($value);

        return $this;
    }

    /**
     * Get the exponential of the number
     *
     * @return DevNumber
     */
    public function _exp(): DevNumber
    {
        $value = exp($this->_data);

        $this->alter($value);

        return $this;
    }

    /**
     * Get the minimum of two numbers
     *
     * @param float $number The second number
     *
     * @return float The minimum of the two numbers
     */
    public function _min(float $number): float {
        return min($this->_data, $number);
    }

    /**
     * Get the maximum of two numbers
     *
     * @param float $number The second number
     *
     * @return float The maximum of the two numbers
     */
    public function _max(float $number): float {
        return max($this->_data, $number);
    }

    /**
     * Return int value of the $_value
     *
     * @return int The int value of the $_value
	 *
     */
    public function _int(): int {
		return (int)$this->_data;
	}

	/**
	 * Increment value by one
	 *
	 * @return DevNumber
	 */
	public function _increment(): DevNumber
	{
		$number = $this->_data;
		$number++;
		$this->alter($number);

		return $this;
	}

	/**
	 * Decrement value by one
	 *
	 * @return DevNumber
	 */
	public function _decrement(): DevNumber
	{
		$number = $this->_data;
		$number--;
		$this->alter($number);

		return $this;
	}

	/**
	 * Returns the string representation of the class instance.
	 * @return string
	 */
	public function __toString(): string {
		if ( $this->_format ) {
			$output = sprintf($this->_format, $this->_data);
		} elseif ($this->_precision) {
			$output = number_format($this->_data, $this->_precision, $this->_decimal, $this->_thousands);
		} else {
			$output = (string)$this->_data;
		}
		return $output;
	}
}