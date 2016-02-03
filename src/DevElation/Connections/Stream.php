<?php
namespace BlueFission\Connections;

use BlueFission\DevValue;
use BlueFission\DevArray;
use BlueFission\Net\HTTP;
use BlueFission\Behavioral\IConfigurable;

class Stream extends Connection implements IConfigurable
{
	protected $_config = array( 'target'=>'',
		'wrapper'=>'http',
		'method'=>'GET',
		'header'=>"Content-type: application/x-www-form-urlencoded\r\n",
	);
	
	public function __construct( $config = null )
	{
		parent::__construct();
	}
	
	public function open() 
	{
		$target = $this->config('target') ? $this->config('target') : HTTP::domain();
		$method = $this->config('method');
		$header = $this->config('header'); 
		$wrapper = $this->config('wrapper');
		
		if ( HTTP::urlExists($target) )
		{
			$options = array(
				$wrapper => array(
					'header'	=>	$header,
					'method'	=>	$method,
				),
			);
			$this->_connection = stream_context_create($options);
			$status = $this->_connection ? self::STATUS_CONNECTED : self::STATUS_NOTCONNECTED;
		}
		else
		{
			$status = self::STATUS_NOTCONNECTED;
		}
		$this->status($status);
	}
	
	public function query ( $query = null )
	{ 
		
		$status = self::STATUS_NOTCONNECTED;
		$context = $this->_connection;
		$wrapper = $this->config('wrapper');
		
		if ($context)
		{
			if (DevValue::isNotNull($query))
			{
				if (DevArray::isAssoc($query))
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