<?php
namespace BlueFission\Connections;

use BlueFission\Behavioral\IConfigurable;
use BlueFission\Behavioral\Configurable;

abstract class Connection extends Configurable implements IConfigurable
{	
	protected $_connection;
	protected $_result;
	
	const STATUS_CONNECTED = 'Connected.';
	const STATUS_NOTCONNECTED = 'Not Connected.';
	const STATUS_DISCONNECTED = 'Disconnected.';
	const STATUS_SUCCESS = 'Query success.';
	const STATUS_FAILED = 'Query failed.';
	
	public function __construct( $config = null )
	{
		parent::__construct();
		if (is_array($config))
			$this->config($config);
	}
		
	abstract public function open();
		
	public function close()
	{
		$this->_connection = null;
		$this->status(self::STATUS_DISCONNECTED);
	}
	
	abstract public function query( $query = null);
	
	public function result( )
	{
		return $this->_result;
	}
}