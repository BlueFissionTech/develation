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
		'method'=>'',
		'refresh'=>false,		
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
		
		if ( HTTP::urlExists($target) )
		{				
			$data = $this->_data;
			
			//open connection
			$this->_connection = curl_init();
			
			curl_setopt($this->_connection, CURLOPT_URL, $url);
			curl_setopt($this->_connection, CURLOPT_COOKIESESSION, $refresh);
			
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
			if (DevValue::isNotNull(($query))
			{
				if (DevArray::isAssoc($query))
				{
					//$this->_data = $query; 
					$this->assign($query);
				}
			}
			$data = $this->_data;
			//set the url, number of POST vars, POST data
			curl_setopt($curl,CURLOPT_POST, count($data));
			curl_setopt($curl,CURLOPT_POSTFIELDS, HTTP::query($data));
			curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
			
			//execute post
			$status = ($this->_result = curl_exec($ch)) ? self::STATUS_SUCCESS : self::STATUS_FAILED;
		}
		else
		{
			$status = self::STATUS_NOTCONNECTED;
		}	
		$this->status($status);
	}
}