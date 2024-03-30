<?php

namespace BlueFission\Data\Storage;

use BlueFission\DevString;
use BlueFission\DevNumber;
use BlueFission\Data\IData;
use BlueFission\Net\HTTP;

/**
 * Class Cookie
 * 
 * This class represents the Cookie storage method and implements the IData interface.
 * 
 * @package BlueFission\Data\Storage
 * @implements IData
 */
class Cookie extends Storage implements IData
{
	/**
	 * Configuration for the cookie, including the name, location, expiration time and security status.
	 *
	 * @var array
	 */
	protected $_config = array( 'location'=>'',
		'name'=>'storage',
		'expire'=>'3600',
		'secure'=>false,
	);
	
	/**
	 * Constructor for the Cookie class.
	 *
	 * @param null|array $config Configuration for the cookie.
	 */
	public function __construct( $config = null )
	{
		parent::__construct( $config );
	}
	
	/**
	 * Activates the cookie.
	 *
	 * @return void
	 */
	public function activate()
	{
		$path = $this->config('location');
		$expire = (int)$this->config('expire');
		$cookiesecure = $this->config('secure');
		$name = $this->config('name') ? (string)$this->config('name') : DevString::random();
		
		if (isset($_COOKIE[$name])) {
			$this->_contents = $_COOKIE[$name];
		} else {
			$_COOKIE[$name] = serialize([]);
			$this->_contents = null;
		}

		$this->_source = isset($_COOKIE[$name]) ? $name : null;
		
		if ( !$this->_source ) 
			$this->status( self::STATUS_FAILED_INIT );
		else
			$this->status( self::STATUS_SUCCESSFUL_INIT );
	}
	
	/**
	 * Writes data to the cookie.
	 *
	 * @return void
	 */
	public function write()
	{	
		$value = HTTP::jsonEncode( !empty($this->_data->value()) ? $this->_data->value() : $this->_contents);
		$label = $this->_source;
		$path = $this->config('location');
		$expire = (int)$this->config('expire');
		$cookiesecure = $this->config('secure');
		
		$path = ($path) ? $path : HTTP::domain();
		$cookiedie = (DevNumber::isValid($expire)) ? time()+(int)$expire : (int)$expire; //expire in one hour
		$cookiesecure = (bool)$cookiesecure;
		$status = ( HTTP::cookie($label, $value, $cookiedie, $path = null, $cookiesecure) ) ?  self::STATUS_SUCCESS : self::STATUS_FAILED;
		
		$this->status( $status );	
	}
	
	/**
	 * Reads the cookie and returns its value.
	 *
	 * @return mixed The value of the cookie.
	 */
	public function read()
	{
		$value = HTTP::cookie($this->_source);
		if ( function_exists('json_decode') && !empty($value) )
		{
			$value = json_decode($value);
			$this->contents($value);
			$this->loadArray((array)$value);
		}	
		return $value;
	}

	/**
	 * Deletes the cookie.
	 *
	 * @return void
	 */
	public function delete()
	{
		$label = $this->_source;
		unset($_COOKIE[$label]);
	}
}