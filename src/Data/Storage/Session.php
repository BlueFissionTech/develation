<?php
namespace BlueFission\Data\Storage;

use BlueFission\DevString;
use BlueFission\DevNumber;
use BlueFission\Data\IData;
use BlueFission\Net\HTTP;

class Session extends Storage implements IData
{
	protected static $_id;
	protected $_config = array( 
		'location'=>'',
		'name'=>'',
		'expire'=>'3600',
		'secure'=>false,
	);
	
	public function __construct( $config = null )
	{
		parent::__construct( $config );
	}
	
	public function activate( )
	{
		$path = $this->config('location');
		$name = $this->config('name') ? (string)$this->config('name') : DevString::random();
		$expire = (int)$this->config('expire');
		$secure = $this->config('secure');
		$this->_source = $name;
		$id = session_id( );
		if ($id == "") 
		{
			$domain = ($path) ? substr($path, 0, strpos($path, '/')) : HTTP::domain();
			$dir = ($path) ? substr($path, strpos($path, '/'), strlen($path)) : '/';
			$cookiedie = (DevNumber::isValid($expire)) ? time()+(int)$expire : (int)$expire; //expire in one hour
			$secure = (bool)$secure;
			
			session_set_cookie_params($cookiedie, $dir, $domain, $secure);
			session_start( $this->_source );
			
			if ( session_id( ) )
				$this->_source = $name;
		}
		
		if ( !$this->_source ) 
			$this->status( self::STATUS_FAILED_INIT );
	}
	
	public function write()
	{			
		$value = HTTP::jsonEncode( $this->_data ? $this->_data : $this->_contents);
		$label = $this->_source;
		$path = $this->config('location');
		$expire = (int)$this->config('expire');
		$secure = $this->config('secure');
				
		$path = ($path) ? $path : HTTP::domain();
		$cookiedie = (DevNumber::isValid($expire)) ? time()+(int)$expire : (int)$expire; //expire in one hour
		$secure = (bool)$secure;
		$status = ( HTTP::session($label, $value, $cookiedie, $path = null, $secure) ) ? self::STATUS_SUCCESS : self::STATUS_FAILED;
		
		$this->status( $status );	
	}
	
	public function read()
	{	
		$value = HTTP::session( $this->_source );
		if ( function_exists('json_decode'))
		{
			$value = json_decode($value);
			$this->contents($value);
			$this->assign((array)$value);
		}
		return $value; 
	}
	
	public function delete()
	{
		$label = $this->_source;
		unset($_SESSION[$label]);
	} 
}