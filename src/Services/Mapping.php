<?php 
namespace BlueFission\Services;

use App;
use BlueFission\DevObject;

class Mapping {

	public String $method;
	public String $path;
	public $callable;
	public String $name;
	private Array $_gateways = [];

	// public function __construct($method, $path, $callable, $name) {

	// }

	static public function add(String $path, $callable, String $name = '', String $method = 'get')
	{
		$app = App::instance();
		$mapping = $app->map(
			strtolower($method), 
			filter_var($path, FILTER_SANITIZE_URL), 
			$callable, 
			trim($name)
		);

		return $mapping;
	}

	public function gateway( $gateway )
	{
		$this->_gateways[] = $gateway;

		return $this;
	}

	public function gateways()
	{
		return $this->_gateways;
	}
}