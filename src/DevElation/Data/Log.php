<?php
namespace BlueFission\Data

use BlueFission\DevValue;
use BlueFission\Data\FileSystem;
use BlueFission\Net\Email;

class Log extends Data implements iData
{
	protected $_config = array('storage'=>'', 'file'=>'application.log', 'email'=>'', 'subject'=>'', 'from'=>'');
	
	static $_messages;
	
	const SYSTEM = 0;
	const EMAIL = 1;
	const FILE = 3;
	
	static $_instance;
	
	public function __construct( $config = null )
	{
		parent::__construct();
		if (is_array($config))
			$this->config($config);
			
		if ( !$this->config('storage') ) $this->config('storage', self::FILE);
		self::$_messages = array();
	}
	
	public function instance()
	{
		if (DevValue::isNull(Log::instance))
			Log::$_instance = new Log();
			
		return Log::$_instance;
	}
	
	public function push($message)
	{
		$time = date('Y-m-d G:i:s');
		$this->field($time, $message);
	}
	
	public function read() 
	{
		$destination = $this->config('file');
		$type = $destination ? $this->config('storage') : self::SYSTEM;
		if ($type == self::FILE && $destination && class_exists('FileSystem') )
		{
			$file_config = array('mode'=>'a'); 
			$messenger = new FileSystem($file_config);
			$messenger->file( $destination );
			$messenger->read();
			$status = $messenger->status();
			$data = $messenger->data();
			
			return $data;
		}
		$this->status("Cannot open log files with current settings.");
		return false;
	}
	
	public function write($file = null)
	{
		$message = $this->message();
		$status = null;
		
		if ($message != '') 
		{	
			$destination = $this->config('email') ? $this->config('email') : $this->config('file');
			$type = $destination ? $this->config('storage') : self::SYSTEM;
		
			switch ($type)
			{
				case self::FILE:
					if ( class_exists('FileSystem') )
					{
						$file_config = array('mode'=>'a'); 
						$messenger = new FileSystem($file_config);
						$messenger->file( $destination );
						$messenger->data( $message );
						$status = $messenger->status();
					}
					else
					{
						$status = error_log($message, $type, $destination) ? "Errors save by system" : "Unable to save errors. Ironic.";
					}
				break;
				case self::EMAIL:
					if ( class_exists('Email') )
					{
						$messenger = new Email($destination, $from, $subject, $message);
						$messenger->send();
						$status = $messenger->status();
					}
					else
					{
						$status = mail($destination, $from, $subject, $message) ? "Log emailed to recipient" : error_log($message, $type, $destination) ? "Errors emailed by system" : "Unable to send email report";
					}
				break;
				default:
				case self::SYSTEM:
					$status = error_log($message, $type, $destination) ? "Errors reported to system" : "Unable to record error";
				break;
			}
		}
		
		$this->status($status);	
	}
	
	public function delete()
	{
		$destination = $this->config('file');
		$type = $destination ? $this->config('storage') : self::SYSTEM;
		if ($type == self::FILE && class_exists('FileSystem') )
		{
			$file_config = array('mode'=>'a'); 
			$messenger = new FileSystem($file_config);
			$messenger->file( $destination );
			$messenger->delete();
			$status = $messenger->status();
			
			return true;
		}
		$this->status("Cannot delete log files with current settings.");
		return false;
	}
	
	private function message( $records = null )
	{
		$records = (DevValue::isNull($records)) ? $records : $this->config('max_logs');
		$message = array_slice($this->_data, -($records));
		$output = implode("\n", $message);
		return $output; 
	}
	 
	public function alert()
	{
		$destination = $this->config('email');
		$type = self::EMAIL;
	
		if ( class_exists('Email') )
		{
			$messenger = new Email($destination, $from, $subject, $message);
			$messenger->send();
			$status = $messenger->status();
		}
		else
		{
			$status = mail($destination, $from, $subject, $message) ? "Alert emailed to recipient" : error_log($message, $type, $destination) ? "Alert emailed by system" : "Unable to send alert";
		}
		
		$this->status($status);
	}
}