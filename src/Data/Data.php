<?php
namespace BlueFission\Data;

use BlueFission\Behavioral\Configurable;

class Data extends Configurable implements IData
{
	public function read() { }
	public function write() { }
	public function delete() { }
	public function contents() { }
		
	public function data() 
	{
		return $this->_data;
	}
	
	public function registerGlobals( string $source = null )
	{
		$source = strtolower($source);
		switch( $source )
		{
			case 'post':
				$vars = filter_input_array(INPUT_POST);
			break;
			case 'get':
				$vars =  filter_input_array(INPUT_GET);
			break;
			case 'session':
				$vars = filter_input_array(INPUT_SESSION);
			break;
			case 'cookie':
			case 'cookies':
				$vars = filter_input_array(INPUT_COOKIE);
			break;
			default:
			case 'globals':
				$vars = $GLOBALS;
			break;
			case 'request':
				$vars = filter_input_array(INPUT_REQUEST);
			break;
		}

		$this->assign($vars);
	}
}