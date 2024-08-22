<?php
namespace BlueFission;

use BlueFission\Val;
use BlueFission\Num;
use BlueFission\Arr;
use BlueFission\Behavioral\Behaviors\Event;
use \DateTime;

class Date extends Val implements IVal
{
	protected $type = DataTypes::DATETIME;

    /**
	 * @var string $format The format of the date
	 */
    protected $format = "c"; //"Y-m-d H:i:s";

    /**
	 * @var string $timezone The timezone of the date
	 */
    protected $timezone = "UTC";

    /**
	 * @var DateTime $dateTime the DateTime object containing the date and managing most date operations
	 */
    protected $dateTime;

    /**
     * @var mixed $value The value of the date
     * @var null
     */
    protected $value = null;

	/**
     * Date constructor.
     *
     * @param mixed|null $value The value to set, if any
     */
    public function __construct( $value = null, $timezone = null ) {
		parent::__construct($value);

    	$this->value = $this->data;

 		$this->setValue($value, $timezone);
		// Register date Array changes as changes to Date object
        $this->data->behavior(new Event( Event::CHANGE ), function($behavior) {
        	$this->dateTime = new DateTime( $this->timestamp() );
        	$this->timezone = $this->dateTime->getTimezone()->getName();
        	$this->value = $this->val();
        });
        $this->echo($this->data, [Event::CHANGE]);
    }

    private function setValue($value = null, $timezone = null): void
    {
    	if ( isset($value) && $this->isValidTimestamp($value) ) {
    		$value = date('Y-m-d H:i:s', (int)$value);
		}

        $this->dateTime = ($value instanceof DateTime) ? $value : new DateTime($value ?? 'now');
        $this->timezone = $timezone ?? $this->dateTime->getTimezone()->getName();

        $this->data = new Arr([
	        'second'=>$this->dateTime->format('s'), 
	        'minute'=>$this->dateTime->format('i'), 
	        'hour'=>$this->dateTime->format('G'), 
	        'day'=>$this->dateTime->format('j'), 
	        'month'=>$this->dateTime->format('n'), 
	        'year'=>$this->dateTime->format('Y'), 
	        'timezone'=>$this->dateTime->format('e'), 
	        'offset'=>$this->dateTime->format('Z')
	    ]);
    }

    public function __get($name) {
    	if ($name === 'datetime') {
    		return $this->dateTime;
    	}
    }

    public function val($value = null): mixed
    {
    	if ( $value ) {

    		parent::val($value);

   	    	$this->value = $this->data;

    		$this->setValue($value);

    		return $this;
    	}

    	$this->setValue($this->value);

    	return $this->dateTime->format($this->format);
    }

    public function cast(): IVal
	{
		if ($this->val()) {
			$this->value = $this->val();
			$this->trigger(Event::CHANGE);
		}

		return $this;
	}

    public function clear(): IVAl
	{
		parent::clear();

		$this->value = 0;

		// Set a default time of the beginning of the epoch
		$this->setValue($this->value);

		return $this;
	}

	public function ref(&$value): IVal
	{
		$this->alter($value);

    	$this->value = &$value;
		$this->value = $this->val();

		$this->setValue($this->value);

		return $this;
	}

	public function snapshot(): IVal
	{
		$this->snapshot = $this->value;

		return $this;
	}

	public function reset(): IVal
	{
		$this->value = $this->snapshot ?? 0;

		$this->setValue($this->value);

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
	    if ( is_null($data) ) {
	        $timestamp = mktime ((int)$this->data['hour'], (int)$this->data['minute'], (int)$this->data['second'], (int)$this->data['month'], (int)$this->data['day'], (int)$this->data['year']);
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
			$timestamp = mktime (func_get_arg(0), func_get_arg(1), 0, $this->data['month'], $this->data['day'], $this->data['year']);
		break;
		case 3:
			$timestamp = mktime (func_get_arg(0), func_get_arg(1), func_get_arg(2), $this->data['month'], $this->data['day'], $this->data['year']);
		break;
		}

		if ( Val::isNull($time) ) {
			$time = date($format, $timestamp);
		}

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
			return $this->format;
		}

		$this->format = $format;
		
		return $this;
	}

	/**
	 * Get the change between the current value and the snapshot
	 *
	 * @return mixed
	 */
	public function delta()
	{
		return Date::diff($this->snapshot, $this->data);

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