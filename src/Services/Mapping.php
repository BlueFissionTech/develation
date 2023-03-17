<?php

namespace BlueFission\Services;

use App;
use BlueFission\DevObject;

/**
 * Class Mapping
 *
 * This class is used for creating mappings between routes and their corresponding
 * callable functions in a web application.
 */
class Mapping {

	/**
	 * The HTTP method for this mapping.
	 *
	 * @var string
	 */
	public String $method;

	/**
	 * The URL path for this mapping.
	 *
	 * @var string
	 */
	public String $path;

	/**
	 * The callable function for this mapping.
	 *
	 * @var mixed
	 */
	public $callable;

	/**
	 * The name for this mapping.
	 *
	 * @var string
	 */
	public String $name;

	/**
	 * An array of gateways for this mapping.
	 *
	 * @var array
	 */
	private Array $_gateways = [];

	/**
	 * Creates a new mapping with the specified parameters.
	 *
	 * @param string $path     The URL path for this mapping.
	 * @param mixed  $callable The callable function for this mapping.
	 * @param string $name     The name for this mapping.
	 * @param string $method   The HTTP method for this mapping.
	 *
	 * @return Mapping The new mapping instance.
	 */
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

	/**
	 * Creates CRUD (Create, Read, Update, Delete) mappings with the specified parameters.
	 *
	 * @param string $root      The root URL for the CRUD mappings.
	 * @param string $package   The package name for the CRUD mappings.
	 * @param string $controller The controller for the CRUD mappings.
	 * @param string $idField   The ID field name for the CRUD mappings.
	 * @param string $gateway   The gateway for the CRUD mappings.
	 */
	static public function crud($root, $package, $controller, $idField, $gateway = '')
	{
		$name = str_replace(['/','-','_'], ['.','.','.'], $root.$package);

		self::add($root.$package, [$controller, 'index'], $name, 'get')->gateway($gateway);
		self::add($root.$package."/$".$idField, [$controller, 'find'], $name.'.get', 'get')->gateway($gateway);
		self::add($root.$package, [$controller, 'save'], $name.'.save', 'post')->gateway($gateway);
		self::add($root.$package."/$".$idField, [$controller, 'update'], $name.'.update', 'post')->gateway($gateway);
		// self::add($root.$package."/$".$idField, [$controller, 'delete'], $name.'.delete', 'get')->gateway($gateway);
	}

	/**
	 * Adds a gateway to the list of gateways for this mapping.
	 *
	 * @param string $gateway The name of the gateway to be added.
	 *
	 * @return Mapping Returns the current instance of the Mapping class.
	 */
	public function gateway( $gateway )
	{
		if ( $gateway) {
			$this->_gateways[] = $gateway;
		}

		return $this;
	}

	/**
	 * Returns the list of gateways associated with this mapping.
	 *
	 * @return array Returns an array of gateways associated with this mapping.
	 */
	public function gateways()
	{
		return $this->_gateways;
	}

}