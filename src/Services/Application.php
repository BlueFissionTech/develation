<?php
namespace BlueFission\Services;

use BlueFission\Behavioral\Programmable;
use BlueFission\Utils\Util;
use BlueFission\Behavioral\Scheme;
use BlueFission\Behavioral\Dispatcher;
use BlueFission\Collections\Collection;
use BlueFission\Services\Mapping;
use BlueFission\DevValue;
use BlueFission\DevArray;
use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Handler;
use BlueFission\Net\HTTP;
use Exception;

class Application extends Programmable {
	private static $_instances = [];

	private $_broadcasted_events = [];
	private $_broadcast_chain = [];
	private $_last_args = null;
	private $_depth = 0;

	protected $_config = array(
		'template'=>'',
		'storage'=>'',
		'name'=>'Application',
	);

	protected $_parameters = array(
		'_method',
		'service',
		'behavior',
		'data',
	);

	private $_context;
	private $_connection;
	private $_storage;
	private $_agent;
	private $_services;
	private $_gateways = [];
	private $_bindings = [];
	private $_mappings = [];
	private $_mappingNames = [];

	private $_routes = [];
	private $_arguments = [];

	// protected static $_class = __CLASS__;

	public function __construct() 
	{
		$calledClass = get_called_class();
		if ( isset(self::$_instances[$calledClass]) )
			return self::$_instances[$calledClass];

		parent::__construct();
		$this->_services = new Collection();
		$this->_broadcasted_events[$this->name()] = [];

		self::$_instances[$calledClass] = $this;
	}

	static function instance()
	{
		$calledClass = get_called_class();
		if (!isset(self::$_instances[$calledClass])) {
			// $c = get_class();
			// self::$_class = ;

			// self::$_instances = new self::$_class;
			self::$_instances[$calledClass] = new static();
		}

		return self::$_instances[$calledClass];
	}

	public function params( $params ) {
		$this->_parameters = DevArray::toArray($params);
	
		return $this;
	}

	public function args() {
		global $argv, $argc;


		if ( $argc > 1 ) {
			$this->_arguments[$this->_parameters[0]] = 'console';
			for ( $i = 1; $i <= $argc-1; $i++) {
				$this->_arguments[$this->_parameters[$i]] = $argv[$i];
			}
		} elseif ( count( $_GET ) > 0 || count( $_POST ) > 0 ) {
			$args = $this->_parameters;
			foreach ( $args as $arg ) {
				$this->_arguments[$arg] = Util::value($arg);
			}
		}

		$url = HTTP::url();

		$parts = [];
		$request = parse_url($url, PHP_URL_PATH);
		$request_parts = explode( '/', $request );
		// $parts = array_reverse($request_parts); // Why did I do this?
		$parts = $request_parts;
	
		// Get the method for this request
		$this->_arguments[$this->_parameters[0]] = (isset($this->_arguments[$this->_parameters[0]])) ? $this->_arguments[$this->_parameters[0]] : strtolower( isset($_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET' );
		
		// Get the service targeted by this request
		$this->_arguments[$this->_parameters[1]] = (isset($this->_arguments[$this->_parameters[1]])) ? $this->_arguments[$this->_parameters[1]] : ( $parts[1] ?? $this->name() );

		// get the behavior triggered by this request
		$this->_arguments[$this->_parameters[2]] = (isset($this->_arguments[$this->_parameters[2]])) ? $this->_arguments[$this->_parameters[2]] : ( $parts[2] ?? '' ); // TODO send a universal default behavior

		// get the data triggered by this request
		$this->_arguments[$this->_parameters[3]] = (isset($this->_arguments[$this->_parameters[3]])) ? $this->_arguments[$this->_parameters[3]] : ( array_slice($parts, 3) ?? null );

		// die(var_dump(parse_url($url, PHP_URL_PATH)));

		return $this;
	}

	public function validateCsrf()
	{
		if ($_SERVER['REQUEST_METHOD'] == 'POST' ) {
			if (empty($_POST['_token'])) {
			    die('Invalid Request');
			} elseif (hash_equals($_SESSION['_token'], $_POST['_token'])) {
				// Continue to process
			}
		}
	}

	public function run() {
		$args = array_slice($this->_arguments, 1);
		// die(var_dump($this->_mappings));

		$behavior = $args['behavior'];

		$url = HTTP::url();

		$location = trim(parse_url($url, PHP_URL_PATH), '/') ?? '/';

		if ( isset($this->_mappings[$this->_arguments['_method']]) && isset($this->_mappings[$this->_arguments['_method']][$location]) ) {

			$mapping = $this->_mappings[$this->_arguments['_method']][$location];

			$request = new Request();

			foreach ($mapping->gateways() as $gatewayName) {
				if ( isset( $this->_gateways[$gatewayName] ) ) {
					$gatewayClass = $this->_gateways[$gatewayName];
					$gateway = $this->getGatwayInstance($gatewayClass);
					$gateway->process( $request, $this->_arguments );	
				}
			}

			$callable = $this->prepareCallable($mapping->callable);

			$result = $this->executeServiceMethod($callable, $args['data']);

			$this->boost(new Event('OnAppNavigated'), $this->getMappingName($location, $this->_arguments['_method']) ?? $location);

			print($result);
		}
		elseif ( $args['service'] == $this->name() ) {
			$data = isset($args['data'])?$args['data']:null;
			
			$this->boost($behavior, $data);
		} else {
			if (\is_string($behavior))
				$behavior = new Behavior($behavior);

			$behavior->_context = $args;
			$behavior->_target = $this;

			try {
				$behavior->_target = $this->service($args['service']);
			} catch( Exception $e ) {
				// Do Nothing
			}
			$args['behavior'] = $behavior;

			// die(var_dump($args));

			call_user_func_array(array($this, 'message'), $args);
		}

		return $this;
	}

	public function boost( $behavior, $args = null ) {
		if (\is_string($behavior)) {
			$behavior = new Behavior($behavior);
		}

		$behavior->_context = $args ?? $behavior->_context;
		$behavior->_target = $behavior->_target ?? $this;

		call_user_func_array(array($this, 'broadcast'), array($behavior));
	}

	public function serve( $service, $behavior, $args ) {
		$this->service($service)->perform($behavior, $args);
	}

	public function execute( $behavior, $args = null )
	{
		$this->_last_args = null;
		if ( \is_string($behavior) )
			$behavior = new Behavior( $behavior );

		if ( $behavior instanceof Behavior ) {
			$this->_broadcasted_events[$this->name()] = array($behavior->name());

			$behavior->_context = $args;	
		
			$this->perform($behavior);
		}

		return $this;
	}

	public function bind( $classname, $newclassname ) 
	{
		$this->_bindings[$classname] = $newclassname;
	}
	
	public function name( $newname = null )
	{
		return $this->config('name', $newname);
	}

	public function map($method, $path, $callable, $name = '')
	{
		// $this->_mappings[$method][$path] = $callable;
		// if ( $name ) {
		// 	$this->_mappingNames[$name] = $path;
		// }
		
		$mapping = new Mapping();
		$mapping->method = $method;
		$mapping->path = $path;
		$mapping->callable = $callable;
		$mapping->name = $name;
		
		$this->_mappings[$method][$path] = $mapping;

		// return $this;
		return $mapping;
	}

	public function getMappingName( $location, $method = 'get' ) {
		$result = '';
		// foreach ( $this->_mappingNames as $name=>$path ) {
		foreach ( $this->_mappings[$method] as $path=>$mapping ) {
			if ( $location == $path || $location.'/' == $path ) {
				$result = $mapping->name;
				break; 
			}
		}
		return $result;
	}

	public function gateway($name, $class)
	{
		$this->_gateways[$name] = $class;

		return $this;
	}

	// Creates a property of the application that is a programmable object
	public function component( $name, $data = null, $configuration = null )
	{	
		$object = null;
		if ( DevValue::isNull($this->$name)) {
			$object = new Programmable();
			$object->config( $configuration );
			if (DevValue::isNotNull($data)) {
				$object->assign( $data );
			}
		}

		return $this->field( $name, $object );
	}

	// Creates a delegate service for the application and registers it
	public function delegate( $name, $reference = null, $args = null )
	{
		$params = func_get_args();
		$args = array_slice( $params, 2 );

		$service = new Service();
		$service->parent($this);
		if ( \is_object($reference) ) {
			$service->instance = $reference;
			$service->type = \get_class($reference);
			$service->scope = $reference;
		} elseif (DevValue::isNotNull($reference) ) {
			$service->type = $reference;	
			$service->scope = $this;
		} else {
			// If type isn't given, creates a programmable object property
			$component = $this->component( $name );
			$component->_parent = $this;
			$service->instance = $component;
			$service->type = \get_class($component);
			$service->scope = $component;
		}
		
		$service->name = $name;
		// $service->scope = $this;
		$service->arguments = $args;

		$this->_services->add( $service, $name );

		return $this;
	}

	// Registers a behavior and a function under a given service, automatically routes it
	public function register( $serviceName, $behavior, $callable, $level = Service::LOCAL_LEVEL, $priority = 0 )
	{
		if (\is_string($behavior))
			$behavior = new Behavior($behavior, $priority);

		if ( $serviceName == $this->name() ) {
			$function_name = uniqid($behavior->name().'_');
			$this->learn($function_name, $callable, $behavior);
			// return $this;
		} elseif ( !$this->_services->has( $serviceName ) ) {
			$this->delegate($serviceName);
		} 

		if ( $serviceName != $this->name() ) {
			$handler = new Handler($behavior, $callable);

			$this->_services[$serviceName]->register($behavior->name(), $handler, $level);
		}

		$this->route($this->name(), $serviceName, $behavior);

		return $this;
	}

	// Configures given behaviors to be routed to given sub-services
	public function route( $senderName, $recipientName, $behavior, $callback = null )
	{
		if (\is_string($behavior))
			$behavior = new Behavior($behavior);

		$handlers = $this->_handlers->get($behavior->name());
		$new_broadcast = true;
		$broadcaster = array($this, 'broadcast');
		foreach ($handlers as $handler) {
			if ($handler->callback() == $broadcaster && $handler->name() == $behavior->name()) {
				$new_broadcast = false;
			}
		}

		if ( $this->name() == $senderName )
		{
			if ($new_broadcast) {
				$this->behavior($behavior, $broadcaster);
			} 
		}
		elseif ( !$this->_services->has( $senderName ) )
		{
			throw new Exception("The service {$senderName} is not registered", 1);
		} elseif ($callback) {
			// echo $senderName ." | ". $behavior . "\n";
			$this->register($senderName, $behavior, array($this, 'boost'));
		}

		if ( !$this->_services->has( $recipientName ) && $this->name() != $recipientName )
		{
			throw new Exception("The service {$recipientName} is not registered", 1);
		}

		$this->_routes[$behavior->name()][$senderName][] = array('recipient'=>$recipientName, 'callback'=>$callback);

		return $this;
	}

	public function service( $serviceName, $call = null )
	{
		if ( !$this->_services->has( $serviceName ) )
			throw new Exception("The service {$serviceName} is not registered", 1);
			
		$service = $this->_services[$serviceName]->instance();
		if ( $call )
		{
			$params = func_get_args();
			$args = array_slice( $params, 2 );

			$response = $this->_services[$serviceName]->call( $call, $args );
			// $response = $service->call( $call, $args );

			// $service->perform(new Event('OnComplete'), $response);
			$this->_services[$serviceName]->dispatch( Event::COMPLETE, $response);
		}

		return $service;
	}

	public function broadcast( $behavior, $args = null )
	{
		if (empty($this->_broadcast_chain)) $this->_broadcast_chain = array("Base");

		if ( !($behavior instanceof Behavior) )
		{
			throw new Exception("Invalid Behavior");
		}

		$behavior->_context = $args ? $args : $behavior->_context;

		// if ( $this->_depth == 0 ) {
			$this->_last_args = $behavior->_context ? $behavior->_context : $this->_last_args;
		// }

		// echo "\nrunning ".$behavior->name()." from ".$behavior->_target->name(). "\n";
		// var_dump($this->_routes);

		$this->_depth++;
		foreach ( $this->_routes as $behaviorName=>$senders )
		{
			if ( $behavior->name() == $behaviorName )
			{
				foreach ( $senders as $senderName=>$recipients )
				{
					if (!isset($this->_broadcasted_events[$senderName])) $this->_broadcasted_events[$senderName] = [];

					if (in_array($behavior->name(), $this->_broadcasted_events[$senderName])) {
						continue;
					}

					foreach ( $recipients as $recipient )
					{
						$target_name = '';
						if ($behavior->_target instanceof \BlueFission\Services\Service || $behavior->_target instanceof \BlueFission\Services\Application) {
							$target_name = $behavior->_target->name();
						} else {
							foreach ( $this->_services as $service ) {
								if ( $service->instance == $behavior->_target ) {
									$target_name = $service->name();
									break;
								}
							}
						}
						if ( $target_name == $senderName || 
							( isset($this->_broadcast_chain[$this->_depth-1]) && $this->_broadcast_chain[$this->_depth-1] == $target_name))
						{
							$name = $recipient['callback'] ? $recipient['callback'] : $behavior->name();

							$this->_broadcast_chain[$this->_depth] = $senderName;
							
							$this->_broadcasted_events[$senderName][] = $name;
							// echo "{$recipient['recipient']} - $name\n";
							$this->message( $recipient['recipient'], $behavior, $this->_last_args, $recipient['callback'] );
						}
					}
				}
			}
		}

		$this->_depth--;

		if ( $this->_depth == 0 ) {
			$this->_broadcasted_events = [];
			$this->_broadcast_chain = [];
			$this->_last_args = null;
		}
	}

	private function message( $service, $behavior, $data = null, $callback = null )
	{
		if ( '' === $service )
		{
			$service = $this->name();
		} 

		if ( $this->name() == $service )
		{
			$recipient = $this;
			$behavior->_context = $data;
		} 
		else
		{
			$recipient = $this->_services[$service];
		}

		if (DevValue::isNotNull($callback) && \is_string($callback)) {
			$behavior = new Behavior($callback);
		}

		// var_dump($behavior);

		if ( $recipient instanceof Application ) {
			$recipient->execute($behavior, $data);
		} elseif ( $recipient instanceof Service ) {
			$recipient->message($behavior, $data);
		} elseif ( $recipient instanceof Scheme ) {
			$recipient->perform($behavior, $data);
		} elseif ( $recipient instanceof Dispatcher ) {
			$recipient->dispatch($behavior, $data);
		} elseif ( \is_callable(array($recipient, $behavior->name() ) ) ) {
			call_user_func_array(array($recipient, $behavior->name()), array($data));
		} else {
			header("HTTP/1.0 404 Not Found");
			return '404';
		}
	}

	private function getDynamicInstance(string $class )
	{
		$constructor = new \ReflectionMethod($class, '__construct');
		$parameters = $constructor->getParameters();

		$dependencies = [];
		foreach ($parameters as $parameter) {
			$dependencyClass = (string) $parameter->getType();
			$dependencies[] = new $dependencClass();
		}

		$instance = new $class($dependencies);
	
		// var_dump($instance);
		return $instance;
	}

	private function getServiceInstance(string $class )
	{
		return $this->getDynamicInstance($class);
	}

	private function getGatwayInstance(string $class )
	{
		return $this->getDynamicInstance($class);
	}

	private function executeServiceMethod( $callable, Array $arguments = [] )
	{
		$functionOrMethod = null;

		if ( \is_array($callable) ) {
			$functionOrMethod = new \ReflectionMethod($callable[0], $callable[1]);
		} elseif ( \is_string($callable) ) {
			$functionOrMethod = new \ReflectionFunction($callable);
		} elseif ( \is_callable($callable) ) {
			return $callable();
		}

		if ( $functionOrMethod === null ) {
			return null;
		}

		$parameters = $functionOrMethod->getParameters();
		$dependencies = [];
		foreach ($parameters as $parameter) {
			$dependencyClass = (string) $parameter->getType();
			$dependencyName = $parameter->getName();

			if (\array_key_exists($dependencyClass, $this->_bindings)) {
				$dependencyClass = $this->_bindings[$dependencyClass];
			}

			$dependencies[$dependencyName] = $arguments[$dependencyName] ?? new $dependencyClass();
		}

		// $result = call_user_func_array([$class, $callable], );
		if ( \is_string($callable) ) {
			$result = $functionOrMethod->invokeArgs( $dependencies );
		}

		if ( \is_array($callable) ) {
			$object = \is_string($callable[0]) ? null : $callable[0];
			$result = $functionOrMethod->invokeArgs($object , $dependencies );
		}
		
		// var_dump($result);
		return $result;
	}
	
	private function prepareCallable( $callable )
	{
		if ( \is_string($callable) ) {

			return $callable;
		}

		if ( \is_array($callable) ) {

			$objectOrClassName = $callable[0];
			$methodName = $callable[1];

			$method = new \ReflectionMethod($objectOrClassName, $methodName);

			if ( \is_string($objectOrClassName) && !$method->isStatic() ) {
				$objectOrClassName = $this->getServiceInstance($objectOrClassName);
			}

			$preparedCallable = [$objectOrClassName, $methodName];

			return $preparedCallable; 
		}

		if ( \is_callable($callable) ) {
			return $callable;
		}
	}
}