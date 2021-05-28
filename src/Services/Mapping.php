<?php 
namespace BlueFission\Services;

use App;
use BlueFission\DevObject;

class Mapping {

	public String $method;
	public String $path;
	public Callable $callable;
	public String $name;
	public Array $gateways = [];

	// public function __construct($method, $path, $callable, $name) {

	// }

	static public function add(String $path, Callable $callable, String $name = '', String $method = 'get')
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
		$this->gateways[] = $gateway;

		return $this;
	}
}