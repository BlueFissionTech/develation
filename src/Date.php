<?php
namespace BlueFission;

use BlueFission\Val;
use BlueFission\Num;
use BlueFission\Arr;
use BlueFission\DevElation as Dev;
use BlueFission\Behavioral\Behaviors\Event;
use \DateTime;

class Date extends Val implements IVal
{
	protected $_type = DataTypes::DATETIME;

    /**
	 * @var string $_format The format of the date
	 */
    protected $_format = "c"; //"Y-m-d H:i:s";

    /**
	 * @var string $_timezone The timezone of the date
	 */
    protected $_timezone = "UTC";

    /**
	 * @var DateTime $_datetime the DateTime object containing the date and managing most date operations
	 */
    protected $_datetime;

    /**
     * @var mixed $_value The value of the date
     * @var null
     */
    protected $_value = null;

	/**
     * Date constructor.
     *
     * @param mixed|null $value The value to set, if any
     */
    public function __construct( $value = null, $timezone = null ) {
		parent::__construct($value);

    	$this->_value = $this->_data;

 		$this->setValue($value, $timezone);
		// Register date Array changes as changes to Date object
        $this->_data->behavior(new Event( Event::CHANGE ), function($behavior) {
        	$this->_datetime = new DateTime( $this->timestamp() );
        	$this->_timezone = $this->_datetime->getTimezone()->getName();
        	$this->_value = $this->val();
        });
        $this->echo($this->_data, [Event::CHANGE]);
    }

    private function setValue($value = null, $timezone = null): void
    {
    	if ( isset($value) && $this->isValidTimestamp($value) ) {
    		$value = date('Y-m-d H:i:s', (int)$value);
		}

        $this->_datetime = ($value instanceof DateTime) ? $value : new DateTime($value ?? 'now');
        $this->_timezone = $timezone ?? $this->_datetime->getTimezone()->getName();

        $this->_data = new Arr([
	        'second'=>$this->_datetime->format('s'), 
	        'minute'=>$this->_datetime->format('i'), 
	        'hour'=>$this->_datetime->format('G'), 
	        'day'=>$this->_datetime->format('j'), 
	        'month'=>$this->_datetime->format('n'), 
	        'year'=>$this->_datetime->format('Y'), 
	        'timezone'=>$this->_datetime->format('e'), 
	        'offset'=>$this->_datetime->format('Z')
	    ]);
    }

    public function __get($name) {
    	if ($name === 'datetime') {
    		return $this->_datetime;
    	}
    }

    public function val($value = null): mixed
    {
    	if ( $value ) {

    		parent::val($value);

   	    	$this->_value = $this->_data;

    		$this->setValue($value);

    		return $this;
    	}

    	$this->setValue($this->_value);

    	return $this->_datetime->format($this->_format);
    }

    public function cast(): IVal
	{
		if ($this->val()) {
			$this->_value = $this->val();
			$this->trigger(Event::CHANGE);
		}

		return $this;
	}

    public function clear(): IVAl
	{
		parent::clear();

		$this->_value = 0;

		// Set a default time of the beginning of the epoch
		$this->setValue($this->_value);

		return $this;
	}

	public function ref(&$value): IVal
	{
		$this->alter($value);

    	$this->_value = &$value;
		$this->_value = $this->val();

		$this->setValue($this->_value);

		return $this;
	}

	public function snapshot(): IVal
	{
		$this->_snapshot = $this->_value;

		return $this;
	}

	public function reset(): IVal
	{
		$this->_value = $this->_snapshot ?? 0;

		$this->setValue($this->_value);

		return $this;

	}

	/**
     * Checks is value is a date as a DateTime, parseable date string, or valid unix timestamp
     *
     * @param mixed $value
     * 
     * @return bool
     */
    public function _is( ): bool {
    	return ( $this->isValidTimestamp($this->timestamp()) );
	}

	/**
	 * Checks if a value is a valid unix timestamp
	 * 
	 * @param  int  $timestamp the proposed unix timestamp
	 * @return bool 		 true if the value is a valid unix timestamp
	 */
	private function isValidTimestamp($timestamp): bool {

	    return is_numeric($timestamp)
	        && ($timestamp <= PHP_INT_MAX)
	        && ($timestamp >= ~PHP_INT_MAX)
	        && ((string) (int) $timestamp === (string) $timestamp)
	        && ($timestamp <= strtotime('+100 year'))  // Any timestamps too far in the future may be invalid
	        && (date('U', $timestamp) === (string)$timestamp);
	}

	/**
	 * Returns the timestamp value of the date and time represented by the current instance
	 * @param $data - optional timestamp value, if passed, it will set the timestamp of the current instance
	 * @return int|null - timestamp value
	 */
	public function _timestamp( $data = null ): int|null
	{
		$data = Dev::apply('_in', $data);

	    if ( is_null($data) ) {
	        $timestamp = mktime ((int)$this->_data['hour'], (int)$this->_data['minute'], (int)$this->_data['second'], (int)$this->_data['month'], (int)$this->_data['day'], (int)$this->_data['year']);
	    } elseif ( Num::is($data) ) {
	        $timestamp = $this->isValidTimestamp($data) ? $data : null;
	    } elseif ( $data instanceof DateTime ) {
			$timestamp = $data->getTimestamp();
	    } else {
	        $timestamp = strtotime($data) !== false ? strtotime($data) : null;
	    }

	    $timestamp = Dev::apply('_out', $timestamp);

	    return $timestamp;
	}

	/**
	 * Get the time
	 * 
	 *
	 * @param int|null $hours Hours to set
	 * @param int|null $minutes Minutes to set
	 * @param int|null $seconds Seconds to set
	 * @return string The formatted time
	 */
	public function time(): string
	{
		$arg_count = func_num_args();
		$format = 'H:i:s';
		$time = null;
		
		switch($arg_count)
		{
		default:
		case 0:
			$timestamp = $this->timestamp();
			$time = date($format, $timestamp);
		break;
		case 1:
			$timestamp = $this->timestamp(func_get_arg(0));
		break;
		case 2:
			$timestamp = mktime (func_get_arg(0), func_get_arg(1), 0, $this->_data['month'], $this->_data['day'], $this->_data['year']);
		break;
		case 3:
			$timestamp = mktime (func_get_arg(0), func_get_arg(1), func_get_arg(2), $this->_data['month'], $this->_data['day'], $this->_data['year']);
		break;
		}

		if ( Val::isNull($time) ) {
			$time = date($format, $timestamp);
		}

		$time = Dev::apply('_out', $time);

		return $time;
	}

	public static function now()
	{
		return new Date();
	}

	/**
	 * set the format for the date
	 *
	 * @param string|null $format The format to set
	 * @return IVal | string The format
	 */
	public function format( string $format = null ): IVal | string
	{
		if ( Val::isNull($format) ) {
			return $this->_format;
		}

		$format = Dev::apply('_in', $format);

		$this->_format = $format;
		
		return $this;
	}

	/**
	 * Get the change between the current value and the snapshot
	 *
	 * @return mixed
	 */
	public function delta()
	{
		return Date::diff($this->_snapshot, $this->_data);
	}

	/**
	 * Get the date
	 *
	 * @param string|null $date Date string in the format specified in config
	 * @param int|null $month Month to set
	 * @param int|null $day Day to set
	 * @return string The formatted date
	 */
	public function date(): string
	{
		$arg_count = func_num_args();
		$date = null;
		
		$format = 'Y-m-d';

		switch($arg_count) {
		default:
		case 0:
			$timestamp = $this->timestamp();

			$date = date($format, $timestamp);
			break;
		case 1:
			if ( version_compare(PHP_VERSION, '5.3.0', '>=') ) {
				$timestamp = $this->timestamp(func_get_arg(0));
			} else {
				$timestamp = strtotime(func_get_arg(0));
			}
			break;
		case 3:
			if ( version_compare(PHP_VERSION, '5.2.0', '>=') ) {
				$date = DateTime::setDate(func_get_arg(0), func_get_arg(1), func_get_arg(2));
				$timestamp = $date->getTimestamp();
			} else {
				$timestamp = $timestamp = mktime (null, null, null, func_get_arg(0), func_get_arg(1), func_get_arg(2));
			}
			break;
		}
		

		if ( Val::isNull($date) ) {
			$date = date($format, $timestamp);
		}

		$date = Dev::apply('_out', $date);
		
		return $date;
	}

	/**
	 * Calculates the difference between two times
	 *
	 * @param string $time1 The first time
	 * @param string $time2 The second time
	 * @param string $interval The interval to measure the difference in, defaults to 'seconds'
	 *
	 * @return float The difference between the two times
	 */
	public function _diff($time2, $interval = null): float
	{
		if (Val::isNull($interval)) $interval = 'seconds';
		$a = $this->timestamp();
		$b = $this->timestamp($time2);
		$difference = (($a > $b) ? ($a - $b) : ($b - $a));
		
		$div = 1;
		switch ($interval) {
		case 'years':
			$div *= 12;
		case 'months':
			$div *= 4;
		case 'weeks':
			$div *= 30;
		case 'days':
			$div *= 24;
		case 'hours':
			$div *= 60;
		case 'minutes':
			$div *= 60;
		default:
		case 'seconds':
			$div *= 1;
			break;
		}
		
		$output = ($difference / $div);

		$output = Dev::apply('_out', $output);

		return $output;
	}

	/**
	 * Returns the string representation of the class instance.
	 * @return string
	 */
	public function __toString(): string {
		return $this->val();
	}
}