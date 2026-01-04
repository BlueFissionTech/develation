<?php
namespace BlueFission\Services;

use BlueFission\Behavioral\Programmable;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Behavioral\IBehavioral;
use BlueFission\Behavioral\IConfigurable;
use BlueFission\Utils\Util;
use BlueFission\Collections\Collection;
use BlueFission\Services\Mapping;
use BlueFission\Val;
use BlueFission\Str;
use BlueFission\Arr;
use BlueFission\Obj;
use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Handler;
use BlueFission\Net\HTTP;
use Exception;

/**
 * Class Application
 * 
 * @package BlueFission\Services
 */
class Application extends Obj implements IConfigurable, IDispatcher, IBehavioral {
	use Programmable {
        Programmable::__construct as private __tConstruct;
    }

	/**
	 * A collection of instances of this class
	 *
	 * @var array
	 */
	private static $_instances = [];

	/**
	 * A collection of broadcasted events
	 *
	 * @var array
	 */
	private $_broadcastedEvents = [];

	/**
	 * An array to store the broadcast chain
	 *
	 * @var array
	 */
	private $_broadcastChain = [];

	/**
	 * Store the last arguments
	 *
	 * @var null
	 */
	private $_last_args = null;

	/**
	 * Store the depth of this application
	 *
	 * @var int
	 */
	private $_depth = 0;

	/**
	 * Store directory to proxy files from
	 *
	 * @var string
	 */
	private $_assetDir = "";

	/**
	 * Store the web root directory
	 *
	 * @var string
	 */
	private $_webRoot = "";

	/**
	 * Default configuration for the application
	 *
	 * @var array
	 */
	protected $_config = array(
		'template'=>'',
		'storage'=>'',
		'name'=>'Application',
	);

	/**
	 * A collection of parameters for this application
	 *
	 * @var array
	 */
	protected $_parameters = array(
		'_method',
		'service',
		'behavior',
		'data',
	);

	/**
	 * The context for this application
	 *
	 * @var mixed
	 */
	private $_context;

	/**
	 * The connection for this application
	 *
	 * @var mixed
	 */
	private $_connection;

	/**
	 * The storage for this application
	 *
	 * @var mixed
	 */
	private $_storage;

	/**
	 * The agent for this application
	 *
	 * @var mixed
	 */
	private $_agent;

	/**
	 * A collection of services for this application
	 *
	 * @var Collection
	 */
	protected $_services;

	/**
	 * A collection of gateways for this application
	 *
	 * @var array
	 */
	protected $_gateways = [];

	/**
	 * A collection of bindings for this application
	 *
	 * @var array
	 */
	protected $_bindings = [];

	/**
	 * A collection of mappings for this application
	 *
	 * @var array
	 */
	protected $_mappings = [];

	/**
	 * A collection of mapping names for this application
	 *
	 * @var array
	 */
	protected $_mappingNames = [];
	
	/**
     * An array to store bound arguments.
     * @var array $_boundArguments 
     */
    protected $_boundArguments = [];

    /**
     * An array to store routes.
     * @var array $_routes 
     */
    protected $_routes = [];
    
    /**
     * An array to store arguments.
     * @var array $_arguments 
     */
    protected $_arguments = [];

    private $_operation = null;

    private $_conditions = [];

    private $_cmdpath = "";

    /**
     * The class constructor
     *
     * @return void
     */
    public function __construct($config = [])
    {
        $name = $config['name'] ??
        	$this->config['name'] ??
        	get_called_class();

        if (isset(self::$_instances[$name])) {
            return self::$_instances[$name];
        }

        parent::__construct();
        $this->__tConstruct(); // Call trait constructor
        $this->config($config);
        $this->_services = new Collection();
        $this->_broadcastedEvents[$name] = [];

        self::$_instances[$name] = $this;
    }

    /**
     * Get an instance of the current class.
     *
     * @return object An instance of the current class.
     */
    static function getInstance($name = null)
    {
        $appName = $name ?? get_called_class();
        if (!isset(self::$_instances[$appName])) {
        	$config = [];
        	if ($name) {
        		$config['name'] = $name;
        	}
            self::$_instances[$appName] = new static($config);
        }

        return self::$_instances[$appName];
    }

    /**
     * Get the first instance of the current class.
     *
     * @return object The first instance of the current class.
     */
    static function instance()
    {
		if (count(self::$_instances) <= 0) {
	        $calledClass = get_called_class();
			self::$_instances[$calledClass] = new static();
		}

		return array_values(self::$_instances)[0];
	}

    /**
     * Set the parameters for this request.
     *
     * @param array $params An array of parameters to set.
     *
     * @return object The current instance of the class.
     */
    public function params($params) 
    {
        $this->_parameters = Arr::toArray($params);
	
        return $this;
    }

    /**
     * Get the arguments for this request.
     *
     * @return object The current instance of the class.
     */
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

		$uri = new Uri();
		
		// Get the method for this request
		$this->_arguments[$this->_parameters[0]] = (isset($this->_arguments[$this->_parameters[0]])) ? $this->_arguments[$this->_parameters[0]] : strtolower( isset($_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET' );
		
		// Get the service targeted by this request
		$this->_arguments[$this->_parameters[1]] = (isset($this->_arguments[$this->_parameters[1]])) ? $this->_arguments[$this->_parameters[1]] : ( $uri->parts[0] ?? $this->name() );

		// get the behavior triggered by this request
		$this->_arguments[$this->_parameters[2]] = (isset($this->_arguments[$this->_parameters[2]])) ? $this->_arguments[$this->_parameters[2]] : ( $uri->parts[1] ?? '' ); // TODO send a universal default behavior

		// get the data triggered by this request
		$this->_arguments[$this->_parameters[3]] = (isset($this->_arguments[$this->_parameters[3]])) ? $this->_arguments[$this->_parameters[3]] : ( array_slice($uri->parts, 2) ?? null );

		return $this;
	}

	public function assetDir($directory = null) {
		if ( $directory ) {
			$directory = trim($directory, '/');
			$this->_assetDir = $directory;
		}
		return $this->_assetDir;
	}

	public function webRoot($path = null) {
		if ( $path ) {
			$path = trim($path, '/');
			$this->_webRoot = $path;
		}
		return $this->_webRoot;
	}

	public function fileExists($path) {
		$templateDir = $this->assetDir();
		$webRoot = $this->webRoot();
		if ( file_exists($webRoot.DIRECTORY_SEPARATOR.$path) ) {
			return true;
		} elseif ( file_exists( $templateDir.DIRECTORY_SEPARATOR.$path ) ) {
			return true;
		} else {
			return false;
		}
	}

	public function fileContents($path) {
		$templateDir = $this->assetDir();
		$webRoot = $this->webRoot();
		if ( $path != "" && file_exists($webRoot.DIRECTORY_SEPARATOR.$path) ) {
			header('Content-type: '. $this->getMimeType($webRoot.DIRECTORY_SEPARATOR.$path));
			return file_get_contents($webRoot.DIRECTORY_SEPARATOR.$path);
		} elseif ( $path != "" && file_exists( $templateDir.DIRECTORY_SEPARATOR.$path ) ) {
			header('Content-type: '. $this->getMimeType($templateDir.DIRECTORY_SEPARATOR.$path));
			return file_get_contents($templateDir.DIRECTORY_SEPARATOR.$path);
		} else {
			return null;
		}
	}

	public function getMimeType($filename) {
	    $realpath = realpath($filename);
	    
	    $extension = pathinfo($filename, PATHINFO_EXTENSION);

	    $mimetype = 'application/octet-stream';

	    switch (strtolower($extension)) {
	        case 'css':
	            $mimetype = 'text/css';
	            break;
	        case 'js':
	            $mimetype = 'application/javascript';
	            break;
	        case 'html':
	            $mimetype = 'text/html';
	            break;
	        case 'gif':
	            $mimetype = 'image/gif';
	            break;
	        case 'png':
	            $mimetype = 'image/png';
	            break;
	        case 'jpg':
	        case 'jpeg':
	            $mimetype = 'image/jpeg';
	            break;
	        // Add more common web formats here as needed
	        default:
				if ($realpath && function_exists('mime_content_type')) {
			        $mimeType = mime_content_type($realpath);
			        if (!empty($mimeType)) {
			            $mimetype = $mimeType;
			            break;
			        }
			    }
	            $mimetype = 'application/octet-stream'; // Return a generic MIME type if not matched
	            break;
	    }

	    Dev::do('_post', [$filename, $mimetype]);

	    return $mimetype;
	}

	public function process() {
		$args = array_slice($this->_arguments, 1);

		$behavior = $args['behavior'];
		
		$uri = new Uri();
		if ( isset($this->_mappings[$this->_arguments['_method']]) && $this->uriExists(array_keys($this->_mappings[$this->_arguments['_method']]) ) ) {

			// $mapping = $this->_mappings[$this->_arguments['_method']][$location];
			$this->_cmdpath = $this->returnMatchingUri(array_keys($this->_mappings[$this->_arguments['_method']]));
			$mapping = $this->_mappings[$this->_arguments['_method']][$this->_cmdpath];

			$request = new Request();

			foreach ($mapping->gateways() as $gatewayName) {
				if ( isset( $this->_gateways[$gatewayName] ) ) {
					$gatewayClass = $this->_gateways[$gatewayName];
					$gateway = $this->resolve($gatewayClass);
					$gateway->process( $request, $this->_arguments );	
				}
			}

			$this->_operation = $this->prepareCallable($mapping->callable);

			$this->_conditions = array_merge($args['data'], $uri->buildArguments($this->_cmdpath) );
		}

		return $this;
	}

	/**
	 * Main method that starts the application
	 *
	 * @return $this
	 */
	public function run() {
		$args = array_slice($this->_arguments, 1);
		$behavior = $args['behavior'];
		$uri = new Uri();

		if ( isset($this->_mappings[$this->_arguments['_method']]) && $this->uriExists(array_keys($this->_mappings[$this->_arguments['_method']]) ) ) {
			// TODO make this more elegant
			/* This should never have an array as callable becase of "prepareCallable"
			if ( $callable[0] instanceof \BlueFission\Services\Service  ) {
				$callable[0]->parent( $this );
			}
			*/
			if ( $this->_operation instanceof \BlueFission\Services\Service  ) {
				$this->_operation->parent( $this );
			}

			$result = $this->executeServiceMethod($this->_operation, $this->_conditions);

			$this->boost(new Event('OnAppNavigated'), $this->getMappingName($this->_cmdpath, $this->_arguments['_method']) ?? $this->_cmdpath);

			print($result);
		} elseif ( $args['service'] == $this->name() ) {
			$data = isset($args['data']) ? $args['data'] : null;
			
			$this->boost($behavior, $data);
		} elseif ($this->fileExists($uri->path) && $uri->path != "") {
			print( $this->fileContents($uri->path) );
		} else {
			if (Str::is($behavior)) {
				$behavior = new Behavior($behavior);
			}

			$behavior->context = $args;
			$behavior->target = $this;

			try {
				$behavior->target = $this->service($args['service']);
			} catch( Exception $e ) {
				// Do Nothing
			}
			$args['behavior'] = $behavior;

			call_user_func_array([$this, 'message'], $args);
		}

		return $this;
	}

	/**
	 * The `boost` method broadcasts a behavior object to the target object.
	 * 
	 * @param mixed $behavior A string or an object that represents a behavior.
	 * @param mixed $args A context for the behavior.
	 * @return void
	 */
	public function boost( $behavior, $args = null ) {
		if (Str::is($behavior)) {
			$behavior = new Behavior($behavior);
		}

		$behavior->context = $args ?? $behavior->context;
		$behavior->target = $behavior->target ?? $this;

		call_user_func_array([$this, 'broadcast'], [$behavior]);
	}

	/**
	 * The `serve` method delegates a behavior to a service.
	 * 
	 * @param string $service The name of the service.
	 * @param mixed $behavior The behavior to be performed.
	 * @param mixed $args The arguments for the behavior.
	 * @return void
	 */
	public function serve( $service, $behavior, $args ) {
		$this->service($service)->perform($behavior, $args);
	}

	/**
	 * The `execute` method executes a behavior.
	 * 
	 * @param mixed $behavior A string or an object that represents a behavior.
	 * @param mixed $args A context for the behavior.
	 * @return $this The instance of the object.
	 */
	public function execute( $behavior, $args = null, $callback = null )
	{
		$this->_last_args = null;
		if ( Str::is($behavior) )
			$behavior = new Behavior( $behavior );

		if ( $behavior instanceof Behavior ) {
			$this->_broadcastedEvents[$this->name()] = array($behavior->name());

			$behavior->context = $args;	
		
			$this->perform($behavior);
		}

		if ( $callback ) {
			call_user_func_array($callback, [$args]);
		}

		return $this;
	}

	/**
	 * Execute a behavior on a service as a command
	 * @param  string $service  The name of the service
	 * @param  Behavior|string $behavior The behavior to be executed
	 * @param  mixed $data     The data to be passed to the behavior
	 * @param  callable $callback The callback function to be executed
	 * @return mixed           The result
	 */
	public function command( $service, $behavior, $data = null, $callback = null )
	{
		$result = null;
		$module = $this->_services[$service];
		
		// $module = instance($service);

		if ( $module ) {
			$module->message($behavior, $data);
			$module = $this->service($service);

			if (method_exists($module, 'response')) {
				$result = $module->response();
			} else if (method_exists($module, 'status')) {
				$result = $module->status();
			}

			if ( $callback ) {
				return $callback($result);
			}
		} else {
			return $callback("Resource not found");
		}
		return;
	}

	/**
	 * The `bind` method creates a binding between two classes.
	 * 
	 * @param string $classname The name of the original class.
	 * @param string $newclassname The name of the new class.
	 * @return void
	 */
	public function bind( $classname, $newclassname ) 
	{
		$this->_bindings[$classname] = $newclassname;
	}

	/**
	 * The `bindArgs` method creates a mapping of arguments for a class.
	 * 
	 * @param array $arguments The array of arguments.
	 * @param string $classname The name of the class to be mapped. Default is '_'.
	 * @return void
	 */
	public function bindArgs( array $arguments, string $classname = '_' )
	{
		$this->_boundArguments[$classname] = $arguments;
	}

	/**
	 * The `name` method returns the name of the object or sets a new name for the object.
	 * 
	 * @param string|null $newname The new name for the object.
	 * @return mixed The name of the object.
	 */
	public function name( $newname = null )
	{
		return $this->config('name', $newname);
	}
	
	/**
	 * Adds a new route to the application's mappings.
	 *
	 * @param string $method The HTTP method for the route.
	 * @param string $path The path of the route.
	 * @param callable $callable The callback function to be executed when the route is matched.
	 * @param string $name An optional name for the route.
	 *
	 * @return Mapping The newly created mapping object.
	 */
	public function map($method, $path, $callable, $name = '')
	{
		$mapping = new Mapping();
		$mapping->method = $method;
		$mapping->path = $path;
		$mapping->callable = $callable;
		$mapping->name = $name;
		
		$this->_mappings[$method][$path] = $mapping;

		return $mapping;
	}

	/**
	 * Returns the mappings for the aplication
	 * 
	 * @return array The mappings for the application
	 */
	public function maps()
	{
		return $this->_mappings;
	}

	/**
	 * Gets the name of a mapping for a given location and HTTP method.
	 *
	 * @param string $location The location to search for.
	 * @param string $method The HTTP method to search for.
	 *
	 * @return string The name of the mapping, or an empty string if no matching mapping was found.
	 */
	public function getMappingName( $location, $method = 'get' ) {
		$result = '';
		foreach ( $this->_mappings[$method] as $path=>$mapping ) {
			if ( $location == $path || $location.'/' == $path ) {
				$result = $mapping->name;
				break; 
			}
		}
		return $result;
	}

	/**
	 * Adds a new gateway class to the application's gateways.
	 *
	 * @param string $name The name of the gateway.
	 * @param string $class The name of the class that implements the gateway.
	 *
	 * @return Application The current application instance.
	 */
	public function gateway($name, $class)
	{
		$this->_gateways[$name] = $class;

		return $this;
	}

	/**
	 * Creates a property of the application that is a programmable object.
	 *
	 * @param string $name The name of the property to create.
	 * @param mixed $data Optional data to assign to the property.
	 * @param mixed $configuration Optional configuration for the programmable object.
	 *
	 * @return Programmable The newly created programmable object.
	 */
	public function component( $name, $data = null, $configuration = null )
	{	
		$object = null;
		if ( Val::isNull($this->$name)) {
			// Create anonymous class
			$object = new class extends Obj {
				use Programmable;
			};
			$object->config( $configuration );
			if (Val::isNotNull($data)) {
				$object->assign( $data );
			}
		}

		$this->field( $name, $object );

		return $object;
	}

	/**
	 * Creates a delegate service for the application and registers it
	 *
	 * @param string $name         The name of the service
	 * @param mixed  $reference    The reference to the instance of the service or the class name
	 * @param array  $args         The arguments passed to the service
	 *
	 * @return self
	 */
	public function delegate( $name, $reference = null, $args = null )
	{
		$params = func_get_args();
		$args = $args ?? array_slice( $params, 2 );

		$service = new Service();
		$service->parent($this);
		if ( is_object($reference) ) {
			$service->instance = $reference;
			$service->type = get_class($reference);
			$service->scope = $reference;
			if ($reference instanceof Service) {
				$reference->parent($service);
			}
		} elseif ( Val::isNotNull($reference) ) {
			$service->type = $reference;	
			$service->scope = $this;
			if ( is_subclass_of($reference, Service::class) && count($args) == 0 ) {
				$service->instance = $this->resolve($reference);
			}
		} else {
			$component = $this->component( $name );
			$component->_parent = $this;
			$service->instance = $component;
			$service->type = get_class($component);
			$service->scope = $component;
		}

		$service->name = $name;
		$service->arguments = $args;

		$this->_services->add( $service, $name );

		return $this;
	}

	/**
	 * Registers a behavior and a function under a given service, automatically routes it
	 *
	 * @param string $serviceName  The name of the service
	 * @param mixed  $behavior     The behavior to be registered
	 * @param mixed  $callable     The callable function to be registered
	 * @param int    $level        The level of the service
	 * @param int    $priority     The priority of the behavior
	 *
	 * @return self
	 */
	public function register( $serviceName, $behavior, $callable, $level = Service::LOCAL_LEVEL, $priority = 0 )
	{
		if (Str::is($behavior))
			$behavior = new Behavior($behavior, $priority);

		if ( $serviceName == $this->name() ) {
			$function_name = uniqid($behavior->name().'_');
			$this->learn($function_name, $callable, $behavior);
		} else {
			if ( !$this->_services->has( $serviceName ) ) {
				$this->delegate($serviceName);
			}

			$handler = new Handler($behavior, $callable);

			$this->_services[$serviceName]->register($behavior->name(), $handler, $level);
		}

		$this->route($this->name(), $serviceName, $behavior);

		return $this;
	}

	/**
	 * Configures the given behaviors to be routed to the given sub-services
	 *
	 * @param string $senderName The name of the sender service
	 * @param string $recipientName The name of the recipient service
	 * @param mixed $behavior The behavior to be routed
	 * @param callable $callback The callback function for the behavior
	 *
	 * @return $this The instance of the class
	 */
	public function route( $senderName, $recipientName, $behavior, $callback = null )
	{
		if ( !$this->_services->has( $senderName ) && $this->name() != $senderName ) {
			throw new Exception("The service {$senderName} is not registered", 1);
		}

		if ( !$this->_services->has( $recipientName ) && $this->name() != $recipientName )
		{
			throw new Exception("The service {$recipientName} is not registered", 1);
		}

		if (Str::is($behavior)) {
			$behavior = new Behavior($behavior);
		}

		$handlers = $this->_handlers->get($behavior->name());
		$new_broadcast = true;
		$broadcaster = [$this, 'broadcast'];

		foreach ($handlers as $handler) {
			if ($handler->callback() == $broadcaster && $handler->name() == $behavior->name()) {
				$new_broadcast = false;
				break;
			}
		}

		if ( $this->name() == $senderName && $new_broadcast ) {
			$this->behavior($behavior, $broadcaster);
		} elseif ($callback) {
			$this->register($senderName, $behavior, [$this, 'boost']);
		}

		$this->_routes[$behavior->name()][$senderName][] = ['recipient'=>$recipientName, 'callback'=>$callback];

		return $this;
	}

	/**
	 * Retrieves the service specified by the service name
	 *
	 * @param string $serviceName The name of the service
	 * @param string $call The function name to call on the service
	 *
	 * @return mixed The instance of the service or the response of the called function on the service
	 */
	public function service( $serviceName, $call = null )
	{
		if ( !$this->_services->has( $serviceName ) )
			throw new Exception("The service {$serviceName} is not registered", 1);
		
		try {
			$service = $this->_services[$serviceName]->instance();
		} catch( \Exception $e ) {
			error_log($e->getMessage());
			$service = $this->resolve($serviceName);
		}
		if ( $call )
		{
			$params = func_get_args();
			$args = array_slice( $params, 2 );

			$response = $this->_services[$serviceName]->call( $call, $args );

			$this->_services[$serviceName]->dispatch( Event::COMPLETE, $response);
		}

		return $service;
	}

	/**
	 * Broadcasts the behavior to all the recipients
	 *
	 * @param Behavior $behavior The behavior to broadcast
	 * @param mixed $args The arguments for the broadcast
	 */
	public function broadcast( $behavior, $args = null )
	{
		if (empty($this->_broadcastChain)) $this->_broadcastChain = ["Base"];

		if ( !($behavior instanceof Behavior) )
		{
			throw new Exception("Invalid Behavior");
		}

		$behavior->context = $args ?? $behavior->context;

		$this->_last_args = $behavior->context ?? $this->_last_args;
		
		$this->_depth++;
		foreach ( $this->_routes as $behaviorName=>$senders )
		{
			if ( $behavior->name() == $behaviorName )
			{
				foreach ( $senders as $senderName=>$recipients )
				{
					if (!isset($this->_broadcastedEvents[$senderName])) $this->_broadcastedEvents[$senderName] = [];

					if (Arr::has($this->_broadcastedEvents[$senderName], $behavior->name())) {
						continue;
					}

					foreach ( $recipients as $recipient )
					{
						$targetName = '';
						if ($behavior->target instanceof Service || $behavior->target instanceof Application) {
							$targetName = $behavior->target->name();
						} else {
							foreach ( $this->_services as $service ) {
								if ( $service->instance === $behavior->target ) {
									$targetName = $service->name();
									break;
								}
							}
						}
						if (
							!Arr::has($this->_broadcastedEvents[$senderName], $behavior->name()) && 
							$targetName == $senderName || 
							( isset($this->_broadcastChain[$this->_depth-1]) && $this->_broadcastChain[$this->_depth-1] == $targetName)
						)
						{
							$name = $recipient['callback'] ? $recipient['callback'] : $behavior->name();

							$this->_broadcastChain[$this->_depth] = $senderName;
							
							$this->_broadcastedEvents[$senderName][] = $name;

							$this->message( $recipient['recipient'], $behavior, $this->_last_args, $recipient['callback'] );
						}
					}
				}
			}
		}

		$this->_depth--;

		if ( $this->_depth == 0 ) {
			$this->_broadcastedEvents = [];
			$this->_broadcastChain = [];
			$this->_last_args = null;
		}
	}

	/**
	 * Check if the given uris exists
	 *
	 * @param array $uris
	 * @return bool
	 */
	private function uriExists( $uris )
	{
		$uri = new Uri();
		foreach ( $uris as $testUri ) {
			if ( $uri->match($testUri) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return the matching uri if exists
	 *
	 * @param array $uris
	 * @return mixed string|bool
	 */
	private function returnMatchingUri( $uris )
	{
		$uri = new Uri();
		foreach ( $uris as $testUri ) {
			if ( $uri->match($testUri) ) {
				return $testUri;
			}
		}
		return false;
	}

	/**
	 * Send a message to the recipient with the specified behavior
	 *
	 * @param string $service
	 * @param object $behavior
	 * @param mixed $data
	 * @param mixed $callback
	 * @return mixed
	 */
	private function message( $service, $behavior, $data = null, $callback = null )
	{
		if ( '' === $service ) {
			$service = $this->name();
		} 

		if ( $this->name() == $service ) {
			$recipient = $this;
			$behavior->context = $data;
		} else {
			$recipient = $this->_services[$service];
		}

		if ( Val::isNotNull($callback) && Str::is($callback) ) {
			$behavior = new Behavior($callback);
		}

		if ( $recipient instanceof Application ) {
			$recipient->execute($behavior, $data, $callback);
		} elseif ( $recipient instanceof Service ) {
			$recipient->message($behavior, $data, $callback);
		} elseif ( $recipient instanceof IBehavioral ) {
			$recipient->perform($behavior, $data);
		} elseif ( $recipient instanceof IDispatcher ) {
			$recipient->dispatch($behavior, $data);
		} elseif ( is_callable([$recipient, $behavior->name()] ) ) {
			call_user_func_array([$recipient, $behavior->name()], [$data]);
		} else {
			header("HTTP/1.0 404 Not Found");
			return '404';
		}
	}

	/**
	 * Return the registered abilities
	 * @return array The abilities of the application
	 */
	public function getAbilities()
    {
        $abilities = [];

        foreach ($this->_routes as $behaviorName => $senders) {

            foreach ($senders as $senderName => $recipients) {
            	foreach ( $recipients as $recipient ) {
            		$service = $recipient['recipient'];
	                if (!isset($abilities[$service])) {
	                    $abilities[$service] = [];
	                }

	                if (!in_array($behaviorName, $abilities[$service])) {
	                    $abilities[$service][] = $behaviorName;
	                }
	            }
            }
        }

        return $abilities;
    }

	/**
	 * Create an instance of a given class
	 *
	 * @param string $class
	 * @return object
	 */
	static function makeInstance( string $class )
	{
		$app = self::instance();
		return $app->getDynamicInstance($class);
	}

	/**
	 * Create an instance of a class with its dependencies
	 *
	 * @param string $class
	 * @return object
	 */
	public function getDynamicInstance(string $class )
	{
		$constructor = new \ReflectionMethod($class, '__construct');

		$dependencies = [];

		$arguments = $this->boundArguments($class);

		$dependencies = $this->handleDependencies($constructor, $arguments);

		$values = array_values($dependencies);

		$instance = new $class(...$values);
	
		return $instance;
	}

	/**
	 * Get instance or registered item
	 * 
	 * @param string $class 
	 * @return mixed
	 */
	public function resolve(string $class )
	{
		return $this->getDynamicInstance($class);
	}

	/**
	 * Execute service method
	 * 
	 * @param callable $callable 
	 * @param array $arguments 
	 * @return mixed
	 */
	private function executeServiceMethod( $callable, Array $arguments = [] )
	{
		$functionOrMethod = null;

		if ( \is_array($callable) ) {
			$functionOrMethod = new \ReflectionMethod($callable[0], $callable[1]);
		} elseif ( \is_string($callable) ) {
			$functionOrMethod = new \ReflectionFunction($callable);
		} elseif ( \is_callable($callable) ) {
			$functionOrMethod = new \ReflectionFunction($callable);
		}

		if ( $functionOrMethod === null ) {
			return null;
		}

		$dependencies = $this->handleDependencies($functionOrMethod, $arguments);

		if ( \is_string($callable) ) {
			$result = $functionOrMethod->invokeArgs( $dependencies );
		} elseif ( \is_array($callable) ) {
			$object = \is_string($callable[0]) ? null : $callable[0];
			$result = $functionOrMethod->invokeArgs($object , $dependencies );
		} elseif ( \is_callable($callable) ) {
			$result = $functionOrMethod->invokeArgs( $dependencies );
		}
		
		return $result;
	}

	/**
	 * Get bound arguments
	 * 
	 * @param string $classname 
	 * @return array
	 */
	private function boundArguments(String $classname = null)
	{
		if ( array_key_exists($classname, $this->_boundArguments) ) {
			return $this->_boundArguments[$classname];
		}

		if ( $classname ) {
			$class = new \ReflectionClass($classname);
	        $parents = [];
	        $interfaces = [];
	       
	        while ($parent = $class->getParentClass()) {
	            $parents[] = $parent->getName();
	            $interfaces = array_merge($parent->getInterfaceNames(), $interfaces);
	            $class = $parent;
	        }

	        foreach ( $parents as $parent ) {
	        	if ( array_key_exists($parent, $this->_boundArguments) ) {
					return $this->_boundArguments[$parent];
				}
	        }
	        foreach ( $interfaces as $interface ) {
	        	if ( array_key_exists($interface, $this->_boundArguments) ) {
					return $this->_boundArguments[$interface];
				}
	        }
	    }

	    return $this->_boundArguments['_'] ?? [];
	}

	/**
	 * Handle the dependencies of the function or method passed as the first parameter
	 *
	 * @param ReflectionFunctionAbstract $functionOrMethod The function or method to handle its dependencies
	 * @param array $arguments The arguments to pass to the function or method
	 *
	 * @return array The dependencies of the function or method
	 */
	private function handleDependencies ( $functionOrMethod, $arguments = [] )
	{
		$parameters = $functionOrMethod->getParameters();
		$dependencies = [];
		
		$varTypes = ['string', 'int', 'float', 'bool', 'array', 'object', 'callable', 'iterable', 'void', 'null'];

		$callingClass = null;
		if ( $functionOrMethod instanceof \ReflectionMethod ) {
			$callingClass = $functionOrMethod?->class;
		}
		
		foreach ($parameters as $parameter) {

			// Get the name of the dependency class
			$dependencyClass = '';
			$dependencyClassObj = $parameter->getType();
			if ( $dependencyClassObj ) {
				$dependencyClass = $dependencyClassObj->getName();
			}
			$dependencyName = $parameter->getName();

			// Check if the dependency class has a binding
			if (\array_key_exists($dependencyClass, $this->_bindings)) {
				$dependencyClass = $this->_bindings[$dependencyClass];
			}

			// Merge the arguments with the application registered named bindings by class
			$arguments = array_merge($this->boundArguments($callingClass), $arguments);

			// Check if the argument exists for the current dependency
			if ( isset($arguments[$dependencyName]) ) {
				$dependencies[$dependencyName] = $arguments[$dependencyName];
			}

			// If the dependency class exists, get its dependencies and create an instance of it
			if ( in_array($dependencyClass, $varTypes) ) {
				$dependencies[$dependencyName] = $arguments[$dependencyName] ?? ( $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null );
			} elseif ( $dependencyClass ) {
				$values = array_values($this->handleDependencies(new \ReflectionMethod($dependencyClass.'::__construct')));

				$dependencies[$dependencyName] = 
					$arguments[$dependencyName] ?? 
					new $dependencyClass(...$values);
			}
		}

		return $dependencies;
	}

	/**
	 * Prepare the callable passed as the parameter
	 *
	 * @param callable $callable The callable to prepare
	 *
	 * @return callable The prepared callable
	 */
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
				$objectOrClassName = $this->resolve($objectOrClassName);
			}

			$preparedCallable = [$objectOrClassName, $methodName];

			return $preparedCallable; 
		}

		if ( \is_callable($callable) ) {
			return $callable;
		}
	}
}