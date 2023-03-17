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
     * @var string $_type The type of number, "int" or "double"
     */
    protected $_type = "double";

    /**
     * DevNumber constructor.
     *
     * @param mixed|null $value The value to set, if any
     */
    public function __construct( $value = null ) {
        $this->_data = $value;
        if ( $this->_type ) {
            $clone = $this->_data;
            settype($clone, $this->_type);
            $remainder = $clone % 1;
            $this->_type = $remainder ? $this->_type : "int";
            settype($this->_data, $this->_type);
        }
    }

    /**
     * Check if the value is a valid number
     *
     * @param bool $allow_zero Whether to allow zero values
     *
     * @return bool If the value is a valid number
     */
    public function _isValid(bool $allow_zero = true) {
        $number = $this->_data;
        return (is_numeric($number) && ((DevValue::isNotEmpty($number) && $number != 0) || $allow_zero)): bool;
    }

    /**
     * Calculate the ratio between two values
     *
     * @param mixed $part The part of the whole
     * @param bool $percent Whether to return the percentage or the raw ratio
     *
     * @return float The ratio between two values
     */
    public function _percentage(float $part = 0, bool $percent = false): float {
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
     * @return float The rounded number
     */
    public function round(int $precision = 0): float {
        return round($this->_data, $precision);
    }

    /**
     * Get the absolute value of the number
     *
     * @return float The absolute value of the number
     */
    public function abs(): float {
        return abs($this->_data);
    }

    /**
     * Get the square of the number
     *
     * @return float The square of the number
     */
    public function square(): float {
        return pow($this->_data, 2);
    }

    /**
     * Get the square root of the number
     *
     * @return float The square root of the number
     */
    public function squareRoot(): float {
        return sqrt($this->_data);
    }

    /**
     * Get the logarithm of the number in a specified base
     *
     * @param float $base The base of the logarithm
     *
     * @return float The logarithm of the number in the specified base
     */
    public function log(float $base = M_E): float {
        return log($this->_data, $base);
    }

    /**
     * Get the exponential of the number
     *
     * @return float The exponential of the number
     */
    public function exp(): float {
        return exp($this->_data);
    }

    /**
     * Get the minimum of two numbers
     *
     * @param float $number The second number
     *
     * @return float The minimum of the two numbers
     */
    public function min(float $number): float {
        return min($this->_data, $number);
    }

    /**
     * Get the maximum of two numbers
     *
     * @param float $number The second number
     *
     * @return float The maximum of the two numbers
     */
    public function max(float $number): float {
        return max($this->_data, $number);
    }
}