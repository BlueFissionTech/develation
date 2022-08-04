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
			filter_var(trim($path, '/'), FILTER_SANITIZE_URL), 
			$callable, 
			trim($name)
		);

		return $mapping;
	}

	static public function crud($root, $package, $controller, $idField, $gateway = '')
	{
		$name = str_replace(['/','-','_'], ['.','.','.'], $root.$package);

		self::add($root.$package, [$controller, 'index'], $name, 'get')->gateway($gateway);
		self::add($root.$package."/$".$idField, [$controller, 'find'], $name.'.get', 'get')->gateway($gateway);
		self::add($root.$package, [$controller, 'save'], $name.'.save', 'post')->gateway($gateway);
		self::add($root.$package."/$".$idField, [$controller, 'update'], $name.'.update', 'post')->gateway($gateway);
		// self::add($root.$package."/$".$idField, [$controller, 'delete'], $name.'.delete', 'get')->gateway($gateway);
	}

	public function gateway( $gateway )
	{
		if ( $gateway) {
			$this->_gateways[] = $gateway;
		}

		return $this;
	}

	public function gateways()
	{
		return $this->_gateways;
	}
}