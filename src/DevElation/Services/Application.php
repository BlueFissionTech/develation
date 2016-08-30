<?php
namespace BlueFission\Services;

use BlueFission\Behavioral\Programmable;
use BlueFission\Utils\Util;
use BlueFission\Behavioral\Scheme;
use BlueFission\Behavioral\Dispatcher;
use BlueFission\Collections\Collection;
use BlueFission\DevValue;
use BlueFission\DevArray;
use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Behaviors\Handler;
use Exception;

class Application extends Programmable {
	static $_instance;

	private $_broadcasted_events = array();

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
	private $_routes;
	private $_arguments = array();

	public function __construct() 
	{
		parent::__construct();
		if ( self::$_instance )
			return self::$_instance;

		$this->_services = new Collection();

		self::$_instance = $this;
	}

	static function instance()
	{
		if (!isset(self::$_instance)) {
			$c = __CLASS__;
			self::$_instance = new $c;
		}

		return self::$_instance;
	}

	public function params( $params ) {
		$this->_parameters = DevArray::toArray($params);
	
		return $this;
	}

	public function args( ) {
		global $argv, $argc;

		if ( $argc > 1 ) {
			for ( $i = 1; $i <= $argc; $i++) {
				$this->_arguments[$this->_parameters[$i]] = $argv[$i];
			}
		} else {
			$args = $this->_parameters;
			foreach ( $args as $arg ) {
				$this->_arguments[$arg] = Util::value($arg);
			}
		}

		$this->_arguments[$this->_parameters[0]] = (isset($this->_arguments[$this->_parameters[0]])) ? $this->_arguments[$this->_parameters[0]] : strtolower( isset($_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET' );
		$this->_arguments[$this->_parameters[1]] = (isset($this->_arguments[$this->_parameters[1]])) ? $this->_arguments[$this->_parameters[1]] : $this->name();

		$this->_arguments[$this->_parameters[2]] = (isset($this->_arguments[$this->_parameters[2]])) ? $this->_arguments[$this->_parameters[2]] : $this->_arguments[$this->_parameters[0]];

		return $this;
	}

	public function run() {
		$args = array_slice($this->_arguments, 1);
		call_user_func_array(array($this, 'message'), $args);

		return $this;
	}

	public function serve( $service, $behavior, $args ) {
		$this->service($service)->perform();
	}

	public function execute( $behavior, $args = null )
	{
		if ( \is_string($behavior) )
			$behavior = new Behavior( $behavior );

		if ( $behavior instanceof Behavior ) {
			$this->_broadcasted_events = array();
			$behavior->_context = $args;	
		
			$this->perform($behavior);
		}

		return $this;
	}
	
	public function name( $newname = null )
	{
		return $this->config('name', $newname);
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
			$service->scope = $this;
		} elseif (DevValue::isNotNull($reference) ) {
			$service->type = $reference;	
			$service->scope = $reference;
		} else {
			// If type isn't given, creates a programmable object property
			$component = $this->component( $name );
			$component->_parent = $this;
			$service->instance = NULL;
			$service->type = \get_class($reference);
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
			$behavior = new Behavior($behavior);

		if ( $serviceName == $this->name() ) {
			$this->learn(uniqid($behavior->name().'_'), $callable, $behavior);

			return $this;
		} elseif ( !$this->_services->has( $serviceName ) ) {
			// throw new Exception("This service is not registered", 1);
			$this->delegate($serviceName);
		}

		$handler = new Handler($behavior, $callable);
		
		$this->_services[$serviceName]->register($behavior->name(), $handler, $level);

		$this->route($this->name(), $serviceName, $behavior);

		return $this;
	}

	// Configures given behaviors to be routed to given sub-services
	public function route( $senderName, $recipientName, $behavior )
	{
		if (\is_string($behavior))
			$behavior = new Behavior($behavior);

		if ( $this->name() == $senderName )
		{
			$handlers = $this->_handlers->get($behavior->name());
			$new_broadcast = true;
			foreach ($handlers as $handler) {
				if ($handler->callback() == array($this, 'broadcast') )
					$new_broadcast = false;
			}
			if ($new_broadcast) {
				$this->behavior($behavior, array($this, 'broadcast'));
			}
		} 
		elseif ( !$this->_services->has( $senderName ) )
		{
			throw new Exception("The service {$senderName} is not registered", 1);
		}

		if ( !$this->_services->has( $recipientName ) && $this->name() != $recipientName )
		{
			throw new Exception("The service {$recipientName} is not registered", 1);
		}

		// if ( $senderName != $recipientName )
			$this->_routes[$behavior->name()][$senderName][] = $recipientName;

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

			$service = $this->_services[$serviceName]->call( $call, $args );
		}

		return $service;
	}

	public function broadcast( $behavior )
	{
		if ( !($behavior instanceof Behavior) )
		{
			throw new Exception("Invalid Behavior");
		}

		foreach ( $this->_routes as $behaviorName=>$senders )
		{
			if ( $behavior->name() == $behaviorName )
			{
				foreach ( $senders as $senderName=>$recipients )
				{
					if ( $behavior->_target->name() == $senderName )
					{
						foreach ( $recipients as $recipientName )
						{
							if ( in_array($behavior->name(), $this->_broadcasted_events) ) {
								continue;
							} else {
								$this->_broadcasted_events[] = $behavior->name();
							}
							$this->message( $recipientName, $behavior, $behavior->_context );
						}
					}
				}
			}
		}
	}

	private function message( $recipientName, $behavior, $arguments = null )
	{
		if ( $this->name() == $recipientName )
		{
			$recipient = $this;
		} 
		else
		{
			//$recipient = $this->_services[$recipientName];
			$recipient = $this->service($recipientName);
		}

		if ( $recipient instanceof Scheme )
			$recipient->perform($behavior, $arguments);
		elseif ( $recipient instanceof Dispatcher ) {
			$recipient->dispatch($behavior, $arguments);
		} else {
			$this->_services[$recipientName]->broadcast($behavior);
		}
	}
}