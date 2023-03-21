<?php
namespace BlueFission\Data\Storage;

use BlueFission\DevString;
use BlueFission\DevNumber;
use BlueFission\Data\IData;
use BlueFission\Net\HTTP;

/**
 * Class Session
 *
 * Represents a session storage mechanism that implements the IData interface.
 */
class Session extends Storage implements IData
{
	/**
	 * @var string $_id The unique identifier for the session.
	 */
	protected static $_id;
	
	/**
	 * @var array $_config An array of configuration options for the session.
	 * 'location': the path to the session.
	 * 'name': the name of the session.
	 * 'expire': the time in seconds for the session to expire.
	 * 'secure': a boolean indicating if the session should be secure.
	 */
	protected $_config = array( 
		'location'=>'',
		'name'=>'',
		'expire'=>'3600',
		'secure'=>false,
	);
	
	/**
	 * Session constructor.
	 * 
	 * @param array|null $config An array of configuration options for the session.
	 */
	public function __construct( $config = null )
	{
		parent::__construct( $config );
	}
	
	/**
	 * Activates the session.
	 */
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
	
	/**
	 * Writes data to the session.
	 */
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
	
	/**
	 * Reads session data
	 * 
	 * @return mixed Session data
	 */
	public function read()
	{	
		$value = HTTP::session( $this->_source );
		if (empty($value)) {
			return null;
		}
		if ( function_exists('json_decode'))
		{
			$value = json_decode($value);
			$this->contents($value);
			$this->assign((array)$value);
		}
		return $value; 
	}

	/**
	 * Deletes session data
	 * 
	 * @return void
	 */
	public function delete()
	{
		$label = $this->_source;
		unset($_SESSION[$label]);
	}
}