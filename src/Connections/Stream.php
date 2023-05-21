<?php
namespace BlueFission\Connections;

use BlueFission\DevValue as Value;
use BlueFission\DevArray as Array;
use BlueFission\Net\HTTP;
use BlueFission\Behavioral\IConfigurable;

/**
 * Class Stream
 * 
 * This class provides a stream connection for sending and receiving data over HTTP.
 * 
 * @package BlueFission\Connections
 * @implements IConfigurable
 */
class Stream extends Connection implements IConfigurable
{
	/**
	 * @var array $_config Configuration options for the stream connection
	 */
	protected $_config = array( 
		'target' => '',  // target URL for the stream connection
		'wrapper' => 'http', // wrapper for the stream context
		'method' => 'GET',  // HTTP method for the stream connection
		'header' => "Content-type: application/x-www-form-urlencoded\r\n", // header for the stream connection
	);
	
	/**
	 * Stream constructor.
	 *
	 * @param mixed|null $config Configuration options for the stream connection
	 */
	public function __construct( $config = null )
	{
		parent::__construct();
	}
	
	/**
	 * Opens a stream connection.
	 *
	 * @return void
	 */
	public function open() 
	{
		$target = $this->config('target') ? $this->config('target') : HTTP::domain();
		$method = $this->config('method');
		$header = $this->config('header'); 
		$wrapper = $this->config('wrapper');
		
		// Check if target URL exists
		if ( HTTP::urlExists($target) )
		{
			// Create a stream context with the options provided in the config
			$options = array(
				$wrapper => array(
					'header'	=>	$header,
					'method'	=>	$method,
				),
			);
			$this->_connection = stream_context_create($options);
			
			// Set the connection status
			$status = $this->_connection ? self::STATUS_CONNECTED : self::STATUS_NOTCONNECTED;
		}
		else
		{
			$status = self::STATUS_NOTCONNECTED;
		}
		$this->status($status);
	}
	
	/**
	 * Sends a query to the target URL and retrieves the result.
	 *
	 * @param mixed|null $query Query to be sent to the target URL
	 * @return bool
	 */
	public function query ( $query = null )
	{ 
		// Set the connection status as not connected
		$status = self::STATUS_NOTCONNECTED;
		$context = $this->_connection;
		$wrapper = $this->config('wrapper');
		
		// If the stream context exists
		if ($context)
		{
			// If a query is not null
			if (Value::isNotNull($query))
			{
				if (Array::isAssoc($query))
				{
					$this->_data = $query; 
				}
				else if (is_string($query))
				{
					$data = urlencode($query);	
					stream_context_set_option ( $context, $wrapper, 'content', $data );			
					$this->_result = file_get_contents($target, false, $context);
					
					$this->status( $this->_result !== false ? self::STATUS_SUCCESS : self::STATUS_FAILED );
					return true;
				}
			}
			$data = HTTP::query( $this->_data );	
	
			stream_context_set_option ( $context, $wrapper, 'content', $data );			
	
			$this->_result = file_get_contents($target, false, $context);
			
			if ($this->_result !== false)
				$status = self::STATUS_SUCCESS;
			
		}
		$this->status($status);
	}
}