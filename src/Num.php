<?php

namespace BlueFission;

use BlueFission\Behavioral\Behaviors\Event;

/**
 * Class Num
 *
 * Num class that extends the Val class and implements the IVal interface.
 * It is used to handle numbers and provide additional functionality, such as checking if a value is valid, 
 * calculating percentages, and automatically casting values to int or double as needed.
 *
 */
class Num extends Val implements IVal {
    /**
     * @var string $type The type of number, "integer" or "double"
     */
    protected $type = DataTypes::DOUBLE;

    /**
	 * @var string $format The format of the number
	 */
    protected $format = "";

    /**
     * @var string $decimals The decimals of the number
     */
    protected $precision = 2;

    /**
	 * @var string $decimal The decimal separator
	 */
    protected $decimal = ".";

    /**
     * @var string $thousands The thousands separator
     */
    protected $thousands = ",";

    /**
     * Num constructor.
     *
     * @param mixed|null $value The value to set, if any
     */
    public function __construct( $value = null, bool $takeSnapshot = true, bool $cast = false  ) {

        $this->data = $value;
        if ( $this->type && ($this->forceType == true || $cast) ) {
            $clone = $this->data;
            settype($clone, $this->type->value);
            $remainder = $clone % 1;
            $this->type = $remainder ? $this->type : DataTypes::INTEGER;
            settype($this->data, $this->type->value);
        }

		parent::__construct($value, $takeSnapshot);
    }

    /**
	 * Convert the value to the type of the var
	 *
	 * @return IVal
	 */
	public function cast(): IVal
	{
		if ( $this->type ) {
            $clone = $this->data;
            settype($clone, $this->type->value);
            $remainder = $clone % 1;
            $this->type = $remainder ? $this->type : DataTypes::INTEGER;
            settype($this->data, $this->type->value);
			$this->trigger(Event::CHANGE);

        }

		return $this;
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
        $number = $this->data;
        return (is_numeric($number) && ((Val::isNotEmpty($number) && $number != 0) || $allowZero));
    }    

	/**
	 * Sets string formatting for the output of the number
	 *
	 * @param string $format The format to use
	 *
	 * @return IVal
	 */
	public function _format(string $format): IVal 
	{
		$this->format = $format;

		return $this;
	}

	/**
	 * Sets the number of decimals to use
	 *
	 * @param int $precision The number of decimals to use
	 *
	 * @return IVal
	 */

	public function _precision(int $precision): IVal 
	{
		$this->precision = $precision;

		return $this;
	}

	/**
	 * Sets the decimal separator
	 *
	 * @param string $decimal The decimal separator to use
	 *
	 * @return IVal
	 */

	public function _decimal(string $decimal): IVal
	{
		$this->decimal = $decimal;

		return $this;
	}

	/**
	 * Sets the thousands separator
	 *
	 * @param string $thousands The thousands separator to use
	 *
	 * @return IVal
	 */
	public function _thousands(string $thousands): IVal
	{
		$this->thousands = $thousands;

		return $this;
	}
    
    /**
     * Adds the numbers to the current value
     *
     * @param mixed $value The value to add
     *
     * @return IVal
     */
    public function _add(): IVal
    {
    	$values = func_get_args();
		$number = $this->data;
		if (!Num::isValid($number)) $number = 0;

		foreach ($values as $value) {
			if (!Num::isValid($value)) $value = 0;
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
	 * @return IVal
	 */
	public function _sub(): IVal
	{
		$values = func_get_args();
		$number = $this->data;
		if (!Num::isValid($number)) $number = 0;

		foreach ($values as $value) {
			if (!Num::isValid($value)) $value = 0;
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
	 * @return IVal
	 */
	public function _multiply(): IVal
	{
		$values = func_get_args();
		$number = $this->data;
		if (!Num::isValid($number)) $number = 0;

		foreach ($values as $value) {
			if (!Num::isValid($value)) $value = 0;
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
	 * @return IVal
	 */
	public function _divide(): IVal
	{
		$values = func_get_args();
		$number = $this->data;
		if (!Num::isValid($number)) $number = 0;

		foreach ($values as $value) {
			if (!Num::isValid($value)) $value = 0;
			if ($value != 0) {
				$number /= $value;
			}
		}

		$this->alter($number);

		return $this;
	}

	/**
	 * Get a random number
	 *
	 * @param int $min The minimum value
	 * @param int|null $max The maximum value
	 *
	 * @return IVal
	 */
	public function _rand( $min = 0, $max = null ): IVal
	{
		if ( $max === null ) {
			$max = $this->data;
		}

		$number = mt_rand($min, $max);

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
        $whole = $this->data;

        if (!Num::isValid($part)) $part = 0;
        if (!Num::isValid($whole)) $whole = 1;

        $ratio = $whole/($part * 100);

        return $ratio*(($percent) ? 100 : 1);
    }

    /**
     * Round the number to a specified number of decimal places
     *
     * @param int $precision The number of decimal places to round to
     *
     * @return IVal
     */
    public function _round(int $precision = 0): IVal
    {
        $value = round($this->data, $precision);

        $this->alter($value);

        return $this;
    }

    /**
     * Get the absolute value of the number
     *
     * @return IVal
     */
    public function _abs(): IVal
    {
        $value = abs($this->data);

        $this->alter($value);

        return $this;
    }

    /**
     * Get the square of the number
     *
     * @return IVal
     */
    public function _sq(): IVal
    {
        $value = $this->pow(2)->val();

        $this->alter($value);

        return $this;
    }

 	/**
 	 * Increase the value of the number by $power
 	 *
 	 * @param int $power The power to raise the number to
 	 *
 	 * @return IVal
 	 */
 	public function _pow($power): IVal
 	{
        $value = pow($this->data, $power);

        $this->alter($value);

        return $this;
    }

    /**
     * Get the square root of the number
     *
     * @return IVal
     */
    public function _sqrt(): IVal 
    {
        $value = sqrt($this->data);

        $this->alter($value);

        return $this;
    }

    /**
     * Get the logarithm of the number in a specified base
     *
     * @param float $base The base of the logarithm
     *
     * @return IVal
     */
    public function _log(float $base = M_E): IVal
    {
        $value = log($this->data, $base);

        $this->alter($value);

        return $this;
    }

    /**
     * Get the exponential of the number
     *
     * @return IVal
     */
    public function _exp(): IVal
    {
        $value = exp($this->data);

        $this->alter($value);

        return $this;
    }

    /**
     * Set or return the decimal representation of a number
     * 
     * @return mixed | Num
     */
    public function _dec(): mixed
    {
    	$values = func_get_args();
    	if ( Arr::count($values) ) {
    		$this->data = $values[0];
    		return $this;
    	}

    	return $this->data;
    }

    /**
	 * Set or return the binary representation of a number
	 * 
	 * @return string | Num
	 */
    public function _bin(): string|Num
    {
    	$values = func_get_args();
    	if ( Arr::count($values) ) {
			$this->data = bindec($values[0]);
			return $this;
		}

    	return decbin($this->data);
	}

	/**
	 * Set or return the hexadecimal representation of a number
	 * 
	 * @return string | Num
	 */
	public function _hex(): string|Num
	{
		$values = func_get_args();
		if ( Arr::count($values) ) {
			$this->data = hexdec($values[0]);
			return $this;
		}

		return dechex($this->data);
	}

	/**
	 * Set or return the octal representation of a number
	 * 
	 * @return string | Num
	 */
	public function _oct(): string|Num
	{
		$values = func_get_args();
		if ( Arr::count($values) ) {
			$this->data = octdec($values[0]);
			return $this;
		}

		return decoct($this->data);
	}

	/**
	 * Set or return the Roman numeral representation of a number
	 * 
	 * @return string | Num
	 */
	public function _rom(): string|Num
	{
	    $values = func_get_args();
	    $rules = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
	    
	    if (count($values)) {
	        // Convert Roman numeral to int
	        $roman = strtoupper($values[0]);
	        $result = 0;
	        foreach ($rules as $key => $value) {
	            while (strpos($roman, $key) === 0) {
	                $result += $value;
	                $roman = substr($roman, strlen($key));
	            }
	        }
	        $this->data = $result;
	        return $this;
	    } else {
	        // Convert int to Roman numeral
	        $number = (int)$this->data;
	        $result = '';
	        foreach ($rules as $key => $value) {
	            while ($number >= $value) {
	                $result .= $key;
	                $number -= $value;
	            }
	        }
	        return $result;
	    }
	}

    /**
     * Get the minimum of two numbers
     *
     * @param float $number The second number
     *
     * @return float The minimum of the two numbers
     */
    public function _min(float $number): float {
        return min($this->data, $number);
    }

    /**
     * Get the maximum of two numbers
     *
     * @param float $number The second number
     *
     * @return float The maximum of the two numbers
     */
    public function _max(float $number): float {
        return max($this->data, $number);
    }

    /**
     * Return int value of the $value
     *
     * @return int The int value of the $value
	 *
     */
    public function _int(): int {
		return (int)$this->data;
	}

	/**
	 * Increment value by one
	 *
	 * @return IVal
	 */
	public function _increment(): IVal
	{
		$number = $this->data;
		$number++;
		$this->alter($number);

		return $this;
	}

	/**
	 * Decrement value by one
	 *
	 * @return IVal
	 */
	public function _decrement(): IVal
	{
		$number = $this->data;
		$number--;
		$this->alter($number);

		return $this;
	}

	/**
	 * Returns the string representation of the class instance.
	 * @return string
	 */
	public function __toString(): string {
		if ( $this->format ) {
			$output = sprintf($this->format, $this->data);
		} elseif ($this->precision) {
			$output = number_format($this->data, $this->precision, $this->decimal, $this->thousands);
		} else {
			$output = (string)$this->data;
		}
		return $output;
	}
}