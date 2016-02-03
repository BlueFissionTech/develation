<?php
namespace BlueFission\Data\Storage;

use BlueFission\DevString;
use BlueFission\DevNumber;
use BlueFission\Data\IData;
use BlueFission\Net\HTTP;

class Cookie extends Storage implements IData
{
	protected $_config = array( 'location'=>'',
		'name'=>'storage',
		'expire'=>'3600',
		'secure'=>false,
	);
	
	public function __construct( $config = null )
	{
		parent::__construct( $config );
	}
	
	public function activate()
	{
		$path = $this->config('location');
		$expire = (int)$this->config('expire');
		$cookiesecure = $this->config('secure');
		$name = $this->config('name') ? (string)$this->config('name') : DevString::random();
		
		$this->_source = HTTP::cookie($name, "", $cookiedie, $path = null, $cookiesecure) ? $name : null;
		
		if ( !$this->_source ) 
			$this->status( self::STATUS_FAILED_INIT );
	}
	
	public function write()
	{	
		$value = HTTP::jsonEncode( $this->_data ? $this->_data : $this->_contents);
		$label = $this->_source;
		$path = $this->config('location');
		$expire = (int)$this->config('expire');
		$cookiesecure = $this->config('secure');
		
		$path = ($path) ? $path : HTTP::domain();
		$cookiedie = (DevNumber::isValid($expire)) ? time()+(int)$expire : (int)$expire; //expire in one hour
		$cookiesecure = (bool)$secure;
		$status = ( HTTP::cookie($label, $value, $cookiedie, $path = null, $cookiesecure) ) ?  self::STATUS_SUCCESS : self::STATUS_FAILED;
		
		$this->status( $status );	
	}
	
	public function read()
	{	
		$value = HTTP::cookie($this->_source);
		if ( function_exists('json_decode'))
		{
			$value = json_decode($value);
			$this->contents($value);
			$this->loadArray((array)$value);
		}	
		return $value;
	}
	
	public function delete()
	{
		$label = $this->_source;
		unset($_COOKIES[$label]);
	} 
}