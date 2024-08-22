<?php
namespace BlueFission\Data\Storage;

use BlueFission\Str;
use BlueFission\Num;
use BlueFission\IObj;
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
	 * @var string $id The unique identifier for the session.
	 */
	protected static $id;
	
	/**
	 * @var array $config An array of configuration options for the session.
	 * 'location': the path to the session.
	 * 'name': the name of the session.
	 * 'expire': the time in seconds for the session to expire.
	 * 'secure': a boolean indicating if the session should be secure.
	 */
	protected $config = [
		'location'=>'',
		'name'=>'',
		'expire'=>'3600',
		'secure'=>false,
	];
	
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
	public function activate( ): IObj
	{
		$path = $this->config('location');
		$name = $this->config('name') ? (string)$this->config('name') : Str::rand();
		$expire = (int)$this->config('expire');
		$secure = $this->config('secure');
		$this->source = $name;
		$id = session_id( );
		if ($id == "") 
		{
			$domain = ($path) ? substr($path, 0, strpos($path, '/')) : HTTP::domain();
			$dir = ($path) ? substr($path, strpos($path, '/'), strlen($path)) : '/';
			$cookiedie = (Num::isValid($expire)) ? time()+(int)$expire : (int)$expire; //expire in one hour
			$secure = (bool)$secure;
			
			session_set_cookie_params($cookiedie, $dir, $domain, $secure);
			session_start( $this->source );
			
			if ( session_id( ) )
				$this->source = $name;
		}
		
		if ( !$this->source ) 
			$this->status( self::STATUS_FAILED_INIT );

		return $this;
	}
	
	/**
	 * Writes data to the session.
	 */
	public function write(): IObj
	{			
		$value = HTTP::jsonEncode( $this->data->size() > 0 ? $this->data->val() : $this->contents);
		$label = $this->source;
		$path = $this->config('location');
		$expire = (int)$this->config('expire');
		$secure = $this->config('secure');
				
		$path = ($path) ? $path : HTTP::domain();
		$cookiedie = (Num::isValid($expire)) ? time()+(int)$expire : (int)$expire; //expire in one hour
		$secure = (bool)$secure;
		$status = ( HTTP::session($label, $value, $cookiedie, $path, $secure) ) ? self::STATUS_SUCCESS : self::STATUS_FAILED;
		
		$this->status( $status );

		return $this;
	}
	
	/**
	 * Reads session data
	 * 
	 * @return IObj
	 */
	public function read(): IObj
	{	
		$value = HTTP::session( $this->source );

		if ( $value && function_exists('json_decode'))
		{
			$value = json_decode($value);
			$this->contents($value);
			$this->assign((array)$value);
		}

		return $this; 
	}

	/**
	 * Deletes session data
	 * 
	 * @return IObj
	 */
	public function delete(): IObj
	{
		$label = $this->source;
		unset($_SESSION[$label]);

		return $this;
	}
}