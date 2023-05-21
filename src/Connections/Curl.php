<?php

namespace BlueFission\Connections;

use BlueFission\DevValue;
use BlueFission\DevArray;
use BlueFission\Net\HTTP;
use BlueFission\Behavioral\IConfigurable;

/**
 * Class Curl
 * 
 * This class implements the connection functionality using cURL. It extends the
 * base Connection class and implements the IConfigurable interface.
 */
class Curl extends Connection implements IConfigurable
{
	/**
	 * Result of the last cURL operation.
	 *
	 * @var string
	 */
	protected $_result;

	/**
	 * Options to use
	 *
	 * @var array
	 */
	protected $_options = [];

	/**
	 * Configuration data for the cURL connection.
	 *
	 * @var array
	 */
	protected $_config = [
		'target'=>'',
		'username'=>'',
		'password'=>'',
		'method'=>'',
		'headers'=>[],
		'refresh'=>false,
		'validate_host'=>false,
	];
	
	/**
	 * Constructor that sets the configuration data.
	 *
	 * @param array|null $config Configuration data.
	 */
	public function __construct( $config = null )
	{
		parent::__construct();
		if (is_array($config))
			$this->config($config);
	}

	/**
	 * Sets options for the cURL connection.
	 *
	 * @param string $option Option to set.
	 * @param mixed $value Value of the option.
	 * 
	 * @return void
	 */
	public function option($option, $value)
	{
		$this->_options[$option] = $value;
	}
	
	/**
	 * Opens a cURL connection.
	 *
	 * @return void
	 */
	public function open()
	{
		$status = '';
		$target = $this->config('target') ? $this->config('target') : HTTP::domain();
		$refresh = (bool)$this->config('refresh');
		
		if ( !$this->config('validate_host') || HTTP::urlExists($target) )
		{
			$data = $this->_data;
			
			//open connection
			$this->_connection = curl_init();
			
			curl_setopt($this->_connection, CURLOPT_URL, $target);
			curl_setopt($this->_connection, CURLOPT_COOKIESESSION, $refresh);
			curl_setopt($this->_connection, CURLOPT_HTTPHEADER, $this->config('headers'));

			if ( $this->config('username') && $this->config('password') )
    			curl_setopt($this->_connection, CURLOPT_USERPWD, $this->config('username') . ':' . $this->config('password'));
			
			$status = $this->_connection ? self::STATUS_CONNECTED : self::STATUS_NOTCONNECTED;
		}
		else
		{
			$status = self::STATUS_NOTCONNECTED;		
		}
		$this->status($status);
	}
	
	/**
	 * Closes a cURL connection.
	 *
	 * @return void
	 */
	public function close ()
	{
		curl_close($this->_connection);
		
		// clean up
		parent::close();
	}
	
	/**
	 * Performs a cURL query to the target URL.
	 * 
	 * @param array $query The query data to be sent to the target URL.
	 * 
	 * @return void
	 */
	public function query($query = null)
	{ 
		$curl = $this->_connection;
		$method = strtolower($this->config('method'));
		
		if ($curl)
		{
			if (DevValue::isNotNull($query))
			{
				if (DevArray::isAssoc($query))
				{
					//$this->_data = $query; 
					$this->assign($query);
				}
			}
			$data = $this->_data;
			//set the url, number of POST vars, POST data
			if ( $method == 'post' ) {
				curl_setopt($curl,CURLOPT_POST, count($data));
				curl_setopt($curl,CURLOPT_POSTFIELDS, HTTP::jsonEncode($data));
			} elseif ( $method == 'get') {
				curl_setopt($curl, CURLOPT_URL, $this->config('target').'/'.HTTP::query($data));
			}
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			foreach ($this->_options as $option=>$value) {
				curl_setopt($curl, $option, $value);
			}
			
			//execute post
			$this->_result = curl_exec($curl);
			$status = ( $this->_result ) ? self::STATUS_SUCCESS : self::STATUS_FAILED;
		}
		else
		{
			$status = self::STATUS_NOTCONNECTED;
		}	
		$this->status($status);
	}

}