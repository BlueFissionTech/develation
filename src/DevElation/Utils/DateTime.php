<?phps
//dev_date_time.php
use \BlueFission;
@include_once('Loader.php');
$loader = BlueFission\Loader::instance();
$loader->load('com.bluefission.develation.functions.common');
$loader->load('com.bluefission.develation.functions.html');
$loader->load('com.bluefission.develation.Configurable');

class DateTime extends Configurable
{
	protected $_data = array('second'=>'', 'minute'=>'', 'hour'=>'', 'day'=>'', 'month'=>'', 'year'=>'', 'timezone'=>'', 'offset'=>'');
	protected $_config = array('date_format'=>'Y-m-d',
			'date_format_long'=>'M, Y', 
			'time_format'=>'r',
			'time_format_long'=>'H:i:sa T',
			'timezone' => 'America/New_York',
			'calendar_start_day'=>'Sunday',
			'full_date'=>true,
			'full_time'=>true,
		);

	public function __construct( $data = null )
	{	
		parent::__construct();
		if (dev_not_null($data))
		{
			if ( is_array($data))
				$this->config($data);
			elseif ( $this->string_is_date($data))
				$this->date($data);
			elseif ( is_int($data ))
			{
				$data = $this->timestamp($data);
				$this->_data = $this->info( $data );
			}
			else 
			{
				$this->timestamp( strtotime($data) );
				$this->_data = $this->info( $data );
			}
		}
		else
		$this->date( date( $this->config('date_format') ) );
	}
	
	protected function field($field, $value = null)
	{
		if ( array_key_exists( $field, $this->_data ) )
			return null;
		
		$value = parent::field($field, $value);
		
		return $value;
	}
	
	public function timestamp( $data = null )
	{
		if (dev_is_null($data))
			return mktime ($this->field('second'), $this->field('minute'), $this->field('hour'), $this->field('month'), $this->field('day'), $this->field('year'));
		elseif (is_numeric($data))
			$timestamp = $data;
		else 
			$timestamp = strtotime($data);		
	}
	public function info($datetime = null) 
	{
		if (dev_is_null($datetime))
			return $this->_data;
		
		$timestamp = $this->timestamp($datetime);
				
		$today =  array();
		$count = 0;
		
		$today = getdate($timestamp);
		$timestamp2 = mktime ( 0, 0, 0, $today['mon'], 1, $today['year']);
		$first = getdate($timestamp2);
		$sd = $today['mday'];
		$tsd = 28;
		$numdays = cal_days_in_month(CAL_GREGORIAN, $today['mon'], $today['year']);
		
		$sm = (isset($sm) && is_numeric($sm)) ? $sm : '';
		$sy = (isset($sy) && is_numeric($sy)) ? $sy : '';
		
		if ($sm <= 12 && $sm >=0 && $sy != '') {
		     $jday = juliantojd($sm, $sd, $sy);
		     $timestamp = jdtounix($jday);
			$today = getdate($timestamp);
			$numdays = cal_days_in_month(CAL_GREGORIAN, $today['mon'], $today['year']);
			$timestamp2 = mktime ( 0, 0, 0, $today['mon'], 1, $today['year']);
			$first = getdate($timestamp2);
		}
		
		$last_month = $today['mon'] - 1;
		if ($last_month < 1) {
			$last_month = 11;
			$last_year = $today['year'] - 1;
		} else {
			$last_year = $today['year'];
		}
		
		$next_month = $today['mon'] + 1;
		if ($next_month >= 12) {
			$next_month = 1;
			$next_year = $today['year'] + 1;
		} else {
			$next_year = $today['year'];
		}
	
		$date = array();
		$date['day'] = (int)$sd;
		$date['firstweekday'] = $first['wday'];
		$date['year'] = (int)$today['year'];
		$date['lastyear'] = (int)$last_year;
		$date['nextyear'] = (int)$next_year;
		$date['month'] = (int)$today['mon'];
		$date['lastmonth'] = (int)$last_month;
		$date['nextmonth'] = (int)$next_month;
		$date['daysinmonth'] = (int)$numdays;
		$date['second'] = (int)$today['seconds'];
		$date['minute'] = (int)$today['minutes'];
		$date['hour'] = (int)$today['hours'];
		$date['timestamp'] = $today[0];
		$date['timezone'] = '';
		$date['offset'] = '';
		
		//Phew! that was a lot of work!
		return $date;
	}
	
	public function month()
	{
		$date = dev_get_date_info($sm, $sy, $timestamp);
		$event_r = dev_value_to_array($event_r);
		$month = array();
		$notes = array();
		for ($i=0, ($j=1 - $date['firstweekday']); ($i<5 || $j<=$date['daysinmonth']); $i++) 
		{
			for ($k = 0; $k < 7; $k++, $j++) {
				if (in_array($j, $event_r) && dev_is_assoc($event_r)) {
					$note_r = array_keys($event_r, $j);
					$notes = implode(', ',$note_r);
				}
				$month[$i][$k] = (($i == 0 && $k < $date['firstweekday']) || $j > $date['daysinmonth']) ? ' ' : ((in_array($j, $event_r)) ? "<b>$j</b> ".(($notes)?" - $notes":'') : $j);
			}
		}
		
		return $month;
	}
	
	public function calendar()
	{
		$output = dev_content_box(dev_month_r($sm, $sy, $timestamp, $event_r), '', '', '', '', false, 0, 1);
		return $output;
	}
	
	public function time()
	{
		$arg_count = func_num_args();
		$format = ($this->config('full_time')) ? $this->config('time_format_long') : $this->config('time_format');
		$time = null;
		
		switch($arg_count)
		{
		default:
		case 0:
			$timestamp = $this->timestamp();
			$time = date($format, $timestamp);
		break;
		case 2:
			$timestamp = mktime (func_get_arg(0), func_get_arg(1), 0, $this->_data['month'], $this->_data['day'], $this->_data['year']);
		break;
		case 3:
			$timestamp = mktime (func_get_arg(0), func_get_arg(1), func_get_arg(2), $this->_data['month'], $this->_data['day'], $this->_data['year']);
		break;
		}
		
		$this->_data = $this->info($timestamp);
		
		if (dev_is_null($time))
			$time = date($format);
		
		return $time;
	}
	
	public function date()
	{
		$arg_count = func_num_args();
		$date = null;
		
		switch($arg_count)
		{
		default:
		case 0:
			$timestamp = $this->timestamp();
			$format = ($this->config('full_date')) ? $this->config('date_format_long') : $this->config('date_format');
			$date = date($format, $timestamp);
		break;
		case 1:
			if ( version_compare(PHP_VERSION, '5.3.0', '>=') )
			{
				$date = DateTime::createFromFormat( $this->config('date_format') , func_get_arg(0));
				$timestamp = $date->getTimestamp();
			}
			else
			{
				$timestamp = strtotime(func_get_arg(0));
			}
		break;
		case 3:
			if ( version_compare(PHP_VERSION, '5.2.0', '>=') )
			{
				$date = DateTime::setDate(func_get_arg(0), func_get_arg(1), func_get_arg(2));
				$timestamp = $date->getTimestamp();
			}
			else
			{
				$timestamp = $timestamp = mktime ('', '', '', func_get_arg(0), func_get_arg(1), func_get_arg(2));
			}
		break;
		}
		
		$this->_data = $this->info($timestamp);
		
		if (dev_is_null($date))	
			$date = date($format, $timestamp);
		
		return $date;
	}
	
	public static function difference($time1, $time2, $interval = null) 
	{
		if (dev_is_null($interval)) $interval = 'seconds';
		$a = strtotime($time1);
		$b = strtotime($time2);
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
	
	public static function string_is_date($string) 
	{
		return preg_match('/^(\d{4}\-\d+\-\d+|\d+\/\d+\/\d{4})/', $string);
	}
}