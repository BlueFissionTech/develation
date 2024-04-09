<?php
namespace BlueFission;

use BlueFission\Val;
use BlueFission\Num;
use BlueFission\Arr;
use BlueFission\Behavioral\Behaviors\Event;
use \DateTime;

class Date extends Val implements IVal
{
	protected $_type = "";

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
     * Date constructor.
     *
     * @param mixed|null $value The value to set, if any
     */
    public function __construct( $value = null, $timezone = null ) {

        $this->_datetime = ($value instanceof DateTime) ? $value : new DateTime($value ?? 'now');
        $this->_timezone = $timezone ?? $this->_datetime->getTimezone()->getName();

		parent::__construct($value);

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

		// Register date Array changes as changes to Date object
        $this->_data->behavior(new Event( Event::CHANGE ), function($behavior) {
        	$this->_datetime = new DateTime( $this->timestamp() );
        	$this->_timezone = $this->_datetime->getTimezone()->getName();
        	$this->dispatch($event);
        });
    }

    public function value($value = null): mixed
    {
    	$value = parent::value($value);
    	return $this->_datetime->format($this->_format);
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
	    if ( is_null($data) ) {
	        $timestamp = mktime ((int)$this->_data['hour'], (int)$this->_data['minute'], (int)$this->_data['second'], (int)$this->_data['month'], (int)$this->_data['day'], (int)$this->_data['year']);
	    } elseif ( Num::is($data) ) {
	        $timestamp = $this->isValidTimestamp($data) ? $data : null;
	    } elseif ( $data instanceof DateTime ) {
			$timestamp = $data->getTimestamp();
	    } else {
	        $timestamp = strtotime($data) !== false ? strtotime($data) : null;
	    }

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
		$format = $this->_format;
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
			$time = date($this->_format, $timestamp);
		}

		return $time;
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
		return Date::difference($this->_snapshot, $this->_data);

		return $this;
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
		
		switch($arg_count) {
		default:
		case 0:
			// $timestamp = $this->timestamp();
			$timestamp = $this->timestamp;
			$format = $this->_format;
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
			$date = date($this->_format, $timestamp);
		}
		
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
	public function _difference($time2, $interval = null): float
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