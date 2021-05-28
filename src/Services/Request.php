<?php 
namespace BlueFission\Services;

use BlueFission\Behavioral\Dispatcher;

class Request extends Dispatcher {
	
	public function __construct()
	{
		parent::__construct();

		$this->_data = $this->all();
	}

	public function all()
	{
		switch($this->type())
		{
			case 'GET':
				$request = filter_input_array(INPUT_GET);
				break;
			case 'POST':
				$request = filter_input_array(INPUT_POST);
				break;
			default:
				$request = filter_input_array(INPUT_REQUEST);
				break;
		}

		return $request;
	}

	public function type()
	{
		return $_SERVER['REQUEST_METHOD'];
	}
}