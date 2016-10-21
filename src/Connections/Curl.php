<?php
namespace BlueFission\Connections;

use BlueFission\DevValue;
use BlueFission\DevArray;
use BlueFission\Net\HTTP;
use BlueFission\Behavioral\IConfigurable;

class Curl extends Connection implements IConfigurable
{
	protected $_result;

	protected $_config = array( 'target'=>'',
		'username'=>'',
		'password'=>'',
		'method'=>'',
		'refresh'=>false,
		'validate_host'=>false,
	);
	
	public function __construct( $config = null )
	{
		parent::__construct();
		if (is_array($config))
			$this->config($config);
	}
	
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
	
	public function close ()
	{
		curl_close($this->_connection);
		
		// clean up
		parent::close();
	}
	
	public function query ( $query = null )
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
				curl_setopt($curl,CURLOPT_POSTFIELDS, HTTP::query($data));
			} elseif ( $method == 'get') {
				curl_setopt($curl, CURLOPT_URL, $this->config('target').'/'.HTTP::query($data));
			}
			curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
			
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