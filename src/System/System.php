<?php 

namespace BlueFission\System;

class System {
	
	protected $_response;
	protected $_processes;

	public function __construct() {}

	public function run( $command )
	{
		if (!$command)
			throw( \BadArgumentException("Invalid command!") );

		$command = escapeshellcmd($command);
		
		if (\substr(\php_uname(), 0, 7) == "Windows")
		{ 
			$handle = \popen("start /B ". $command, "r");
		} 
		else
		{
			$handle = \popen($command . " 2>&1", "r");
		}

		$read = \fread($handle, 2096);
		$this->_response = $read;
		\pclose($handle);
	}

	public function response()
	{
		return $this->_response;
	}
}