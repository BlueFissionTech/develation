<?php

namespace BlueFission\Services;

use BlueFission\Services\Application as App;
use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\Val;

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
		$httpMethod = Str::make($method)->trim()->lower();
		$routePath = Str::make($path)->trim()->trim('/');
		$routeName = Str::make($name)->trim();

		$mapping = $app->map(
			$httpMethod->val(),
			filter_var($routePath->val(), FILTER_SANITIZE_URL),
			$callable, 
			$routeName->val()
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
		$name = Str::make($root.$package)
			->replace('/', '.')
			->replace('-', '.')
			->replace('_', '.');
		$resourcePath = self::pathFromParts($root, $package);
		$itemPath = Str::make($resourcePath->val())->append('/$')->append($idField);

		self::add($resourcePath->val(), [$controller, 'index'], $name->val(), 'get')->gateway($gateway);
		self::add($itemPath->val(), [$controller, 'find'], Str::make($name->val())->append('.get')->val(), 'get')->gateway($gateway);
		self::add($resourcePath->val(), [$controller, 'save'], Str::make($name->val())->append('.save')->val(), 'post')->gateway($gateway);
		self::add($itemPath->val(), [$controller, 'update'], Str::make($name->val())->append('.update')->val(), 'post')->gateway($gateway);
		self::add($itemPath->val(), [$controller, 'delete'], Str::make($name->val())->append('.delete')->val(), 'delete')->gateway($gateway);
	}

	/**
	 * Build a URL path from arbitrary path segments.
	 *
	 * @param mixed ...$parts
	 * @return Str
	 */
	private static function pathFromParts(...$parts): Str
	{
		return Arr::make($parts)
			->map(fn ($part) => Str::make($part)->trim()->trim('/')->val())
			->filter(fn ($part) => Str::make($part)->isNotEmpty())
			->join('/');
	}

	/**
	 * Adds a gateway to the list of gateways for this mapping.
	 *
	 * @param string|array $gateway The name of the gateway to be added.
	 *
	 * @return Mapping Returns the current instance of the Mapping class.
	 */
	public function gateway( $gateway )
	{
		if (!Val::is($gateway)) {
			return $this;
		}

		$gateways = Arr::make(Arr::is($gateway) ? $gateway : [$gateway])
			->filter(fn ($g) => Str::make((string)$g)->trim()->isNotEmpty());

		foreach ($gateways as $g) {
			$this->_gateways[] = $g;
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
