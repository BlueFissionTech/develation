<?php
namespace BlueFission;

@include_once('Loader.php');
$loader = Loader::instance();
$loader->load('com.bluefission.develation.functions.common');
$loader->load('com.bluefission.develation.DevProgrammable');
$loader->load('com.bluefission.behaviors.*');
$loader->load('com.bluefission.framework.*');

class Application extends \DevProgrammable {
	static $_instance;

	protected $_config = array(
		'template'=>'',
		'storage'=>'',
		'name'=>'Application',
	);

	private $_context;
	private $_connection;
	private $_storage;
	private $_agent;
	private $_services;
	private $_routes;

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

	public function execute( $behavior, $args = null )
	{
		if ( \is_string($behavior) )
			$behavior = new Behavior( $behavior );

		$behavior->_context = $args;
		
		$this->perform($behavior);
	}
	public function name( $newname = null )
	{
		return $this->config('name', $newname);
	}
	public function component( $name, $data = null, $configuration = null )
	{		
		$object = new \DevProgrammable();
		$object->config( $configuration );
		$object->assign( $data );

		return $this->field( $name, $object );
	}
	public function delegate( $name, $reference, $args = null )
	{
		$params = func_get_args();
		$args = array_slice( $params, 2 );

		$service = new Service();
		if ( \is_object($reference) ) {
			$service->instance = $reference;
			$service->type = \get_class($reference);
		} else {
			$service->type = $reference;	
		}
		
		$service->name = $name;
		$service->scope = $this;
		$service->arguments = $args;

		$this->_services->add( $service, $name );
	}

	public function register( $serviceName, $behavior, $callable, $level = Service::LOCAL_LEVEL, $priority = 0 )
	{
		if ( !$this->_services->has( $serviceName ) )
			throw new \Exception("This service is not registered", 1);

		if (\is_string($behavior))
			$behavior = new Behavior($behavior);

		$handler = new Handler($behavior, $callable);
		
		$this->_services[$serviceName]->register($behavior->name(), $handler, $level);
	}

	public function route( $senderName, $recipientName, $behavior )
	{
		if (\is_string($behavior))
			$behavior = new Behavior($behavior);

		if ( $this->name() == $senderName )
		{
			$this->behavior($behavior, array($this, 'broadcast'));
		} 
		elseif ( !$this->_services->has( $senderName ) )
		{
			throw new \Exception("This service is not registered", 1);
		}

		if ( !$this->_services->has( $recipientName ) && $this->name() != $recipientName )
		{
			throw new \Exception("This service is not registered", 1);
		}

		$this->_routes[$behavior->name()][$senderName][] = $recipientName;
	}

	public function service( $serviceName, $call = null )
	{
		if ( !$this->_services->has( $serviceName ) )
			throw new \Exception("This service is not registered", 1);
			
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
			throw new \Exception("Invalid Behavior");
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

		return $recipient->perform($behavior, $arguments);
	}
}